<?php

/*
 * Copyright (C) 2020 Sergey Ilyin <developer@ilyins.ru>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class for SMSC
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class SMSC {
    public static function syncMessages($login){
        if (SMSC_ENABLED && SMSC_ACCOUNT != '' && SMSC_PSW != '') {
            Logs::handler(__CLASS__."::".__FUNCTION__." | $login");
            $success = false;
            $last = strtotime(DB::query("SELECT last FROM request WHERE service='smsc' AND login='$login'")->fetch_object()->last);
            if (time() - $last > 160){
                $url="https://smsc.ru/sys/get.php";
                $post['login'] = SMSC_ACCOUNT;
                $post['psw'] = SMSC_PSW;
                $post['fmt'] = 3;
                $post['charset'] = 'utf-8';
                $post['start'] = date('d.m.Y', strtotime('Now - 1 day'));
                $post['cnt'] = 1000;
                $post['get_messages'] = 1;
                $result = cURL::executeRequest($url, $post, false, false, false);
                $json=json_decode($result);
                if (is_array($json)) {
                    for ($i=0; $i<count($json); $i++){
                        if ($json[$i]->id && $json[$i]->phone && $json[$i]->message){
                            $query = "SELECT id FROM smsc_messages WHERE login='$login' AND id='{$json[$i]->id}'";
                            $result = DB::query($query);
                            if ($result->num_rows == 0) {
                                $message = preg_replace("/'/", "\'", $json[$i]->message);
                                $query = "INSERT INTO smsc_messages (`id`, `phone`, `message`, `login`) VALUES ('{$json[$i]->id}', '{$json[$i]->phone}', '$message', '$login')";
                                Logs::handler(__CLASS__.'::'.__FUNCTION__." | $login | {$json[$i]->id}");
                                DB::query($query);
                            }
                        }
                    }
                }
                if (isset($json->error)) {
                    switch ($json->error) {
                        case 'message not found':
                            break;

                        default:
                            $message = __CLASS__.'::'.__FUNCTION__." | $login | {$json->error}";
                            Logs::error($message);
                            BX24::sendBotMessage($message);
                            Telegram::alert($message);
                            break;
                    }
                }
                DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='smsc' AND login='$login'");
                $success = true;
            }
            return json_encode(array('success' => $success,));
        }
    }

    public static function sendWaGc($login){
        Logs::handler(__CLASS__."::".__FUNCTION__." | $login");
        //$success = false;
        $obj = DB::query("SELECT * FROM smsc_messages WHERE success=0 AND login='$login'");
        $messages = $obj->fetch_all();
        for ($i = 0; $i < count($messages); $i++) {
            $id = $messages[$i][0];
            if (SMSC_FWD_ALL_WA) {
                $toWazzup24['chatId'] = $messages[$i][1];
                $toWazzup24['text'] = $messages[$i][2];
                Wazzup24::queue($login, $toWazzup24);
                $toChatApi['phone'] = $messages[$i][1];
                $toChatApi['body'] = $messages[$i][2];
                ChatApi::queue($login, $toChatApi);
                $toSemySMS['phone'] = $messages[$i][1];
                $toSemySMS['msg'] = $messages[$i][2];
                SemySMS::queue($login, $toSemySMS);
                DB::query("UPDATE smsc_messages SET success=1 WHERE id=$id");
            } elseif (preg_match("/Вам пишет .*:/",$messages[$i][2])) {
                $toWazzup24['chatId'] = $messages[$i][1];
                $toWazzup24['text'] = substr($messages[$i][2], stripos($messages[$i][2],':')+2);
                Wazzup24::queue($login, $toWazzup24);
                $toChatApi['phone'] = $messages[$i][1];
                $toChatApi['body'] = substr($messages[$i][2], stripos($messages[$i][2],':')+2);
                ChatApi::queue($login, $toChatApi);
                $toSemySMS['phone'] = $messages[$i][1];
                $toSemySMS['msg'] = substr($messages[$i][2], stripos($messages[$i][2],':')+2);
                SemySMS::queue($login, $toSemySMS);
                DB::query("UPDATE smsc_messages SET success=1 WHERE id=$id");
            }
            if (preg_match("/\|\d*\|\S*@\S*\|http.*\|/",$messages[$i][2])){
                global $addFields;
                $msg_part=explode("|", $messages[$i][2]);
                $params['user']['email'] = $msg_part[2];
                $params['user']['addfields'] = array(
                    $addFields->{$msg_part[1]} => $msg_part[3],
                    );
                GetCourse::addUser($params);
                DB::query("UPDATE smsc_messages SET success=1 WHERE id=$id");
            }
        }
        //return json_encode(array('success' => $success,));
    }

    public function historyClear($login, $args) {
        DB::query("DELETE FROM smsc_messages WHERE login='$login' AND last_update < CURRENT_DATE - INTERVAL {$args['interval']}");
    }
}

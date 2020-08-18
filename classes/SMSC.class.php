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

    /**
     * Synchronize messages from SMSC to MySQL database
     * 
     * @param type $login
     * @param type $logDir
     * @return type
     */
    public static function syncMessages($login, $logDir){
        if (SMSC_ACCOUNT && SMSC_PSW) {
            $success = false;
            $last = strtotime(DB::query("SELECT last FROM request WHERE service='smsc'")->fetch_object()->last);
            if (time() - $last > 180){
                $url="https://smsc.ru/sys/get.php";
                $post['login'] = SMSC_ACCOUNT;
                $post['psw'] = SMSC_PSW;
                $post['fmt'] = 3;
                $post['charset'] = 'utf-8';
                $post['start'] = date('d.m.Y', strtotime('Now - 1 day'));
                $post['cnt'] = 1000;
                $post['get_messages'] = 1;
                $result = cURL::executeRequest($url, $post, false, false, $logDir);
                $json=json_decode($result);
                for ($i=0; $i<count($json); $i++){
                    if ($json[$i]->id && $json[$i]->phone && $json[$i]->message){
                       DB::query("INSERT INTO smsc_messages (`id`, `phone`, `message`, `login`) VALUES ('".$json[$i]->id."', '".$json[$i]->phone."', '".$json[$i]->message."', '$login')");
                    }
                }
                DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='smsc'");
                $success = true;
            }
            return json_encode(array('success' => $success,));
        }
    }

    /**
     * Send messages from GetCourse to Wazzup24
     * 
     * @global array $addFields
     * @param string $logDir
     */
    public static function sendWaGc($login, $logDir){
        //$success = false;
        $obj = DB::query("SELECT * FROM smsc_messages WHERE success=0 AND login='$login'");
        $messages = $obj->fetch_all();
        for ($i = 0; $i < count($messages); $i++) {
            $id = $messages[$i][0];
            if (preg_match("/Вам пишет .*:/",$messages[$i][2])){
                $toWa24['to'] = $messages[$i][1];
                $toWa24['text'] = substr($messages[$i][2], stripos($messages[$i][2],':')+2);
                $toWa24['transport'] = 'whatsapp';
                Wazzup24::queue($messages[$i][3], $toWa24);
                DB::query("UPDATE smsc_messages SET success=1 WHERE id=$id");
            }
            if (preg_match("/\|\d*\|\S*@\S*\|http.*\|/",$messages[$i][2])){
                global $addFields;
                $msg_part=explode("|", $messages[$i][2]);
                $params['user']['email'] = $msg_part[2];
                $params['user']['addfields'] = array(
                    $addFields->{$msg_part[1]} => $msg_part[3],
                    );
                GetCourse::addUser($params, $logDir);
                DB::query("UPDATE smsc_messages SET success=1 WHERE id=$id");
            }
        }
        //return json_encode(array('success' => $success,));
    }
}

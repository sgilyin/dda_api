<?php

/*
 * Copyright (C) 2022 Sergey Ilyin <developer@ilyins.ru>
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
 * Class for Wazzup24
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Wazzup24 {
    public static function send($login) {
        if (WA24_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s', __CLASS__, __FUNCTION__, $login));
            for($i = 0; $i < 4; $i++){
                sleep(rand(11,15));
                if ($row = DB::query("SELECT * FROM send_to_wazzup24 WHERE sendTime=0 AND login='$login' LIMIT 1")->fetch_object()) {
                    $alreadySent = DB::checkSentWhatsapp($row->chatId, $row->text);
                    if ($alreadySent) {
                        Logs::handler(sprintf('%s::%s | %s | Message %d already sent via %s',
                            __CLASS__, __FUNCTION__, $login, $row->id, $alreadySent));
                        DB::query("UPDATE send_to_wazzup24 SET sendTime=CURRENT_TIMESTAMP() WHERE id={$row->id}");
                    } else {
                        $url = 'https://api.wazzup24.com/v3/message';
                        $headers = array();
                        $post = array();
                        $headers[] = "Content-type:application/json";
                        $headers[] = "Authorization: Bearer ".WA24_API_KEY;
                        $post['chatType'] = $row->chatType;
                        $post['channelId'] = $row->channelId;
                        $post['chatId'] = $row->chatId;
                        if ($row->text != '') {
                            $post['text'] = $row->text;
                        }
                        if (!$row->content == '') {
                            $post['contentUri'] = $row->content;
                        }
                        $post=json_encode($post);
                        $result = json_decode(cURL::executeRequest($url, $post, $headers, false, false));
                        DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='wazzup24' AND login='$login'");
                        if (isset($result->messageId)) {
                            DB::query("UPDATE send_to_wazzup24 SET sendTime=CURRENT_TIMESTAMP() WHERE id={$row->id}");
                        }
                    }
                }
            }
            if (isset($result->errors)) {
                $message = sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                    $login, $result->errors[0]->description);
                Logs::error($message);
                BX24::sendBotMessage($message);
                Telegram::alert($message);
            }
            return true;
        } else {
            return false;
        }
    }

    public static function queue($login, $args) {
        if (WA24_ENABLED) {
            if (isset($args['chatId']) && (isset($args['text']) || isset($args['content']))) {
                $args['chatId'] = intval($args['chatId']);
                $chatIdLen = strlen($args['chatId']);
                if ($chatIdLen > 8 && $chatIdLen < 17) {
                    $args['channelId'] = $args['channelId'] ?? WA24_CID_WA;
                    $args['chatType'] = $args['chatType'] ?? 'whatsapp';
                    foreach ($args as $key => $val) {
                        $setArr[] = "$key='$val'";        
                    }
                    $setStr = implode(", ", $setArr);
                    $query = "INSERT INTO send_to_wazzup24 SET $setStr, login='$login'";
                    DB::query($query);
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    public static function trap($login, $inputRequestData) {
        if (WA24_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($inputRequestData)));
            if (isset($inputRequestData['messages'])) {
                for ($i = 0; $i < count($inputRequestData['messages']); $i++) {
                    if ($inputRequestData['messages'][$i]['type']=="text") {
                        if (date_diff($inputRequestData['messages'][$i]['dateTime'], date_create('Now'))->h > 0) {
                            Logs::handler(sprintf('%s::%s | %s | SKP (1H) | %s | %s',
                                __CLASS__, __FUNCTION__, $login,
                                $inputRequestData['messages'][$i]['chatId'],
                                $inputRequestData['messages'][$i]['text']));
                        } else {
                            Logs::handler(sprintf('%s::%s | %s | RCVD | %s | %s',
                                __CLASS__, __FUNCTION__, $login,
                                $inputRequestData['messages'][$i]['chatId'],
                                $inputRequestData['messages'][$i]['text']));
                            $phone = substr(preg_replace('/[^0-9]/', '', $inputRequestData['messages'][$i]['chatId']), -15);
                            try {
                                $user = DB::query("SELECT email, instagram, firstName FROM gc_users WHERE login='$login' AND phone REGEXP '$phone'")->fetch_object();
                                $email = $user->email ?? null;
                                $firstName = $user->firstName ?? null;
                                $instagram = $user->instagram ?? null;
                            } catch (Exception $exc) {
                                Logs::error(sprintf('%s::%s | %s | var user | %s',
                                    __CLASS__, __FUNCTION__, $login, $exc));
                            }
                            if (empty($email)) {
                                try {
                                    $nameFromWhatsapp = $inputRequestData['messages'][$i]['contact']['name'];
                                    $params = Dadata::cleanNameFromWhatsapp($nameFromWhatsapp);
                                } catch (Exception $exc) {
                                    Logs::error(sprintf('%s::%s | %s | dadata cleanName | %s',
                                    __CLASS__, __FUNCTION__, $login, $exc));
                                }
                                preg_match("/\|.*\|/",$inputRequestData['messages'][$i]['text'],$matches);
                                if ($matches) {
                                    $item=explode("|", $matches[0]);
                                    if ($item[1]) {
                                        global $addFields;
                                        $params['user']['group_name']= array($addFields->{$item[1]});
                                    }
                                    if ($item[2]) {
                                        $params['user']['addfields']['d_utm_source']=$item[2];
                                    }
                                    if ($item[3]) {
                                        $params['user']['addfields']['d_utm_medium']=$item[3];
                                    }
                                    if ($item[4]) {
                                        $params['user']['addfields']['d_utm_content']=$item[4];
                                    }
                                    if ($item[5]) {
                                        $params['user']['addfields']['d_utm_campaign']=$item[5];
                                    }
                                    if ($item[6]) {
                                        $params['user']['addfields']['d_utm_term']=$item[6];
                                    }
                                    if ($item[7]) {
                                        $params['user']['addfields']['d_utm_rs']=$item[7];
                                    }
                                    if ($item[8]) {
                                        $params['user']['addfields']['d_utm_acc']=$item[8];
                                    }
                                    if ($item[9]) {
                                        $params['user']['addfields']['Возраст']=$item[9];
                                    }
                                    if ($item[10]) {
                                        $emailInMessage=$item[10];
                                    }
                                }
                                $email = $emailInMessage ?? "$phone@facebook.com";
                            }
                            if (empty($firstName)) {
                                try {
                                    $nameFromWhatsapp = $inputRequestData['messages'][$i]['contact']['name'];
                                    $params = Dadata::cleanNameFromWhatsapp($nameFromWhatsapp);
                                } catch (Exception $exc) {
                                    Logs::error(sprintf('%s::%s | %s | dadata nameFromWa | %s',
                                    __CLASS__, __FUNCTION__, $login, $exc));
                                }
                            }
                            $params['user']['phone'] = $phone;
                            $params['user']['email'] = $email;
                            $params['user']['addfields']['whatsapp']=$phone;
                            GetCourse::addUser($params);
                            $alreadySent = DB::checkSentGetCourse($email, $inputRequestData['messages'][$i]['text']);
                            if (!$alreadySent) {
                                DB::query("INSERT INTO gc_contact_form SET email='$email', text='{$inputRequestData['messages'][$i]['text']}'");
                                GetCourse::sendContactForm($email, $inputRequestData['messages'][$i]['text'].PHP_EOL.'Отправлено из WhatsApp ('.__CLASS__.')');
                            }
                            return true;
                        }
                    }
                }
            }
        } else {
            return false;
        }
    }

    public static function alertSemySMS($login, $argsI) {
        if (WA24_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, $argsI['message']));
            global $WA24SemySMSAlrtDst;
            for ($index = 0; $index < count($WA24SemySMSAlrtDst); $index++) {
                $argsO = array(
                    'chatId' => $WA24SemySMSAlrtDst[$index],
                    'text' => $argsI['message'],
                );
                self::queue($login, $argsO);
            }
        }
    }
}

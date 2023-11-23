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
                    $alreadySent = DB::checkSentWhatsapp($row->chatId, htmlspecialchars_decode($row->text));
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
                            $post['text'] = htmlspecialchars_decode($row->text);
                        }
                        if (!$row->contentUri == '') {
                            $post['contentUri'] = $row->contentUri;
                        }
                        $post=json_encode($post);
                        $result = json_decode(cURL::executeRequest($url, $post, $headers, false, false));
                        DB::query("UPDATE options SET option_value=CURRENT_TIMESTAMP() WHERE login='$login' AND option_name='wazzup24_request'");
                        if (isset($result->messageId)) {
                            DB::query("UPDATE send_to_wazzup24 SET sendTime=CURRENT_TIMESTAMP(), result='{$result->messageId}' WHERE id={$row->id}");
                        }
                    }
                }
            }
            if (isset($result->error)) {
                $description[] = $result->description ?? '';
                $description[] = $result->data[0]->description ?? '';
                $error = sprintf('%s %s. %s', $result->error,
                    implode(', ', $result->data->fields),
                    implode('. ', $description));
                #$error = $result->error . ': ' . implode(', ', $result->data->fields) . '. ' . $description;
                DB::query("UPDATE send_to_wazzup24 SET sendTime=CURRENT_TIMESTAMP(), result='$error' WHERE id={$row->id}");
                $message = sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                    $login, $error);
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
            if (isset($args['chatId']) && (isset($args['text']) || isset($args['contentUri']))) {
                $args['chatId'] = intval($args['chatId']);
                $args['text'] = htmlspecialchars($args['text']);
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
                    if ($inputRequestData['messages'][$i]['type']=="text" &&
                        $inputRequestData['messages'][$i]['status']=="inbound") {
                        if (date_diff(date_create($inputRequestData['messages'][$i]['dateTime']), date_create('Now'))->h > 0) {
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
                                !isset($user->email) ?: $email = $user->email;
                                !isset($user->firstName) ?: $firstName = $user->firstName;
                            } catch (Exception $exc) {
                                Logs::error(sprintf('%s::%s | %s | var user | %s',
                                    __CLASS__, __FUNCTION__, $login, $exc));
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
                            preg_match("/\|.*\|/",$inputRequestData['messages'][$i]['text'],$matches);
                            if ($matches) {
                                $item=explode("|", $matches[0]);
                                global $addFields;
                                if (isset($item[1]) && $item[1] != '') {
                                    $params['user']['group_name']= array($addFields->{$item[1]});
                                }
                                if (isset($item[2]) && $item[2] != '') {
                                    $params['user']['addfields']['d_utm_source']=$item[2];
                                }
                                if (isset($item[3]) && $item[3] != '') {
                                    $params['user']['addfields']['d_utm_medium']=$item[3];
                                }
                                if (isset($item[4]) && $item[4] != '') {
                                    $params['user']['addfields']['d_utm_content']=$item[4];
                                }
                                if (isset($item[5]) && $item[5] != '') {
                                    $params['user']['addfields']['d_utm_campaign']=$item[5];
                                }
                                if (isset($item[6]) && $item[6] != '') {
                                    $params['user']['addfields']['d_utm_term']=$item[6];
                                }
                                if (isset($item[7]) && $item[7] != '') {
                                    $params['user']['addfields']['d_utm_rs']=$item[7];
                                }
                                if (isset($item[8]) && $item[8] != '') {
                                    $params['user']['addfields']['d_utm_acc']=$item[8];
                                }
                                if (isset($item[9]) && $item[9] != '') {
                                    $params['user']['addfields']['Возраст']=$item[9];
                                }
                                if (isset($item[10]) && $item[10] != '') {
                                    $emailInMessage=$item[10];
                                }
//                                !isset($item[1]) ?: $params['user']['group_name']= array($addFields->{$item[1]});
//                                !isset($item[2]) ?: $params['user']['addfields']['d_utm_source']=$item[2];
//                                !isset($item[3]) ?: $params['user']['addfields']['d_utm_medium']=$item[3];
//                                !isset($item[4]) ?: $params['user']['addfields']['d_utm_content']=$item[4];
//                                !isset($item[5]) ?: $params['user']['addfields']['d_utm_campaign']=$item[5];
//                                !isset($item[6]) ?: $params['user']['addfields']['d_utm_term']=$item[6];
//                                !isset($item[7]) ?: $params['user']['addfields']['d_utm_rs']=$item[7];
//                                !isset($item[8]) ?: $params['user']['addfields']['d_utm_acc']=$item[8];
//                                !isset($item[9]) ?: $params['user']['addfields']['Возраст']=$item[9];
//                                !isset($item[10]) ?: $emailInMessage=$item[10];
                            }
                            $email = $email ?? $emailInMessage ?? "$phone@facebook.com";
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

    public static function clearWa24Queue($login) {
        if (WA24_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s', __CLASS__, __FUNCTION__, $login));
            DB::query("DELETE FROM send_to_wazzup24 WHERE login='$login' AND sendTime=0");
        }
    }

    public function historyClear($login, $args) {
        DB::query("DELETE FROM send_to_wazzup24 WHERE login='$login' AND sendTime < CURRENT_DATE - INTERVAL {$args['interval']}");
    }
}

<?php
/**
 * Class for ChatApi
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class ChatApi {
    public static function queue($login, $args) {
        if (CHAT_API_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            if ($args['phone'] && $args['body']){
                $args['phone'] = intval($args['phone']);
                $phoneLen = strlen($args['phone']);
                if ($phoneLen > 8 && $phoneLen < 15) {
                   DB::query("INSERT INTO send_to_chatapi SET phone='{$args['phone']}', body='{$args['body']}', login='$login'");
                    return true; 
                }
            }
        } else {
            return true;
        }
    }

    public static function send($login) {
        if (CHAT_API_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s', __CLASS__, __FUNCTION__, $login));
            for($i = 0; $i < 3; $i++){
                sleep(rand(15,19));
                if ($row = DB::query("SELECT * FROM send_to_chatapi WHERE sendTime=0 AND login='$login' LIMIT 1")->fetch_object()) {
                    $alreadySent = DB::checkSentWhatsapp($row->phone, $row->body);
                    if ($alreadySent) {
                        Logs::handler(sprintf('%s::%s | %s | Message %i already sent via %s',
                            __CLASS__, __FUNCTION__, $login, $row->id, $alreadySent));
                        DB::query("UPDATE send_to_chatapi SET sendTime=CURRENT_TIMESTAMP() WHERE id={$row->id}");
                    } else {
                        $url = 'https://api.chat-api.com/instance' . CHAT_API_INSTANCE . '/sendMessage?token=' . CHAT_API_TOKEN;
                        $headers = array();
                        $post = array();
                        $headers[] = "Content-Type: application/json";
                        $post['body'] = $row->body;
                        $post['phone'] = $row->phone;
                        $post=json_encode($post);
                        $answer = cURL::execute('POST', $url, $post, $headers, false, false);
                        $result = json_decode($answer);
                        DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='chatapi' AND login='$login'");
                        if (isset($result->sent)) {
                            DB::query("UPDATE send_to_chatapi SET sendTime=CURRENT_TIMESTAMP() WHERE id={$row->id}");
                        } else {
                            $error = $result->error ?? $answer;
                            $message = sprintf('%s::%s | %s | %s', __CLASS__,
                                __FUNCTION__, $login, $error);
                            Logs::error($message);
                            BX24::sendBotMessage($message);
                            Telegram::alert($message);
                        }
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public static function trap($login, $inputRequestData) {
        if (CHAT_API_ENABLED) {
            if (isset($inputRequestData['messages'])) {
                if ($inputRequestData['messages'][0]['fromMe'] == false) {
                    Logs::handler(sprintf('%s::%s | %s', __CLASS__, __FUNCTION__, $login));
                    $phone = substr(preg_replace('/[^0-9]/', '', $inputRequestData['messages'][0]['chatId']), -15);
                    try {
                        $user = DB::query("SELECT email, instagram, firstName FROM gc_users WHERE login='$login' AND phone REGEXP '$phone'")->fetch_object();
                        $email = $user->email ?? null;
                        $firstName = $user->firstName ?? null;
                    } catch (Exception $exc) {
                        Logs::error(sprintf('%s::%s | var user | %s', __CLASS__, __FUNCTION__, $exc));
                    }
                    if (empty($firstName)) {
                        try {
                            $nameFromWhatsapp = $inputRequestData['messages'][0]['senderName'] ?? $inputRequestData['messages'][0]['nameInMessenger'];
                            $params = Dadata::cleanNameFromWhatsapp($nameFromWhatsapp);
                        } catch (Exception $exc) {
                            Logs::error(__CLASS__ . '::' . __FUNCTION__ . " | dadata nameFromWa | $exc");
                        }
                    }
                    preg_match("/\|.*\|/",$inputRequestData['messages'][0]['body'],$matches);
                    if ($matches){
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
//                        !isset($item[1]) ?: $params['user']['group_name']= array($addFields->{$item[1]});
//                        !isset($item[2]) ?: $params['user']['addfields']['d_utm_source']=$item[2];
//                        !isset($item[3]) ?: $params['user']['addfields']['d_utm_medium']=$item[3];
//                        !isset($item[4]) ?: $params['user']['addfields']['d_utm_content']=$item[4];
//                        !isset($item[5]) ?: $params['user']['addfields']['d_utm_campaign']=$item[5];
//                        !isset($item[6]) ?: $params['user']['addfields']['d_utm_term']=$item[6];
//                        !isset($item[7]) ?: $params['user']['addfields']['d_utm_rs']=$item[7];
//                        !isset($item[8]) ?: $params['user']['addfields']['d_utm_acc']=$item[8];
//                        !isset($item[9]) ?: $params['user']['addfields']['Возраст']=$item[9];
//                        !isset($item[10]) ?: $emailInMessage=$item[10]; 
                    }
                    $email = $email ?? $emailInMessage ?? "$phone@facebook.com";
                    $params['user']['phone'] = $phone;
                    $params['user']['email'] = $email;
                    $params['user']['addfields']['whatsapp']=$phone;
                    GetCourse::usersAdd($login, $params);
                    $alreadySent = DB::checkSentGetCourse($email, $inputRequestData['messages'][0]['body']);
                    if (!$alreadySent) {
                        DB::query("INSERT INTO gc_contact_form SET email='$email', text='{$inputRequestData['messages'][0]['body']}'");
                        GetCourse::sendContactForm($email, $inputRequestData['messages'][0]['body'].PHP_EOL.'Отправлено из WhatsApp ('.__CLASS__.')');
                    }
                    return true;
                }
            }
        } else {
            return false;
        }
    }
}

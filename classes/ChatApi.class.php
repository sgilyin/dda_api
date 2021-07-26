<?php
/**
 * Class for ChatApi
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class ChatApi {
    public static function queue($login, $inputRequestData) {
        if (CHAT_API_ENABLED && CHAT_API_TOKEN != '') {
            if ($inputRequestData['phone'] && $inputRequestData['body']){
                $phone = $inputRequestData['phone'];
                $body = $inputRequestData['body'];
                DB::query("INSERT INTO send_to_chatapi (`phone`, `body`, `login`) VALUES ('$phone', '$body', '$login')");
                return true;
            }
        } else {
            return true;
        }
    }

    public static function send($login, $logDir) {
        if (CHAT_API_ENABLED && CHAT_API_TOKEN != '') {
            for($i = 0; $i < 3; $i++){
                sleep(rand(15,19));
#                $last = strtotime(DB::query("SELECT last FROM request WHERE service='wazzup24'")->fetch_object()->last);
                if ($row = DB::query("SELECT * FROM send_to_chatapi WHERE success=0 AND login='$login' LIMIT 1")->fetch_object()){
                    $url = 'https://api.chat-api.com/instance272955/sendMessage?token=' . CHAT_API_TOKEN;
                    $headers = array();
                    $post = array();
                    $headers[] = "Content-Type: application/json";
                    $post['body'] = $row->body;
                    $post['phone'] = $row->phone;
                    $post=json_encode($post);
                    $result = json_decode(cURL::executeRequest($url, $post, $headers, false, $logDir));
                    DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='chatapi' AND login='$login'");
                    if ($result->sent) {
                        DB::query("UPDATE send_to_chatapi SET success=1 WHERE id={$row->id}");
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }
    }

    public static function trap($login, $inputRequestData, $logDir) {
        if (CHAT_API_ENABLED && CHAT_API_TOKEN != '') {
            if (isset($inputRequestData['messages'])) {
                if ($inputRequestData['messages'][0]['fromMe'] == false) {
                    $phone = substr(preg_replace('/[^0-9]/', '', $inputRequestData['messages'][0]['chatId']), -15);
                    try {
                        $user = DB::query("SELECT email, instagram, firstName FROM gc_users WHERE phone='$phone' AND login='$login'")->fetch_object();
                        $email = $user->email ?? null;
                        $firstName = $user->firstName ?? null;
                        $instagram = $user->instagram ?? null;
                    } catch (Exception $exc) {
                        Logs::error(__CLASS__ . '::' . __FUNCTION__ . " | var user | $exc");
                    }
                    if (empty($email)){
                        try {
                            $nameFromWhatsapp = $inputRequestData['messages'][0]['senderName'] ?? $inputRequestData['messages'][0]['nameInMessenger'];
                            $params = Dadata::cleanNameFromWhatsapp($nameFromWhatsapp, $logDir);
                        } catch (Exception $exc) {
                            Logs::error(__CLASS__ . '::' . __FUNCTION__ . " | dadata cleanName | $exc");
                        }
                        preg_match("/\|.*\|/",$inputRequestData['messages'][0]['body'],$matches);
                        if ($matches){
                            $item=explode("|", $matches[0]);
                            if ($item[1]){
                                global $addFields;
                                $params['user']['group_name']= array($addFields->{$item[1]});
                            }
                            if ($item[2]){
                                $params['user']['addfields']['d_utm_source']=$item[2];
                            }
                            if ($item[3]){
                                $params['user']['addfields']['d_utm_medium']=$item[3];
                            }
                            if ($item[4]){
                                $params['user']['addfields']['d_utm_content']=$item[4];
                            }
                            if ($item[5]){
                                $params['user']['addfields']['d_utm_campaign']=$item[5];
                            }
                            if ($item[6]){
                                $params['user']['addfields']['d_utm_term']=$item[6];
                            }
                            if ($item[7]){
                                $params['user']['addfields']['d_utm_rs']=$item[7];
                            }
                            if ($item[8]){
                                $params['user']['addfields']['d_utm_acc']=$item[8];
                            }
                            if ($item[9]){
                                $params['user']['addfields']['Возраст']=$item[9];
                            }
                            if ($item[10]){
                                $emailInMessage=$item[10];
                            }
                        }
                        $email = $emailInMessage ?? "$phone@facebook.com";
                    }
                    if (empty($firstName)) {
                        try {
                            $nameFromWhatsapp = $inputRequestData['messages'][0]['senderName'] ?? $inputRequestData['messages'][0]['nameInMessenger'];
                            $params = Dadata::cleanNameFromWhatsapp($nameFromWhatsapp, $logDir);
                        } catch (Exception $exc) {
                            Logs::error(__CLASS__ . '::' . __FUNCTION__ . " | dadata nameFromWa | $exc");
                        }
                    }
                    $params['user']['phone'] = $phone;
                    $params['user']['email'] = $email;
                    $params['user']['addfields']['whatsapp']=$phone;
                    GetCourse::addUser($params, $logDir);
                    GetCourse::sendContactForm($email, $inputRequestData['messages'][0]['body'].PHP_EOL.'Отправлено из WhatsApp', $logDir);
                    return true;
                }
            }
        } else {
            return false;
        }
    }
}

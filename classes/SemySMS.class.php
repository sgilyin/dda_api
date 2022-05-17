<?php

/**
 * Description of SemySMS
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class SemySMS {
    public static function trap($login, $inputRequestData) {
        if (SEMYSMS_ENABLED) {
            if ($inputRequestData['type']=='2') {
                Logs::handler(__CLASS__ . '::' . __FUNCTION__ . 
                    ' | RCVD | ' . $inputRequestData['phone'] . 
                    ' | ' . $inputRequestData['msg']);
                $phone = substr(preg_replace('/[^0-9]/', '', $inputRequestData['phone']), -15);
                try {
                    $obj = DB::query("SELECT email FROM gc_users WHERE login='$login' AND phone REGEXP '$phone'")->fetch_object();
                    if (isset($obj->email)) {
                        $email = $obj->email;
                    }
                } catch (Exception $exc) {
                    #nothing yet
                }
                if (!isset($email)) {
                    preg_match("/\|.*\|/",$inputRequestData['msg'],$matches);
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
                $params['user']['phone'] = $phone;
                $params['user']['email'] = $email;
                $params['user']['addfields']['whatsapp']=$phone;
                GetCourse::addUser($params);
                $alreadySent = DB::checkSentGetCourse($email, $inputRequestData['msg']);
                if (!$alreadySent) {
                    DB::query("INSERT INTO gc_contact_form SET email='$email', text='{$inputRequestData['msg']}'");
                    GetCourse::sendContactForm($email, $inputRequestData['msg'].PHP_EOL.'Отправлено из WhatsApp ('.__CLASS__.')');
                }
            }
            if ($inputRequestData['type']=='0') {
                $whatsapp['to'] = WA_SEMYSMS_NOTIFY;
                $whatsapp['transport'] = 'whatsapp';
                $whatsapp['text'] = $inputRequestData['phone'] . PHP_EOL .
                    $inputRequestData['msg'] . PHP_EOL . $inputRequestData['date'];
                Wazzup24::queue($login, $whatsapp);
                $toChatApi['phone'] = WA_SEMYSMS_NOTIFY;
                $toChatApi['body'] = $inputRequestData['phone'] . PHP_EOL .
                    $inputRequestData['msg'] . PHP_EOL . $inputRequestData['date'];
                ChatApi::queue($login, $toChatApi);
            }
        }
    }

    public static function queue($login, $args) {
        if (SEMYSMS_ENABLED && SEMYSMS_TOKEN != '') {
            Logs::handler(__CLASS__."::".__FUNCTION__." | $login");
            if (isset($args['phone']) && isset($args['msg'])) {
                $args['device'] = $args['device'] ?? SEMYSMS_DEVICE;
                foreach ($args as $key => $val) {
                    $setArr[] = "$key='$val'";        
                }
                $setStr = implode(", ", $setArr);
                $query = "INSERT INTO send_to_semysms SET $setStr, login='$login'";
                DB::query($query);
            }
            return true;
        } else {
            return false;
        }
    }

    public static function send($login) {
        if (SEMYSMS_ENABLED && SEMYSMS_TOKEN != '') {
            Logs::handler(__CLASS__."::".__FUNCTION__." | $login");
            for($i = 0; $i < 3; $i++){
                sleep(rand(15,19));
                if ($row = DB::query("SELECT * FROM send_to_semysms WHERE sendTime=0 AND login='$login' LIMIT 1")->fetch_object()) {
                    $alreadySent = DB::checkSentWhatsapp($row->phone, $row->msg);
                    if ($alreadySent) {
                        Logs::handler(__CLASS__."::".__FUNCTION__." | $login | Message {$row->id} already sent via $alreadySent");
                        DB::query("UPDATE send_to_semysms SET sendTime=CURRENT_TIMESTAMP() WHERE id={$row->id}");
                    } else {
                        $url = 'https://semysms.net/api/3/sms.php';
                        $post = array();
                        $post['phone'] = $row->phone;
                        $post['msg'] = $row->msg;
                        $post['device'] = $row->device;
                        $post['token'] = SEMYSMS_TOKEN;
                        $result = json_decode(cURL::executeRequestTest('POST', $url, $post, false, false, false));
                        DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='semysms' AND login='$login'");
                        if ($result->code == '0') {
                            DB::query("UPDATE send_to_semysms SET sendTime=CURRENT_TIMESTAMP() WHERE id={$row->id}");
                        } else {
                            $message = __CLASS__.'::'.__FUNCTION__." | $login | $result";
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
}

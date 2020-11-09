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
 * Class for Wazzup24
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Wazzup24 {

    /**
     * Send params from queue to Wazzup24
     * 
     * @param string $logDir
     * @return string
     */
    public static function send($login, $logDir) {
        if (WA_API_KEY_20) {
            for($i = 0; $i < 4; $i++){
                sleep(rand(11,15));
    //            $last = strtotime(DB::query("SELECT last FROM request WHERE service='wazzup24'")->fetch_object()->last);
                if ($row = DB::query("SELECT * FROM send_to_wazzup24 WHERE success=0 AND login='$login' LIMIT 1")->fetch_object()){
                    $url = 'https://api.wazzup24.com/v2/send_message';
                    $headers = array();
                    $post = array();
                    $headers[] = "Content-type:application/json";
                    $headers[] = "Authorization: Basic ".WA_API_KEY_20;
                    $post['chatType'] = $row->transport;
                    $post['channelId'] = ($row->transport == 'whatsapp') ? WA_CID_WA : WA_CID_IG;
                    //$post['chatId']=$row->to;
                    $post['chatId'] = ($row->transport == 'whatsapp') ? preg_replace('/[^0-9]/', '', $row->to) : $row->to;
                    $post['text'] = $row->text ?? $row->content;
 //                   $post['content'] = $row->content ?? false;
                    $post=json_encode($post);
                    $result = cURL::executeRequest($url, $post, $headers, false, $logDir);
                    DB::query("UPDATE send_to_wazzup24 SET success=1 WHERE id={$row->id}");
                    DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='wazzup24' AND login='$login'");
                }
            }
            return true;
        }
    }

    public static function old_send($login, $logDir) {
        if (WA_URL_SUBDOMAIN && WA_API_KEY) {
            for($i = 0; $i < 4; $i++){
                sleep(rand(11,15));
    //            $last = strtotime(DB::query("SELECT last FROM request WHERE service='wazzup24'")->fetch_object()->last);
                if ($row = DB::query("SELECT * FROM send_to_wazzup24 WHERE success=0 AND login='$login' LIMIT 1")->fetch_object()){
                    $url = 'https://'.WA_URL_SUBDOMAIN.'.wazzup24.com/api/v1.1/send_message';
                    $headers = array();
                    $post = array();
                    $headers[] = "Content-type:application/json";
                    $headers[] = "Authorization:".WA_API_KEY;
                    $post['transport'] = $row->transport;
                    $post['from'] = ($row->transport == 'whatsapp') ? WA_PHONE_FROM : INSTAGRAM_FROM;
                    $post['to']=$row->to;
                    $post['text']=$row->text;
                    $post['content']=$row->content;
                    $post=json_encode($post);
                    $result = cURL::executeRequest($url, $post, $headers, false, $logDir);
                    DB::query("UPDATE send_to_wazzup24 SET success=1 WHERE id={$row->id}");
                    DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='wazzup24'");
                }
            }
            return true;
        }
    }

    /**
     * Add params to queue for Wazzup24
     * 
     * @param string $login
     * @param array $inputRequestData
     * @return boolean
     */
    public static function queue($login, $inputRequestData) {
        if ($inputRequestData['transport'] && $inputRequestData['to'] && ($inputRequestData['text'] || $inputRequestData['content'])){
            $to = $inputRequestData['to'];
            $text = $inputRequestData['text'] ?? '';
            $content = $inputRequestData['content'] ?? '';
            $transport = $inputRequestData['transport'] ?? '';

            DB::query("INSERT INTO send_to_wazzup24 (`to`, `text`, `content`, `login`, `transport`) VALUES ('$to', '$text', '$content', '$login', '$transport')");
            
            return true;
        }
    }

    /**
     * Check input message, create user in GetCourse and send form from user to GetCourse
     * 
     * @global array $addFields
     * @param array $inputRequestData
     * @param string $logDir
     */
    public static function trap($login, $inputRequestData, $logDir) {
            if ($inputRequestData['messages'][0]['status']=="99") {
                $phone = substr(preg_replace('/[^0-9]/', '', $inputRequestData['messages'][0]['phone']), -15);
                try {
                    $email = DB::query("SELECT email FROM gc_users WHERE phone='$phone' AND login='$login'")->fetch_object()->email;
                } catch (Exception $exc) {
                }
    //            $email = DB::query("SELECT email FROM gc_users WHERE phone='$phone'")->fetch_object()->email;
            if (!$email){
                preg_match("/\|.*\|/",$inputRequestData['messages'][0]['text'],$matches);
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
                $params['user']['phone'] = $phone;
                $params['user']['email'] = $email;
                $params['user']['addfields']['whatsapp']=$phone;
                GetCourse::addUser($params, $logDir);
            }
            GetCourse::sendContactForm($email, $inputRequestData['messages'][0]['text'].PHP_EOL.'Отправлено из WhatsApp', $logDir);
        }
    }
}

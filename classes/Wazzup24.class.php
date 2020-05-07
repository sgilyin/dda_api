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
     * 
     * @param string $logDir
     * @return string
     */
    public static function send($logDir) {
        for($i = 0; $i < 2; $i++){
            sleep(rand(15,25));
            $last = strtotime(DB::query("SELECT last FROM request WHERE service='wazzup24'")->fetch_object()->last);
//            if (time() - $last > 15){
                if ($row = DB::query('SELECT * FROM send_to_wazzup24 WHERE success=0 LIMIT 1')->fetch_object()){
                    $url="https://".WA_URL_SUBDOMAIN.".wazzup24.com/api/v1.1/send_message";
                    $headers = array();
                    $post = array();
                    $headers[]="Content-type:application/json";
                    $headers[]="Authorization:".WA_API_KEY;
                    $post['transport']='whatsapp';
                    $post['from']=WA_PHONE_FROM;
                    $post['to']=$row->to;
                    $post['text']=$row->text;
                    $post['content']=$row->content;
                    $post=json_encode($post);
                    $result = cURL::executeRequest($url, $post, $headers, false, $logDir);
                    DB::query("UPDATE send_to_wazzup24 SET success=1 WHERE id={$row->id}");
                    DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='wazzup24'");
                }
//            }
        }

        return true;
    }

    /**
     * 
     * @param string $login
     * @param array $inputRequestData
     * @return boolean
     */
    public static function queue($login, $inputRequestData) {
        if ($inputRequestData['to'] && ($inputRequestData['text'] || $inputRequestData['content'])){
            $to = $inputRequestData['to'];
            if ($inputRequestData['text']) {
                $text = $inputRequestData['text'];
            } else {
                $text = '';
            }
            if ($inputRequestData['content']){
                $content = $inputRequestData['content'];
            } else {
                $content = '';
            }
            DB::query("INSERT INTO send_to_wazzup24 (`to`, `text`, `content`, `login`) VALUES ('$to', '$text', '$content', '$login')");
            
            return true;
        }    
    }

    public static function trap($inputRequestData, $logDir) {
            if ($inputRequestData['messages'][0]['status']=="99") {
                $phone = substr(preg_replace('/[^0-9]/', '', $inputRequestData['messages'][0]['phone']), -15);
                try {
                    $email = DB::query("SELECT email FROM gc_users WHERE phone='$phone'")->fetch_object()->email;
                } catch (Exception $exc) {
                }
    //            $email = DB::query("SELECT email FROM gc_users WHERE phone='$phone'")->fetch_object()->email;
            if (!$email){
                preg_match("/\|.*\|/",$inputRequestData['messages'][0]['text'],$matches);
                if ($matches){
                    $item=explode("|", $matches[0]);
                    if ($item[1]){
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
                }
                $email = "$phone@facebook.com";
                $params['user']['phone'] = $phone;
                $params['user']['email'] = $email;
                $params['user']['addfields']['whatsapp']=$phone;
                GetCourse::addUser($params, $logDir);
            }
            GetCourse::sendContactForm($email, $inputRequestData['messages'][0]['text'].PHP_EOL.'Отправлено из WhatsApp', $logDir);
        }
    }
}

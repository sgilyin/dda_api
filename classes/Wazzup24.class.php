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
        $last = strtotime(DB::query("SELECT last FROM request WHERE service='wazzup24'")->fetch_object()->last);
        if (time() - $last > rand(7,15)){
            if ($row = DB::query('SELECT * FROM send_to_wazzup24 WHERE success=0 LIMIT 1')->fetch_object()){
                $url="https://".WA_URL_SUBDOMAIN.".wazzup24.com/api/v1.1/send_message";
                $headers[]="Content-type:application/json";
                $headers[]="Authorization:".WA_API_KEY;
                $post['transport']='whatsapp';
                $post['from']=WA_PHONE_FROM;
                $post['to']=$row->to;
                $post['text']=$row->text;
                $post['content']=$row->content;
                $post=json_encode($post);
                $result = cURL::executeRequest($url,$post,$headers,$logDir);
                DB::query("UPDATE send_to_wazzup24 SET success=1 WHERE id={$row->id}");
                DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='wazzup24'");
                return $result;
            }
        }
    }

    /**
     * 
     * @param string $login
     * @param array $inputRequestData
     * @return boolean
     */
    public static function queue($login, $inputRequestData) {
        DB::query("INSERT INTO send_to_wazzup24 (`to`, `text`, `content`, `login`) VALUES ('".$inputRequestData['to']."', '".$inputRequestData['text']."', '".$inputRequestData['content']."', '$login')");
        return true;
    }
}

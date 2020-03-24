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
 * Class for cURL
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class cURL {

    /**
     * Execute request to some server
     * 
     * @param string $url
     * @param array $post
     * @param array $headers
     * @param string $logDir
     * @return response
     */
    public static function executeRequest($url,$post,$headers,$logDir) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if($post){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if($headers){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);
        if ($logDir){Logs::add($logDir,'cURL',date('d-m-Y G:i')." | $url".PHP_EOL.print_r($post,TRUE).PHP_EOL.print_r($result,TRUE));}
        return $result;
    }
}

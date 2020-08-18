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
 * Class for MyTarget
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class MyTarget {
    public static function modifyAudience($inputRequestData, $logDir) {
        if (MT_ACCESS_TOKEN) {
            $segmentId = $inputRequestData['segmentId'] ?? false;
            if ($segmentId){
                $query = "SELECT * FROM my_target_audience WHERE segment='$segmentId'";
                if ($result = DB::query($query)) {
                    file_put_contents("$segmentId.csv", 'email,phone');
                    $url = 'https://target-sandbox.my.com/api/v2/remarketing/users_lists.json';
                    $headers[] = 'Authorization: Bearer ' . MT_ACCESS_TOKEN;
                    $headers[] = 'Content-Type: multipart/form-data';
                    while ($obj = $result->fetch_object()) {
                        file_put_contents("$segmentId.csv", PHP_EOL . $obj->email . ',' . $obj->phone, FILE_APPEND);
                    }
                    $result->close();
                    $post['file'] = new CurlFile(realpath("$segmentId.csv"));
                    $post['data'] = '{"name": "Test", "type": "vk"}';
                    return cURL::executeRequest($url, $post, $headers, false, $logDir);
                }
            } else {
                return false;
            }
        }
    }

    public static function addItemToAudience($inputRequestData, $login) {
        $email = $inputRequestData['email'] ?? false;
        $phone = $inputRequestData['phone'] ?? false;
        $segmentId = $inputRequestData['segmentId'] ?? false;
        if ($segmentId && ($email || $phone)) {
            $query = "INSERT INTO my_target_audience (`email`, `phone`, `login`, `segment`) VALUES ('$email', '$phone', '$login', '$segmentId')";
            return DB::query($query);
        } else {
            return false;
        }
    }

    public static function delItemFromAudience($inputRequestData, $login) {
        $email = $inputRequestData['email'] ?? false;
        $phone = $inputRequestData['phone'] ?? false;
        $segmentId = $inputRequestData['segmentId'] ?? false;
        if ($segmentId && ($email || $phone)) {
            $query = "DELETE FROM my_target_audience WHERE login='$login' AND segment='$segmentId'";
            if ($email){
                $query = $query." AND email='$email'";
            }
            if ($phone){
                $query = $query." AND phone='$phone'";
            }
        return DB::query($query);
        } else {
            return false;
        }
    }

    public static function getAccessToken($logDir) {
        if (MT_CLIENT_ID && MT_CLIENT_SECRET) {
            $url = 'https://target-sandbox.my.com/api/v2/oauth2/token.json';
            $headers = array(
                'Content-Type: application/x-www-form-urlencoded',
            );
            $post = 'grant_type=client_credentials&client_id=' . MT_CLIENT_ID . '&client_secret=' . MT_CLIENT_SECRET . '&permanent=true';
            return cURL::executeRequest($url, $post, $headers, false, $logDir);
        }
    }

    public static function clearAccessTokens($logDir) {
        if (MT_CLIENT_ID && MT_CLIENT_SECRET) {
            $url = 'https://target-sandbox.my.com/api/v2/oauth2/token/delete.json';
            $headers = array(
                'Content-Type: application/x-www-form-urlencoded',
            );
            $post = 'client_id=' . MT_CLIENT_ID . '&client_secret=' . MT_CLIENT_SECRET;
            return cURL::executeRequest($url, $post, $headers, false, $logDir);
        }
    }
}

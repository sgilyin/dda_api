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
 * Class for Yandex
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Yandex {

    /**
     * Modify Yandex Audience whith CSV-file
     * 
     * @param array $inputRequestData
     * @return boolean
     */
    public static function modifyAudience($inputRequestData) {
        if (YANDEX_TOKEN) {
            $segmentId = $inputRequestData['segmentId'] ?? false;
            if ($segmentId){
                $query = "SELECT * FROM yandex_audience WHERE segment='$segmentId'";
                if ($result = DB::query($query)) {
                    file_put_contents("$segmentId.csv", 'email,phone');
                    $url = "https://api-audience.yandex.ru/v1/management/segment/$segmentId/modify_data?modification_type=replace";
                    $headers[] = 'Authorization: OAuth ' . YANDEX_TOKEN;
                    $headers[] = 'Content-Type: multipart/form-data';
                    while ($obj = $result->fetch_object()) {
                        file_put_contents("$segmentId.csv", PHP_EOL . $obj->email . ',' . $obj->phone, FILE_APPEND);
                    }
                    $result->close();
                    $post['file'] = new CurlFile(realpath("$segmentId.csv"));
                    return cURL::executeRequest($url, $post, $headers, false, false);
                }
            } else {
                return false;
            }
        }
    }

    /**
     * Add item to DB for Yandex Audience
     * 
     * @param array $inputRequestData
     * @param string $login
     * @return boolean
     */
    public static function addItemToAudience($inputRequestData, $login) {
        $email = $inputRequestData['email'] ?? false;
        $phone = $inputRequestData['phone'] ?? false;
        $segmentId = $inputRequestData['segmentId'] ?? false;
        if ($segmentId && ($email || $phone)) {
            $query = "INSERT INTO yandex_audience (`email`, `phone`, `login`, `segment`) VALUES ('$email', '$phone', '$login', '$segmentId')";
            return DB::query($query);
        } else {
            return false;
        }
    }

    /**
     * Delete item from DB for Yandex Audience
     * 
     * @param array $inputRequestData
     * @param string $login
     * @return boolean
     */
    public static function delItemFromAudience($inputRequestData, $login) {
        $email = $inputRequestData['email'] ?? false;
        $phone = $inputRequestData['phone'] ?? false;
        $segmentId = $inputRequestData['segmentId'] ?? false;
        if ($segmentId && ($email || $phone)) {
            $query = "DELETE FROM yandex_audience WHERE login='$login' AND segment='$segmentId'";
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

    public static function trap($login, $args) {
        Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
            $login, serialize($args)));
        foreach ($args['params'] as $key => $value) {
            if (substr($key, 0, 4) == 'utm_') {
                $param['session'][$key] = $value;
                $param['user']['addfields']["origin_$key"] = $value;
                $param['user']['addfields']["d_$key"] = $value;
            }
        }
        $param['user']['phone'] = $args['params']['phone'];
        $param['user']['email'] = $args['params']['email'];
        $param['user']['first_name'] = $args['params']['first_name'];
        GetCourse::usersAdd($login, $param);
    }
}

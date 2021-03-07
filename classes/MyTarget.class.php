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
    public function modifyAudience($inputRequestData, $login, $logDir) {
        if (MT_ACCESS_TOKEN && $inputRequestData['operation'] && $inputRequestData['segment'] && $inputRequestData['name']) {
            $query = "SELECT * FROM my_target_audience WHERE login='$login' AND segment={$inputRequestData['segment']} AND operation='{$inputRequestData['operation']}' AND success=0";
            if ($result = DB::query($query)) {
                $file = "MT_{$inputRequestData['segment']}.txt";
                file_put_contents($file, '');
                $url = 'https://target-sandbox.my.com/api/v2/remarketing/users_lists.json';
                $headers[] = 'Authorization: Bearer ' . MT_ACCESS_TOKEN;
                $headers[] = 'Content-Type: multipart/form-data';
                while ($obj = $result->fetch_object()) {
                    $ids[] = $obj->id;
                    $type = $obj->type;
                    file_put_contents($file, PHP_EOL . $obj->value, FILE_APPEND);
                    
                }
                $result->close();
                $prefix = ($inputRequestData['operation'] == 'del') ? '-' : '';
                $post['data'] = '{"base": ' . $prefix . $inputRequestData['segment'] . ', "name": "' . $inputRequestData['name'] . '", "type": "' . $type . '"}';
                $post['file'] = new CurlFile(realpath("MT_{$inputRequestData['segment']}.txt"));
                $json = json_decode(cURL::executeRequest($url, $post, $headers, false, $logDir));
                if ($json->id) {
                    $stringIds = implode(',', $ids);
                    $query = "UPDATE my_target_audience SET success=1 WHERE id IN ($stringIds)";
                    DB::query($query);
                    unlink($file);
                }
                return $json;
            }
        }
    }

    public function modifyItem($inputRequestData, $login) {
        if ($inputRequestData['operation'] && $inputRequestData['segment'] && $inputRequestData['type'] && $inputRequestData['value']) {
            $query = "INSERT INTO my_target_audience (`login`, `operation`, `segment`, `type`, `value`) VALUES ('$login', '{$inputRequestData['operation']}' ,'{$inputRequestData['segment']}', '{$inputRequestData['type']}', '{$inputRequestData['value']}')";
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
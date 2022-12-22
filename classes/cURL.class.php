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
    public static function executeRequest($url, $post, $headers, $userpwd, $ssl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        !$post ?: curl_setopt($ch, CURLOPT_POST, TRUE);
        !$post ?: curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        !$headers ?: curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        !$userpwd ?: curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSLCERT, $ssl->certPath);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSLKEY, $ssl->keyPath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        Logs::handler(sprintf('%s::%s | %d | %s | %s | %s', __CLASS__,
            __FUNCTION__, $http_code, $url, serialize($post), serialize($result)));
        if ($http_code != 200 && $http_code != 201) {
            Logs::error(sprintf('%s::%s | %d | %s | %s | %s | %s | %s', __CLASS__,
                __FUNCTION__, $http_code, $url, serialize($post), serialize($result),
                serialize($headers), $error));
        }
        return $result;
    }

    public static function executeRequestTest($customRequest, $url, $post, $headers, $userpwd, $ssl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        !$post ?: curl_setopt($ch, CURLOPT_POST, TRUE);
        !$post ?: curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        !$headers ?: curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        !$userpwd ?: curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSLCERT, $ssl->certPath);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSLKEY, $ssl->keyPath);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);
        Logs::handler(sprintf('%s::%s | %d | %s | %s | %s | %s', __CLASS__,
            __FUNCTION__, $info['http_code'], $url, serialize($post),
            serialize($result), serialize($info)));
        if ($http_code != 200 && $http_code != 201) {
            Logs::error(sprintf('%s::%s | %d | %s | %s | %s | %s', __CLASS__,
                __FUNCTION__, $info['http_code'], $url,
                serialize($post), serialize($result), serialize($info), $error));
        }
        return $result;
    }

    public static function execute($customRequest, $url, $post, $headers, $userpwd, $ssl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        !$post ?: curl_setopt($ch, CURLOPT_POST, TRUE);
        !$post ?: curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        !$headers ?: curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        !$userpwd ?: curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSLCERT, $ssl->certPath);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSLKEY, $ssl->keyPath);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);
        Logs::handler(sprintf('%s::%s | %d | %s | %s | %s', __CLASS__,
            __FUNCTION__, $info['http_code'], $url, serialize($post),
            serialize($result)));
        if ($info['http_code'] != 200 && $info['http_code'] != 201) {
            Logs::error(sprintf('%s::%s | %d | %s | %s | %s | %s', __CLASS__,
                __FUNCTION__, $info['http_code'], $url,
                serialize($post), serialize($result), serialize($info), $error));
        }
        return $result;
    }
}

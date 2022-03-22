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
 * Class for Senler
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Senler {

    public static function trap($inputRequestData) {
        if ($inputRequestData['type']=='subscribe'){
            $params['user']['email'] = 'id'.$inputRequestData['vk_user_id'].'@vk.com';
            $params['user']['addfields']['vk_uid'] = $inputRequestData['vk_user_id'] ?? '';
            $params['user']['addfields']['d_utm_source'] = $inputRequestData['utm_source'] ?? '';
            $params['user']['addfields']['d_utm_medium'] = $inputRequestData['utm_medium'] ?? '';
            $params['user']['addfields']['d_utm_campaign'] = $inputRequestData['utm_campaign'] ?? '';
            $params['user']['addfields']['d_utm_content'] = $inputRequestData['utm_content'] ?? '';
            $params['user']['addfields']['d_utm_term'] = $inputRequestData['utm_term'] ?? '';

            return GetCourse::addUser($params);
        }
    }

    /**
     * Add subscriber in Senler
     * 
     * @param array $inputRequestData
     * @return string
     */
    public static function addSubscriber($inputRequestData) {
        if (SENLER_CALLBACK_KEY) {
            if ($inputRequestData['vk_group_id'] && $inputRequestData['vk_user_id'] && $inputRequestData['subscription_id']){
                $params['vk_group_id'] = $inputRequestData['vk_group_id'];
                $params['vk_user_id'] = $inputRequestData['vk_user_id'];
                $params['subscription_id'] = $inputRequestData['subscription_id'];
                $params['v'] = '1.0';
                $params['hash'] = static::getHash($params, SENLER_CALLBACK_KEY);
                $url = 'https://senler.ru/api/subscribers/add';

                return cURL::executeRequest($url, http_build_query($params), false, false, false);
            }
        }
    }

    /**
     * Delete subscriber in Senler
     * 
     * @param array $inputRequestData
     * @param string 
     * @return string
     */
    public static function delSubscriber($inputRequestData) {
        if (SENLER_CALLBACK_KEY) {
            if ($inputRequestData['vk_group_id'] && $inputRequestData['vk_user_id'] && $inputRequestData['subscription_id']){
                $params['vk_group_id'] = $inputRequestData['vk_group_id'];
                $params['vk_user_id'] = $inputRequestData['vk_user_id'];
                $params['subscription_id'] = $inputRequestData['subscription_id'];
                $params['v'] = '1.0';
                $params['hash'] = static::getHash($params, SENLER_CALLBACK_KEY);
                $url = 'https://senler.ru/api/subscribers/del';

                return cURL::executeRequest($url, http_build_query($params), false, false, false);
            }
        }
    }

    /**
     * Add subscription in Senler
     * 
     * @param array $inputRequestData
     * @return string
     */
    public static function addSubscription($inputRequestData) {
        if (SENLER_CALLBACK_KEY) {
            if($inputRequestData['vk_group_id'] && $inputRequestData['name']){
                $params['vk_group_id'] = $inputRequestData['vk_group_id'];
                $params['name'] = $inputRequestData['name'];
                $params['v'] = '1.0';
                $params['hash'] = static::getHash($params, SENLER_CALLBACK_KEY);
                $url = 'https://senler.ru/api/subscriptions/add';

                return cURL::executeRequest($url, http_build_query($params), false, false, false);
            }
        }
    }

    /**
     * Create hash for Senler
     * 
     * @param array $params
     * @param string $secret
     * @return string
     */
    private function getHash($params, $secret) {
        $values = "";
        foreach ($params as $value) {
            $values .= (is_array($value) ? implode("", $value) : $value);
        }

        return md5($values . $secret);
    }
}

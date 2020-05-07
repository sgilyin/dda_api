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
 * Class for GetCourse
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class GetCourse {
    /**
     * Send contact form to GetCourse
     * 
     * @param string $email
     * @param string $text
     * @return string
     */
    public static function sendContactForm($email, $text, $logDir){
        $url='https://'.GC_ACCOUNT.'.getcourse.ru/cms/system/contact';
        $page = file_get_contents($url);
        if ($page) {
            preg_match('/window\.requestTime.*(\d{10})/m', $page, $window_requestTime);
            preg_match('/window\.requestSimpleSign.*([0-9a-z]{32})/m', $page, $window_requestSimpleSign);
            preg_match('/<form.*data-xdget-id="([0-9]{5}(_\d*)*).*>/m', $page, $xdgetId);
            sleep(rand(4, 11));
            $params = array(
                "action" => "processXdget",
                "xdgetId" => $xdgetId[1],
                "params[action]" => "form",
                "params[url]" => $url,
                "params[email]" => $email,
                "params[full_name]" => "",
                "params[text]" => $text,
                "requestTime" => $window_requestTime[1],
                "requestSimpleSign" => $window_requestSimpleSign[1]
            );
            $post = http_build_query($params);
            $headers = array(
                "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
                "User-Agent: Mozilla/5.0 (compatible; Rigor/1.0.0; http://rigor.com)",
                "Accept: */*",
            );
        }

        return cURL::executeRequest($url, $post, $headers, false, $logDir);
    }

    /**
     * Add user to GetCourse
     * 
     * @param array $params
     * @param string $logDir
     * @return string
     */
    public static function addUser($params, $logDir) {
        $url = 'https://'.GC_ACCOUNT.'.getcourse.ru/pl/api/users';
        $post['action'] = "add";
        $post['key'] = GC_API_KEY;
        $params['system']['refresh_if_exists'] = 1;
        $post['params']=base64_encode(json_encode($params));

        return cURL::executeRequest($url, $post, false, false, $logDir);
    }

    public static function addUserRequest($inputRequestData, $logDir) {
        if ($inputRequestData['phone']){
            $params['user']['phone'] = $inputRequestData['phone'];
            $params['user']['email'] = $inputRequestData['phone'].'@facebook.com';
        }
        if ($inputRequestData['groups']){
            $params['user']['group_name'] = static::getRequestGroups($inputRequestData['groups']);
        }

        return static::addUser($params, $logDir);
    }

    private function getRequestGroups($requestGroups) {
        $groups = explode(',', $requestGroups);
        global $addFields;
        for ($i = 0; $i < count($groups); $i++) {
            $result[] = $addFields->{$groups[$i]};
        }

        return $result;
    }
}

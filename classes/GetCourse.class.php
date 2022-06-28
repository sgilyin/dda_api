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
    public static function sendContactForm($email, $text){
        if (GC_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $email, $text));
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
            return cURL::execute('POST', $url, $post, $headers, false, false);
        }
    }

    public static function addUser($params) {
        if (GC_ACCOUNT && GC_API_KEY != '') {
            $url = 'https://'.GC_ACCOUNT.'.getcourse.ru/pl/api/users';
            $post['action'] = "add";
            $post['key'] = GC_API_KEY;
            $params['system']['refresh_if_exists'] = 1;
            $post['params']=base64_encode(json_encode($params));
            Logs::handler(__CLASS__.'::'.__FUNCTION__." | {$params['user']['email']}");
            $userGC = json_decode(cURL::executeRequest($url, $post, false, false, false));
            if ($userGC->success) {
                return $userGC->result->user_id;
            } else {
                Logs::error(__CLASS__ . '::' . __FUNCTION__ . " | {$params['user']['email']} | {$userGC->result->error_message}");
            }
        }
    }

    public static function addUserRequest($inputRequestData) {
        if ($inputRequestData['phone']){
            //preg_replace('/[^0-9]/', '', $inputRequestData['phone'])
            //$params['user']['phone'] = $inputRequestData['phone'];
            //$params['user']['email'] = $inputRequestData['phone'].'@facebook.com';
            $phoneNum = preg_replace('/[^0-9]/', '', $inputRequestData['phone']);
            $params['user']['phone'] = $phoneNum;
            $params['user']['email'] = $phoneNum . '@facebook.com';
        }
        if ($inputRequestData['groups']){
            $params['user']['group_name'] = static::getRequestGroups($inputRequestData['groups']);
        }

        return static::addUser($params);
    }

    private function getRequestGroups($requestGroups) {
        $groups = explode(',', $requestGroups);
        global $addFields;
        for ($i = 0; $i < count($groups); $i++) {
            $result[] = $addFields->{$groups[$i]};
        }

        return $result;
    }

    private static function execute($login, $method, $action, $param) {
        Logs::handler(sprintf('%s::%s | %s | %s | %s | %s', __CLASS__,
            __FUNCTION__, $login, $method, $action, serialize($param)));
        $url = 'https://'.GC_ACCOUNT.".getcourse.ru/pl/api/$method/";
        $post['key'] = GC_API_KEY;
        switch ($action) {
            case 'export':
                break;
            case 'update':
                $param['system']['refresh_if_exists'] = 1;
                $post['params']=base64_encode(json_encode($param));
                break;

            default:
                $post['action'] = "add";
                $param['system']['refresh_if_exists'] = 1;
                $post['params']=base64_encode(json_encode($param));
                break;
        }
        DB::query("UPDATE options SET option_value=current_timestamp()"
            . "WHERE login='$login' AND option_name='getcourse_request'");
        return cURL::execute('POST', $url, $post, false, false, false);
    }

    public static function usersAdd($login, $args) {
        if (GC_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            $args['user']['email'] = (isset($args['user']['email'])) ?
                $args['user']['email'] : preg_replace('/[^0-9]/', '',
                $args['user']['phone']).'@facebook.com';
            !isset($args['groups']) ?: $args['user']['group_name'] =
                self::getRequestGroups($args['groups']);
            echo self::execute($login, 'users', 'add', $args);
        } else { echo 'Service is not configured. Check config.'; }
    }

    public static function dealsAdd($login, $args) {
        if (GC_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            if (isset($args['phone']) && isset($args['number']) && isset($args['status'])) {
                $query = "SELECT created_at FROM gc_deals WHERE id={$args['id']}";
                $created_at = DB::query($query)->fetch_object()->created_at;
                $param['user']['phone'] = $args['phone'];
                $param['user']['email'] = $args['email'];
                $param['deal']['deal_number'] = $args['number'];
                $param['deal']['deal_status'] = $args['status'];
                $param['deal']['deal_created_at'] = "$created_at 00:00:00";
                $param['deal']['product_title'] = $args['positions'];
                $param['deal']['deal_cost'] = $args['cost_money_value'];
                $param['deal']['addfields']['api_status'] = $args['status'];
                echo self::execute($login, 'deals', 'add', $param);
            } else {
                echo self::execute($login, 'deals', 'add', $args);
            }
        } else { echo 'Service is not configured. Check config.'; }
    }

    private function utmFromArgs($args, $pref = '') {
        $arr = false;
        $haystack = array('utm_source', 'utm_medium', 'utm_campaign',
            'utm_content', 'utm_group', 'gcpc', 'gcao', 'referer');
        foreach ($args as $key => $value) {
            if (in_array($key, $haystack)) {
                $arr["$pref$key"] = $value;
            }
        }
        return $arr;
    }

    public static function utmSession($login, $args) {
        if (GC_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            if (isset($args['email'])) {
                $param['user']['email'] = $args['email'];
                $param['session'] = self::utmFromArgs($args);
                echo self::execute($login, 'users', 'add', $param);
            } else {
                echo 'Не указан email';
            }
        } else { echo 'Service is not configured. Check config.'; }
    }

    public static function utmOrigin($login, $args) {
        if (GC_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            if (isset($args['email'])) {
                $param['user']['email'] = $args['email'];
                $param['user']['addfields'] = self::utmFromArgs($args, 'origin_');
                echo self::execute($login, 'users', 'add', $param);
            } else {
                echo 'Не указан email';
            }
        } else { echo 'Service is not configured. Check config.'; }
    }
}

<?php

/**
 * Description of Skorozvon
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */

class Skorozvon {
    private function refreshToken($login) {
        $opRes = DB::query("SELECT option_value, option_update FROM options WHERE login='$login' AND option_name='skorozvon_refresh'");
        $url = 'https://app.skorozvon.ru/oauth/token';
        $post['client_id'] = SKOROZVON_CLIENT_ID;
        $post['client_secret'] = SKOROZVON_CLIENT_SECRET;
        if ($opRes->num_rows > 0) {
            $refreshToken = $opRes->fetch_object()->option_value;
            $accessToken = DB::query("SELECT option_value FROM options WHERE login='$login' AND option_name='skorozvon_access'")->fetch_object()->option_value;
            $post['grant_type'] = 'refresh_token';
            $post['refresh_token'] = $refreshToken;
            $headers = array("Authorization: Bearer $accessToken");
            $result = json_decode(cURL::execute('POST', $url, $post, $headers, false, false));
            $accessToken = $result->access_token;
            DB::query(sprintf("UPDATE options SET option_value='%s' WHERE login='%s' AND option_name='%s'", $accessToken, $login, 'skorozvon_access'));
            DB::query(sprintf("UPDATE options SET option_value='%s' WHERE login='%s' AND option_name='%s'", $result->refresh_token, $login, 'skorozvon_refresh'));
        } else {
            $post['grant_type'] = 'password';
            $post['username'] = SKOROZVON_USERNAME;
            $post['api_key'] = SKOROZVON_API_KEY;
            $result = json_decode(cURL::execute('POST', $url, $post, false, false, false));
            $accessToken = $result->access_token;
            DB::query(sprintf("INSERT INTO options VALUE ('%s', '%s', '%s', current_timestamp())", $login, 'skorozvon_access', $accessToken));
            DB::query(sprintf("INSERT INTO options VALUE ('%s', '%s', '%s', current_timestamp())", $login, 'skorozvon_refresh', $result->refresh_token));
        }
        return $accessToken;
    }

    private function accessTokenGet($login) {
        $query = "SELECT option_value, option_update FROM options WHERE login='$login' AND option_name='skorozvon_access'";
        $opRes = DB::query($query);
        if ($opRes->num_rows > 0) {
            $row = $opRes->fetch_object();
            $accessTokenTime = date_create($row->option_update);
            if (date_diff($accessTokenTime, date_create('Now'))->h < 1) {
                $accessToken = $row->option_value;
            } else {
                $accessToken = self::refreshToken($login);
            }
        } else {
            $accessToken = self::refreshToken($login);
        }
        return $accessToken;
    }

    private function headersGen($login) {
        $accessToken = self::accessTokenGet($login);
        $headers = array("Authorization: Bearer $accessToken");
        return $headers;
    }

    public static function execute($login, $args) {
        Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__, $login, serialize($args)));
        $url = 'https://app.skorozvon.ru'.$args['action'];
        $headers = self::headersGen($login);
        $post = $args['param'] ?? false;
        DB::query("UPDATE options SET option_value=current_timestamp()"
            . "WHERE login='$login' AND option_name='skorozvon_request'");
        echo cURL::execute($args['method'], $url, $post, $headers, false, false);
    }

    public static function trap($login, $args) {
        if (SKOROZVON_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__, $login, serialize($args)));
            $param['user']['email'] = $args['lead']['emails'][0];
            $param['user']['addfields']['sz_call'] = sprintf('%s (%s)',
                date("d-m-Y H:i T", strtotime($args['call']['started_at'])),
                gmdate("H:i:s", $args['call']['duration']));
            $param['user']['addfields']['sz_comment'] = $args['call_result']['comment'];
            $param['user']['addfields']['sz_call_record'] = sprintf(
                'https://api.dmitry-dubrovsky.ru/%s/?class[method]=Skorozvon::execute&args[method]=GET&args[action]=/api/v2/calls/%d.mp3',
                $login, $args['call']['id']);
            echo GetCourse::usersAdd($login, $param);
        }
    }
}

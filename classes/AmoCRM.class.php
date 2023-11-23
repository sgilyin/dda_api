<?php

/**
 * Class for AmoCRM
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class AmoCRM {
    public function refreshToken($login) {
        if (AMOCRM_ENABLED) {
            $refresh_token = DB::query("SELECT option_value FROM options "
                . "WHERE login='$login' AND option_name='amocrm_refresh' AND "
                . "option_update < NOW() - INTERVAL 12 HOUR")->fetch_object()
                ->option_value;
            if ($refresh_token) {
                $url = 'https://' . AMOCRM_USERNAME . '.amocrm.ru/oauth2/access_token';
                $post['client_id'] = AMOCRM_CLIENT_ID;
                $post['client_secret'] = AMOCRM_CLIENT_SECRET;
                $post['grant_type'] = 'refresh_token';
                $post['refresh_token'] = $refresh_token;
                $post['redirect_uri'] = AMOCRM_REDIRECT_URI;
                $header[] = 'Content-Type: application/json';
                $result = json_decode(cURL::executeRequest($url, json_encode($post), 
                    $header, false, false));
                if ($result->refresh_token == '' || $result->access_token == '') {
                    $message = __CLASS__.'::'.__FUNCTION__." | $login | empty tokens";
                    Logs::error($message);
                    BX24::sendBotMessage($message);
                    Telegram::alert($message);
                } else {
                    DB::query(sprintf("UPDATE options SET option_value='%s' WHERE "
                        . "login='%s' AND option_name='%s'", $result->access_token,
                        $login, 'amocrm_access'));
                    DB::query(sprintf("UPDATE options SET option_value='%s' WHERE "
                        . "login='%s' AND option_name='%s'", $result->refresh_token,
                        $login, 'amocrm_refresh'));
                }
            }            
        }
    }

    private function accessTokenGet($login) {
        return DB::query("SELECT option_value FROM options WHERE login='$login' "
            . "AND option_name='amocrm_access'")->fetch_object()->option_value;
    }

    private function headersGen($login) {
        $accessToken = self::accessTokenGet($login);
        $headers = array("Authorization: Bearer $accessToken");
        return $headers;
    }

    public static function execute($login, $args) {
        if (AMOCRM_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            $url = 'https://' . AMOCRM_USERNAME . '.amocrm.ru/api/v4/' . $args['action'];
            $headers = self::headersGen($login);
            $post = '['.json_encode($args['param'], JSON_NUMERIC_CHECK).']';
            DB::query("UPDATE options SET option_value=current_timestamp()"
                . "WHERE login='$login' AND option_name='amocrm_request'");
            echo cURL::execute($args['method'], $url, $post, $headers, false, false);
        }
    }
}

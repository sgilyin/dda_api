<?php
/**
 * Class for Rixk
 *
 * @author sgilyin
 */
class Rixk {
    public static function checkPhoneCreditRating($login, $param) {
        if (RIXK_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($param)));
            $param['method'] = '/check/phone-credit-rating';
            return self::execute($login, $param);
        }
    }

    private static function execute($login, $param) {
        if (RIXK_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($param)));
            $url = RIXK_API_BASE . $param['method'];
            $header[] = 'Content-Type: application/json';
            $header[] = 'X-API-Key: ' . RIXK_API_KEY;
            return cURL::execute('POST', $url, json_encode($param), $header,
                false, false);
        }
    }
}

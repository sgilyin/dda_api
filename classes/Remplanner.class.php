<?php

/**
 * Description of Remplanner
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Remplanner {
    private static function execute($login, $action, $param) {
        Logs::handler(sprintf('%s::%s | %s | %s | %s | %s', __CLASS__,
            __FUNCTION__, $login, $action, serialize($param)));
        $url = 'https://remplanner.ru/setup/api/external/';
        $param['authkey'] = REMPLANER_AUTHKEY;
        $param['action'] = $action;
        $post = json_encode($param);
        DB::query("UPDATE options SET option_value=current_timestamp()"
            . "WHERE login='$login' AND option_name='remplanner_request'");
        return cURL::execute('POST', $url, $post, false, false, false);
    }

    public static function allow_promocode($login, $args) {
        if (REMPLANER_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__,
                __FUNCTION__, $login, serialize($args)));
            echo self::execute($login, __FUNCTION__, $args);
        } else { echo 'Service is not configured. Check config.'; }
    }
}

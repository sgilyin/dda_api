<?php

/**
 * Description of Telegram
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Telegram {
    public static function sendMessage($login, $args) {
        if (TELEGRAM_ENABLED && TELEGRAM_BOT_TOKEN != '') {
            if (isset($args['chat_id']) && isset($args['text'])) {
                Logs::handler(__CLASS__."::".__FUNCTION__." | $login | " . serialize($args));
                $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/' . __FUNCTION__;
                return cURL::executeRequestTest('POST', $url, $args, false, false, false);
            }
        }
    }

    public static function alert($message) {
        Logs::handler(__CLASS__."::".__FUNCTION__." | $message");
        global $TgAlrtDst;
        for ($index = 0; $index < count($TgAlrtDst); $index++) {
            $args = array(
                'chat_id' => $TgAlrtDst[$index],
                'text' => $message,
            );
            self::sendMessage(false, $args);
        }
    }

    public static function notice($message) {
        Logs::handler(__CLASS__."::".__FUNCTION__." | $message");
        global $TgNtcDst;
        for ($index = 0; $index < count($TgNtcDst); $index++) {
            $args = array(
                'chat_id' => $TgNtcDst[$index],
                'text' => $message,
            );
            self::sendMessage(false, $args);
        }
    }
}

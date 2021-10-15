<?php

/**
 * Description of Telegram
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Telegram {
    public static function sendMessage($login, $args) {
        if (TELEGRAM_ENABLED && TELEGRAM_BOT_TOKEN != '') {
            $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/' . __FUNCTION__;
            return cURL::executeRequestTest('POST', $url, $args, false, false);
        }
    }

    public static function alert($message) {
        global $TgAlrtDst;
        for ($index = 0; $index < count($TgAlrtDst); $index++) {
            $args = array(
                'chat_id' => $TgAlrtDst[$index],
                'text' => $message,
            );
            self::sendMessage(false, $args);
        }
    }
}

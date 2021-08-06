<?php

/**
 * Class for Bitrix24
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class BX24 {
    public static function callMethod($bx24Method, $bx24Data) {
        if (BX24_ENABLED && BX24_HOST != '' && BX24_USER != '' && BX24_SECRET != '') {
            $url = BX24_HOST.'/rest/'.BX24_USER.'/'.BX24_SECRET."/{$bx24Method}";
            $result = cURL::executeRequestTest('POST', $url, $bx24Data, false, false);
            Logs::handler(__CLASS__."::".__FUNCTION__." | $result");
            return $result;
        }
    }

    public static function sendBotMessage($message) {
        if (BX24_ENABLED && BX24_BOT_ID != '' && BX24_CLIENT_ID != '') {
            Logs::handler(__CLASS__."::".__FUNCTION__." | ".BX24_ALARM_CHAT_ID." | $message");
            $bx24Data = http_build_query(
                array(
                    'DIALOG_ID' => BX24_ALARM_CHAT_ID,
                    'MESSAGE' => $message,
                    'BOT_ID' => BX24_BOT_ID,
                    'CLIENT_ID' => BX24_CLIENT_ID,
                )
            );
            return static::callMethod('imbot.message.add.json', $bx24Data);
        }
    }
}

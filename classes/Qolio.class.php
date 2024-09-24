<?php

/**
 * Description of Qolio
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Qolio {
    public static function onpbxConvert($login, $param) {
        if (QOLIO_ENABLED){
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($param)));
            $qolioParams = array();
            $qolioParams['operator_id'] = $param['caller'];
            $qolioParams['uid'] = $param['uuid'];
            $qolioParams['client_phone_number'] = $param['callee'];
            $qolioParams['media_url'] = $param['download_url'];
            switch ($param['direction']) {
                case 'outbound':
                    $qolioParams['direction'] = 'outcoming';
                    break;
                case 'inbound':
                    $qolioParams['direction'] = 'incoming';
                    break;

                default:
                    break;
            }
            $qolioParams['duration'] = floatval($param['dialog_duration']);
            $qolioParams['started_at'] = date('c', $param['date']);
            self::send($login, $qolioParams);
        }
    }

    public static function send($login, $param) {
        if (QOLIO_ENABLED){
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($param)));
            $url = 'https://api.prod1.qolio.ru/api/v1/integrations/'.QOLIO_INTEGRATION_UID.'/phone_calls';
            $headers = array();
            $headers[] = "Content-type:application/json";
            $headers[] = "Authorization:".QOLIO_TOKEN;
            $result = json_decode(cURL::executeRequest($url, json_encode($param), $headers, false, false));
            if ($result->errors->detail){
                $message = sprintf('%s::%s | %s | %s', __CLASS__,
                    __FUNCTION__, $login, serialize($param));
                Logs::error($message);
                BX24::sendBotMessage($message);
                Telegram::alert($message);
            }
        }
    }
}

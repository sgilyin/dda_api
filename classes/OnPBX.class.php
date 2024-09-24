<?php

/**
 * Description of OnPBX
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class OnPBX {
    public static function trap($login, $args){
        if (ONPBX_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            Qolio::onpbxConvert($login, $args);
        }
    }
}

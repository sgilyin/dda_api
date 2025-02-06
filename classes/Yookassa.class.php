<?php

/**
 * Class for Yookassa
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Yookassa {
    public static function trap($login, $args){
        if (YOOKASSA_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            
        }
    }
}

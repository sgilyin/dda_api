<?php

/**
 * Description of Marquiz
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Marquiz {
    public static function trap($login, $args){
        if (MARQUIZ_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            
        }
    }
}

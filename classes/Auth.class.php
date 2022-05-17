<?php
/**
 * Class for Authorization
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Auth {
    public static function logIn($login, $args) {
        if (isset($args)) {
            if (isset($args['user']) && isset($args['password'])) {
                echo <<<HTML
                <form method=post>
                    User: <input type=text id=user name=user value=$args[user]>
                    Password: <input type=password id=password name=password value=$args[password]>
                    <input type=submit value=LogIn>
                </form>
                HTML;
                $deals = (self::checkAccess($login, $args)) ?
                    DB::getManagersDeals($login, $args['user']) :
                    'Неверный логин/пароль или пользователь отключен';
                echo $deals;
            }
        } else {
            echo <<<HTML
            <form method=post>
                User: <input type=text id=user name=user>
                Password: <input type=password id=password name=password>
                <input type=submit value=LogIn>
            </form>
            HTML;
        }
    }

    public static function checkAccess($login, $args) {
        $query = "SELECT user, password, status, salt FROM gc_managers WHERE login='$login' AND user='{$args['user']}'";
        $manager = DB::query($query)->fetch_object();
        $access = (md5(md5($args['password']).$manager->salt) == $manager->password && $manager->status == 'enable') ? true : false;
        Logs::handler(__CLASS__.'::'.__FUNCTION__." | $login | {$args['user']} | $access");
        return $access;
    }
}

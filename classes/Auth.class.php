<?php
/**
 * Class for Authorization
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Auth {
    public static function logIn($login, $inputRequestData) {
        echo <<<HTML
        <form method=post>
            User: <input type=text id=user name=user value=$inputRequestData[user]>
            Password: <input type=password id=password name=password value=$inputRequestData[password]>
            <input type=submit value=LogIn>
        </form>
        HTML;
        if (isset($inputRequestData)) {
            global $managersGC;
            $manager = array_keys($managersGC)[array_search($inputRequestData['user'], array_column($managersGC, 'user'))];
            if ($inputRequestData['user'] == $managersGC[$manager]['user'] && $inputRequestData[password] == $managersGC[$manager]['password']) {
                DB::getManagersDeals($login, $manager);
            } else {
                echo 'Неверный логин или пароль';
            }
        }
    }
}

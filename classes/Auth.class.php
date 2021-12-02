<?php
/**
 * Class for Authorization
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Auth {
    public static function logIn($login, $inputRequestData) {
        if (isset($inputRequestData)) {
            if (isset($inputRequestData['user']) && isset($inputRequestData['password'])) {
                echo <<<HTML
                <form method=post>
                    User: <input type=text id=user name=user value=$inputRequestData[user]>
                    Password: <input type=password id=password name=password value=$inputRequestData[password]>
                    <input type=submit value=LogIn>
                </form>
                HTML;
                $query = "SELECT user, password, status, salt FROM gc_managers WHERE login='$login' AND user='{$inputRequestData['user']}'";
                $manager = DB::query($query)->fetch_object();
                if (md5(md5($inputRequestData['password']).$manager->salt) == $manager->password && $manager->status == 'enable') {
                    DB::getManagersDeals($login, $manager->user);
                } else {
                    echo 'Неверный логин/пароль или пользователь отключен';
                }
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
}

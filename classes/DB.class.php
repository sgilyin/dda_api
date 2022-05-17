<?php

/*
 * Copyright (C) 2020 Sergey Ilyin <developer@ilyins.ru>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class for MySQL DB
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class DB {
    private static function argsToStrSet($args){
        return implode(', ', array_map(function ($v, $k) { return sprintf("%s='%s'", $k, $v); }, $args, array_keys($args)));
    }

    public static function query($query){
        if (DB_HOST != '' && DB_USER != '' && DB_PASSWORD != '' && DB_NAME != '') {
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if ($mysqli->connect_errno) {
                Logs::handler(__CLASS__.'::'.__FUNCTION__.' | No DB connection: '.$mysqli->connect_error);
                Logs::error(__CLASS__.'::'.__FUNCTION__.' | No DB connection: '.$mysqli->connect_error);
                exit();
            }
            $mysqli->set_charset('utf8mb4');
            $result = $mysqli->query($query);
            $errNo = $mysqli->errno;
            if (!$result) {
                Logs::error(__CLASS__.'::'.__FUNCTION__." | $query | {$mysqli->errno} | {$mysqli->error}");
            }
            Logs::handler(__CLASS__.'::'.__FUNCTION__." | $query | {$mysqli->errno} | {$mysqli->error}");
            $mysqli->close();
            switch (strtok($query," ")){
                case 'INSERT':
                case 'DELETE':
                case 'UPDATE':
                    return $errNo;
                default:
                    return $result;
            }
        }
    }

    public static function userAdd($login, $inputRequestData) {
        if ($inputRequestData['id'] && $inputRequestData['email']){
            $columns = implode("`, `",array_keys($inputRequestData));
            $values  = implode("', '", preg_replace('/^[+]?([0-9]{0,4})'
                    . '\(?([0-9]{3})\)?([ .-]?)([0-9]{3})([ .-]?)([0-9]{2,5})'
                    . '([ .-]?)([0-9]{2,5})$/', '$1$2$4$6$8', $inputRequestData));
            $query = "INSERT INTO gc_users (`login`, `$columns`) VALUES ('$login', '$values')";
            $result = static::query($query);
            Logs::handler(__CLASS__.'::'.__FUNCTION__." | '$login', '$values' | $result");
            return $result;
        }
    }

    public static function userUpdateGet($login, $args){
        if ($args['id'] && $args['email'] && $args['phone']) {
            $conditions = preg_replace('/^[+]?([0-9]{0,4})\(?([0-9]{3})\)?([ .-]?)([0-9]{3})([ .-]?)([0-9]{2,5})([ .-]?)([0-9]{2,5})$/', '$1$2$4$6$8', $args['_']);
            foreach ($conditions as $key => $val) {
                $setArr[] = "$key='$val'";
            }
            $setStr = implode(", ", $setArr);
            Logs::handler(__CLASS__.'::'.__FUNCTION__." | $login | $setStr");
            return static::query("UPDATE gc_users SET $setStr WHERE login=$login AND id='{$args['_']['id']}'");
        }
    }

    public static function userUpdate($login, $args){
        $strSet = self::argsToStrSet($args);
        Logs::handler(__CLASS__.'::'.__FUNCTION__." | $login | {$args['id']} | $strSet");
        $query = "SELECT id FROM gc_users WHERE login='$login' AND id='{$args['id']}'";
        $result = self::query($query);
        if ($result->num_rows > 0) {
            $query = "UPDATE gc_users SET $strSet WHERE id='{$args['id']}' AND login='$login'";
        } else {
            $query = "INSERT INTO gc_users SET $strSet, login='$login'";
        }
        return static::query($query);
    }

    public static function dealUpdate($login, $args) {
        if (isset($args['id']) && isset($args['number'])) {
            $query = "SELECT id FROM gc_deals WHERE login='$login' AND id='{$args['id']}'";
            $result = self::query($query);
            $strSet = self::argsToStrSet($args);
            if ($result->num_rows > 0) {
                $query = "UPDATE gc_deals SET $strSet WHERE login='$login' AND id='{$args['id']}'";
            } else {
                $query = "INSERT INTO gc_deals SET $strSet, login='$login'";
            }
            $error = self::query($query);
            if ($error == 0) {
                #GetCourse::dealsAdd($login, $args);
            }
            Logs::handler(__CLASS__.'::'.__FUNCTION__." | $login | {$args['id']}");
        }
    }

    public static function checkRoute($route) {
        $query = "SELECT name FROM route_subnets WHERE route='$route'";
        $result = self::query($query);
        $name = ($result->num_rows > 0) ? $result->fetch_object()->name : 'Unknown';
        if ($name == 'Unknown' && $route != '') {
            $query = "INSERT INTO route_subnets VALUES ('$route', '$name')";
            self::query($query);
        }
        Logs::handler(__CLASS__.'::'.__FUNCTION__." | $route | $name");
        return $name;
    }

    public static function checkIp($ip) {
        $query = "SELECT ip, route, name FROM ip_route WHERE ip='$ip'";
        $result = self::query($query);
        if ($result->num_rows > 0) {
            $obj = $result->fetch_object();
            $name = $obj->name;
            $route = $obj->route;
            if ($route == '') {
                exec('whois -H -K '.$ip.' | grep route', $whois);
                $route = preg_replace('/.*[ \t]/', '', $whois[0]);
                $query = "UPDATE ip_route SET route='$route' WHERE ip='$ip'";
                self::query($query);
            }
        } else {
            exec('whois -H -K '.$ip.' | grep route', $whois);
            $route = preg_replace('/.*[ \t]/', '', $whois[0]);
            $query = "SELECT name FROM ip_route WHERE route='$route' LIMIT 1";
            $result = self::query($query);
            $name = ($result->num_rows > 0) ? $result->fetch_object()->name : 'Unknown';
            $query = "INSERT INTO ip_route SET ip='$ip', route='$route', name='$name'";
            self::query($query);
            BX24::sendBotMessage("ip_route: $ip | $route | $name");
        }
        Logs::handler(__CLASS__.'::'.__FUNCTION__." | $ip | $route | $name");
        return $name;
    }

    public static function managerUpdate($login, $args) {
        if (isset($args['id']) && isset($args['user']) && isset($args['status'])) {
            Logs::handler(__CLASS__.'::'.__FUNCTION__." | $login | {$args['user']}");
#            $query = "SELECT user, salt FROM gc_managers WHERE login='$login' AND id='{$args['id']}' AND user='{$args['user']}'";
            $query = "SELECT user, salt FROM gc_managers WHERE login='$login' AND id='{$args['id']}'";
            $result = self::query($query);
            $manager = $result->fetch_object();
            $salt = (empty($manager->salt)) ? self::genSalt() : $manager->salt;
            $args['password'] = md5(md5($args['password']).$salt);
            $strSet = self::argsToStrSet($args);
            if ($result->num_rows > 0) {
                $query = "UPDATE gc_managers SET $strSet WHERE login='$login' AND id='{$args['id']}'";
            } else {
                $query = "INSERT INTO gc_managers SET $strSet, salt='$salt', login='$login'";
            }
            self::query($query);
        }
    }

    private static function genSalt() {
        $salt = '';
        for($i=0; $i<5; $i++) {
             $salt .= chr(rand(40,126));
        }
        return $salt;
    }

    public function showSendQueue($login, $args) {
        if (isset($args['service'])) {
            switch ($args['service']) {
                case 'chat-api':
                    $query = "SELECT COUNT(*) AS count FROM send_to_chatapi WHERE sendTime=0 AND login='$login'";
                    break;
                case 'wazzup24':
                    $query = "SELECT COUNT(*) AS count FROM send_to_wazzup24 WHERE sendTime=0 AND login='$login'";
                    break;
                case 'semysms':
                    $query = "SELECT COUNT(*) AS count FROM send_to_semysms WHERE sendTime=0 AND login='$login'";
                    break;

                default:
                    break;
            }
            if (isset($query)) {
                echo (static::query($query)->fetch_object()->count);
            }
        }
    }

    public static function getManagersDeals($login, $manager) {
        $query = "SELECT * FROM gc_deals WHERE login='$login' AND manager='$manager' AND status NOT IN ('Отменен')";
        Logs::handler(__CLASS__.'::'.__FUNCTION__." | $login | $manager");
        $deals = static::query($query);
        $rows = '';
        while ($deal = $deals->fetch_object()) {
            $rows .= "
                <tr>
                    <td><a target=_blank href='https://dubrovskaya-interior.ru/sales/control/deal/update/id/$deal->id'>$deal->id</a></td>
                    <td>$deal->number</td>
                    <td>$deal->created_at</td>
                    <td>$deal->status</td>
                    <td>$deal->positions</td>
                    <td>$deal->cost_money_value</td>
                    <td><a target=_blank href='https://dubrovskaya-interior.ru/user/control/user/update/id/$deal->client_id'>$deal->first_name</a></td>
                    <td><a href='tel:$deal->phone'>$deal->phone</a></td>
                </tr>";
        }
        echo <<<HTML
        <table>
            <tr>
                <td>ID заказа</td>
                <td>Номер заказа</td>
                <td>Дата заказа</td>
                <td>Статус заказа</td>
                <td>Состав заказа</td>
                <td>Сумма заказа</td>
                <td>Имя клиента</td>
                <td>Телефон клиента</td>
            </tr>
            $rows
        </table>
        HTML;
    }

    /**
     * Add user to MySQL database
     * 
     * @param array $inputRequestData
     * @return integer
     */
    public static function addUser($login, $inputRequestData){
        if ($inputRequestData['id'] && $inputRequestData['email']){
            $phoneNum = (empty($inputRequestData['phone'])) ? '' : substr(preg_replace('/[^0-9]/', '', $inputRequestData['phone']), -15);
            $email = $inputRequestData['email'];
            $id = $inputRequestData['id'];
            $firstName = $inputRequestData['name'] ?? '';
            Logs::handler(__CLASS__.'::'.__FUNCTION__." | $login | $id");
            $query = "SELECT id FROM gc_users WHERE login='$login' AND id='$id'";
            $result = static::query($query);
            #$strSet = static::argsToStrSet($args);
            if ($result->num_rows > 0) {
                #$query = "UPDATE gc_deals SET $strSet WHERE login='$login' AND id='{$args['id']}'";
            } else {
                $query = "INSERT INTO gc_users (`email`, `phone`, `id`, `login`, `firstName`) VALUES ('$email', '$phoneNum', '$id', '$login', '$firstName')";
            }
            return static::query($query);
        }
    }

    /**
     * Update user in MySQL database
     * 
     * @param array $inputRequestData
     * @return integer
     */
    public static function updateUser($login, $inputRequestData){
        if ($inputRequestData['id'] && $inputRequestData['email'] && $inputRequestData['phone']){
            $phoneNum = substr(preg_replace('/[^0-9]/', '', $inputRequestData['phone']), -15);
            $email = $inputRequestData['email'];
            $id = $inputRequestData['id'];
            $firstName = $inputRequestData['name'] ?? '';
            Logs::handler(__CLASS__.'::'.__FUNCTION__." | $login | $id");
            return static::query("UPDATE gc_users SET email='$email', phone='$phoneNum', firstName='$firstName' WHERE id='$id' AND login='$login'");
        }
    }

    public static function deleteUser($login, $inputRequestData){
        if ($inputRequestData['conditions']){
            if (isset($inputRequestData['conditions']['phone'])) {
                $inputRequestData['conditions']['phone'] = substr(preg_replace('/[^0-9]/', '', $inputRequestData['conditions']['phone']), -15);    
            }
            foreach ($inputRequestData['conditions'] as $key => $value) {
                $conditions[] = $key . "='" . $value . "'";
            }
            $conditionsString = implode(" AND ", $conditions);
            Logs::handler(__CLASS__.' | '.__FUNCTION__." | $login | $conditionsString");

            return static::query("DELETE FROM gc_users WHERE login='$login' $conditionsString LIMIT 1");
        }
    }

    public static function syncUsers($login){
        $mysqli = static::query("SELECT last FROM request WHERE service='getcourse' AND login='$login'");
        $result = $mysqli->fetch_object();
        $last = strtotime($result->last);
        $allCount = 0;
        if (time() - $last > 180){
            $export_ids = static::runExports();
            //static::query("TRUNCATE TABLE gc_users");
            for ($i=0; $i<count($export_ids); $i++) {
                $json = static::getExportData($export_ids[$i]);
                //var_dump($json);
                for ($j=0; $j<count($json->info->items); $j++) {
                    $allCount++;
                    $id = $json->info->items[$j][0];
                    $email = $json->info->items[$j][1];
                    $phone = substr(preg_replace('/[^0-9]/', '', $json->info->items[$j][7]), -15);
                    static::query("INSERT INTO gc_users (`id`, `email`, `phone`, `login`) VALUES ('$id', '$email', '$phone', '$login')");
                }
            }
            static::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='getcourse' AND login='$login'");
            return $allCount;
        } else {
            return false;
        }
    }

    private function runExports(){
        $export_ids[] = static::getExportId('active');
        $export_ids[] = static::getExportId('in_base');

        return $export_ids;
    }

    private function getExportId($status){
        if (GC_ACCOUNT && GC_API_KEY) {
            $post['key'] = GC_API_KEY;
            $url = "https://".GC_ACCOUNT.".getcourse.ru/pl/api/account/users?status=$status";
            do {
                $response = cURL::executeRequest($url, $post, false, false, false);
                $json = json_decode($response);
                sleep(60);
            } while (!$json->success);
            return $json->info->export_id;
        }
    }

    private function getExportData($export_id){
        if (GC_ACCOUNT && GC_API_KEY) {
            $url = "https://".GC_ACCOUNT.".getcourse.ru/pl/api/account/exports/$export_id";
            $post['key'] = GC_API_KEY;
            do {
                $response = cURL::executeRequest($url, $post, false, false, false);
                $json = json_decode($response);
                sleep(60);
            } while (!$json->success);
            return $json;
        }
    }

    /**
     * Clear queue to Wazzup24
     * 
     * @return string
     */
    public static function clearWa24Queue() {
        static::query("TRUNCATE send_to_wazzup24");
    }

    /**
     * Show send queue to Vkontakte
     * 
     * @return string
     */
    public static function showVkQueue() {
        return static::query("SELECT COUNT(*) AS count FROM vk_api WHERE success=0")->fetch_object()->count;
    }

    public static function exportDublicatePhonesToExcel($login) {
        if ($dublicates = static::query("SELECT * FROM gc_users WHERE login='$login' AND phone IN (SELECT phone FROM gc_users WHERE login='$login' AND NOT phone IS NULL AND NOT phone='' GROUP BY phone HAVING count(*) > 1);")) {
            header( "Content-Type: application/vnd.ms-excel" );
            header( "Content-disposition: attachment; filename=$login-".date('Y-m-d').".xls" );
            printf ("%s\t%s\t%s\t%s\t%s\n", 'id', 'login', 'email', 'phone', 'instagram');
            while ($dublicate = mysqli_fetch_object($dublicates)) {
                printf ("%s\t%s\t%s\t%s\t%s\n", $dublicate->id, $dublicate->login, $dublicate->email, $dublicate->phone, $dublicate->instagram);
            }
        }        
    }

    public static function showUsers($login, $conditions){
        if ($conditions){
            if ($conditions['phone']) {
                $conditions['phone'] = substr(preg_replace('/[^0-9]/', '', $conditions['phone']), -15);    
            }
            foreach ($conditions as $key => $value) {
                $conditionsForString[] = "$key='$value'";
            }
            $conditionsString = implode(" AND ", $conditionsForString);

            Logs::handler(__CLASS__.' | '.__FUNCTION__." | $login | $conditionsString");

            if ($users = static::query("SELECT * FROM gc_users WHERE login='$login' AND $conditionsString")){
                print_r('<table><tr><td>id</td><td>login</td><td>email</td><td>phone</td><td>instagram</td></tr>');
                while ($obj = $users->fetch_object()) {
                    printf ("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>", $obj->id, $obj->login, $obj->email, $obj->phone, $obj->instagram);
                }
                print_r("</table>");
            }
        }
    }

    public static function checkSentWhatsapp($phone, $message) {
        $alreadySent = false;
        if (static::query("SELECT COUNT(*) AS count FROM send_to_chatapi WHERE sendTime > CURRENT_TIMESTAMP - INTERVAL 24 HOUR AND phone='$phone' AND body='$message'")->fetch_object()->count > 0) {
            $alreadySent = 'ChatApi';
        }
        if (static::query("SELECT COUNT(*) AS count FROM send_to_semysms WHERE sendTime > CURRENT_TIMESTAMP - INTERVAL 24 HOUR AND phone='$phone' AND msg='$message'")->fetch_object()->count > 0) {
            $alreadySent = 'SemySMS';
        }
        if (static::query("SELECT COUNT(*) AS count FROM send_to_wazzup24 WHERE sendTime > CURRENT_TIMESTAMP - INTERVAL 24 HOUR AND chatId='$phone' AND text='$message'")->fetch_object()->count > 0) {
            $alreadySent = 'Wazzup24';
        }
        Logs::handler(__CLASS__.'::'.__FUNCTION__." | $phone | $message | $alreadySent");
        return $alreadySent;
    }

    public static function checkSentGetCourse($email, $text) {
        $alreadySent = false;
        if (static::query("SELECT COUNT(*) AS count FROM gc_contact_form WHERE sendTime > CURRENT_TIMESTAMP - INTERVAL 24 HOUR AND email='$email' AND text='$text'")->fetch_object()->count > 0) {
            $alreadySent = true;
            Logs::handler(__CLASS__.'::'.__FUNCTION__." | $email | $text | already sent");
        }
        return $alreadySent;
    }

    public static function syncDeals($login, $args){
        $mysqli = static::query("SELECT last FROM request WHERE service='getcourse' AND login='$login'");
        $result = $mysqli->fetch_object();
        $last = strtotime($result->last);
        $allCount = 0;
        if (time() - $last > 180){
            #$export_ids = static::runExports();
            //static::query("TRUNCATE TABLE gc_users");
            #for ($i=0; $i<count($export_ids); $i++) {
            #$json = static::getExportData($export_ids[$i]);
            $json = static::getExportData($args['export_id']);
            static::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='getcourse' AND login='$login'");
            var_dump(count($json->info->items));
            for ($j=0; $j<count($json->info->items); $j++) {
            #    $allCount++;
                $id = $json->info->items[$j][0];
                $number = $json->info->items[$j][1];
                $created_at = $json->info->items[$j][6];
                $query = "SELECT * FROM gc_deals_sync WHERE id=$id";
                $result = static::query($query);
                if ($result->num_rows > 0) {
                    $query = "UPDATE gc_deals_sync SET created_at='$created_at' WHERE id=$id";
                    static::query($query);
                    echo "<br>UPDATE: $id, $number, $created_at";
                } else {
                    if (preg_match('/Дизайнер интерьера с .*/', $json->info->items[$j][8])) {
                        $query = "INSERT INTO gc_deals_sync (`login`, `id`, `number`, `created_at`) VALUES ('$login', '$id', '$number', '$created_at')";
                        static::query($query);
                        echo "<br>INSERT: $id, $number, $created_at";
                    }
                }
                #$email = $json->info->items[$j][1];
                #$phone = substr(preg_replace('/[^0-9]/', '', $json->info->items[$j][7]), -15);
                #static::query("INSERT INTO gc_users (`id`, `email`, `phone`, `login`) VALUES ('$id', '$email', '$phone', '$login')");
            }
            #}
            #return $allCount;
        } else {
            echo 'Рано';
            #return false;
        }
    }

    public static function createSchema($login) {
        $tables[] = '
CREATE TABLE IF NOT EXISTS `gc_contact_form` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sendTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(50) COLLATE utf8_bin NOT NULL,
  `text` varchar(2048) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
';
        $tables[] = '
CREATE TABLE IF NOT EXISTS `gc_deals` (
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `id` int NOT NULL,
  `number` int NOT NULL,
  `created_at` date DEFAULT curdate(),
  `status` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `positions` varchar(2048) COLLATE utf8_bin DEFAULT NULL,
  `manager` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `cost_money_value` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `payed_money` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `payed_at` varchar(20) COLLATE utf8_bin DEFAULT NULL,
  `client_id` int DEFAULT NULL,
  `first_name` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8_bin DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY `id` (`login`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
';
        $tables[] = '
CREATE TABLE IF NOT EXISTS `gc_users` (
  `id` int NOT NULL,
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `email` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `phone` varchar(15) COLLATE utf8_bin DEFAULT NULL,
  `instagram` varchar(30) COLLATE utf8_bin DEFAULT NULL,
  `firstName` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  UNIQUE KEY `id` (`id`,`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
';
        $tables[] = "
CREATE TABLE IF NOT EXISTS `request` (
  `service` varchar(100) COLLATE utf8_bin NOT NULL,
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `last` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
";
        $tables[] = "
INSERT INTO request VALUES ('chatapi', '$login', 0), ('getcourse', '$login', 0), ('smsc', '$login', 0), ('semysms', '$login', 0), ('vkontakte', '$login', 0), ('wazzup24', '$login', 0)
";
        $tables[] = "
CREATE TABLE IF NOT EXISTS `send_to_chatapi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `queueTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `sendTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `phone` varchar(30) COLLATE utf8_bin NOT NULL,
  `body` varchar(2048) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
";
        $tables[] = "
CREATE TABLE IF NOT EXISTS `send_to_semysms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `queueTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `sendTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `phone` varchar(30) COLLATE utf8_bin NOT NULL,
  `msg` varchar(2048) COLLATE utf8_bin DEFAULT NULL,
  `device` int NOT NULL,
  `priority` int DEFAULT NULL,
  `name` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `surname` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `add_contact` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
";
        $tables[] = "
CREATE TABLE IF NOT EXISTS `send_to_wazzup24` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `queueTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `sendTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `channelId` varchar(40) COLLATE utf8_bin NOT NULL,
  `chatType` varchar(10) COLLATE utf8_bin NOT NULL,
  `chatId` varchar(30) COLLATE utf8_bin NOT NULL,
  `text` varchar(2048) COLLATE utf8_bin DEFAULT NULL,
  `content` varchar(254) COLLATE utf8_bin DEFAULT NULL,
  `refMessageId` varchar(40) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
";
        $tables[] = "
CREATE TABLE IF NOT EXISTS `smsc_messages` (
  `id` int NOT NULL,
  `phone` varchar(15) COLLATE utf8_bin NOT NULL,
  `message` varchar(2048) COLLATE utf8_bin DEFAULT NULL,
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `success` varchar(1) COLLATE utf8_bin NOT NULL DEFAULT '0',
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
";
        $tables[] = "
CREATE TABLE IF NOT EXISTS `vk_api` (
  `id` int NOT NULL AUTO_INCREMENT,
  `method` varchar(255) COLLATE utf8_bin NOT NULL,
  `params` varchar(2048) COLLATE utf8_bin DEFAULT NULL,
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `success` varchar(1) COLLATE utf8_bin NOT NULL DEFAULT '0',
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
";
        $tables[] = "
CREATE TABLE IF NOT EXISTS `yandex_audience` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(50) COLLATE utf8_bin DEFAULT NULL,
  `phone` varchar(15) COLLATE utf8_bin DEFAULT NULL,
  `login` varchar(50) COLLATE utf8_bin NOT NULL,
  `segment` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
";
        $tables[] = "
CREATE TABLE IF NOT EXISTS `route_subnets` (
  `route` varchar(30) COLLATE utf8_bin NOT NULL,
  `name` varchar(30) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`route`),
  UNIQUE KEY `route` (`route`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
";
        $tables[] = "
INSERT INTO route_subnets VALUES 
('195.191.78.0/24', 'developer'),
('185.43.4.0/23', 'DDA-API'),
('135.181.0.0/16', 'Chat-API'),
('136.243.0.0/16', 'Senler'),
('78.46.0.0/15', 'Wazzup24'),
('144.76.0.0/16', 'Wazzup24'),
('157.90.0.0/16', 'Wazzup24'),
('159.69.0.0/16', 'Wazzup24'),
('178.63.0.0/16', 'Wazzup24'),
('94.130.0.0/16', 'Wazzup24'),
('148.251.0.0/16', 'Wazzup24'),
('193.42.110.0/23', 'SemySMS'),
('91.194.226.0/24', 'Dolyame'),
('84.38.188.0/23', 'GetCourse'),
('87.251.80.0/22', 'GetCourse'),
('188.124.46.0/23', 'GetCourse'),
('188.68.216.0/23', 'GetCourse'),
('46.148.230.0/23', 'GetCourse'),
('46.148.234.0/23', 'GetCourse'),
('82.202.192.0/18', 'GetCourse'),
('185.151.240.0/23', 'GetCourse')
";
        for ($i =0; $i < count($tables); $i++) {
            static::query($tables[$i]);
        }
    }
}

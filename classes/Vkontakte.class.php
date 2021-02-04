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
 * Description of Vkontakte
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Vkontakte {

    /**
     * Execute VK request
     * 
     * @param string $method
     * @param array $inputRequestData
     * @param string $logDir
     * @return string
     */
    private static function vkExecute($method, $inputRequestData, $logDir) {
        if (VK_TOKEN) {
            $url = "https://api.vk.com/method/$method";
            $data['v'] = '5.110';
            $data['access_token'] = VK_TOKEN;
            $data['account_id'] = $inputRequestData['account_id'] ?? false;
            $data['client_id'] = $inputRequestData['client_id'] ?? false;
            $data['target_group_id'] = $inputRequestData['target_group_id'] ?? false;
            $data['contacts'] = $inputRequestData['contacts'] ?? false;
            DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='vkontakte'");
            return cURL::executeRequest($url, $data, false, false, $logDir);
        }
    }

    /**
     * Import target contacts to VK ads w/o queue
     * 
     * @param array $inputRequestData
     * @param string $logDir
     * @return string
     */
    public static function adsImportTargetContactsNow($inputRequestData, $logDir) {
        $method = 'ads.importTargetContacts';
        return static::vkExecute($method, $inputRequestData, $logDir);
    }

    /**
     * Import target contacts to VK ads w queue
     * 
     * @param array $inputRequestData
     * @param string $login
     * @return string
     */
    public static function adsImportTargetContactsQueue($inputRequestData, $login) {
        $method = 'ads.importTargetContacts';
        return static::queue($method, $inputRequestData, $login);
    }

    /**
     * Remove target contacts from VK ads w/o queue
     * 
     * @param array $inputRequestData
     * @param string $logDir
     * @return string
     */
    public static function adsRemoveTargetContactsNow($inputRequestData, $logDir) {
        $method = 'ads.removeTargetContacts';
        return static::vkExecute($method, $inputRequestData, $logDir);
    }

    /**
     * Remove target contacts from VK ads w queue
     * 
     * @param array $inputRequestData
     * @param string $login
     * @return string
     */
    public static function adsRemoveTargetContactsQueue($inputRequestData, $login) {
        $method = 'ads.removeTargetContacts';
        return static::queue($method, $inputRequestData, $login);
    }

    /**
     * Insert task to VK queue
     * 
     * @param string $method
     * @param array $inputRequestData
     * @param string $login
     * @return boolean
     */
    private static function queue($method, $inputRequestData, $login) {
        DB::query("INSERT INTO vk_api (`method`, `params`, `login`) VALUES ('$method', '" . serialize($inputRequestData) . "', '$login')");
        return true;
    }

    /**
     * Send params from queue to VK
     * 
     * @param string $logDir
     * @return boolean
     */
    public static function send($login, $logDir) {
        $mysqliObj = DB::query("SELECT id, method, params FROM vk_api WHERE login='$login' AND success=0 LIMIT 1000");
        $idArray = array();
        $vkArray = array();

        for ($i=0; $i<$mysqliObj->num_rows; $i++) {
            $rowObj = $mysqliObj->fetch_object();
            $paramsArray = unserialize($rowObj->params);
            array_push($idArray, $rowObj->id);
            $vkArray[$rowObj->method][$paramsArray['account_id']][$paramsArray['client_id']][$paramsArray['target_group_id']]['contacts'][] = $paramsArray['contacts'];
        }

        foreach ($vkArray as $method => $methodValue) {
            foreach ($methodValue as $account_id => $accountIdValue) {
                foreach ($accountIdValue as $client_id => $clientIdValue) {
                    foreach ($clientIdValue as $target_group_id => $targetGroupIdValue) {
                        $data['account_id'] = $account_id;
                        $data['client_id'] = $client_id;
                        $data['target_group_id'] = $target_group_id;
                        $data['contacts'] = implode(',', $vkArray[$method][$account_id][$client_id][$target_group_id]['contacts']);
                        sleep(rand(15,25));
                        static::vkExecute($method, $data, $logDir);
                    }
                }
            }
        }
        if ($idArray){
            DB::query('UPDATE vk_api SET success=1 WHERE id IN (' . implode(',', $idArray) . ')');
            DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='vkontakte'");
            return true;
        } else {
            return false;
        }
    }
}

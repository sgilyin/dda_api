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

ini_set('max_execution_time', '300');
// ini_set('memory_limit', '-1'); //Для загрузки всех пользователей из ГК
set_time_limit(300);

spl_autoload_register(function ($class) {
    include __DIR__."/classes/$class.class.php";
});

$inputRemoteAddr = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
$inputRequestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
$logDir = __DIR__.dirname(filter_input(INPUT_SERVER, 'PHP_SELF'));
$login = substr(dirname(filter_input(INPUT_SERVER, 'PHP_SELF')),1);

if ($login == '' && $inputRemoteAddr == '195.191.78.178') {
    echo 'ok';
    die();
}

include_once 'config.php';

switch ($inputRequestMethod){
    case 'GET':
        $inputRequestData = filter_input_array(INPUT_GET);
        switch ($inputRequestData['cmd']) {
            case 'dadataCleanName':
                Dadata::cleanName($inputRequestData, $logDir);
                break;

            case 'dadataCleanPhone':
                Dadata::cleanPhone($inputRequestData, $logDir);
                break;

            case 'wazzup24Send':
                Wazzup24::queue($login, $inputRequestData);
                break;

            case 'cron':
                SMSC::sendWaGc($login, $logDir);
                Wazzup24::send($login, $logDir);
                SMSC::syncMessages($login, $logDir);
                Vkontakte::send($login, $logDir);
                break;

            case 'dbAddUser':
                DB::addUser($login, $inputRequestData);
                break;

            case 'dbUpdateUser':
                DB::updateUser($login, $inputRequestData);
                break;

            case 'dbSyncUsers':
                var_dump(DB::syncUsers($login, $logDir));
                break;

            case 'gcAddUserRequest':
                GetCourse::addUserRequest($inputRequestData, $logDir);
                break;

            case 'twilioSend':
                var_dump(Twilio::send($inputRequestData, $logDir));
                break;

            case 'twilioCall':
                var_dump(Twilio::call($inputRequestData, $logDir));
                break;

            case 'senlerAddSubscriber':
                Senler::addSubscriber($inputRequestData, $logDir);
                break;

            case 'senlerDelSubscriber':
                Senler::delSubscriber($inputRequestData, $logDir);
                break;

            case 'senlerAddSubscription':
                Senler::addSubscription($inputRequestData, $logDir);
                break;

            case 'showWa24Queue':
                echo DB::showWa24Queue();
                break;

            case 'clearWa24Queue':
                DB::clearWa24Queue();
                echo 'Ok';
                break;

            case 'vkAdsImportTargetContactsNow':
                Vkontakte::adsImportTargetContactsNow($inputRequestData, $logDir);
                break;

            case 'vkAdsRemoveTargetContactsNow':
                Vkontakte::adsRemoveTargetContactsNow($inputRequestData, $logDir);
                break;

            case 'vkAdsImportTargetContactsQueue':
                Vkontakte::adsImportTargetContactsQueue($inputRequestData, $login);
                break;

            case 'vkAdsRemoveTargetContactsQueue':
                Vkontakte::adsRemoveTargetContactsQueue($inputRequestData, $login);
                break;

            case 'showVkQueue':
                echo DB::showVkQueue();
                break;

            case 'yaAddItemToAudience':
                Yandex::addItemToAudience($inputRequestData, $login);
                break;

            case 'yaDelItemFromAudience':
                Yandex::delItemFromAudience($inputRequestData, $logDir);
                break;

            case 'yaModifyAudience':
                Yandex::modifyAudience($inputRequestData, $logDir);
                break;

            case 'mtGetAccessToken':
                MyTarget::getAccessToken($logDir);
                break;

            case 'mtClearAccessTokens':
                MyTarget::clearAccessTokens($logDir);
                break;

            case 'mtAddItemToAudience':
                MyTarget::addItemToAudience($inputRequestData, $login);
                break;

            case 'mtDelItemFromAudience':
                MyTarget::delItemFromAudience($inputRequestData, $login);
                break;

            case 'mtModifyAudience':
                MyTarget::modifyAudience($inputRequestData, $logDir);
                break;

            case 'sbCreditRegister':
                echo Sberbank::register($inputRequestData, $logDir);
                break;

            case 'test':
                var_dump(Facebook::test($logDir));
                break;
        }
        break;
    case 'POST':
        $inputRequestData = filter_input_array(INPUT_POST);
        if (!$inputRequestData){
            $inputRequestData = json_decode(file_get_contents("php://input"), true);
        }
        Wazzup24::trap($login, $inputRequestData, $logDir);
        break;
}

Logs::clear($logDir);
Logs::add($logDir, basename(__FILE__,".php"), "$inputRemoteAddr | $inputRequestMethod | ".serialize($inputRequestData));

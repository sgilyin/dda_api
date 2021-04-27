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
                DB::addUser($login, $inputRequestData);#depricated
                break;

            case 'dbUserAdd':
                DB::userAdd($login, $inputRequestData['_']);
                break;

            case 'dbUserUpdate':
                DB::userUpdate($login, $inputRequestData['_']);
                break;

            case 'dbUpdateUser':
                DB::updateUser($login, $inputRequestData);#depricated
                break;

            case 'dbDeleteUser':
                DB::deleteUser($login, $inputRequestData);
                break;

            case 'dbSyncUsers':
                var_dump(DB::syncUsers($login, $logDir));
                break;

            case 'dbShowUsers':
                DB::showUsers($login, $inputRequestData['conditions']);
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
                var_dump(Yandex::delItemFromAudience($inputRequestData, $login));
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

            case 'mtModifyItem':
                var_dump(MyTarget::modifyItem($inputRequestData['_'], $login));
                break;

            case 'mtModifyAudience':
                var_dump(MyTarget::modifyAudience($inputRequestData['_'], $login, $logDir));
                break;

            case 'sbCreditRegister':
                echo Sberbank::register($inputRequestData, $logDir);
                break;

            case 'semySMSTrap':
                
                break;

            case 'exportDublicatePhonesToExcel':
                DB::exportDublicatePhonesToExcel($login);
                break;

            case 'test':
                $nameFromWhatsapp = 'Anton';
                var_dump(Dadata::cleanNameFromWhatsapp($nameFromWhatsapp, $logDir));
                #$params['user']['addfields']['d_utm_source']='var1';
                #$params['user']['addfields']['Возраст']='var2';
                #$result['user']['addfields']['Имя из ватсапа'] = 'var3';
                #var_dump(array_merge($params,$result));
                #var_dump(DB::query('SELECT * FROM vk_api WHERE success=0 LIMIT 1')->fetch_object());
                #var_dump(implode('/', array_filter(array(filter_input(INPUT_SERVER, 'DOCUMENT_ROOT'), substr(dirname(filter_input(INPUT_SERVER, 'PHP_SELF')),1), 'logs', '*.log'))));
                
                break;
        }
        break;
    case 'POST':
        $inputRequestData = filter_input_array(INPUT_POST);
        if (!$inputRequestData){
            $inputRequestData = json_decode(file_get_contents("php://input"), true);
        }
        switch ($inputRemoteAddr) {
            case '95.211.243.70':
                SemySMS::trap($login, $inputRequestData, $logDir);
                break;

            case '136.243.44.89':
                Senler::trap($inputRequestData, $logDir);
                break;

            default:
                Wazzup24::trap($login, $inputRequestData, $logDir);
                break;
        }
        break;
}

#Logs::clear($logDir);
#Logs::add($logDir, basename(__FILE__,".php"), "$inputRemoteAddr | $inputRequestMethod | ".serialize($inputRequestData));
Logs::access("$inputRemoteAddr | $inputRequestMethod | ".serialize($inputRequestData));

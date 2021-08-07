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
$inputRemoteHost = filter_input(INPUT_SERVER, 'REMOTE_HOST');
$inputRequestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
$login = substr(dirname(filter_input(INPUT_SERVER, 'PHP_SELF')),1);

Logs::handler("$inputRemoteAddr | $inputRequestMethod");

if ($login == '' && $inputRemoteAddr == '195.191.78.178') {
    echo 'ok';
    die();
}

include_once 'config.php';

switch ($inputRequestMethod){
    case 'GET':
        $inputRequestData = filter_input_array(INPUT_GET);
        if (isset($inputRequestData['cmd'])) {
            switch ($inputRequestData['cmd']) {
                case 'dadataCleanName':
                    Dadata::cleanName($inputRequestData);
                    break;

                case 'dadataCleanPhone':
                    Dadata::cleanPhone($inputRequestData);
                    break;

                case 'wazzup24Send':
                    Wazzup24::queue($login, $inputRequestData);
                    break;

                case 'chatApiSend':
                    ChatApi::queue($login, $inputRequestData);
                    break;

                case 'cron':
                    SMSC::sendWaGc($login);
                    Wazzup24::send($login);
                    ChatApi::send($login);
                    SMSC::syncMessages($login);
                    Vkontakte::send($login);
                    break;

                case 'dbAddUser':
                    DB::addUser($login, $inputRequestData);#depricated
                    break;

                case 'dbUserAdd':
                    DB::userAdd($login, $inputRequestData['_']);
                    break;

                case 'dbUserUpdate':
                    DB::userUpdateGet($login, $inputRequestData['_']);
                    break;

                case 'dbUpdateUser':
                    DB::updateUser($login, $inputRequestData);#depricated
                    break;

                case 'dbDeleteUser':
                    DB::deleteUser($login, $inputRequestData);
                    break;

                case 'dbSyncUsers':
                    var_dump(DB::syncUsers($login));
                    break;

                case 'dbShowUsers':
                    DB::showUsers($login, $inputRequestData['conditions']);
                    break;

                case 'gcAddUserRequest':
                    GetCourse::addUserRequest($inputRequestData);
                    break;

                case 'twilioSend':
                    var_dump(Twilio::send($inputRequestData));
                    break;

                case 'twilioCall':
                    var_dump(Twilio::call($inputRequestData));
                    break;

                case 'senlerAddSubscriber':
                    Senler::addSubscriber($inputRequestData);
                    break;

                case 'senlerDelSubscriber':
                    Senler::delSubscriber($inputRequestData);
                    break;

                case 'senlerAddSubscription':
                    Senler::addSubscription($inputRequestData);
                    break;

                case 'showWa24Queue':
                    echo DB::showWa24Queue();
                    break;

                case 'clearWa24Queue':
                    DB::clearWa24Queue();
                    echo 'Ok';
                    break;

                case 'vkAdsImportTargetContactsNow':
                    Vkontakte::adsImportTargetContactsNow($inputRequestData);
                    break;

                case 'vkAdsRemoveTargetContactsNow':
                    Vkontakte::adsRemoveTargetContactsNow($inputRequestData);
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
                    Yandex::modifyAudience($inputRequestData);
                    break;

                case 'mtGetAccessToken':
                    MyTarget::getAccessToken();
                    break;

                case 'mtClearAccessTokens':
                    MyTarget::clearAccessTokens();
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
                    var_dump(MyTarget::modifyAudience($inputRequestData['_'], $login));
                    break;

                case 'sbCreditRegister':
                    echo Sberbank::register($inputRequestData);
                    break;

                case 'semySMSTrap':

                    break;

                case 'exportDublicatePhonesToExcel':
                    DB::exportDublicatePhonesToExcel($login);
                    break;

                case 'test':
                    
                    break;

                default:
                    echo 'Silent is golden';
                    break;
            }
        }
        break;
    case 'POST':
        $inputRequestData = filter_input_array(INPUT_POST);
        if (!$inputRequestData){
            $inputRequestData = json_decode(file_get_contents("php://input"), true);
        }
        switch ($inputRemoteAddr) {
            case '95.211.243.70':
            case '193.42.110.5':
                SemySMS::trap($login, $inputRequestData);
                break;

            case '136.243.44.89':
                Senler::trap($inputRequestData);
                break;

#            case '159.69.73.62':
#            case '35.228.37.107':
#                ChatApi::trap($login, $inputRequestData);
#                break;

#            case '148.251.13.26':
#            case '144.76.56.26':
#                Wazzup24::trap($login, $inputRequestData);
#                break;

            default:
#                ChatApi::trap($login, $inputRequestData);
                Wazzup24::trap($login, $inputRequestData);
                break;
        }
        break;
}

switch ($inputRemoteAddr) {
    case '185.151.241.45':
    case '87.251.80.4':
    case '217.66.154.84':
    case '149.154.161.20':
#    case '195.191.78.178':
        if (isset($inputRequestData['class']['method'])) {
            $inputRequestData['class']['method']($login, $inputRequestData['args']);
        }
        break;

    default:
        if (isset($inputRequestData['class']['method'])) {
            if (isset($inputRequestData['args'])) {
                $inputRequestData['class']['method']($login, $inputRequestData['args']);
            } else {
                $inputRequestData['class']['method']($login);
            }
        } else {
            Auth::logIn($login, $inputRequestData);
        }
#        echo 'Silent is golden';
        break;
}
Logs::access("$inputRemoteAddr | $inputRemoteHost | $inputRequestMethod | ".serialize($inputRequestData));

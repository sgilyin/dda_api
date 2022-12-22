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
#ini_set('memory_limit', '-1');#Для загрузки всего импорта из ГК
set_time_limit(300);

spl_autoload_register(function ($class) {
    include __DIR__."/classes/$class.class.php";
});

$inputRemoteAddr = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
$inputRequestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
$login = substr(dirname(filter_input(INPUT_SERVER, 'PHP_SELF')),1);
$inputRequestData = filter_input_array(INPUT_POST) ?? 
    json_decode(file_get_contents("php://input"), true) ??
    filter_input_array(INPUT_GET);

if ($login == '') {
    if ($inputRemoteAddr == '195.191.78.178') {
        echo 'ok';
    } else {
        echo 'Silent is golden';
    }
    die();
}

include_once 'config.php';

switch ($inputRequestMethod){
    case 'GET':
        if (isset($inputRequestData['cmd'])) {
            #BX24::sendBotMessage('Used depricated method - cmd='.$inputRequestData['cmd']);
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
                    echo 'Depricated. Use:<br>
                        https://api.dmitry-dubrovsky.ru/<LOGIN>/?class[method]=DB::showQueue&args[*]=<VALUE>&args[*]=<VALUE>
                        <br><br>class[method]=DB::showQueue<br>
                        *<br>
                        service (required) - сервис отправки: chat-api | wazzup24 | semysms';
                    break;
                case 'clearWa24Queue':
                    echo 'Depricated. Use:<br>
                        https://api.dmitry-dubrovsky.ru/<LOGIN>/?class[method]=Wazzup24::clearWa24Queue';
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
                case 'exportDublicatePhonesToExcel':
                    DB::exportDublicatePhonesToExcel($login);
                    break;

                default:
                    break;
            }
        }
        break;
}
$serviceByIP = DB::checkIp($inputRemoteAddr);
#Logs::debug("$serviceByIP found by IP: $inputRemoteAddr");
Logs::access("$inputRemoteAddr ($serviceByIP) | $inputRequestMethod | ".serialize($inputRequestData));
Logs::handler("$inputRemoteAddr ($serviceByIP) | $inputRequestMethod | ".serialize($inputRequestData));

switch ($serviceByIP) {
    case 'developer':
    case 'DDA-API':
    case 'GetCourse':
    case 'SMSC':
    case 'Senler':
    case 'manager':
        if (isset($inputRequestData['class']['method'])) {
            if (isset($inputRequestData['args'])) {
                $inputRequestData['class']['method']($login, $inputRequestData['args']);
            } else {
                $inputRequestData['class']['method']($login);
            }
        } else {
            Auth::logIn($login, $inputRequestData);
        }
        break;
    case 'Chat-API':
        ChatApi::trap($login, $inputRequestData);
        break;
    case 'Dolyame':
        Dolyame::trap($login, $inputRequestData);
        break;
    case 'SemySMS':
        SemySMS::trap($login, $inputRequestData);
        break;
    case 'Wazzup24':
        Wazzup24::trap($login, $inputRequestData);
        break;
    case 'Skorozvon':
        Skorozvon::trap($login, $inputRequestData);
        break;
    case 'Yandex':
        Yandex::trap($login, $inputRequestData);
        break;
    case 'Zvonobot':
        Zvonobot::trap($login, $inputRequestData);
        break;

    default:
        BX24::sendBotMessage("$serviceByIP IP: $inputRemoteAddr");
        if (isset($inputRequestData['class']['method'])) {
            if (isset($inputRequestData['args'])) {
                $inputRequestData['class']['method']($login, $inputRequestData['args']);
            } else {
                $inputRequestData['class']['method']($login);
            }
        } else {
            Auth::logIn($login, $inputRequestData);
            if (Auth::checkAccess($login, $inputRequestData)) {
                DB::query("UPDATE ip_route SET name='manager' WHERE ip='$inputRemoteAddr'");
                BX24::sendBotMessage("ip_route: $inputRemoteAddr | manager");
            }
        }
        break;
}

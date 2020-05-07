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

include_once 'config.php';

ini_set('max_execution_time', '300');
set_time_limit(300);

spl_autoload_register(function ($class) {
    include __DIR__."/classes/$class.class.php";
});

$inputRemoteAddr = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
$inputRequestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
$logDir = __DIR__.dirname(filter_input(INPUT_SERVER, 'PHP_SELF'));
$login = substr(dirname(filter_input(INPUT_SERVER, 'PHP_SELF')),1);

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
                SMSC::sendWaGc($logDir);
                Wazzup24::send($logDir);
                SMSC::syncMessages($login, $logDir);
                break;

            case 'dbAddUser':
                DB::addUser($inputRequestData);
                break;

            case 'dbUpdateUser':
                DB::updateUser($inputRequestData);
                break;

            case 'dbSyncUsers':
                var_dump(DB::syncUsers($logDir));
                break;

            case 'gcAddUserRequest':
                GetCourse::addUserRequest($inputRequestData, $logDir);
                break;

            case 'twilioSend':
                Twilio::send($inputRequestData, $logDir);
                break;

            case 'test':
                break;
        }
        break;
    case 'POST':
        $inputRequestData = filter_input_array(INPUT_POST);
        if (!$inputRequestData){
            $inputRequestData = json_decode(file_get_contents("php://input"), true);
        }
        Wazzup24::trap($inputRequestData, $logDir);
        break;
}

Logs::clear($logDir);
Logs::add($logDir, basename(__FILE__,".php"), "$inputRemoteAddr | $inputRequestMethod | ".serialize($inputRequestData));

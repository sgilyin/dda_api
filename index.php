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
        break;
    case 'POST':
        $inputRequestData = filter_input_array(INPUT_POST);
        break;
}

Logs::clear($logDir);
Logs::add($logDir, basename(__FILE__,".php"), "$inputRemoteAddr | $inputRequestMethod | ".print_r($inputRequestData,TRUE));

switch ($inputRequestData['cmd']) {
    case 'dadataCleanName':
        if ($inputRequestData['email'] && $inputRequestData['data']){
            Dadata::cleanName($inputRequestData, $logDir);
        }
        break;

    case 'dadataCleanPhone':
        if ($inputRequestData['email'] && $inputRequestData['data']){
            Dadata::cleanPhone($inputRequestData, $logDir);
        }
        break;

    case 'sendWazzup24':
        if ($inputRequestData['to'] && ($inputRequestData['text'] || $inputRequestData['content'])){
            Wazzup24::queue($login, $inputRequestData);
        }
        break;

    case 'cron':
        SMSC::sendWaGc($logDir);
        Wazzup24::send($logDir);
        SMSC::syncMessages($login, $logDir);
        break;

    case 'test':
        var_dump(Wazzup24::send($logDir));
        break;

    default:
        break;
}


//var_dump(DB::syncGetcourseUsers($logDir));
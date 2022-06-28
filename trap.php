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

function logClear($logDir) {
    foreach (glob("$logDir/*.log") as $file) {
        if(time() - filectime($file) > 604800){
            unlink($file);
        }
    }
}

function logAdd($logDir, $file, $text){
    file_put_contents("$logDir/{$file}_".date('Ymd').'.log',PHP_EOL.date('Y-m-d H:i:s')." | $text", FILE_APPEND);
}

$inputRemoteAddr = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
$inputRequestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
$logDir = __DIR__.dirname(filter_input(INPUT_SERVER, 'PHP_SELF'));
$login = substr(dirname(filter_input(INPUT_SERVER, 'PHP_SELF')),1);

$inputRequestData = filter_input_array(INPUT_POST) ?? 
    json_decode(file_get_contents("php://input"), true) ??
    filter_input_array(INPUT_GET);

logClear($logDir);
logAdd($logDir, basename(__FILE__,".php"), "$inputRemoteAddr | $inputRequestMethod | ".serialize($inputRequestData));
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
 * Class for working with Logs
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Logs {

    /**
     * Clear logs
     * 
     * @param string $logDir
     */
    public static function clear($logDir) {

        foreach (glob("$logDir/log/*.log") as $file) {
            if(time() - filectime($file) > 604800){
                unlink($file);
            }
        }

        foreach (glob(implode('/', array_filter(array(filter_input(INPUT_SERVER, 'DOCUMENT_ROOT'), substr(dirname(filter_input(INPUT_SERVER, 'PHP_SELF')),1), 'logs', '*.log')))) as $file) {
            if(time() - filectime($file) > 604800){
                unlink($file);
            }
        }
    }

    /**
     * Add position into log
     * 
     * @param string $logDir
     * @param string $file
     * @param string $text
     */
    public static function add($logDir,$file,$text){

        file_put_contents("$logDir/log/{$file}_".date('Ymd').'.log',PHP_EOL.date('Y-m-d H:i:s')." | $text", FILE_APPEND);
    }

    public static function error($logMessage) {
        static::test(__FUNCTION__, $logMessage);
    }

    public static function access($logMessage) {
        static::test(__FUNCTION__, $logMessage);
    }

    public static function handler($logMessage) {
        static::test(__FUNCTION__, $logMessage);
    }

    private function test($logType, $logMessage) {
        error_log(PHP_EOL.PHP_EOL.date('Y-m-d H:i:s')." | $logMessage", 3, implode('/', array_filter(array(filter_input(INPUT_SERVER, 'DOCUMENT_ROOT'), substr(dirname(filter_input(INPUT_SERVER, 'PHP_SELF')),1), 'logs', date('Ymd').".$logType.log"))));
    }
}

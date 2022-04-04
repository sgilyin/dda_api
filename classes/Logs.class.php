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
    public static function error($logMessage) {
        static::execute(__FUNCTION__, $logMessage);
    }

    public static function access($logMessage) {
        static::execute(__FUNCTION__, $logMessage);
    }

    public static function handler($logMessage) {
        static::execute(__FUNCTION__, $logMessage);
    }

    public static function debug($logMessage) {
        static::execute(__FUNCTION__, $logMessage);
    }

    private function execute($logType, $logMessage) {
        error_log(PHP_EOL.PHP_EOL.date('Y-m-d H:i:s')." | $logMessage", 3, implode('/', array_filter(array(filter_input(INPUT_SERVER, 'DOCUMENT_ROOT'), substr(dirname(filter_input(INPUT_SERVER, 'PHP_SELF')),1), 'logs', date('Ymd').".$logType.log"))));
    }
}

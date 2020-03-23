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
    public static function clear($folder) {
        /**
         * @static
         * @param string $folder Request dir
         * @return nothing
         * @author Sergey Ilyin <developer@ilyins.ru>
         */
        foreach (glob($folder."/log/*.log") as $file) {
            if(time() - filectime($file) > 604800){
                unlink($file);
            }
        }
    }

    public static function add($folder,$file,$text){
        /**
         * @static
         * @param string $folder Request dir
         * @param string $file Filename
         * @param string $text Text for log
         * @return nothing
         * @author Sergey Ilyin <developer@ilyins.ru>
         */
        file_put_contents("{$folder}/log/{$file}_".date('Ymd').".log",PHP_EOL.date('d-m-Y G:i').' | '.$text, FILE_APPEND);
    }
}

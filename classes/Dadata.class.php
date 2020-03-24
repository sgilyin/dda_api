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
 * Class for Dadata
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Dadata {

    /**
     * Standardizes data in Dadata
     * 
     * @param string $type
     * @param string $value
     * @param string $logDir
     * @return object
     */
    public static function clean($type, $value, $logDir) {
        $url = "https://cleaner.dadata.ru/api/v1/clean/$type";
        $headers = array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Token " . DADATA_API_KEY,
            "X-Secret: " . DADATA_SECRET_KEY,
        );
        $post = json_encode(array($value));
        return cURL::executeRequest($url, $post, $headers, $logDir);
    }
}

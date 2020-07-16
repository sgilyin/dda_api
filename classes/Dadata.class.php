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
     * Standardizes Name in Dadata
     * @param array $inputRequestData
     * @param string $logDir
     * @return string
     */
    public static function cleanName($inputRequestData, $logDir) {
        if ($inputRequestData['email'] && $inputRequestData['data']){
            $dadataName = json_decode(static::clean('name', $inputRequestData['data'], $logDir));
            $params['user']['email'] = $inputRequestData['email'];
            if ($dadataName[0]->name){
                $params['user']['addfields']['first_name'] = $dadataName[0]->name;
                $params['user']['addfields']['Имя DADATA'] = $dadataName[0]->name;
            }
            if ($dadataName[0]->surname){
                $params['user']['addfields']['last_name'] = $dadataName[0]->surname;
                $params['user']['addfields']['Фамилия DADATA'] = $dadataName[0]->surname;
            }
            if ($dadataName[0]->patronymic){
                $params['user']['addfields']['Ваше Отчество'] = $dadataName[0]->patronymic;
            }
            if ($dadataName[0]->gender){
                $params['user']['addfields']['Пол DADATA'] = $dadataName[0]->gender;
            }
            if ($dadataName[0]->qc){
                $params['user']['addfields']['QC ФИО DADATA'] = $dadataName[0]->qc;
            } else {
                $params['user']['addfields']['QC ФИО DADATA'] = 0;
            }
            return GetCourse::addUser($params, $logDir);
        }
    }

    /**
     * Standardizes Phone in Dadata
     * 
     * @param array $inputRequestData
     * @param string $logDir
     * @return string
     */
    public static function cleanPhone($inputRequestData, $logDir) {
        if ($inputRequestData['email'] && $inputRequestData['data']){
            $dadataPhone = json_decode(static::clean('phone', $inputRequestData['data'], $logDir));
            $params['user']['email'] = $inputRequestData['email'];
            if ($dadataPhone[0]->phone){
                $params['user']['phone'] = $dadataPhone[0]->phone;
            }
            if ($dadataPhone[0]->country){
                $params['user']['addfields']['Страна мобильного по DADATA'] = $dadataPhone[0]->country;
            }
            if ($dadataPhone[0]->region){
                $params['user']['addfields']['Регион мобильного по DADATA'] = $dadataPhone[0]->region;
            }
            if ($dadataPhone[0]->provider){
                $params['user']['addfields']['Моб оператор DADATA'] = $dadataPhone[0]->provider;
            }
            if ($dadataPhone[0]->timezone){
                preg_match_all('/UTC[-+]\d+/', $dadataPhone[0]->timezone, $matches);
                $negative = false;
                for ($i=0; $i<count($matches[0]); $i++){
                    $arr[$i] = substr($matches[0][$i], 3);
                    if (intval($arr[$i]) <= 0) {$negative = true;}
                }
                $timezone = ($negative) ? min($arr) : max($arr);
                $timezone = $timezone ?? '0';
                $params['user']['addfields']['UTC+'] = $timezone;
            }
            return GetCourse::addUser($params, $logDir);
        }
    }

    /**
     * Standardizes data in Dadata
     * 
     * @param string $type
     * @param string $value
     * @param string $logDir
     * @return string
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
        return cURL::executeRequest($url, $post, $headers, false, $logDir);
    }
}

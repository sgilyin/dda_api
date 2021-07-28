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
    public static function cleanNameFromWhatsapp($nameFromWhatsapp) {
        if (DADATA_ENABLED && DADATA_API_KEY != '' && DADATA_SECRET_KEY != '') {
            $dadataName = json_decode(static::clean('name', $nameFromWhatsapp));
            $result['user']['addfields']['QC имя из ватсапа'] = $dadataName[0]->qc;
            switch ($dadataName[0]->qc) {
                case 0:
                    $result['user']['first_name'] = ($dadataName[0]->patronymic) ? $dadataName[0]->name.' '.$dadataName[0]->patronymic : $dadataName[0]->name;
                    if ($dadataName[0]->surname) {
                        $result['user']['last_name'] = $dadataName[0]->surname;
                    }
                    $result['user']['addfields']['Имя из ватсапа'] = $dadataName[0]->source;
                    break;
                case 1:
                    $result['user']['addfields']['Имя из ватсапа'] = $dadataName[0]->source;
                    break;

                default:
                    break;
            }
            return $result;
        }
        
    }

    public static function cleanName($inputRequestData) {
        if ($inputRequestData['email'] && $inputRequestData['data']){
            $dadataName = json_decode(static::clean('name', $inputRequestData['data']));
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
                $params['user']['addfields']['Пол_DADATA'] = $dadataName[0]->gender;
            }
            if ($dadataName[0]->qc){
                $params['user']['addfields']['QC ФИО DADATA'] = $dadataName[0]->qc;
            } else {
                $params['user']['addfields']['QC ФИО DADATA'] = 0;
            }
            return GetCourse::addUser($params);
        }
    }

    public static function cleanPhone($inputRequestData) {
        if ($inputRequestData['email'] && $inputRequestData['data']){
            $dadataPhone = json_decode(static::clean('phone', $inputRequestData['data']));
            $params['user']['email'] = $inputRequestData['email'];
            if ($dadataPhone[0]->phone){
                $params['user']['phone'] = $dadataPhone[0]->phone;
            }
            if ($dadataPhone[0]->region){
                $params['user']['addfields']['Регион_мобильного_по_DADATA'] = $dadataPhone[0]->region;
            }
            if ($dadataPhone[0]->provider){
                $params['user']['addfields']['Моб_оператор_DADATA'] = $dadataPhone[0]->provider;
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
            $params['user']['addfields']['Страна_мобильного_по_DADATA'] = $dadataPhone[0]->country ?? 'null';
            return GetCourse::addUser($params);
        }
    }

    public static function clean($type, $value) {
        if (DADATA_ENABLED && DADATA_API_KEY != '' && DADATA_SECRET_KEY != '') {
            Logs::handler(__CLASS__."::".__FUNCTION__." | $type | $value");
            $url = "https://cleaner.dadata.ru/api/v1/clean/$type";
            $headers = array(
                "Content-Type: application/json",
                "Accept: application/json",
                "Authorization: Token " . DADATA_API_KEY,
                "X-Secret: " . DADATA_SECRET_KEY,
            );
            $post = json_encode(array($value));
            return cURL::executeRequestTest('POST', $url, $post, $headers, false);
        }
    }
}

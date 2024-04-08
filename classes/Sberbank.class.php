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
 * Description of Sberbank
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Sberbank {
    public static function register($inputRequestData) {
        if ($inputRequestData['email'] && $inputRequestData['orderNumber'] && $inputRequestData['itemPrice']) {
            $itemPrice = intval($inputRequestData['itemPrice'])*100;
            $url = 'https://securepayments.sberbank.ru/sbercredit/'.__FUNCTION__.'.do';
            $post['userName'] = SBRF_CREDIT_USER;
            $post['password'] = SBRF_CREDIT_PASSWORD;
            $post['orderNumber'] = $inputRequestData['orderNumber'];
            $post['amount'] = $itemPrice;
            $post['currency'] = 643;
            $post['returnUrl'] = SBRF_CREDIT_RETURNURL;
            $jsonParams = new stdClass();
            $jsonParams->email = $inputRequestData['email'];
            $post['jsonParams'] = json_encode($jsonParams);
            $installments->productType = $inputRequestData['productType'] ?? 'INSTALLMENT';
            $installments->productID = 10;
            $quantity->value = 1;
            $quantity->measure = 'pc';
            $arr = array(
                array(
                    "positionId" => 1,
                    "name" => 'Order_'.$inputRequestData['orderNumber'],
                    "quantity" => $quantity,
                    "itemAmount" => $itemPrice,
                    "itemCode" => $inputRequestData['orderNumber'],
                    "itemPrice" => $itemPrice
                )
            );
            $orderBundle->cartItems->items = $arr;
            $orderBundle->installments = $installments;
            $post['orderBundle'] = json_encode($orderBundle);
            $result = json_decode(cURL::executeRequest($url, $post, false, false, false));

            if ($result->formUrl) {
                $params['user']['email'] = $inputRequestData['email'];
                $params['user']['addfields']['Sber'] = $result->formUrl;
                return GetCourse::addUser($params);
            } else {var_dump($result);}
        }
    }
}

<?php

/*
 * Copyright (C) 2022 Sergey Ilyin <developer@ilyins.ru>
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
 * Description of Dolyame
 *
 * @author sgilyin <developer@ilyins.ru>
 */
class Dolyame {
    private function generateCorrelationId()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function create($login, $args) {
        if (DOLYAME_ENABLED){
            Logs::handler(__CLASS__."::".__FUNCTION__." | $login");
            $url = 'https://partner.dolyame.ru/v1/orders/' . __FUNCTION__;
            $headers = array(
                'Content-Type: application/json',
                'X-Correlation-ID: ' . self::generateCorrelationId(),
            );
            $userpwd = DOLYAME_USER . ':' . DOLYAME_PASSWORD;
            $order->id = $args['orderId'];
            $order->amount = floatval($args['itemPrice']);
            $order->prepaid_amount = 0.00;
            $order->items = array(array(
                'name' => $args['orderId'],
                'quantity' => 1,
                'price' => floatval($args['itemPrice']),
            ));
            $data = array(
                'order' => $order,
                'fail_url' => DOLYAME_FAIL_URL,
                'success_url' => DOLYAME_SUCCESS_URL,
            );
            $ssl->certPath = DOLYAME_CERT;
            $ssl->keyPath = DOLYAME_PRIVATE;
            echo (json_decode(cURL::executeRequest($url, json_encode($data), $headers, $userpwd, $ssl))->link);
        } else {
            echo 'Service is not configured. Check config.';
        }
    }
}

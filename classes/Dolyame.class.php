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
    private function generateCorrelationId() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function generateHeaders() {
        $headers = array(
            'Content-Type: application/json',
            'X-Correlation-ID: ' . self::generateCorrelationId(),
            );
        return $headers;
    }

    private function generateSSL() {
        $ssl->certPath = DOLYAME_CERT;
        $ssl->keyPath = DOLYAME_PRIVATE;
        return $ssl;
    }

    private static function execute($action, $post, $orderId = false) {
        Logs::handler(__CLASS__."::".__FUNCTION__);
        switch ($action) {
            case 'create':
                $url = "https://partner.dolyame.ru/v1/orders/$action";
                break;
            case 'commit':
            case 'cancel':
            case 'refund':
                $url = "https://partner.dolyame.ru/v1/orders/$orderId/$action";
                break;

            default:
                break;
        }
        return cURL::executeRequest($url, json_encode($post), self::generateHeaders(), DOLYAME_USER.':'.DOLYAME_PASSWORD, self::generateSSL());
    }

    public static function trap($login, $args) {
        if (DOLYAME_ENABLED){
            Logs::handler(__CLASS__."::".__FUNCTION__." | $login");
            switch ($args['status']) {
                case 'wait_for_commit':
                    $items = array(
                        array(
                            'name' => $args['id'],
                            'quantity' => 1,
                            'price' => $args['amount'],
                            )
                        );
                    $post = array(
                        'orderId' => $args['id'],
                        'amount' => $args['amount'],
                        'prepaid_amount' => 0.00,
                        'items' => $items,
                        );
                    self::execute('commit', $post, $args['id']);
                    break;

                default:
                    break;
            }
        } else {
            echo 'Service is not configured. Check config.';
        }
    }

    public function create($login, $args) {
        if (DOLYAME_ENABLED){
            Logs::handler(__CLASS__."::".__FUNCTION__." | $login");
            $order->id = $args['orderId'];
            $order->amount = floatval($args['itemPrice']);
            $order->prepaid_amount = 0.00;
            $order->items = array(
                array(
                    'name' => $args['orderId'],
                    'quantity' => 1,
                    'price' => floatval($args['itemPrice']),
                    )
                );
            $post = array(
                'order' => $order,
                'notification_url' => DOLYAME_NOTIFICATION_URL,
                'fail_url' => DOLYAME_FAIL_URL,
                'success_url' => DOLYAME_SUCCESS_URL,
                );
            $link = json_decode(self::execute(__FUNCTION__, $post, $args['orderId']))->link;
            if (isset($args['email'])) {
                $params['user']['email'] = $args['email'];
                $params['user']['addfields']['Dolyami'] = $link;
                return GetCourse::addUser($params);
            } else {
                echo $link;
            }
        } else {
            echo 'Service is not configured. Check config.';
        }
    }

    public function cancel($login, $args) {
        if (DOLYAME_ENABLED){
            Logs::handler(__CLASS__."::".__FUNCTION__." | $login");
            $post = array(
                'orderId' => $orderId,
                );
            echo self::execute(__FUNCTION__, $post, $args['orderId']);
        } else {
            echo 'Service is not configured. Check config.';
        }
    }

    public function refund($login, $args) {
        if (DOLYAME_ENABLED){
            Logs::handler(__CLASS__."::".__FUNCTION__." | $login");
            $returned_items = array(
                array(
                    'name' => $args['orderId'],
                    'quantity' => 1,
                    'price' => floatval($args['amount']),
                    )
                );
            $post = array(
                'orderId' => $args['orderId'],
                'amount' => $args['amount'],
                'returned_items' => $returned_items,
                );
            echo self::execute(__FUNCTION__, $post, $args['orderId']);
        } else {
            echo 'Service is not configured. Check config.';
        }
    }
}

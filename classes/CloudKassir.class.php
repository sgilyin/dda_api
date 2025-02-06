<?php

/**
 * Class for CloudKassir
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class CloudKassir {
    private function execute($login, $args) {
        if (CLOUDKASSIR_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            $url = CLOUDKASSIR_URL . $args['action'];
            $headers[] = 'Content-Type: application/json';
//            $headers[] = 'Content-Length: 0';
            $userpwd = sprintf('%s:%s', CLOUDKASSIR_PUBLIC_ID, CLOUDKASSIR_API_SECRET);
            $post = $args['json'];
            return cURL::executeRequest($url, $post, $headers, $userpwd, false);
        }
    }

    public function test($login, $args) {
        if (CLOUDKASSIR_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            $cr['action'] = '/test';
            echo self::execute($login, $cr);
        }
    }

    public function receipt($login, $args) {
        if (CLOUDKASSIR_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            $cr['action'] = '/kkt/receipt';
            $json = array(
                'Inn' => CLOUDKASSIR_INN,
                'Type' => $args['Type'],
                'CustomerReceipt' => array(
                    'Items' => array(array(
                        'Label' => $args['Label'],
                        'Price' => $args['Amount'],
                        'Quantity' => 1,
                        'Vat' => 0,
                        'Amount' => $args['Amount'])),
                    'TaxationSystem' => $args['TaxationSystem'],
                    'Amounts' => array(
                        'AdvancePayment' => $args['Amount'],
                    ),
                    'PaymentPlace' => CLOUDKASSIR_PAYMENTPLACE,
                ),
            );
            $cr['json'] = json_encode($json);
            var_dump($cr);
            echo self::execute($login, $cr);
        }
    }

    public function correctionReceipt($login, $args) {
        if (CLOUDKASSIR_ENABLED) {
            Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
                $login, serialize($args)));
            $cr['action'] = '/kkt/correction-receipt';
            $json['CorrectionReceiptData'] = array(
                'OrganizationInn' => CLOUDKASSIR_INN,
                'VatRate' => 6,
                'TaxationSystem' => $args['TaxationSystem'],
                'DeviceNumber' => CLOUDKASSIR_DEVICENUMBER,
                'CorrectionReceiptType' => 1,
                'CauseCorrection' => array(
                    'CorrectionDate' => $args['CorrectionDate'],
                    'CorrectionNumber' => 'б//н'),
                'Amounts' => array(
                    'AdvancePayment' => $args['Amount']),
                'Items' => array(array(
                    'Label' => 'Обучающий курс ' . $args['Label'],
                    'Price' => $args['Amount'],
                    'Quantity' => 1,
                    'Vat' => 0,
                    'Amount' => $args['Amount'])),
                'PaymentPlace' => CLOUDKASSIR_PAYMENTPLACE,
                'CorrectionType' => 0);
            $cr['json'] = json_encode($json);
//            var_dump(json_encode($json));
            echo self::execute($login, $cr);
        }
    }
}

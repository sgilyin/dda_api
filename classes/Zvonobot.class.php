<?php

/**
 * Description of Zvonobot
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */

class Zvonobot {
    public static function trap($login, $args) {
        Logs::handler(sprintf('%s::%s | %s | %s', __CLASS__, __FUNCTION__,
            $login, serialize($args)));
        $phone = substr(preg_replace('/[^0-9]/', '', $args['call']['phone']), -15);
        try {
            $user = DB::query("SELECT email, instagram, firstName FROM gc_users WHERE login='$login' AND phone REGEXP '$phone'")->fetch_object();
            $email = $user->email ?? "$phone@facebook.com";
        } catch (Exception $exc) {
            Logs::error(sprintf('%s::%s | var user | %s', __CLASS__, __FUNCTION__, $exc));
        }
        $param['user']['phone'] = $phone;
        $param['user']['email'] = $email;
        $param['deal']['product_title'] = 'Консультация';
        $param['deal']['deal_status'] = 'Новый';
        GetCourse::dealsAdd($login, $param);
        $msg = sprintf('%s %s', $args['msg'], $args['call']['answer']);
        $to = $args['to'];
        switch ($args['integration']) {
            case 'SemySMS':
                $whatsapp['phone'] = $to;
                $whatsapp['msg'] = $msg;
                break;
            case 'Wazzup24':
                $whatsapp['chatId'] = $to;
                $whatsapp['text'] = $msg;

            default:
                break;
        }
        $args['integration']::queue($login, $whatsapp);
    }
}

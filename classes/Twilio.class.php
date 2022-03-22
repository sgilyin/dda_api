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
 * Class for Twilio
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Twilio {

    /**
     * 
     * @param array $inputRequestData
     * @return boolean
     */
    public static function send($inputRequestData) {
        if (TWILIO_SID && TWILIO_TOKEN) {
            $url="https://api.twilio.com/2010-04-01/Accounts/".TWILIO_SID."/Messages.json";
            $userpwd=TWILIO_SID.':'.TWILIO_TOKEN;
            switch ($inputRequestData['Transport']) {
                case 'SMS':
                    $post['messagingServiceSid'] = TWILIO_SMS_NUMBER;
                    $post['From'] = TWILIO_SMS_NUMBER;
                    $post['To'] = $inputRequestData['To'];
                    break;

                case 'Whatsapp':
                    $post['From'] = 'whatsapp:'.TWILIO_WA_NUMBER;
                    $post['To'] = 'whatsapp:'.$inputRequestData['To'];
                    break;
            }
            $post['Body'] = $inputRequestData['Body'];
            if ($inputRequestData['MediaUrl']){
                $post['MediaUrl'] = $inputRequestData['MediaUrl'];
            }
            return cURL::executeRequest($url, $post, false, $userpwd, false);
        }
    }

    public static function call($inputRequestData) {
        if (TWILIO_SID && TWILIO_TOKEN && TWILIO_CALL_NUMBER) {
            $url="https://api.twilio.com/2010-04-01/Accounts/".TWILIO_SID."/Calls.json";
            $userpwd=TWILIO_SID.':'.TWILIO_TOKEN;
            $post['From'] = TWILIO_CALL_NUMBER;
            $post['To'] = $inputRequestData['To'];
            $post['Twiml'] = '<Response><Play>' . $inputRequestData['File'] .'</Play></Response>';
            return cURL::executeRequest($url, $post, false, $userpwd, false);
        }
    }
}

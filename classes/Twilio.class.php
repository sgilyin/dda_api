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
     * @param string $logDir
     * @return boolean
     */
    public static function send($inputRequestData, $logDir) {
        $url="https://api.twilio.com/2010-04-01/Accounts/".TWILIO_SID."/Messages.json";
        $userpwd=TWILIO_SID.':'.TWILIO_TOKEN;
        $post['From'] = 'whatsapp:'.TWILIO_WA_NUMBER;
        $post['To'] = 'whatsapp:'.$inputRequestData['To'];
        $post['Body'] = $inputRequestData['Body'];
        if ($inputRequestData['MediaUrl']){
            $post['MediaUrl'] = $inputRequestData['MediaUrl'];
        }
        $result = cURL::executeRequest($url, $post, false, $userpwd, $logDir);
        return true;
    }
}

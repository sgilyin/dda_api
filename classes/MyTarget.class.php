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
 * Class for MyTarget
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class MyTarget {
    public static function test($logDir) {
        $url = 'https://target-sandbox.my.com/api/v2/oauth2/token.json';
//        $url = 'https://target-sandbox.my.com/api/v2/oauth2/token.json?grant_type=client_credentials&client_id=xkHURu59gG9mXWRw&client_secret=50JgIZ9FmBsvNKj92Dsfx9XaHva46XIms8oZ98O5gpjUerCBv7ZJIXF7oZXi­2Enzjn63KnhZhq5OJD0BlraP81IEzF27edzbP0I9LqfNPFt4UuTjQuZbRzkY­8Iqyh4PsmBJM5Bw7W8YZufT34mboHB6L9f4XCyxXbeZioo1dg418LHhmvJde­5VUJBR4PGdBzrvRddzMIf8sAYz6y7PmgmMBEwVLf79Nkj';
        $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
        );
        $post = array(
            'grant_type' => 'client_credentials',
            'client_id' => 'pH41d3vuluevpss6',
            'client_secret' => '50JgIZ9FmBsvNKj92Dsfx9XaHva46XIms8oZ98O5gpjUerCBv7ZJIXF7oZXi­2Enzjn63KnhZhq5OJD0BlraP81IEzF27edzbP0I9LqfNPFt4UuTjQuZbRzkY­8Iqyh4PsmBJM5Bw7W8YZufT34mboHB6L9f4XCyxXbeZioo1dg418LHhmvJde­5VUJBR4PGdBzrvRddzMIf8sAYz6y7PmgmMBEwVLf79Nkj',
        );
        return cURL::executeRequestTest('POST', $url, $post, $headers, false, $logDir);
    }
}

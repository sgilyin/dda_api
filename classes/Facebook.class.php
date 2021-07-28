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
 * Description of Facebook
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class Facebook {
    private static function getAuthToken($param) {
        
    }
    
    public static function test() {
        $url = 'https://graph.facebook.com/v8.0/act_2772558393070366/customaudiences';
        $post['name'] = 'Test from API';
        $post['subtype'] = 'CUSTOM';
        $post['description'] = 'Test';
        $post['customer_file_source'] = 'USER_PROVIDED_ONLY';
        $post['access_token'] = FB_ACCESS_TOKEN;
        return cURL::executeRequest($url, $post, false, false);
    }
}

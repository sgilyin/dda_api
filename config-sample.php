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

#define('NAME', '<Enter here>');

# DB Settings
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_NAME', '');

# WA24 Settings
define('WA24_ENABLED', false);
define('WA24_API_KEY', '');
define('WA24_CID_WA', '');
define('WA24_CID_IG', '');
$WA24SemySMSAlrtDst = array();

#SMSC Settings
define('SMSC_ENABLED', false);
define('SMSC_ACCOUNT', '');
define('SMSC_PSW', '');
define('SMSC_FWD_ALL_WA', false);

#GC Settings
define('GC_ENABLED', false);
define('GC_ACCOUNT', '');
define('GC_API_KEY', '');

#Dadata Settings
define('DADATA_ENABLED', false);
define('DADATA_API_KEY', '');
define('DADATA_SECRET_KEY', '');

#Twilio Settings
define('TWILIO_ENABLED', false);
define('TWILIO_SID', '');
define('TWILIO_TOKEN', '');
define('TWILIO_WA_NUMBER', '');
define('TWILIO_SMS_NUMBER', '');
define('TWILIO_CALL_NUMBER', '');

#Senler Settings
define('SENLER_ENABLED', false);
define('SENLER_CALLBACK_KEY', '');

define('INSTAGRAM_FROM', '');

#VK Settings
define('VK_ENABLED', false);
define('VK_TOKEN', '');

#Yandex Settings
define('YANDEX_ENABLED', false);
define('YANDEX_TOKEN', '');

#MT Settings
define('MT_ENABLED', false);
define('MT_CLIENT_ID', '');
define('MT_CLIENT_SECRET', '');
define('MT_ACCESS_TOKEN', '');

#FB Settings
define('FB_ENABLED', false);
define('FB_ACCESS_TOKEN', '');

#SBRF Settings
define('SBRF_ENABLED', false);
define('SBRF_CREDIT_USER', '');
define('SBRF_CREDIT_PASSWORD', '');
define('SBRF_CREDIT_RETURNURL', '');

#SemySMS Settings
define('SEMYSMS_ENABLED', false);
define('SEMYSMS_TOKEN', '');
define('SEMYSMS_DEVICE', '');
define('WA_SEMYSMS_NOTIFY', '');
$SemySMSAlrtDst = array();

#ChatApi Settings
define('CHAT_API_ENABLED', false);
define('CHAT_API_INSTANCE', '');
define('CHAT_API_TOKEN', '');
$ChatAPISemySMSAlrtDst = array();

#Bitrix24 Settings
define('BX24_ENABLED', false);
define('BX24_HOST', '');
define('BX24_USER', '');
define('BX24_SECRET', '');
define('BX24_BOT_ID', '');
define('BX24_CLIENT_ID', '');
define('BX24_ALARM_CHAT_ID', '');

#Telegram Settings
define('TELEGRAM_ENABLED', false);
define('TELEGRAM_BOT_TOKEN', '');
$TgAlrtDst = array();

#Dolyame Settings
define('DOLYAME_ENABLED', false);
define('DOLYAME_USER', '');
define('DOLYAME_PASSWORD', '');
define('DOLYAME_PRIVATE', '');
define('DOLYAME_CERT', '');
define('DOLYAME_NOTIFICATION_URL', '');
define('DOLYAME_FAIL_URL', '');
define('DOLYAME_SUCCESS_URL', '');

#Skorozvon Settings
define('SKOROZVON_ENABLED', false);
define('SKOROZVON_USERNAME', '');
define('SKOROZVON_API_KEY', '');
define('SKOROZVON_CLIENT_ID', '');
define('SKOROZVON_CLIENT_SECRET', '');

#GC Fields
#$addFields = new stdClass();
#$addFields->{'123456'} = '<Enter here>';

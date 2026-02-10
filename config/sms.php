<?php
return [
    'timeout' => 5.0,
    'default' => [
        'gateways' => ['aliyun'],
    ],
    'gateways' => [
        'aliyun' => [
            'access_key_id' => env('ALIYUN_SMS_ACCESS_KEY_ID'),
            'access_key_secret' => env('ALIYUN_SMS_ACCESS_KEY_SECRET'),
            'sign_name' => env('ALIYUN_SMS_SIGN_NAME'),
        ],
    ],
];
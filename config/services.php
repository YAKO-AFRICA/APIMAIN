<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'info_bip_api_key' => env('INFOBIP_API_KEY'),

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'yvon' => [
        // URL interne (serveur à serveur, sécurisé)
        'url'        => env('YVON_API_URL',      'https://yvon.yakoafricassur.com'),
        // URL publique exposée au widget et aux apps mobiles
        'public_url' => env('YVON_PUBLIC_URL',   'https://apimain.yakoafricassur.com'),
        'username'   => env('YVON_API_USER',     'demo'),
        'password'   => env('YVON_API_PASSWORD', 'yako2024'),
    ],

    'jeko' => [
        'base_url' => env('JEKO_BASE_URL', 'https://api.jeko.africa'),
        'store_id' => env('JEKO_STORE_ID'),
        'api_key' => env('JEKO_PARTNER_API_KEY'),
        'api_key_id' => env('JEKO_PARTNER_API_KEY_ID'),
        'webhook_secret' => env('JEKO_WEBHOOK_SECRET'),
    ],

    'api' => [
        'encaissement_bis' => env('API_ENCAISSEMENT_BIS', 'https://api.yakoafricassur.com/oldweb/encaissement-bis'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];

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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'yvon' => [
        // URL interne (serveur à serveur, sécurisé)
        'url'        => env('YVON_API_URL',      'http://yvon.yakoafricassur.com:443'),
        // 'url'        => env('YVON_API_URL',      'https://yvon.yakoafricassur.com'),
        // URL publique exposée au widget et aux apps mobiles
        'public_url' => env('YVON_PUBLIC_URL',   'https://apimain.yakoafricassur.com'),
        'username'   => env('YVON_API_USER',     'demo'),
        'password'   => env('YVON_API_PASSWORD', 'yako2024'),
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

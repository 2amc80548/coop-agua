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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'pagofacil' => [
        'base_url'     => env('PAGO_FACIL_BASE_URL', 'https://masterqr.pagofacil.com.bo/api/services/v2'),
        'service_key'  => env('PAGO_FACIL_SERVICE'),
        'secret_key'   => env('PAGO_FACIL_SECRET'),
        'prefix'       => env('PAGO_FACIL_PREFIX', 'grupo05cc'),
        'callback_url' => env('PAGO_FACIL_CALLBACK_URL', 'https://www.tecnoweb.org.bo/inf513/grupo05cc/coop-agua/public/api/payment/callback'),
        'defaults'     => [
            'name'  => env('PAGO_FACIL_DEFAULT_NAME', 'Cliente CoopAgua'),
            'ci'    => env('PAGO_FACIL_DEFAULT_CI', '0'),
            'phone' => env('PAGO_FACIL_DEFAULT_PHONE', '70000000'),
            'email' => env('PAGO_FACIL_DEFAULT_EMAIL', 'contacto@coopagua.com'),
        ],
    ],

];

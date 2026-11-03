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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // WhatsApp Cloud API (ADR 0003). O token da Meta fica só no worker; aqui, o número que envia e o segredo do callback.
    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'worker_secret' => env('WHATSAPP_WORKER_SECRET'),
    ],

    // Asaas (assinatura do SaaS). Sandbox por padrão; produção: https://api.asaas.com/v3.
    'asaas' => [
        'base_url' => env('ASAAS_BASE_URL', 'https://api-sandbox.asaas.com/v3'),
        'api_key' => env('ASAAS_API_KEY'),
        // Token que o Asaas manda no header `asaas-access-token` de cada webhook.
        'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];

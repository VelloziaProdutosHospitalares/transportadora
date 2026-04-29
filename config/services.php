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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'octalog' => [
        'url' => env('OCTALOG_URL', 'https://integracao.octalog.com.br'),
        'auth_url' => env('OCTALOG_AUTH_URL', 'https://api.octalog.com.br'),
        'usuario' => env('OCTALOG_USUARIO'),
        'senha' => env('OCTALOG_SENHA'),
        /** Valor esperado no cabeçalho configurado na Octalog (ex.: Bearer ou mesmo segredo sem prefixo). Vazio = não validar. */
        'webhook_secret' => env('OCTALOG_WEBHOOK_SECRET'),
    ],

    'serpro' => [
        'consumer_key' => env('SERPRO_CONSUMER_KEY'),
        'consumer_secret' => env('SERPRO_CONSUMER_SECRET'),
        // Opcional: Base64 do par key:secret (após "Basic " no curl). Se preenchido, tem prioridade em relação a consumer_key + consumer_secret no POST /token.
        'basic_auth_base64' => env('SERPRO_BASIC_AUTH_BASE64'),
        // Gateway São Paulo (recomendado). Deprecated na doc: https://apigateway.serpro.gov.br/token (Brasília).
        'token_url' => env('SERPRO_TOKEN_URL', 'https://gateway.apiserpro.serpro.gov.br/token'),
        'consulta_base_url' => env('SERPRO_CONSULTA_BASE_URL'),
        'timeout' => env('SERPRO_TIMEOUT', 30),
        // false = equivalente ao curl -k (útil se CA local não confiar no gateway; produção: manter true)
        'verify_ssl' => filter_var(env('SERPRO_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    ],

];

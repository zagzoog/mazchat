<?php
// Load environment configuration
require_once __DIR__ . '/environment.php';

// Webhook URLs
$config = [
    'test' => [
        'webhook_url' => 'https://n8n.mazcode.com/webhook/b0e277cb-a3ab-40be-9c7d-2048c6bbad8f',
        'ssl_verify' => true,
        'conversations_per_page' => 10,
        'debug' => true,
        'development_mode' => true,
        'debug_logging' => true,
        'free_monthly_limit' => 200,
        'silver_monthly_limit' => 100,
        'gold_monthly_limit' => 999999,
        'free_question_limit' => 500,
        'silver_question_limit' => 2000,
        'gold_question_limit' => 999999,
        'silver_price' => 9.99,
        'gold_price' => 30,
        'paypal_client_id' => 'AS2_uTbJJFC56yZqOv6Y_CfFZNLNCKkV_z13UW8sjcWhkq3dU93o8H1hhr7gKOnujp1NfNB5oj3xe2bf',
        'paypal_secret' => 'EDGDx3mheykckofj14L7Zud6CUPU0kap4lwZLSzm41D51AmWBacoPUf9-W-MI6_9e75uVDlBvAvRXSIe',
        'paypal_mode' => 'sandbox',
        'trusted_proxies' => ['cloudflare'],
        'force_ssl' => true
    ],
    'production' => [
        'webhook_url' => 'https://n8n.mazcode.com/webhook/b0e277cb-a3ab-40be-9c7d-2048c6bbad8f',
        'ssl_verify' => true,
        'debug' => false,
        'conversations_per_page' => 10,
        'development_mode' => false,
        'debug_logging' => false,
        'free_monthly_limit' => 200,
        'silver_monthly_limit' => 100,
        'gold_monthly_limit' => 999999,
        'free_question_limit' => 500,
        'silver_question_limit' => 2000,
        'gold_question_limit' => 999999,
        'silver_price' => 9.99,
        'gold_price' => 30,
        'paypal_client_id' => 'AS2_uTbJJFC56yZqOv6Y_CfFZNLNCKkV_z13UW8sjcWhkq3dU93o8H1hhr7gKOnujp1NfNB5oj3xe2bf',
        'paypal_secret' => 'EDGDx3mheykckofj14L7Zud6CUPU0kap4lwZLSzm41D51AmWBacoPUf9-W-MI6_9e75uVDlBvAvRXSIe',
        'paypal_mode' => 'sandbox',
        'trusted_proxies' => ['cloudflare'],
        'force_ssl' => true
    ]
];

// Get current environment settings
$currentConfig = $config[ENVIRONMENT];

// Export configuration
return $currentConfig;
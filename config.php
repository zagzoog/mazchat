<?php
// Load environment configuration
require_once __DIR__ . '/environment.php';

// Webhook URLs
$config = [
    'test' => [
        'webhook_url' => 'https://n8n.mazcode.com/webhook/b0e277cb-a3ab-40be-9c7d-2048c6bbad8f',
        'ssl_verify' => false,
        'conversations_per_page' => 5,
        'debug' => true,
        'development_mode' => true,
        'debug_logging' => true,
        'free_monthly_limit' => 50,
        'silver_monthly_limit' => 100,
        'gold_monthly_limit' => 999999,
        'free_question_limit' => 500,
        'silver_question_limit' => 2000,
        'gold_question_limit' => 999999,
        'silver_price' => 9.99,
        'gold_price' => 19.99,
        'paypal_client_id' => '',
        'paypal_secret' => '',
        'paypal_mode' => 'sandbox'
    ],
    'production' => [
        'webhook_url' => 'https://n8n.mazcode.com/webhook/b0e277cb-a3ab-40be-9c7d-2048c6bbad8f',
        'ssl_verify' => false,
        'debug' => true,
        'conversations_per_page' => 5,
        'development_mode' => true,
        'debug_logging' => true,
        'free_monthly_limit' => 50,
        'silver_monthly_limit' => 100,
        'gold_monthly_limit' => 999999,
        'free_question_limit' => 500,
        'silver_question_limit' => 2000,
        'gold_question_limit' => 999999,
        'silver_price' => 9.99,
        'gold_price' => 19.99,
        'paypal_client_id' => '',
        'paypal_secret' => '',
        'paypal_mode' => 'sandbox'
    ]
];

// Get current environment settings
$currentConfig = $config[ENVIRONMENT];

// Export configuration
return $currentConfig;
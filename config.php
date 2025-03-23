<?php
// Environment configuration
define('ENVIRONMENT', 'production'); // Changed from 'test' to 'production'

// Webhook URLs
$config = [
    'test' => [
        'webhook_url' => 'https://n8n.mazcode.com/webhook/b0e277cb-a3ab-40be-9c7d-2048c6bbad8f',
        'ssl_verify' => false,
        'conversations_per_page' => 10,
        'debug' => true,
        'development_mode' => true,
        'debug_logging' => true
    ],
    'production' => [
        'webhook_url' => 'https://n8n.mazcode.com/webhook/b0e277cb-a3ab-40be-9c7d-2048c6bbad8f',
        'ssl_verify' => false,
        'debug' => false,
        'conversations_per_page' => 10,
        'development_mode' => false,
        'debug_logging' => false
    ]
];

// Get current environment settings
$currentConfig = $config[ENVIRONMENT];

// Export configuration
return $currentConfig; 
<?php
// Environment configuration
define('ENVIRONMENT', 'production'); // Changed from 'test' to 'production'

// Webhook URLs
$config = [
    'test' => [
        'webhook_url' => 'https://n8n.mazcode.com/webhook-test/b0e277cb-a3ab-40be-9c7d-2048c6bbad8f',
        'ssl_verify' => false,
        'debug' => true
    ],
    'production' => [
        'webhook_url' => 'https://n8n.mazcode.com/webhook/b0e277cb-a3ab-40be-9c7d-2048c6bbad8f',
        'ssl_verify' => true,
        'debug' => false
    ]
];

// Get current environment settings
$currentConfig = $config[ENVIRONMENT];

// Export configuration
return $currentConfig; 
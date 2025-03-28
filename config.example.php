<?php
return [
    'version' => '1.0.3',
    'development_mode' => true,
    'conversations_per_page' => 10,
    'max_message_length' => 1000,
    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'upload_path' => __DIR__ . '/uploads',
    'log_path' => __DIR__ . '/logs',
    'cache_path' => __DIR__ . '/cache',
    'session_lifetime' => 7200, // 2 hours
    'api_rate_limit' => 100, // requests per minute
    'maintenance_mode' => false,
    'site_url' => 'http://localhost/chat',
    'admin_email' => 'admin@example.com',
    'support_email' => 'support@example.com',
    'timezone' => 'Asia/Riyadh',
    'language' => 'ar',
    'theme' => 'default',
    'plugins' => [
        'enabled' => true,
        'auto_update' => true
    ]
]; 
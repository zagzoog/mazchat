<?php
/**
 * Chat Application Configuration
 * 
 * @author Mohammed Zagzoog
 * @version 1.0.0
 */

// Load environment configuration
require_once __DIR__ . '/environment.php';

// Webhook URLs
$config = [
    'test' => [
        'version' => '1.0.40',
        'domain_name' => 'http://localhost/chat',
        'directory_path' => '/c:/Users/zagzo/Downloads/UniServerZ/www/chat',
        'webhook_url' => 'https://n8n.mazcode.com/webhook/b0e277cb-a3ab-40be-9c7d-2048c6bbad8f',
        'ssl_verify' => false,
        'conversations_per_page' => 10,
        'debug' => true,
        'development_mode' => true,
        'debug_logging' => true,
        'free_monthly_limit' => 10,
        'silver_monthly_limit' => 100,
        'gold_monthly_limit' => 999999,
        'free_question_limit' => 100,
        'silver_question_limit' => 2000,
        'gold_question_limit' => 999999,
        'free_plan' => 'free',
        'silver_plan' => 'silver',
        'gold_plan' => 'gold',
        'free_plan_name' => 'مجاني',
        'silver_plan_name' => 'فضي',
        'gold_plan_name' => 'ذهبي',
        'free_plan_description' => 'الخطة المجانية',
        'silver_plan_description' => 'الخطة الفضية',
        'gold_plan_description' => 'الخطة الذهبية',
        'free_plan_price' => 0,
        'silver_plan_price' => 10,
        'gold_plan_price' => 20,
        'free_plan_features' => [
            'الوصول إلى المحادثات الأساسية',
            'حد 10 محادثات شهرياً',
            'حد 100 سؤال شهرياً',
            'دعم البريد الإلكتروني'
        ],
        'silver_plan_features' => [
            'الوصول إلى المحادثات المتقدمة',
            'حد 100 محادثة شهرياً',
            'حد 2000 سؤال شهرياً',
            'دعم البريد الإلكتروني',
            'دعم الدردشة المباشرة',
            'تحليلات متقدمة'
        ],
        'gold_plan_features' => [
            'الوصول إلى جميع المحادثات',
            'حد غير محدود من المحادثات',
            'حد غير محدود من الأسئلة',
            'دعم البريد الإلكتروني',
            'دعم الدردشة المباشرة',
            'تحليلات متقدمة',
            'دعم مخصص'
        ],
        'paypal_client_id' => 'AS2_uTbJJFC56yZqOv6Y_CfFZNLNCKkV_z13UW8sjcWhkq3dU93o8H1hhr7gKOnujp1NfNB5oj3xe2bf',
        'paypal_secret' => 'EDGDx3mheykckofj14L7Zud6CUPU0kap4lwZLSzm41D51AmWBacoPUf9-W-MI6_9e75uVDlBvAvRXSIe',
        'paypal_mode' => 'sandbox'
    ],
    'production' => [
        'version' => '1.0.40',
        'domain_name' => 'https://n9ib.com',
        'directory_path' => '/home/n9ib/public_html',
        'webhook_url' => 'https://n8n.mazcode.com/webhook/b0e277cb-a3ab-40be-9c7d-2048c6bbad8f',
        'ssl_verify' => true,
        'conversations_per_page' => 10,
        'debug' => false,
        'development_mode' => false,
        'debug_logging' => false,
        'free_monthly_limit' => 10,
        'silver_monthly_limit' => 100,
        'gold_monthly_limit' => 999999,
        'free_question_limit' => 100,
        'silver_question_limit' => 2000,
        'gold_question_limit' => 999999,
        'free_plan' => 'free',
        'silver_plan' => 'silver',
        'gold_plan' => 'gold',
        'free_plan_name' => 'مجاني',
        'silver_plan_name' => 'فضي',
        'gold_plan_name' => 'ذهبي',
        'free_plan_description' => 'الخطة المجانية',
        'silver_plan_description' => 'الخطة الفضية',
        'gold_plan_description' => 'الخطة الذهبية',
        'free_plan_price' => 0,
        'silver_plan_price' => 10,
        'gold_plan_price' => 20,
        'free_plan_features' => [
            'الوصول إلى المحادثات الأساسية',
            'حد 10 محادثات شهرياً',
            'حد 100 سؤال شهرياً',
            'دعم البريد الإلكتروني'
        ],
        'silver_plan_features' => [
            'الوصول إلى المحادثات المتقدمة',
            'حد 100 محادثة شهرياً',
            'حد 2000 سؤال شهرياً',
            'دعم البريد الإلكتروني',
            'دعم الدردشة المباشرة',
            'تحليلات متقدمة'
        ],
        'gold_plan_features' => [
            'الوصول إلى جميع المحادثات',
            'حد غير محدود من المحادثات',
            'حد غير محدود من الأسئلة',
            'دعم البريد الإلكتروني',
            'دعم الدردشة المباشرة',
            'تحليلات متقدمة',
            'دعم مخصص'
        ],
        'paypal_client_id' => 'AS2_uTbJJFC56yZqOv6Y_CfFZNLNCKkV_z13UW8sjcWhkq3dU93o8H1hhr7gKOnujp1NfNB5oj3xe2bf',
        'paypal_secret' => 'EDGDx3mheykckofj14L7Zud6CUPU0kap4lwZLSzm41D51AmWBacoPUf9-W-MI6_9e75uVDlBvAvRXSIe',
        'paypal_mode' => 'sandbox'
    ]
];

// Return the full configuration array
return $config;
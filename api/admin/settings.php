<?php
session_start();
require_once '../../app/utils/ResponseCompressor.php';
require_once '../../db_config.php';
require_once '../../app/utils/Logger.php';
require_once '../../app/models/Model.php';
require_once '../../app/models/User.php';

// Initialize response compression
$compressor = ResponseCompressor::getInstance();
$compressor->start();

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    $compressor->end();
    exit;
}

// Check if user is admin
$userModel = new User();
$user = $userModel->findById($_SESSION['user_id']);

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Admin access required']);
    $compressor->end();
    exit;
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Return current settings
            require_once '../../environment.php';
            $config = require '../../config.php';
            echo json_encode([
                'success' => true,
                'data' => array_merge($config, [
                    'environment' => ENVIRONMENT
                ])
            ]);
            break;
            
        case 'POST':
            // Update settings
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['type']) || !isset($data['settings'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid request data']);
                break;
            }
            
            $configFile = '../../config.php';
            $currentConfig = require $configFile;
            
            // Update settings based on type
            switch ($data['type']) {
                case 'app':
                    // Update environment setting
                    $envContent = "<?php\n";
                    $envContent .= "// Environment configuration\n";
                    $envContent .= "if (!defined('ENVIRONMENT')) {\n";
                    $envContent .= "    define('ENVIRONMENT', '" . $data['settings']['environment'] . "');\n";
                    $envContent .= "}";
                    
                    if (file_put_contents('../../environment.php', $envContent) === false) {
                        throw new Exception('Failed to write environment file');
                    }
                    
                    $currentConfig['debug'] = $data['settings']['debug'];
                    $currentConfig['conversations_per_page'] = $data['settings']['conversations_per_page'];
                    break;
                    
                case 'api':
                    $currentConfig['webhook_url'] = $data['settings']['webhook_url'];
                    $currentConfig['ssl_verify'] = $data['settings']['ssl_verify'];
                    break;

                case 'subscription':
                    $currentConfig['free_monthly_limit'] = $data['settings']['free_monthly_limit'];
                    $currentConfig['silver_monthly_limit'] = $data['settings']['silver_monthly_limit'];
                    $currentConfig['gold_monthly_limit'] = $data['settings']['gold_monthly_limit'];
                    $currentConfig['free_question_limit'] = $data['settings']['free_question_limit'];
                    $currentConfig['silver_question_limit'] = $data['settings']['silver_question_limit'];
                    $currentConfig['gold_question_limit'] = $data['settings']['gold_question_limit'];
                    $currentConfig['silver_price'] = $data['settings']['silver_price'];
                    $currentConfig['gold_price'] = $data['settings']['gold_price'];
                    break;

                case 'paypal':
                    $currentConfig['paypal_client_id'] = $data['settings']['paypal_client_id'];
                    $currentConfig['paypal_secret'] = $data['settings']['paypal_secret'];
                    $currentConfig['paypal_mode'] = $data['settings']['paypal_mode'];
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid settings type']);
                    break;
            }
            
            // Generate new config file content
            $configContent = "<?php\n";
            $configContent .= "// Load environment configuration\n";
            $configContent .= "require_once __DIR__ . '/environment.php';\n\n";
            $configContent .= "// Webhook URLs\n";
            $configContent .= "\$config = [\n";
            $configContent .= "    'test' => [\n";
            $configContent .= "        'webhook_url' => '" . $currentConfig['webhook_url'] . "',\n";
            $configContent .= "        'ssl_verify' => " . ($currentConfig['ssl_verify'] ? 'true' : 'false') . ",\n";
            $configContent .= "        'conversations_per_page' => " . $currentConfig['conversations_per_page'] . ",\n";
            $configContent .= "        'debug' => " . ($currentConfig['debug'] ? 'true' : 'false') . ",\n";
            $configContent .= "        'development_mode' => true,\n";
            $configContent .= "        'debug_logging' => true,\n";
            $configContent .= "        'free_monthly_limit' => " . ($currentConfig['free_monthly_limit'] ?? 50) . ",\n";
            $configContent .= "        'silver_monthly_limit' => " . ($currentConfig['silver_monthly_limit'] ?? 100) . ",\n";
            $configContent .= "        'gold_monthly_limit' => " . ($currentConfig['gold_monthly_limit'] ?? 999999) . ",\n";
            $configContent .= "        'free_question_limit' => " . ($currentConfig['free_question_limit'] ?? 500) . ",\n";
            $configContent .= "        'silver_question_limit' => " . ($currentConfig['silver_question_limit'] ?? 2000) . ",\n";
            $configContent .= "        'gold_question_limit' => " . ($currentConfig['gold_question_limit'] ?? 999999) . ",\n";
            $configContent .= "        'silver_price' => " . ($currentConfig['silver_price'] ?? 9.99) . ",\n";
            $configContent .= "        'gold_price' => " . ($currentConfig['gold_price'] ?? 19.99) . ",\n";
            $configContent .= "        'paypal_client_id' => '" . ($currentConfig['paypal_client_id'] ?? '') . "',\n";
            $configContent .= "        'paypal_secret' => '" . ($currentConfig['paypal_secret'] ?? '') . "',\n";
            $configContent .= "        'paypal_mode' => '" . ($currentConfig['paypal_mode'] ?? 'sandbox') . "'\n";
            $configContent .= "    ],\n";
            $configContent .= "    'production' => [\n";
            $configContent .= "        'webhook_url' => '" . $currentConfig['webhook_url'] . "',\n";
            $configContent .= "        'ssl_verify' => " . ($currentConfig['ssl_verify'] ? 'true' : 'false') . ",\n";
            $configContent .= "        'debug' => " . ($currentConfig['debug'] ? 'true' : 'false') . ",\n";
            $configContent .= "        'conversations_per_page' => " . $currentConfig['conversations_per_page'] . ",\n";
            $configContent .= "        'development_mode' => true,\n";
            $configContent .= "        'debug_logging' => true,\n";
            $configContent .= "        'free_monthly_limit' => " . ($currentConfig['free_monthly_limit'] ?? 50) . ",\n";
            $configContent .= "        'silver_monthly_limit' => " . ($currentConfig['silver_monthly_limit'] ?? 100) . ",\n";
            $configContent .= "        'gold_monthly_limit' => " . ($currentConfig['gold_monthly_limit'] ?? 999999) . ",\n";
            $configContent .= "        'free_question_limit' => " . ($currentConfig['free_question_limit'] ?? 500) . ",\n";
            $configContent .= "        'silver_question_limit' => " . ($currentConfig['silver_question_limit'] ?? 2000) . ",\n";
            $configContent .= "        'gold_question_limit' => " . ($currentConfig['gold_question_limit'] ?? 999999) . ",\n";
            $configContent .= "        'silver_price' => " . ($currentConfig['silver_price'] ?? 9.99) . ",\n";
            $configContent .= "        'gold_price' => " . ($currentConfig['gold_price'] ?? 19.99) . ",\n";
            $configContent .= "        'paypal_client_id' => '" . ($currentConfig['paypal_client_id'] ?? '') . "',\n";
            $configContent .= "        'paypal_secret' => '" . ($currentConfig['paypal_secret'] ?? '') . "',\n";
            $configContent .= "        'paypal_mode' => '" . ($currentConfig['paypal_mode'] ?? 'sandbox') . "'\n";
            $configContent .= "    ]\n";
            $configContent .= "];\n\n";
            $configContent .= "// Get current environment settings\n";
            $configContent .= "\$currentConfig = \$config[ENVIRONMENT];\n\n";
            $configContent .= "// Export configuration\n";
            $configContent .= "return \$currentConfig;";
            
            // Write new config file
            if (file_put_contents($configFile, $configContent) === false) {
                throw new Exception('Failed to write config file');
            }
            
            Logger::log("Updated application settings", 'INFO', [
                'user_id' => $_SESSION['user_id'],
                'settings_type' => $data['type']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    Logger::log("Error in settings endpoint", 'ERROR', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

$compressor->end(); 
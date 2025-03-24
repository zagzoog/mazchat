<?php

require_once dirname(__DIR__, 2) . '/db_config.php';

// Ensure user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'directmessagehandler_settings') {
    try {
        $db = getDBConnection();
        
        // Validate input
        $provider = filter_var($_POST['provider'], FILTER_SANITIZE_STRING);
        $model = filter_var($_POST['model'], FILTER_SANITIZE_STRING);
        $api_key = filter_var($_POST['api_key'], FILTER_SANITIZE_STRING);
        $temperature = filter_var($_POST['temperature'], FILTER_VALIDATE_FLOAT);
        $max_tokens = filter_var($_POST['max_tokens'], FILTER_VALIDATE_INT);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate provider
        if (!in_array($provider, ['openai', 'anthropic'])) {
            throw new Exception('Invalid provider');
        }
        
        // Validate model based on provider
        $valid_openai_models = ['gpt-4', 'gpt-3.5-turbo'];
        $valid_anthropic_models = ['claude-2', 'claude-instant'];
        
        if ($provider === 'openai' && !in_array($model, $valid_openai_models)) {
            throw new Exception('Invalid OpenAI model');
        } else if ($provider === 'anthropic' && !in_array($model, $valid_anthropic_models)) {
            throw new Exception('Invalid Anthropic model');
        }
        
        // Validate temperature
        if ($temperature < 0 || $temperature > 2) {
            throw new Exception('Temperature must be between 0 and 2');
        }
        
        // Validate max tokens
        if ($max_tokens < 1 || $max_tokens > 8000) {
            throw new Exception('Max tokens must be between 1 and 8000');
        }
        
        // Update settings
        $stmt = $db->prepare("
            UPDATE direct_message_settings 
            SET provider = ?,
                model = ?,
                api_key = ?,
                temperature = ?,
                max_tokens = ?,
                is_active = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = 1
        ");
        
        $stmt->execute([
            $provider,
            $model,
            $api_key,
            $temperature,
            $max_tokens,
            $is_active
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
} 
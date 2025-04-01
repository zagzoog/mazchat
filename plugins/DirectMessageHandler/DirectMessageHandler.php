<?php

require_once dirname(__DIR__, 2) . "/app/plugins/Plugin.php";

class DirectMessageHandler extends Plugin {
    private $pluginId;
    private $activeModel;

    public function __construct($pluginId = null) {
        parent::__construct();
        
        $this->name = 'DirectMessageHandler';
        $this->version = '1.0.0';
        $this->description = 'Handles message processing directly through configured AI providers';
        $this->author = 'System Admin';
        $this->pluginId = $pluginId;
    }
    
    public function initialize() {
        // Register hooks
        $this->registerHook('before_send_message', [$this, 'processMessage'], 10);
        $this->registerHook('admin_settings_page', [$this, 'addSettingsPage']);
    }
    
    public function activate($pluginId = null) {
        $this->pluginId = $pluginId;
        parent::activate($pluginId);
        
        // Create necessary database tables
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS direct_message_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                plugin_id INT UNSIGNED NOT NULL,
                provider VARCHAR(50) NOT NULL DEFAULT 'openai',
                model VARCHAR(50) NOT NULL DEFAULT 'gpt-3.5-turbo',
                api_key VARCHAR(255) NOT NULL,
                temperature FLOAT DEFAULT 0.7,
                max_tokens INT DEFAULT 2000,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE
            )
        ");
        
        // Insert default settings if none exist
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM direct_message_settings WHERE plugin_id = ?");
        $stmt->execute([$this->pluginId]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $this->db->prepare("
                INSERT INTO direct_message_settings (plugin_id, provider, model, api_key) 
                VALUES (?, 'openai', 'gpt-3.5-turbo', '')
            ");
            $stmt->execute([$this->pluginId]);
        }
    }
    
    public function deactivate($pluginId = null) {
        parent::deactivate($pluginId);
    }
    
    public function processMessage($message) {
        try {
            // Get settings
            $settings = $this->getSettings();
            
            if (!$settings || empty($settings['api_key'])) {
                error_log("No API key configured for DirectMessageHandler");
                return false;
            }
            
            // Prepare the request data based on provider
            switch ($settings['provider']) {
                case 'openai':
                    $response = $this->processOpenAIMessage($message, $settings);
                    break;
                case 'anthropic':
                    $response = $this->processAnthropicMessage($message, $settings);
                    break;
                default:
                    throw new Exception("Unsupported provider: " . $settings['provider']);
            }
            
            if (!$response) {
                throw new Exception("Failed to get response from AI provider");
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Error processing message through DirectMessageHandler: " . $e->getMessage());
            return false;
        }
    }
    
    private function processOpenAIMessage($message, $settings) {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        
        $data = [
            'model' => $settings['model'],
            'messages' => [
                ['role' => 'user', 'content' => $message['content']]
            ],
            'temperature' => $settings['temperature'],
            'max_tokens' => $settings['max_tokens']
        ];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $settings['api_key']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("OpenAI API request failed with status code: " . $httpCode);
            return false;
        }
        
        $responseData = json_decode($response, true);
        return $responseData['choices'][0]['message']['content'] ?? false;
    }
    
    private function processAnthropicMessage($message, $settings) {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        
        $data = [
            'model' => $settings['model'],
            'messages' => [
                ['role' => 'user', 'content' => $message['content']]
            ],
            'max_tokens' => $settings['max_tokens']
        ];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $settings['api_key'],
            'anthropic-version: 2023-06-01'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Anthropic API request failed with status code: " . $httpCode);
            return false;
        }
        
        $responseData = json_decode($response, true);
        return $responseData['content'][0]['text'] ?? false;
    }
    
    public function getSettings() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM direct_message_settings WHERE plugin_id = ? LIMIT 1");
            $stmt->execute([$this->pluginId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'provider' => 'openai',
                'model' => 'gpt-3.5-turbo',
                'api_key' => '',
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'is_active' => true
            ];
        } catch (Exception $e) {
            error_log("Error getting DirectMessageHandler settings: " . $e->getMessage());
            return false;
        }
    }
    
    public function addSettingsPage() {
        $settings = $this->getSettings();
        include dirname(__FILE__) . '/templates/settings.php';
    }
} 
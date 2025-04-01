<?php

require_once dirname(__DIR__, 2) . "/app/plugins/Plugin.php";

class LLMModelSelector extends Plugin {
    public function __construct() {
        parent::__construct();
        
        $this->name = 'LLMModelSelector';
        $this->version = '1.0.0';
        $this->description = 'Allows selection of different LLM models for chat responses';
        $this->author = 'Your Name';
    }
    
    public function initialize() {
        // Register hooks
        $this->registerHook('before_send_message', [$this, 'selectModel'], 5);
        $this->registerHook('admin_settings_page', [$this, 'addSettingsPage']);
    }
    
    public function activate($pluginId = null) {
        parent::activate($pluginId);
        
        // Create necessary database tables
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS llm_models (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                provider VARCHAR(50) NOT NULL,
                api_key VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Insert default models if none exist
        $stmt = $this->db->query("SELECT COUNT(*) FROM llm_models");
        if ($stmt->fetchColumn() == 0) {
            $this->db->exec("
                INSERT INTO llm_models (name, provider, api_key) VALUES
                ('GPT-4', 'openai', ''),
                ('GPT-3.5 Turbo', 'openai', ''),
                ('Claude 2', 'anthropic', '')
            ");
        }
    }
    
    public function deactivate($pluginId = null) {
        parent::deactivate($pluginId);
        // Clean up if necessary
    }
    
    public function selectModel($message) {
        // Get active model from database
        $stmt = $this->db->query("SELECT * FROM llm_models WHERE is_active = TRUE LIMIT 1");
        $model = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$model) {
            throw new Exception("No active LLM model found");
        }
        
        // Set the model in the message context
        $message['model'] = $model;
    }
    
    public function addSettingsPage() {
        // Add settings page to admin panel
        $models = $this->db->query("SELECT * FROM llm_models ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        
        include dirname(__FILE__) . '/templates/settings.php';
    }
} 
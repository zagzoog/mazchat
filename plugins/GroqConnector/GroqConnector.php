<?php

require_once dirname(__DIR__, 2) . "/app/plugins/Plugin.php";

class GroqConnector extends Plugin {
    private $pluginId;

    public function __construct($pluginId = null) {
        error_log("GroqConnector: Constructing with plugin ID: " . ($pluginId ?? 'null'));
        parent::__construct();
        
        $this->name = 'GroqConnector';
        $this->version = '1.0.0';
        $this->description = 'Connects to Groq API for enhanced chat capabilities';
        $this->author = 'System Admin';
        $this->pluginId = $pluginId;
        
        error_log("GroqConnector: Construction completed");
    }
    
    public function initialize() {
        error_log("GroqConnector: Initializing plugin");
        
        // Clear existing hooks to prevent duplicates
        $this->hooks = [];
        
        // Register hooks
        error_log("GroqConnector: Registering hooks");
        $this->registerHook('admin_settings_page', [$this, 'addSettingsPage']);
        $this->registerHook('admin_settings_update', [$this, 'handleSettingsUpdate']);
        
        error_log("GroqConnector: Hooks registered: " . print_r($this->hooks, true));
        error_log("GroqConnector: Initialization completed");
    }
    
    public function activate($pluginId = null) {
        error_log("GroqConnector: Activating plugin with ID: " . ($pluginId ?? 'null'));
        
        if ($pluginId) {
            $this->pluginId = $pluginId;
        }
        
        if (!$this->pluginId) {
            error_log("GroqConnector ERROR: Cannot activate plugin without an ID");
            throw new Exception("Plugin ID is required for activation");
        }
        
        parent::activate($this->pluginId);
        
        try {
            error_log("GroqConnector: Creating/checking database tables");
            // Create necessary database tables
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS groq_connector_settings (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    plugin_id INT UNSIGNED NOT NULL,
                    api_key VARCHAR(255) NOT NULL,
                    model VARCHAR(50) DEFAULT 'mixtral-8x7b-32768',
                    temperature FLOAT DEFAULT 0.7,
                    max_tokens INT DEFAULT 4096,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE
                )
            ");
            
            // Insert default settings if none exist
            error_log("GroqConnector: Checking for existing settings");
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM groq_connector_settings WHERE plugin_id = ?");
            $stmt->execute([$this->pluginId]);
            if ($stmt->fetchColumn() == 0) {
                error_log("GroqConnector: No settings found, creating default settings");
                $stmt = $this->db->prepare("
                    INSERT INTO groq_connector_settings (plugin_id, api_key) 
                    VALUES (?, '')
                ");
                $stmt->execute([$this->pluginId]);
            }
            error_log("GroqConnector: Activation completed successfully");
            
            // Initialize the plugin after activation
            $this->initialize();
            
        } catch (Exception $e) {
            error_log("GroqConnector ERROR: Activation failed: " . $e->getMessage());
            error_log("GroqConnector ERROR: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    public function deactivate($pluginId = null) {
        parent::deactivate($pluginId);
        // Clean up if necessary
    }
    
    public function getSettings() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM groq_connector_settings WHERE plugin_id = ? LIMIT 1");
            $stmt->execute([$this->pluginId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'api_key' => '',
                'model' => 'mixtral-8x7b-32768',
                'temperature' => 0.7,
                'max_tokens' => 4096,
                'is_active' => true
            ];
        } catch (Exception $e) {
            error_log("Error getting Groq connector settings: " . $e->getMessage());
            return [
                'api_key' => '',
                'model' => 'mixtral-8x7b-32768',
                'temperature' => 0.7,
                'max_tokens' => 4096,
                'is_active' => true
            ];
        }
    }
    
    public function addSettingsPage($settings) {
        ob_start();
        ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="groq_connector_settings">
            <input type="hidden" name="plugin_id" value="<?php echo $this->pluginId; ?>">
            <div class="mb-3">
                <label for="api_key" class="form-label">مفتاح API</label>
                <input type="password" class="form-control" id="api_key" name="api_key" 
                       value="<?php echo htmlspecialchars($settings['api_key'] ?? ''); ?>" required>
                <div class="form-text">أدخل مفتاح API الخاص بـ Groq</div>
            </div>
            
            <div class="mb-3">
                <label for="model" class="form-label">النموذج</label>
                <select class="form-select" id="model" name="model">
                    <option value="mixtral-8x7b-32768" <?php echo ($settings['model'] ?? '') === 'mixtral-8x7b-32768' ? 'selected' : ''; ?>>Mixtral 8x7B</option>
                    <option value="llama2-70b-4096" <?php echo ($settings['model'] ?? '') === 'llama2-70b-4096' ? 'selected' : ''; ?>>Llama 2 70B</option>
                </select>
                <div class="form-text">اختر نموذج Groq الذي تريد استخدامه</div>
            </div>
            
            <div class="mb-3">
                <label for="temperature" class="form-label">درجة الحرارة</label>
                <input type="number" class="form-control" id="temperature" name="temperature" 
                       value="<?php echo htmlspecialchars($settings['temperature'] ?? 0.7); ?>" 
                       min="0" max="1" step="0.1">
                <div class="form-text">قيمة بين 0 و 1. القيم الأعلى تجعل النموذج أكثر إبداعاً</div>
            </div>
            
            <div class="mb-3">
                <label for="max_tokens" class="form-label">الحد الأقصى للرموز</label>
                <input type="number" class="form-control" id="max_tokens" name="max_tokens" 
                       value="<?php echo htmlspecialchars($settings['max_tokens'] ?? 4096); ?>" 
                       min="1" max="32768">
                <div class="form-text">الحد الأقصى لطول الاستجابة</div>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                           <?php echo ($settings['is_active'] ?? true) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">تفعيل الاتصال بـ Groq</label>
                    <div class="form-text">تفعيل أو تعطيل استخدام Groq في المحادثات</div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
        </form>
        <?php
        return ob_get_clean();
    }
    
    public function handleSettingsUpdate($data) {
        try {
            error_log("GroqConnector: Handling settings update with data: " . print_r($data, true));
            
            // Validate plugin ID
            if (!isset($data['plugin_id']) || $data['plugin_id'] != $this->pluginId) {
                error_log("GroqConnector ERROR: Invalid plugin ID");
                throw new Exception("معرف الإضافة غير صالح");
            }
            
            $apiKey = $data['api_key'] ?? '';
            $model = $data['model'] ?? 'mixtral-8x7b-32768';
            $temperature = floatval($data['temperature'] ?? 0.7);
            $maxTokens = intval($data['max_tokens'] ?? 4096);
            $isActive = isset($data['is_active']) ? 1 : 0;

            // Validate temperature
            if ($temperature < 0 || $temperature > 1) {
                throw new Exception("درجة الحرارة يجب أن تكون بين 0 و 1");
            }

            // Validate max tokens
            if ($maxTokens < 1 || $maxTokens > 32768) {
                throw new Exception("الحد الأقصى للرموز يجب أن يكون بين 1 و 32768");
            }

            // First, check if settings exist
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM groq_connector_settings WHERE plugin_id = ?");
            $stmt->execute([$this->pluginId]);
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                // Insert new settings if none exist
                $stmt = $this->db->prepare("
                    INSERT INTO groq_connector_settings (plugin_id, api_key, model, temperature, max_tokens, is_active)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                if (!$stmt->execute([$this->pluginId, $apiKey, $model, $temperature, $maxTokens, $isActive])) {
                    error_log("GroqConnector ERROR: Failed to insert settings");
                    throw new Exception("فشل في إضافة الإعدادات");
                }
            } else {
                // Update existing settings
                $stmt = $this->db->prepare("
                    UPDATE groq_connector_settings 
                    SET api_key = ?, model = ?, temperature = ?, max_tokens = ?, is_active = ?
                    WHERE plugin_id = ?
                ");
                if (!$stmt->execute([$apiKey, $model, $temperature, $maxTokens, $isActive, $this->pluginId])) {
                    error_log("GroqConnector ERROR: Failed to update settings");
                    throw new Exception("فشل في تحديث الإعدادات");
                }
            }

            error_log("GroqConnector: Settings updated successfully");
            return true;
        } catch (Exception $e) {
            error_log("GroqConnector ERROR: " . $e->getMessage());
            error_log("GroqConnector ERROR: Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
} 
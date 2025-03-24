<?php

require_once dirname(__DIR__, 2) . "/app/plugins/Plugin.php";

class N8nWebhookHandler extends Plugin {
    private $pluginId;

    public function __construct($pluginId = null) {
        $this->name = 'N8nWebhookHandler';
        $this->version = '1.0.0';
        $this->description = 'Handles message processing through n8n webhooks';
        $this->author = 'System Admin';
        $this->pluginId = $pluginId;
        
        parent::__construct();
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
            CREATE TABLE IF NOT EXISTS n8n_webhook_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                plugin_id INT UNSIGNED NOT NULL,
                webhook_url VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                timeout INT DEFAULT 30,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE
            )
        ");
        
        // Insert default settings if none exist
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM n8n_webhook_settings WHERE plugin_id = ?");
        $stmt->execute([$this->pluginId]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $this->db->prepare("
                INSERT INTO n8n_webhook_settings (plugin_id, webhook_url) 
                VALUES (?, '')
            ");
            $stmt->execute([$this->pluginId]);
        }
    }
    
    public function deactivate($pluginId = null) {
        parent::deactivate($pluginId);
        // Clean up if necessary
    }
    
    public function processMessage($message) {
        try {
            // Get webhook settings
            $stmt = $this->db->query("SELECT * FROM n8n_webhook_settings WHERE is_active = TRUE LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings || empty($settings['webhook_url'])) {
                error_log("No active n8n webhook URL configured");
                return;
            }
            
            // Prepare the request data
            $requestData = [
                'message' => $message['content'],
                'conversation_id' => $message['conversation_id'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Initialize cURL session
            $ch = curl_init($settings['webhook_url']);
            
            // Set cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, $settings['timeout']);
            
            // Execute the request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode !== 200) {
                error_log("n8n webhook request failed with status code: " . $httpCode);
                return;
            }
            
            // Parse the response
            $responseData = json_decode($response, true);
            
            if (!$responseData) {
                error_log("Invalid JSON response from n8n webhook");
                return;
            }
            
            // Store the response as a new message
            $stmt = $this->db->prepare("
                INSERT INTO messages (id, conversation_id, content, is_user)
                VALUES (UUID(), ?, ?, ?)
            ");
            $stmt->execute([
                $message['conversation_id'],
                $responseData['response'] ?? 'No response received from n8n',
                false
            ]);
            
        } catch (Exception $e) {
            error_log("Error processing message through n8n webhook: " . $e->getMessage());
        }
    }
    
    public function addSettingsPage() {
        $stmt = $this->db->query("SELECT * FROM n8n_webhook_settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">إعدادات N8n Webhook</h5>
            </div>
            <div class="card-body">
                <form id="n8n-settings-form" method="POST">
                    <input type="hidden" name="action" value="n8nwebhookhandler_settings">
                    <div class="mb-3">
                        <label for="webhook_url" class="form-label">رابط Webhook</label>
                        <input type="url" class="form-control" id="webhook_url" name="webhook_url" 
                               value="<?php echo htmlspecialchars($settings['webhook_url'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="timeout" class="form-label">مهلة الاتصال (بالثواني)</label>
                        <input type="number" class="form-control" id="timeout" name="timeout" 
                               value="<?php echo htmlspecialchars($settings['timeout'] ?? 30); ?>" min="1" max="300">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                   <?php echo ($settings['is_active'] ?? true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">تفعيل Webhook</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                </form>
            </div>
        </div>
        <?php
    }

    public function handleSettingsUpdate($data) {
        try {
            $webhookUrl = $data['webhook_url'] ?? '';
            $timeout = intval($data['timeout'] ?? 30);
            $isActive = isset($data['is_active']) ? 1 : 0;

            // Validate timeout
            if ($timeout < 1 || $timeout > 300) {
                throw new Exception("مهلة الاتصال يجب أن تكون بين 1 و 300 ثانية");
            }

            // First, check if settings exist
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM n8n_webhook_settings WHERE plugin_id = ?");
            $stmt->execute([$this->pluginId]);
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                // Insert new settings if none exist
                $stmt = $this->db->prepare("
                    INSERT INTO n8n_webhook_settings (plugin_id, webhook_url, timeout, is_active)
                    VALUES (?, ?, ?, ?)
                ");
                if (!$stmt->execute([$this->pluginId, $webhookUrl, $timeout, $isActive])) {
                    throw new Exception("فشل في إضافة الإعدادات");
                }
            } else {
                // Update existing settings
                $stmt = $this->db->prepare("
                    UPDATE n8n_webhook_settings 
                    SET webhook_url = ?, timeout = ?, is_active = ?
                    WHERE plugin_id = ?
                ");
                if (!$stmt->execute([$webhookUrl, $timeout, $isActive, $this->pluginId])) {
                    throw new Exception("فشل في تحديث الإعدادات");
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Error updating n8n webhook settings: " . $e->getMessage());
            throw $e;
        }
    }

    public function getSettings() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM n8n_webhook_settings WHERE plugin_id = ? LIMIT 1");
            $stmt->execute([$this->pluginId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'webhook_url' => '',
                'timeout' => 30,
                'is_active' => true
            ];
        } catch (Exception $e) {
            error_log("Error getting n8n webhook settings: " . $e->getMessage());
            return [
                'webhook_url' => '',
                'timeout' => 30,
                'is_active' => true
            ];
        }
    }
} 
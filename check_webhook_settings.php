<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Check N8nWebhookHandler settings
    $stmt = $db->query("
        SELECT s.*, p.name as plugin_name
        FROM n8n_webhook_settings s
        JOIN plugins p ON s.plugin_id = p.id
        WHERE p.name = 'N8nWebhookHandler'
    ");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "N8nWebhookHandler settings:\n";
    foreach ($settings as $setting) {
        echo "Plugin: " . $setting['plugin_name'] . " (ID: " . $setting['plugin_id'] . ")\n";
        echo "Webhook URL: " . $setting['webhook_url'] . "\n";
        echo "Is Active: " . ($setting['is_active'] ? 'Yes' : 'No') . "\n";
        echo "Timeout: " . $setting['timeout'] . " seconds\n";
        echo "Created At: " . $setting['created_at'] . "\n";
        echo "Updated At: " . $setting['updated_at'] . "\n";
        echo "-------------------\n";
    }
    
    if (empty($settings)) {
        echo "No N8nWebhookHandler settings found in database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} 
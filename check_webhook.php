<?php
require_once 'db_config.php';

$db = getDBConnection();

// Get webhook settings
$stmt = $db->query("SELECT * FROM n8n_webhook_settings");
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== Webhook Settings ===\n";
if (empty($settings)) {
    echo "No webhook settings found\n";
} else {
    foreach ($settings as $setting) {
        echo "\nPlugin ID: " . $setting['plugin_id'] . "\n";
        echo "Webhook URL: " . $setting['webhook_url'] . "\n";
        echo "Active: " . ($setting['is_active'] ? 'yes' : 'no') . "\n";
        echo "Timeout: " . $setting['timeout'] . " seconds\n";
        echo "Created: " . $setting['created_at'] . "\n";
        echo "Updated: " . $setting['updated_at'] . "\n";
        echo "------------------------\n";
    }
}

// Get plugin info
$stmt = $db->query("SELECT * FROM plugins WHERE name = 'N8nWebhookHandler'");
$plugin = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\n=== Plugin Info ===\n";
if ($plugin) {
    echo "Plugin ID: " . $plugin['id'] . "\n";
    echo "Name: " . $plugin['name'] . "\n";
    echo "Active: " . ($plugin['is_active'] ? 'yes' : 'no') . "\n";
    echo "Created: " . $plugin['created_at'] . "\n";
    echo "Updated: " . $plugin['updated_at'] . "\n";
} else {
    echo "Plugin not found\n";
} 
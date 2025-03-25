<?php
require_once 'db_config.php';
require_once 'app/plugins/PluginManager.php';

$db = getDBConnection();

// Get the plugin ID
$stmt = $db->prepare("SELECT id FROM plugins WHERE name = ?");
$stmt->execute(['N8nWebhookHandler']);
$pluginId = $stmt->fetchColumn();

if (!$pluginId) {
    echo "Plugin not found in database!\n";
    exit(1);
}

echo "Found plugin ID: " . $pluginId . "\n";

// Initialize the plugin manager
$pluginManager = PluginManager::getInstance();

// Deactivate the plugin
echo "Deactivating plugin...\n";
$pluginManager->deactivatePlugin('N8nWebhookHandler');

// Update database directly
$stmt = $db->prepare("UPDATE plugins SET is_active = 0 WHERE id = ?");
$stmt->execute([$pluginId]);

// Wait a moment
sleep(1);

// Update database directly
$stmt = $db->prepare("UPDATE plugins SET is_active = 1 WHERE id = ?");
$stmt->execute([$pluginId]);

// Activate the plugin
echo "Activating plugin...\n";
$result = $pluginManager->activatePlugin('N8nWebhookHandler');

if ($result) {
    echo "Plugin activated successfully!\n";
} else {
    echo "Failed to activate plugin!\n";
}

// Verify plugin status
$stmt = $db->prepare("SELECT is_active FROM plugins WHERE id = ?");
$stmt->execute([$pluginId]);
$isActive = $stmt->fetchColumn();

echo "Plugin is_active status: " . ($isActive ? "Active" : "Inactive") . "\n";

// Check webhook settings
$stmt = $db->prepare("SELECT * FROM n8n_webhook_settings WHERE plugin_id = ?");
$stmt->execute([$pluginId]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\nWebhook settings:\n";
print_r($settings); 
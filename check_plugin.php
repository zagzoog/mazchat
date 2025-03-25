<?php
require_once 'db_config.php';
require_once 'app/plugins/PluginManager.php';

// Initialize the plugin manager
$pluginManager = PluginManager::getInstance();

// Get all plugins and active plugins
$allPlugins = $pluginManager->getAllPlugins();
$activePlugins = $pluginManager->getActivePlugins();

echo "=== All Plugins ===\n";
foreach ($allPlugins as $name => $plugin) {
    echo "Plugin: $name\n";
    echo "Class: " . get_class($plugin) . "\n";
    echo "Hooks: " . print_r($plugin->getHooks(), true) . "\n";
    echo "-------------------\n";
}

echo "\n=== Active Plugins ===\n";
foreach ($activePlugins as $name => $plugin) {
    echo "Plugin: $name\n";
    echo "Class: " . get_class($plugin) . "\n";
    echo "Hooks: " . print_r($plugin->getHooks(), true) . "\n";
    echo "-------------------\n";
}

// Check database status
$db = getDBConnection();
$stmt = $db->query("SELECT * FROM plugins WHERE name = 'N8nWebhookHandler'");
$pluginRecord = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\n=== Database Status ===\n";
if ($pluginRecord) {
    echo "Plugin found in database:\n";
    print_r($pluginRecord);
} else {
    echo "Plugin not found in database\n";
}

// Check webhook settings
$stmt = $db->query("SELECT * FROM n8n_webhook_settings WHERE is_active = TRUE");
$webhookSettings = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\n=== Webhook Settings ===\n";
if ($webhookSettings) {
    echo "Active webhook settings found:\n";
    print_r($webhookSettings);
} else {
    echo "No active webhook settings found\n";
} 
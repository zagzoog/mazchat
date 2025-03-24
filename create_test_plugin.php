<?php
require_once 'db_config.php';
require_once 'app/models/Plugin.php';

try {
    $plugin = new Plugin();
    
    // Create a test plugin
    $testPlugin = $plugin->create([
        'name' => 'Code Formatter',
        'slug' => 'code-formatter',
        'description' => 'A plugin that automatically formats code in various programming languages.',
        'version' => '1.0.0',
        'author' => 'Chat App Team',
        'homepage_url' => 'https://example.com/plugins/code-formatter',
        'repository_url' => 'https://github.com/chatapp/code-formatter',
        'icon_url' => 'https://example.com/plugins/code-formatter/icon.png',
        'is_official' => true,
        'requires_version' => '1.0.0',
        'price' => 0.00,
        'is_featured' => true,
        'status' => 'published'
    ]);
    
    echo "Test plugin created successfully!\n";
    echo "Plugin details:\n";
    echo json_encode($testPlugin, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Error creating test plugin: " . $e->getMessage() . "\n";
    exit(1);
} 
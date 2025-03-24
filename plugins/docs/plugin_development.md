# Plugin Development Guide

## Table of Contents
1. [Introduction](#introduction)
2. [Plugin Structure](#plugin-structure)
3. [Creating a New Plugin](#creating-a-new-plugin)
4. [Plugin Class Requirements](#plugin-class-requirements)
5. [Example Plugin](#example-plugin)
6. [Plugin Settings](#plugin-settings)
7. [Best Practices](#best-practices)

## Introduction

This guide will help you create custom plugins for the chat system. Plugins allow you to extend the functionality of message processing by implementing custom handlers for different AI providers or services.

## Plugin Structure

A typical plugin should have the following structure:

```
plugins/
└── YourPluginName/
    ├── YourPluginName.php      # Main plugin class file
    ├── admin_handler.php       # Handles admin settings requests
    └── templates/
        └── settings.php        # Admin settings page template
```

## Creating a New Plugin

1. Create a new directory in the `plugins` folder with your plugin name
2. Create the main plugin class file
3. Implement required methods
4. Create settings template and handler
5. Register the plugin in the database

## Plugin Class Requirements

Your plugin class must extend the base `Plugin` class and implement these methods:

```php
class YourPluginName extends Plugin {
    public function __construct($pluginId = null) {
        // Initialize plugin properties
    }
    
    public function initialize() {
        // Register hooks and setup
    }
    
    public function activate($pluginId = null) {
        // Activation logic
    }
    
    public function deactivate($pluginId = null) {
        // Deactivation logic
    }
    
    public function processMessage($message) {
        // Message processing logic
    }
    
    public function getSettings() {
        // Get plugin settings
    }
}
```

## Example Plugin

Here's a simple example of an Echo Plugin that just echoes back the user's message:

```php
<?php

require_once dirname(__DIR__, 2) . "/app/plugins/Plugin.php";

class EchoPlugin extends Plugin {
    private $pluginId;
    
    public function __construct($pluginId = null) {
        $this->name = 'EchoPlugin';
        $this->version = '1.0.0';
        $this->description = 'A simple plugin that echoes back messages';
        $this->author = 'Your Name';
        $this->pluginId = $pluginId;
        
        parent::__construct();
    }
    
    public function initialize() {
        $this->registerHook('before_send_message', [$this, 'processMessage'], 10);
        $this->registerHook('admin_settings_page', [$this, 'addSettingsPage']);
    }
    
    public function activate($pluginId = null) {
        $this->pluginId = $pluginId;
        parent::activate($pluginId);
        
        // Create settings table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS echo_plugin_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                plugin_id INT UNSIGNED NOT NULL,
                prefix VARCHAR(50) DEFAULT 'Echo: ',
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE
            )
        ");
    }
    
    public function processMessage($message) {
        try {
            $settings = $this->getSettings();
            return $settings['prefix'] . $message['content'];
        } catch (Exception $e) {
            error_log("Error in EchoPlugin: " . $e->getMessage());
            return false;
        }
    }
    
    public function getSettings() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM echo_plugin_settings 
                WHERE plugin_id = ? 
                LIMIT 1
            ");
            $stmt->execute([$this->pluginId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'prefix' => 'Echo: ',
                'is_active' => true
            ];
        } catch (Exception $e) {
            error_log("Error getting EchoPlugin settings: " . $e->getMessage());
            return false;
        }
    }
}
```

## Plugin Settings

### Settings Template
Create a `settings.php` file in your plugin's templates directory:

```php
<?php if (!defined('ADMIN_PANEL')) die('Direct access not permitted'); ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Plugin Settings</h5>
    </div>
    <div class="card-body">
        <form id="plugin-settings-form" method="POST">
            <input type="hidden" name="action" value="plugin_settings">
            
            <!-- Add your settings fields here -->
            <div class="mb-3">
                <label for="setting_name" class="form-label">Setting Name</label>
                <input type="text" class="form-control" id="setting_name" 
                       name="setting_name" value="<?php echo htmlspecialchars($settings['setting_name']); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>
```

### Admin Handler
Create an `admin_handler.php` file to process settings updates:

```php
<?php
require_once dirname(__DIR__, 2) . '/db_config.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $db = getDBConnection();
        
        // Process your settings here
        $setting_name = filter_var($_POST['setting_name'], FILTER_SANITIZE_STRING);
        
        // Update settings in database
        $stmt = $db->prepare("UPDATE plugin_settings SET setting_name = ? WHERE id = ?");
        $stmt->execute([$setting_name, 1]);
        
        echo json_encode(['success' => true, 'message' => 'Settings updated']);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
```

## Best Practices

1. **Error Handling**: Always implement proper error handling and logging
2. **Security**: Sanitize all input and validate data
3. **Performance**: Optimize database queries and API calls
4. **Documentation**: Document your code and provide clear installation instructions
5. **Settings**: Make your plugin configurable through the admin interface
6. **Testing**: Test your plugin thoroughly before deployment

## Installation

1. Copy your plugin directory to the `plugins` folder
2. Register the plugin in the database:
```sql
INSERT INTO plugins (name, class_name, version, description, author, is_active)
VALUES ('YourPluginName', 'YourPluginClass', '1.0.0', 'Plugin description', 'Your Name', TRUE);
```
3. Activate the plugin through the admin interface

## Support

For additional support or questions, please contact the system administrator or refer to the main documentation. 
# Plugin Development Guide

This guide will help you develop plugins for the Chat Application using the N8NWebhookHandler as an example.

## Plugin Structure

A plugin should follow this basic structure:

```
YourPluginName/
├── YourPluginName.php      # Main plugin class
├── admin_handler.php       # Admin settings handler (optional)
└── js/                    # JavaScript files (optional)
    └── settings.js        # Settings page JavaScript (optional)
```

## Main Plugin Class

Your main plugin class should extend the base `Plugin` class. Here's the basic structure:

```php
<?php
require_once dirname(__DIR__, 2) . "/app/plugins/Plugin.php";

class YourPluginName extends Plugin {
    private $pluginId;

    public function __construct($pluginId = null) {
        $this->name = 'YourPluginName';
        $this->version = '1.0.0';
        $this->description = 'Your plugin description';
        $this->author = 'Your Name';
        $this->pluginId = $pluginId;
        
        parent::__construct();
    }
    
    public function initialize() {
        // Register your hooks here
        $this->registerHook('before_send_message', [$this, 'processMessage']);
        $this->registerHook('after_send_message', [$this, 'processMessage']);
        $this->registerHook('admin_settings_page', [$this, 'addSettingsPage']);
    }
    
    public function activate($pluginId = null) {
        if ($pluginId) {
            $this->pluginId = $pluginId;
        }
        
        if (!$this->pluginId) {
            throw new Exception("Plugin ID is required for activation");
        }
        
        parent::activate($this->pluginId);
        
        // Create necessary database tables
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS your_plugin_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                plugin_id INT UNSIGNED NOT NULL,
                setting1 VARCHAR(255),
                setting2 INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE
            )
        ");
        
        // Initialize the plugin
        $this->initialize();
    }
    
    public function deactivate($pluginId = null) {
        parent::deactivate($pluginId);
        // Clean up if necessary
    }
    
    public function processMessage($message) {
        // Process the message
        // Return true/false based on success
    }
    
    public function addSettingsPage() {
        // Add your settings page HTML here
    }
    
    public function handleSettingsUpdate($data) {
        // Handle settings update
    }
    
    public function getSettings() {
        // Return current settings
    }
}
```

## Available Hooks

The following hooks are available for plugins:

- `before_send_message`: Called before a message is sent
- `after_send_message`: Called after a message is sent
- `admin_settings_page`: Called when displaying plugin settings in admin panel
- `before_process_message`: Called before message processing
- `after_process_message`: Called after message processing

## Database Integration

Plugins can create their own database tables. Use the following pattern:

```php
$this->db->exec("
    CREATE TABLE IF NOT EXISTS your_plugin_table (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        plugin_id INT UNSIGNED NOT NULL,
        // Your columns here
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE
    )
");
```

## Settings Management

Plugins can have their own settings page in the admin panel. Here's how to implement it:

1. Add the settings page HTML in `addSettingsPage()`:
```php
public function addSettingsPage() {
    $settings = $this->getSettings();
    ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Your Plugin Settings</h5>
        </div>
        <div class="card-body">
            <form id="your-plugin-settings-form" method="POST">
                <input type="hidden" name="action" value="your_plugin_settings">
                <!-- Your form fields here -->
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
    <?php
}
```

2. Handle settings updates in `handleSettingsUpdate()`:
```php
public function handleSettingsUpdate($data) {
    try {
        // Validate and process settings
        // Update database
        return true;
    } catch (Exception $e) {
        error_log("Error updating settings: " . $e->getMessage());
        throw $e;
    }
}
```

## Testing Your Plugin

1. Create a test file in your plugin directory:
```php
<?php
require_once 'db_config.php';
require_once 'app/plugins/PluginManager.php';

// Initialize plugin manager
$pluginManager = PluginManager::getInstance();

// Get your plugin
$plugin = $pluginManager->getPlugin('YourPluginName');

// Test hooks
$message = [
    'conversation_id' => 1,
    'content' => 'Test message',
    'role' => 'user'
];

$plugin->executeHook('before_send_message', [$message]);
$plugin->executeHook('after_send_message', [$message]);
```

## Best Practices

1. Always use proper error handling and logging
2. Validate all input data
3. Use prepared statements for database queries
4. Follow the existing code style
5. Document your code thoroughly
6. Test your plugin thoroughly before deployment

## Deployment

1. Create a ZIP file of your plugin directory
2. Upload the ZIP file through the admin panel
3. Activate the plugin
4. Configure plugin settings

## Example Implementation

For a complete example, refer to the N8NWebhookHandler plugin in the `plugins/N8nWebhookHandler` directory. 
# Chat Application

A production-ready chat application with plugin support and easy updates.

## System Requirements

- PHP 8.2 or higher
- Apache 2.4 or higher
- MySQL 5.7 or higher
- mod_rewrite enabled
- PDO MySQL extension
- OpenSSL extension
- JSON extension

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/chat.git
cd chat
```

2. Run the installer:
```bash
sudo php installer/install.php
```

3. Follow the installation prompts to configure:
   - Database settings
   - Application URL
   - Admin account

4. Set proper permissions:
```bash
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 777 logs uploads
```

## Plugin System

The application supports plugins for extending functionality. Plugins can be installed in the `app/plugins` directory.

### Creating a Plugin

1. Create a new directory in `app/plugins` with your plugin name
2. Create a main PHP file with the same name as the directory
3. Extend the base `Plugin` class:

```php
class YourPlugin extends Plugin {
    public function __construct() {
        $this->name = 'YourPlugin';
        $this->version = '1.0.0';
        $this->description = 'Your plugin description';
        $this->author = 'Your Name';
        
        parent::__construct();
    }
    
    public function initialize() {
        // Register hooks and initialize your plugin
    }
    
    public function activate() {
        // Create necessary database tables or perform setup
    }
    
    public function deactivate() {
        // Clean up if necessary
    }
}
```

### Available Hooks

- `before_send_message`: Called before sending a message
- `after_send_message`: Called after sending a message
- `admin_settings_page`: Called when rendering admin settings
- `before_user_login`: Called before user login
- `after_user_login`: Called after user login

### Example Plugin

See the `LLMModelSelector` plugin in `app/plugins/LLMModelSelector` for an example implementation.

## Updates

The application includes an automatic update system:

1. Go to the admin panel
2. Click on "System Updates"
3. Click "Check for Updates"
4. If an update is available, click "Update Now"

The system will:
- Create a backup of your database
- Apply any new migrations
- Update plugins
- Update the application version

## Security

- All passwords are hashed using PHP's password_hash()
- API keys are stored securely in the database
- Input is sanitized and validated
- XSS protection is enabled
- CSRF protection is implemented

## Maintenance

### Backup

To create a manual backup:
```bash
php app/utils/backup.php
```

### Logs

Logs are stored in the `logs` directory. Rotate them regularly:
```bash
php app/utils/rotate_logs.php
```

## License

This project is licensed under the MIT License - see the LICENSE file for details. 
<?php
if (!defined('ADMIN_PANEL')) {
    die('Direct access not permitted');
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2>Plugin Developer Guide</h2>
            <p class="text-muted">Learn how to create and publish plugins for the chat application.</p>
        </div>
        <div class="col-auto">
            <a href="admin/plugin_marketplace.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Marketplace
            </a>
        </div>
    </div>

    <!-- Quick Start Guide -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Quick Start Guide</h5>
        </div>
        <div class="card-body">
            <ol>
                <li>Create a new directory in the <code>plugins</code> folder with your plugin name</li>
                <li>Create a main PHP file with the same name as your directory</li>
                <li>Extend the base <code>Plugin</code> class</li>
                <li>Implement required methods</li>
                <li>Test your plugin locally</li>
                <li>Package and submit for review</li>
            </ol>
        </div>
    </div>

    <!-- Plugin Structure -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Plugin Structure</h5>
        </div>
        <div class="card-body">
            <pre class="bg-light p-3 rounded">
plugins/
└── YourPlugin/
    ├── YourPlugin.php      # Main plugin file
    ├── config.php          # Plugin configuration
    ├── templates/          # HTML templates
    │   └── settings.php    # Settings page template
    ├── assets/            # CSS, JS, images
    │   ├── css/
    │   ├── js/
    │   └── img/
    └── README.md          # Plugin documentation</pre>
        </div>
    </div>

    <!-- Basic Plugin Template -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Basic Plugin Template</h5>
        </div>
        <div class="card-body">
            <pre class="bg-light p-3 rounded"><code><?php echo htmlspecialchars('<?php

require_once dirname(__DIR__) . \'/Plugin.php\';

class YourPlugin extends Plugin {
    public function __construct() {
        $this->name = \'YourPlugin\';
        $this->version = \'1.0.0\';
        $this->description = \'Your plugin description\';
        $this->author = \'Your Name\';
        
        parent::__construct();
    }
    
    public function initialize() {
        // Register hooks and initialize your plugin
        $this->registerHook(\'before_send_message\', [$this, \'yourMethod\']);
    }
    
    public function activate() {
        // Create necessary database tables or perform setup
        $db = getDBConnection();
        $db->exec("
            CREATE TABLE IF NOT EXISTS your_plugin_table (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                // Your table structure
            )
        ");
    }
    
    public function deactivate() {
        // Clean up if necessary
    }
    
    public function yourMethod($message) {
        // Your plugin logic here
    }
}'); ?></code></pre>
        </div>
    </div>

    <!-- Available Hooks -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Available Hooks</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Hook Name</th>
                            <th>Description</th>
                            <th>Parameters</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>before_send_message</code></td>
                            <td>Called before sending a message</td>
                            <td><code>$message</code> - Message array</td>
                        </tr>
                        <tr>
                            <td><code>after_send_message</code></td>
                            <td>Called after sending a message</td>
                            <td><code>$message</code> - Message array</td>
                        </tr>
                        <tr>
                            <td><code>admin_settings_page</code></td>
                            <td>Called when rendering admin settings</td>
                            <td>None</td>
                        </tr>
                        <tr>
                            <td><code>before_user_login</code></td>
                            <td>Called before user login</td>
                            <td><code>$username</code>, <code>$password</code></td>
                        </tr>
                        <tr>
                            <td><code>after_user_login</code></td>
                            <td>Called after user login</td>
                            <td><code>$user</code> - User array</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Plugin Submission Process -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Plugin Submission Process</h5>
        </div>
        <div class="card-body">
            <h6>1. Development</h6>
            <ul>
                <li>Develop and test your plugin locally</li>
                <li>Ensure all required methods are implemented</li>
                <li>Add proper error handling</li>
                <li>Document your plugin in README.md</li>
            </ul>

            <h6>2. Packaging</h6>
            <ul>
                <li>Create a ZIP file of your plugin directory</li>
                <li>Include all necessary files</li>
                <li>Exclude development files (.git, .DS_Store, etc.)</li>
            </ul>

            <h6>3. Submission</h6>
            <ul>
                <li>Go to the Plugin Marketplace</li>
                <li>Click "Add New Plugin"</li>
                <li>Upload your ZIP file</li>
                <li>Fill in the submission form</li>
            </ul>

            <h6>4. Review Process</h6>
            <ul>
                <li>Security review</li>
                <li>Code quality check</li>
                <li>Functionality testing</li>
                <li>Documentation review</li>
            </ul>
        </div>
    </div>

    <!-- Best Practices -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Best Practices</h5>
        </div>
        <div class="card-body">
            <h6>Security</h6>
            <ul>
                <li>Always validate and sanitize user input</li>
                <li>Use prepared statements for database queries</li>
                <li>Implement proper access controls</li>
                <li>Never store sensitive data in plain text</li>
            </ul>

            <h6>Performance</h6>
            <ul>
                <li>Optimize database queries</li>
                <li>Cache when appropriate</li>
                <li>Minimize file operations</li>
                <li>Use efficient algorithms</li>
            </ul>

            <h6>Code Quality</h6>
            <ul>
                <li>Follow PSR-12 coding standards</li>
                <li>Add proper documentation</li>
                <li>Include error handling</li>
                <li>Write unit tests</li>
            </ul>

            <h6>User Experience</h6>
            <ul>
                <li>Provide clear error messages</li>
                <li>Include helpful documentation</li>
                <li>Make settings intuitive</li>
                <li>Support multiple languages</li>
            </ul>
        </div>
    </div>

    <!-- Example Plugin -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Example Plugin</h5>
        </div>
        <div class="card-body">
            <p>Here's a complete example of a simple plugin that adds a custom welcome message:</p>
            <pre class="bg-light p-3 rounded"><code><?php echo htmlspecialchars('<?php

require_once dirname(__DIR__) . \'/Plugin.php\';

class WelcomeMessage extends Plugin {
    public function __construct() {
        $this->name = \'WelcomeMessage\';
        $this->version = \'1.0.0\';
        $this->description = \'Adds a custom welcome message to the chat\';
        $this->author = \'Your Name\';
        
        parent::__construct();
    }
    
    public function initialize() {
        $this->registerHook(\'before_send_message\', [$this, \'addWelcomeMessage\']);
    }
    
    public function activate() {
        // Create settings table
        $db = getDBConnection();
        $db->exec("
            CREATE TABLE IF NOT EXISTS welcome_message_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                message TEXT NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Insert default message
        $stmt = $db->query("SELECT COUNT(*) FROM welcome_message_settings");
        if ($stmt->fetchColumn() == 0) {
            $db->exec("
                INSERT INTO welcome_message_settings (message) 
                VALUES (\'Welcome to the chat!\')
            ");
        }
    }
    
    public function deactivate() {
        // Clean up if necessary
    }
    
    public function addWelcomeMessage($message) {
        if ($message[\'role\'] === \'user\') {
            $db = getDBConnection();
            $stmt = $db->query("
                SELECT message 
                FROM welcome_message_settings 
                WHERE is_active = TRUE 
                LIMIT 1
            ");
            $welcomeMessage = $stmt->fetchColumn();
            
            if ($welcomeMessage) {
                $message[\'content\'] = $welcomeMessage . \'\\n\' . $message[\'content\'];
            }
        }
    }
    
    public function addSettingsPage() {
        $db = getDBConnection();
        $stmt = $db->query("SELECT * FROM welcome_message_settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        include dirname(__FILE__) . \'/templates/settings.php\';
    }
}'); ?></code></pre>
        </div>
    </div>
</div> 
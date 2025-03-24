#!/usr/bin/env php
<?php

class Installer {
    private $requirements = [
        'php' => '8.2.0',
        'apache' => '2.4.0',
        'mysql' => '5.7.0'
    ];
    
    private $config = [];
    private $rootDir;
    
    public function __construct() {
        $this->rootDir = dirname(__DIR__);
    }
    
    public function run() {
        echo "Starting installation process...\n";
        
        // Check if running as root
        if (posix_getuid() !== 0) {
            die("This installer must be run as root\n");
        }
        
        // Check system requirements
        $this->checkRequirements();
        
        // Get configuration
        $this->getConfiguration();
        
        // Configure Apache
        $this->configureApache();
        
        // Configure MySQL
        $this->configureMySQL();
        
        // Set up application
        $this->setupApplication();
        
        echo "\nInstallation completed successfully!\n";
    }
    
    private function checkRequirements() {
        echo "\nChecking system requirements...\n";
        
        // Check PHP version
        if (version_compare(PHP_VERSION, $this->requirements['php'], '<')) {
            die("PHP version {$this->requirements['php']} or higher is required\n");
        }
        
        // Check Apache
        if (!function_exists('apache_get_version')) {
            die("Apache is not installed or not properly configured\n");
        }
        
        // Check MySQL
        if (!extension_loaded('mysqli')) {
            die("MySQL extension is not installed\n");
        }
        
        echo "All requirements met!\n";
    }
    
    private function getConfiguration() {
        echo "\nPlease provide the following configuration:\n";
        
        // Database configuration
        $this->config['db_host'] = readline("Database host (default: localhost): ") ?: 'localhost';
        $this->config['db_name'] = readline("Database name: ");
        $this->config['db_user'] = readline("Database user: ");
        $this->config['db_pass'] = readline("Database password: ");
        
        // Application configuration
        $this->config['app_url'] = readline("Application URL (e.g., http://localhost): ");
        $this->config['admin_email'] = readline("Admin email: ");
        $this->config['admin_password'] = readline("Admin password: ");
    }
    
    private function configureApache() {
        echo "\nConfiguring Apache...\n";
        
        // Create Apache virtual host configuration
        $vhost = <<<EOT
<VirtualHost *:80>
    ServerName {$this->config['app_url']}
    DocumentRoot {$this->rootDir}/public
    
    <Directory {$this->rootDir}/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/chat-error.log
    CustomLog \${APACHE_LOG_DIR}/chat-access.log combined
</VirtualHost>
EOT;
        
        // Write virtual host configuration
        file_put_contents('/etc/apache2/sites-available/chat.conf', $vhost);
        
        // Enable required Apache modules
        $modules = ['rewrite', 'headers', 'ssl'];
        foreach ($modules as $module) {
            exec("a2enmod {$module}");
        }
        
        // Enable the site
        exec('a2ensite chat');
        
        // Restart Apache
        exec('systemctl restart apache2');
        
        echo "Apache configuration completed!\n";
    }
    
    private function configureMySQL() {
        echo "\nConfiguring MySQL...\n";
        
        // Create database
        $pdo = new PDO(
            "mysql:host={$this->config['db_host']}",
            $this->config['db_user'],
            $this->config['db_pass']
        );
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS {$this->config['db_name']}");
        $pdo->exec("USE {$this->config['db_name']}");
        
        // Import database schema
        $schema = file_get_contents($this->rootDir . '/migrations/001_create_users_table.sql');
        $pdo->exec($schema);
        
        // Create admin user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([
            'admin',
            $this->config['admin_email'],
            password_hash($this->config['admin_password'], PASSWORD_DEFAULT)
        ]);
        
        echo "MySQL configuration completed!\n";
    }
    
    private function setupApplication() {
        echo "\nSetting up application...\n";
        
        // Create configuration file
        $config = [
            'db' => [
                'host' => $this->config['db_host'],
                'name' => $this->config['db_name'],
                'user' => $this->config['db_user'],
                'pass' => $this->config['db_pass']
            ],
            'app' => [
                'url' => $this->config['app_url'],
                'debug' => false
            ]
        ];
        
        file_put_contents(
            $this->rootDir . '/config/config.php',
            '<?php return ' . var_export($config, true) . ';'
        );
        
        // Create necessary directories
        $dirs = [
            $this->rootDir . '/logs',
            $this->rootDir . '/uploads',
            $this->rootDir . '/plugins',
            $this->rootDir . '/plugins/LLMModelSelector',
            $this->rootDir . '/plugins/LLMModelSelector/templates'
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            chmod($dir, 0755);
        }
        
        // Set proper permissions for specific directories
        chmod($this->rootDir . '/logs', 0777);
        chmod($this->rootDir . '/uploads', 0777);
        
        echo "Application setup completed!\n";
    }
}

// Run the installer
$installer = new Installer();
$installer->run(); 
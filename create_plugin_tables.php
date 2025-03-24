<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Drop existing tables if they exist
    $db->exec("DROP TABLE IF EXISTS plugin_reviews");
    $db->exec("DROP TABLE IF EXISTS user_plugins");
    $db->exec("DROP TABLE IF EXISTS marketplace_items");
    $db->exec("DROP TABLE IF EXISTS plugins");
    
    // Create plugins table
    $db->exec("
        CREATE TABLE plugins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            description TEXT,
            version VARCHAR(50) NOT NULL,
            author VARCHAR(255),
            homepage_url VARCHAR(255),
            repository_url VARCHAR(255),
            icon_url VARCHAR(255),
            is_official BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            requires_version VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_slug (slug)
        )
    ");
    
    // Create marketplace_items table
    $db->exec("
        CREATE TABLE marketplace_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            plugin_id INT UNSIGNED NOT NULL,
            price DECIMAL(10,2) DEFAULT 0.00,
            is_featured BOOLEAN DEFAULT FALSE,
            status ENUM('draft', 'pending', 'published', 'rejected') DEFAULT 'draft',
            downloads INT UNSIGNED DEFAULT 0,
            rating DECIMAL(3,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE
        )
    ");
    
    // Create user_plugins table for tracking installations
    $db->exec("
        CREATE TABLE user_plugins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            plugin_id INT UNSIGNED NOT NULL,
            is_enabled BOOLEAN DEFAULT TRUE,
            installed_version VARCHAR(50) NOT NULL,
            installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_plugin (user_id, plugin_id)
        )
    ");
    
    // Create plugin_reviews table
    $db->exec("
        CREATE TABLE plugin_reviews (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            plugin_id INT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
            review_text TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_review (user_id, plugin_id)
        )
    ");
    
    // Create indexes for better performance
    $db->exec("CREATE INDEX idx_marketplace_plugin ON marketplace_items(plugin_id)");
    $db->exec("CREATE INDEX idx_user_plugins_user ON user_plugins(user_id)");
    $db->exec("CREATE INDEX idx_user_plugins_plugin ON user_plugins(plugin_id)");
    $db->exec("CREATE INDEX idx_plugin_reviews_user ON plugin_reviews(user_id)");
    $db->exec("CREATE INDEX idx_plugin_reviews_plugin ON plugin_reviews(plugin_id)");
    
    echo "Plugin and marketplace tables created successfully!\n";
    
} catch (PDOException $e) {
    echo "Error creating plugin tables: " . $e->getMessage() . "\n";
    exit(1);
} 
<?php
require_once dirname(__DIR__) . '/db_config.php';

try {
    $db = getDBConnection();
    
    // Create direct_message_settings table
    $sql = "CREATE TABLE IF NOT EXISTS `direct_message_settings` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `plugin_id` INT UNSIGNED NOT NULL,
        `api_key` VARCHAR(255) NULL,
        `api_url` VARCHAR(255) NULL,
        `model` VARCHAR(100) NULL,
        `temperature` DECIMAL(3,2) DEFAULT 0.7,
        `max_tokens` INT DEFAULT 2048,
        `is_active` BOOLEAN DEFAULT TRUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`plugin_id`) REFERENCES `plugins`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($sql);
    echo "Migration completed successfully: direct_message_settings table created.\n";

    // Create user_plugin_preferences table
    $sql = "CREATE TABLE IF NOT EXISTS `user_plugin_preferences` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `plugin_id` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_user_preference` (`user_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`plugin_id`) REFERENCES `plugins`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($sql);
    echo "Migration completed successfully: user_plugin_preferences table created.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
} 
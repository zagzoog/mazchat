CREATE TABLE IF NOT EXISTS `direct_message_settings` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 
-- Plugin System Migration

-- Create plugins table
CREATE TABLE IF NOT EXISTS plugins (
    id int unsigned NOT NULL AUTO_INCREMENT,
    name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    slug varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    description text COLLATE utf8mb4_unicode_ci,
    version varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    author varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    homepage_url varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    repository_url varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    icon_url varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    is_official tinyint(1) DEFAULT '0',
    is_active tinyint(1) DEFAULT '1',
    requires_version varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add plugin reference to conversations
ALTER TABLE conversations ADD COLUMN plugin_id int unsigned DEFAULT NULL;
ALTER TABLE conversations ADD CONSTRAINT fk_conversation_plugin 
    FOREIGN KEY (plugin_id) REFERENCES plugins (id) ON DELETE SET NULL;

-- Create plugin settings table
CREATE TABLE IF NOT EXISTS plugin_settings (
    id int unsigned NOT NULL AUTO_INCREMENT,
    plugin_id int unsigned NOT NULL,
    setting_key varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    setting_value text COLLATE utf8mb4_unicode_ci,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_plugin_setting (plugin_id, setting_key),
    CONSTRAINT plugin_settings_ibfk_1 FOREIGN KEY (plugin_id) REFERENCES plugins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create plugin reviews table
CREATE TABLE IF NOT EXISTS plugin_reviews (
    id int unsigned NOT NULL AUTO_INCREMENT,
    user_id int unsigned NOT NULL,
    plugin_id int unsigned NOT NULL,
    rating tinyint unsigned NOT NULL,
    review_text text COLLATE utf8mb4_unicode_ci,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_review (user_id, plugin_id),
    KEY idx_plugin_reviews_user (user_id),
    KEY idx_plugin_reviews_plugin (plugin_id),
    CONSTRAINT plugin_reviews_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT plugin_reviews_ibfk_2 FOREIGN KEY (plugin_id) REFERENCES plugins (id) ON DELETE CASCADE,
    CONSTRAINT plugin_reviews_chk_1 CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create n8n webhook settings table
CREATE TABLE IF NOT EXISTS n8n_webhook_settings (
    id int unsigned NOT NULL AUTO_INCREMENT,
    plugin_id int unsigned NOT NULL,
    webhook_url varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    is_active tinyint(1) DEFAULT '1',
    timeout int DEFAULT '30',
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY plugin_id (plugin_id),
    CONSTRAINT n8n_webhook_settings_ibfk_1 FOREIGN KEY (plugin_id) REFERENCES plugins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create direct message settings table
CREATE TABLE IF NOT EXISTS direct_message_settings (
    id int unsigned NOT NULL AUTO_INCREMENT,
    plugin_id int unsigned NOT NULL,
    api_key varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    api_url varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    model varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    temperature decimal(3,2) DEFAULT '0.70',
    max_tokens int DEFAULT '2048',
    is_active tinyint(1) DEFAULT '1',
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY plugin_id (plugin_id),
    CONSTRAINT direct_message_settings_ibfk_1 FOREIGN KEY (plugin_id) REFERENCES plugins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 
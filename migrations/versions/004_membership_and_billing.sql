-- Membership and Billing Migration

-- Create memberships table
CREATE TABLE IF NOT EXISTS memberships (
    id int unsigned NOT NULL AUTO_INCREMENT,
    user_id int unsigned NOT NULL,
    type enum('free','basic','premium') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free',
    start_date date NOT NULL,
    end_date date NOT NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_memberships_user_date (user_id, end_date),
    CONSTRAINT memberships_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create marketplace items table
CREATE TABLE IF NOT EXISTS marketplace_items (
    id int unsigned NOT NULL AUTO_INCREMENT,
    plugin_id int unsigned NOT NULL,
    price decimal(10,2) DEFAULT '0.00',
    is_featured tinyint(1) DEFAULT '0',
    status enum('draft','pending','published','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
    downloads int unsigned DEFAULT '0',
    rating decimal(3,2) DEFAULT '0.00',
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_marketplace_plugin (plugin_id),
    CONSTRAINT marketplace_items_ibfk_1 FOREIGN KEY (plugin_id) REFERENCES plugins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create API keys table
CREATE TABLE IF NOT EXISTS api_keys (
    id int unsigned NOT NULL AUTO_INCREMENT,
    user_id int unsigned NOT NULL,
    name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    description text COLLATE utf8mb4_unicode_ci,
    api_key varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    is_active tinyint(1) DEFAULT '1',
    last_used_at timestamp NULL DEFAULT NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_api_key (api_key),
    KEY user_id (user_id),
    CONSTRAINT api_keys_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create LLM models table
CREATE TABLE IF NOT EXISTS llm_models (
    id int unsigned NOT NULL AUTO_INCREMENT,
    name varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    provider varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    api_key varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    is_active tinyint(1) DEFAULT '1',
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 
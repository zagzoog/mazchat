-- Create plugins table
CREATE TABLE IF NOT EXISTS plugins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    author VARCHAR(100),
    version VARCHAR(20),
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create plugin settings table
CREATE TABLE IF NOT EXISTS plugin_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id INT UNSIGNED NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plugin_setting (plugin_id, setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes
CREATE INDEX idx_plugins_name ON plugins(name);
CREATE INDEX idx_plugins_slug ON plugins(slug);
CREATE INDEX idx_plugins_active ON plugins(is_active);
CREATE INDEX idx_plugin_settings_plugin ON plugin_settings(plugin_id); 
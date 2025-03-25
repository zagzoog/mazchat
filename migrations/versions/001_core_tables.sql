-- Core Tables Migration

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id int unsigned NOT NULL AUTO_INCREMENT,
    username varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    email varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    password varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    full_name varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    role enum('user','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
    is_active tinyint(1) DEFAULT '1',
    email_verified_at timestamp NULL DEFAULT NULL,
    remember_token varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    last_login_at timestamp NULL DEFAULT NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_username (username),
    UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create system settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
    name varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    value text COLLATE utf8mb4_unicode_ci NOT NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_setting_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admin settings table
CREATE TABLE IF NOT EXISTS admin_settings (
    id int unsigned NOT NULL AUTO_INCREMENT,
    setting_key varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    setting_value text COLLATE utf8mb4_unicode_ci NOT NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin settings
INSERT INTO admin_settings (setting_key, setting_value) VALUES
('free_monthly_limit', '100'),
('silver_monthly_limit', '1000'),
('gold_monthly_limit', '999999'),
('silver_price', '9.99'),
('gold_price', '19.99');

-- Rollback:
DROP TABLE IF EXISTS admin_settings;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS users;
-- End Rollback: 
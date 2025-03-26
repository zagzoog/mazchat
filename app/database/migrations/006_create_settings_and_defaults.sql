-- Create admin settings table
CREATE TABLE IF NOT EXISTS admin_settings (
    id VARCHAR(36) PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create system settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
-- Password: Admin@123 (hashed with password_hash)
INSERT INTO users (id, username, password, email, role) VALUES 
(UUID(), 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin');

-- Insert default admin settings
INSERT INTO admin_settings (id, setting_key, setting_value) VALUES
(UUID(), 'max_conversations_free', '5'),
(UUID(), 'max_conversations_silver', '20'),
(UUID(), 'max_conversations_gold', 'unlimited'),
(UUID(), 'max_messages_per_conversation_free', '50'),
(UUID(), 'max_messages_per_conversation_silver', '200'),
(UUID(), 'max_messages_per_conversation_gold', 'unlimited'),
(UUID(), 'silver_plan_price', '9.99'),
(UUID(), 'gold_plan_price', '19.99');

-- Insert default system settings
INSERT INTO system_settings (id, name, value) VALUES
(UUID(), 'site_name', 'MyChat'),
(UUID(), 'site_description', 'AI Chat Application'),
(UUID(), 'maintenance_mode', 'false'),
(UUID(), 'registration_enabled', 'true'),
(UUID(), 'max_upload_size', '5242880'); 
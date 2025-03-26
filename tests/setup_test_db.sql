-- Drop database if exists to start fresh
DROP DATABASE IF EXISTS mychat_test;

-- Create test database
CREATE DATABASE mychat_test;

-- Grant privileges to mychat user
GRANT ALL PRIVILEGES ON mychat_test.* TO 'mychat'@'localhost';
GRANT ALL PRIVILEGES ON mychat_test.* TO 'mychat'@'%';
FLUSH PRIVILEGES;

-- Use the test database
USE mychat_test;

-- Create users table
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active'
);

-- Create conversations table
CREATE TABLE conversations (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create messages table
CREATE TABLE messages (
    id VARCHAR(36) PRIMARY KEY,
    conversation_id VARCHAR(36) NOT NULL,
    content TEXT NOT NULL,
    role ENUM('user', 'assistant', 'system') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
);

-- Create memberships table
CREATE TABLE IF NOT EXISTS memberships (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    type ENUM('free', 'premium', 'enterprise') NOT NULL DEFAULT 'free',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create plugins table
CREATE TABLE plugins (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    version VARCHAR(20) NOT NULL,
    author VARCHAR(100),
    status ENUM('active', 'inactive', 'deprecated') NOT NULL DEFAULT 'active',
    type ENUM('internal', 'external') NOT NULL DEFAULT 'internal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create plugin_settings table
CREATE TABLE plugin_settings (
    id VARCHAR(36) PRIMARY KEY,
    plugin_id VARCHAR(36) NOT NULL,
    setting_key VARCHAR(50) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plugin_setting (plugin_id, setting_key)
);

-- Create user_plugin_preferences table
CREATE TABLE user_plugin_preferences (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    plugin_id VARCHAR(36) NOT NULL,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_plugin (user_id, plugin_id)
);

-- Create plugin_reviews table
CREATE TABLE IF NOT EXISTS plugin_reviews (
    id VARCHAR(36) PRIMARY KEY,
    plugin_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_plugin_review (user_id, plugin_id)
);

-- Create marketplace_items table
CREATE TABLE IF NOT EXISTS marketplace_items (
    id VARCHAR(36) PRIMARY KEY,
    plugin_id VARCHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive', 'deprecated') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE
);

-- Create plugin_downloads table
CREATE TABLE IF NOT EXISTS plugin_downloads (
    id VARCHAR(36) PRIMARY KEY,
    plugin_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    version VARCHAR(20) NOT NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create usage_stats table
CREATE TABLE usage_stats (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    date DATE NOT NULL,
    total_messages INT NOT NULL DEFAULT 0,
    total_tokens INT NOT NULL DEFAULT 0,
    total_cost DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    message_type VARCHAR(50),
    question TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_conversations_user_id ON conversations(user_id);
CREATE INDEX idx_messages_conversation_id ON messages(conversation_id);
CREATE INDEX idx_messages_created_at ON messages(created_at);
CREATE INDEX idx_memberships_user_id ON memberships(user_id);
CREATE INDEX idx_memberships_type ON memberships(type);
CREATE INDEX idx_memberships_dates ON memberships(start_date, end_date);
CREATE INDEX idx_plugins_slug ON plugins(slug);
CREATE INDEX idx_plugins_status ON plugins(status);
CREATE INDEX idx_plugins_type ON plugins(type);
CREATE INDEX idx_plugin_settings_plugin_id ON plugin_settings(plugin_id);
CREATE INDEX idx_user_plugin_preferences_user_id ON user_plugin_preferences(user_id);
CREATE INDEX idx_user_plugin_preferences_plugin_id ON user_plugin_preferences(plugin_id);
CREATE INDEX idx_plugin_reviews_plugin_id ON plugin_reviews(plugin_id);
CREATE INDEX idx_plugin_reviews_user_id ON plugin_reviews(user_id);
CREATE INDEX idx_plugin_reviews_status ON plugin_reviews(status);
CREATE INDEX idx_marketplace_items_plugin_id ON marketplace_items(plugin_id);
CREATE INDEX idx_marketplace_items_status ON marketplace_items(status);
CREATE INDEX idx_plugin_downloads_plugin_id ON plugin_downloads(plugin_id);
CREATE INDEX idx_plugin_downloads_user_id ON plugin_downloads(user_id);
CREATE INDEX idx_usage_stats_user_id ON usage_stats(user_id);
CREATE INDEX idx_usage_stats_date ON usage_stats(date);
CREATE INDEX idx_usage_stats_message_type ON usage_stats(message_type);

-- Create test admin user
INSERT INTO users (id, username, email, password, role) VALUES 
(UUID(), 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Create test regular user
INSERT INTO users (id, username, email, password, role) VALUES 
(UUID(), 'testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'); 
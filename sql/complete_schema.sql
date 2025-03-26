-- Complete Database Schema
-- This file combines all database schema, tables, and optimizations

-- Create the database
CREATE DATABASE IF NOT EXISTS mychat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE mychat;

-- Core Application Tables
-- Users and Authentication
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    api_token VARCHAR(255) NULL,
    api_token_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plugin System Tables
CREATE TABLE IF NOT EXISTS plugins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    version VARCHAR(50) NOT NULL,
    author VARCHAR(255),
    homepage_url VARCHAR(255),
    repository_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversations and Messages
CREATE TABLE IF NOT EXISTS conversations (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    message_count INT DEFAULT 0,
    total_words INT DEFAULT 0,
    plugin_id INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id VARCHAR(36) PRIMARY KEY,
    conversation_id VARCHAR(36) NOT NULL,
    content TEXT NOT NULL,
    role ENUM('user', 'assistant') NOT NULL DEFAULT 'user',
    word_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membership and Usage Tracking
CREATE TABLE IF NOT EXISTS memberships (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    type ENUM('free', 'silver', 'gold') NOT NULL DEFAULT 'free',
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    auto_renew BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usage_stats (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    conversation_id VARCHAR(36) NOT NULL,
    message_id INT DEFAULT NULL,
    message_type VARCHAR(10) DEFAULT 'user',
    question TEXT NOT NULL,
    message_count INT NOT NULL DEFAULT 0,
    word_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Processing
CREATE TABLE IF NOT EXISTS payments (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    membership_type ENUM('silver', 'gold') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    paypal_transaction_id VARCHAR(255) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings
CREATE TABLE IF NOT EXISTS admin_settings (
    id VARCHAR(36) PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_settings (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plugin System Tables
CREATE TABLE IF NOT EXISTS plugin_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id INT UNSIGNED NOT NULL,
    setting_key VARCHAR(50) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plugin_setting (plugin_id, setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_plugin_preferences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    plugin_id INT UNSIGNED NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_plugin (user_id, plugin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plugin_reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    plugin_id INT UNSIGNED NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_review (user_id, plugin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketplace_items (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plugin_downloads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id INT UNSIGNED NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plugin-Specific Tables
CREATE TABLE IF NOT EXISTS n8n_webhook_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id INT UNSIGNED NOT NULL,
    webhook_url VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    timeout INT DEFAULT 30,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS direct_message_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id INT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL DEFAULT 'openai',
    model VARCHAR(50) NOT NULL DEFAULT 'gpt-3.5-turbo',
    api_key VARCHAR(255) NOT NULL,
    temperature FLOAT DEFAULT 0.7,
    max_tokens INT DEFAULT 2000,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS llm_models (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    provider VARCHAR(50) NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS test_marketplace_data (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id INT UNSIGNED NOT NULL,
    data_key VARCHAR(50) NOT NULL,
    data_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plugin_data (plugin_id, data_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS welcome_message_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration Tracking
CREATE TABLE IF NOT EXISTS applied_migrations (
    id VARCHAR(36) PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Data
-- Default admin user (password: Admin@123)
INSERT INTO users (id, username, password, email, role) VALUES 
(UUID(), 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin');

-- Default admin settings
INSERT INTO admin_settings (id, setting_key, setting_value) VALUES
(UUID(), 'max_conversations_free', '5'),
(UUID(), 'max_conversations_silver', '20'),
(UUID(), 'max_conversations_gold', 'unlimited'),
(UUID(), 'max_messages_per_conversation_free', '50'),
(UUID(), 'max_messages_per_conversation_silver', '200'),
(UUID(), 'max_messages_per_conversation_gold', 'unlimited'),
(UUID(), 'silver_plan_price', '9.99'),
(UUID(), 'gold_plan_price', '19.99');

-- Default system settings
INSERT INTO system_settings (id, name, value) VALUES
(UUID(), 'site_name', 'MyChat'),
(UUID(), 'site_description', 'AI Chat Application'),
(UUID(), 'maintenance_mode', 'false'),
(UUID(), 'registration_enabled', 'true'),
(UUID(), 'max_upload_size', '5242880');

-- Database Optimizations
-- Optimize table storage and performance
OPTIMIZE TABLE users, conversations, messages, plugins, plugin_settings, 
    user_plugin_preferences, plugin_reviews, marketplace_items, plugin_downloads,
    n8n_webhook_settings, direct_message_settings, llm_models, test_marketplace_data,
    welcome_message_settings, usage_stats, memberships, payments, admin_settings, system_settings;

-- Add composite indexes for frequently joined tables
CREATE INDEX idx_conversations_user_created ON conversations(user_id, created_at);
CREATE INDEX idx_messages_conversation_created ON messages(conversation_id, created_at);
CREATE INDEX idx_plugin_settings_key_value ON plugin_settings(plugin_id, setting_key);
CREATE INDEX idx_user_plugin_prefs_user_plugin ON user_plugin_preferences(user_id, plugin_id);
CREATE INDEX idx_plugin_reviews_plugin_rating ON plugin_reviews(plugin_id, rating);
CREATE INDEX idx_marketplace_items_status_rating ON marketplace_items(status, rating);
CREATE INDEX idx_usage_stats_user_conversation ON usage_stats(user_id, conversation_id);

-- Add full-text search indexes for text columns
ALTER TABLE messages ADD FULLTEXT INDEX idx_messages_content (content);
ALTER TABLE conversations ADD FULLTEXT INDEX idx_conversations_title (title);
ALTER TABLE plugins ADD FULLTEXT INDEX idx_plugins_description (description);

-- Add indexes for date-based queries
CREATE INDEX idx_users_last_login ON users(last_login);
CREATE INDEX idx_memberships_dates ON memberships(start_date, end_date);
CREATE INDEX idx_payments_dates ON payments(created_at, completed_at);
CREATE INDEX idx_plugin_downloads_date ON plugin_downloads(downloaded_at);

-- Add indexes for status-based queries
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_memberships_type ON memberships(type);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_marketplace_items_featured ON marketplace_items(is_featured);

-- Add indexes for foreign key relationships
CREATE INDEX idx_messages_user ON messages(conversation_id, role);
CREATE INDEX idx_usage_stats_user ON usage_stats(user_id);
CREATE INDEX idx_payments_user ON payments(user_id);
CREATE INDEX idx_memberships_user ON memberships(user_id);

-- Add indexes for sorting and filtering
CREATE INDEX idx_plugins_created ON plugins(created_at);
CREATE INDEX idx_plugin_reviews_created ON plugin_reviews(created_at);
CREATE INDEX idx_marketplace_items_created ON marketplace_items(created_at);
CREATE INDEX idx_conversations_updated ON conversations(updated_at);

-- Add indexes for unique constraints
CREATE UNIQUE INDEX idx_users_email ON users(email);
CREATE UNIQUE INDEX idx_users_username ON users(username);
CREATE UNIQUE INDEX idx_plugins_slug ON plugins(slug);
CREATE UNIQUE INDEX idx_plugin_settings_unique ON plugin_settings(plugin_id, setting_key);

-- Add indexes for range queries
CREATE INDEX idx_messages_date_range ON messages(created_at);
CREATE INDEX idx_conversations_date_range ON conversations(created_at);
CREATE INDEX idx_usage_stats_date_range ON usage_stats(created_at);

-- Add indexes for count queries
CREATE INDEX idx_conversations_message_count ON conversations(message_count);
CREATE INDEX idx_conversations_total_words ON conversations(total_words);
CREATE INDEX idx_messages_word_count ON messages(word_count);

-- Add indexes for plugin-specific queries
CREATE INDEX idx_n8n_webhook_url ON n8n_webhook_settings(webhook_url);
CREATE INDEX idx_direct_message_provider ON direct_message_settings(provider);
CREATE INDEX idx_direct_message_model ON direct_message_settings(model);
CREATE INDEX idx_llm_models_provider ON llm_models(provider);

-- Add indexes for marketplace queries
CREATE INDEX idx_marketplace_items_price ON marketplace_items(price);
CREATE INDEX idx_marketplace_items_downloads ON marketplace_items(downloads);
CREATE INDEX idx_plugin_reviews_rating ON plugin_reviews(rating);

-- Add indexes for user preferences
CREATE INDEX idx_user_plugin_prefs_enabled ON user_plugin_preferences(is_enabled);
CREATE INDEX idx_plugin_settings_active ON plugin_settings(plugin_id, setting_key);

-- Add indexes for system settings
CREATE INDEX idx_admin_settings_key ON admin_settings(setting_key);
CREATE INDEX idx_system_settings_name ON system_settings(name);

-- Add indexes for API and authentication
CREATE INDEX idx_users_api_token ON users(api_token);
CREATE INDEX idx_users_token_expires ON users(api_token_expires);

-- Add indexes for message processing
CREATE INDEX idx_messages_processing ON messages(conversation_id, role, created_at);
CREATE INDEX idx_conversations_processing ON conversations(user_id, plugin_id, created_at);

-- Add indexes for usage tracking
CREATE INDEX idx_usage_stats_tracking ON usage_stats(user_id, conversation_id, message_id);
CREATE INDEX idx_plugin_downloads_tracking ON plugin_downloads(plugin_id, user_id);

-- Add indexes for marketplace analytics
CREATE INDEX idx_marketplace_analytics ON marketplace_items(status, is_featured, rating, downloads);
CREATE INDEX idx_plugin_reviews_analytics ON plugin_reviews(plugin_id, rating, created_at);

-- Add indexes for user activity
CREATE INDEX idx_user_activity ON users(last_login, created_at);
CREATE INDEX idx_conversation_activity ON conversations(updated_at, message_count);
CREATE INDEX idx_message_activity ON messages(created_at, word_count); 
-- Default System Settings
INSERT INTO system_settings (id, name, value) VALUES
(UUID(), 'site_name', 'MyChat'),
(UUID(), 'site_description', 'A powerful chat application'),
(UUID(), 'maintenance_mode', 'false'),
(UUID(), 'registration_enabled', 'true'),
(UUID(), 'max_conversations', '100'),
(UUID(), 'max_messages_per_conversation', '1000');

-- Default Admin User (password: admin123)
INSERT INTO users (username, email, password, full_name, role, is_active, email_verified_at) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 1, NOW());

-- Default LLM Models
INSERT INTO llm_models (name, provider, api_key, is_active) VALUES
('GPT-3.5 Turbo', 'openai', 'your-api-key-here', 1),
('GPT-4', 'openai', 'your-api-key-here', 1),
('Claude 2', 'anthropic', 'your-api-key-here', 1);

-- Default Plugin Categories
INSERT INTO plugin_categories (name, description) VALUES
('Productivity', 'Tools to enhance your productivity'),
('Education', 'Educational and learning tools'),
('Entertainment', 'Fun and entertainment plugins'),
('Business', 'Business and professional tools'); 
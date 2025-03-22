-- Create memberships table
CREATE TABLE IF NOT EXISTS memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('free', 'basic', 'premium') NOT NULL DEFAULT 'free',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create usage_stats table
CREATE TABLE IF NOT EXISTS usage_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    conversation_id INT NOT NULL,
    question TEXT NOT NULL,
    word_count INT NOT NULL,
    topic VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
);

-- Create admin_settings table
CREATE TABLE IF NOT EXISTS admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO admin_settings (setting_key, setting_value) VALUES
('free_monthly_limit', '50'),
('basic_monthly_limit', '100'),
('premium_monthly_limit', '999999'),
('basic_price', '9.99'),
('premium_price', '19.99')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Create indexes for faster queries
CREATE INDEX IF NOT EXISTS idx_memberships_user_date ON memberships(user_id, end_date);
CREATE INDEX IF NOT EXISTS idx_usage_stats_user_date ON usage_stats(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_usage_stats_conversation ON usage_stats(conversation_id); 
-- Check and modify users table if needed
ALTER TABLE users MODIFY COLUMN id INT UNSIGNED AUTO_INCREMENT;

-- Drop existing tables (this will automatically drop foreign keys)
DROP TABLE IF EXISTS usage_stats;
DROP TABLE IF EXISTS memberships;

-- Recreate memberships table with correct data types
CREATE TABLE memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('free', 'basic', 'premium') NOT NULL DEFAULT 'free',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Recreate usage_stats table with correct data types
CREATE TABLE usage_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    conversation_id INT NOT NULL,
    question TEXT NOT NULL,
    word_count INT NOT NULL,
    topic VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
);

-- Recreate indexes
CREATE INDEX idx_memberships_user_date ON memberships(user_id, end_date);
CREATE INDEX idx_usage_stats_user_date ON usage_stats(user_id, created_at);
CREATE INDEX idx_usage_stats_conversation ON usage_stats(conversation_id); 
-- Drop existing backup table if it exists
DROP TABLE IF EXISTS usage_stats_backup;

-- Backup existing usage_stats data
CREATE TABLE usage_stats_backup AS SELECT * FROM usage_stats;

-- Drop messages table and usage_stats table
DROP TABLE IF EXISTS usage_stats;
DROP TABLE IF EXISTS messages;

-- Create messages table
CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    role ENUM('user', 'assistant') NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    word_count INT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
);

-- Recreate usage_stats table
CREATE TABLE usage_stats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    conversation_id INT UNSIGNED NOT NULL,
    message_id INT UNSIGNED NOT NULL,
    word_count INT UNSIGNED NOT NULL DEFAULT 0,
    topic VARCHAR(255),
    message_type ENUM('user', 'assistant') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
); 
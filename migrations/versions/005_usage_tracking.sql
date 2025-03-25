-- Usage Tracking Migration

-- Create usage stats table
CREATE TABLE IF NOT EXISTS usage_stats (
    id int unsigned NOT NULL AUTO_INCREMENT,
    user_id int unsigned NOT NULL,
    conversation_id int unsigned NOT NULL,
    question text COLLATE utf8mb4_unicode_ci NOT NULL,
    word_count int NOT NULL,
    topic varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    message_id int DEFAULT NULL,
    message_type varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'user',
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_usage_stats_user_date (user_id, created_at),
    KEY idx_usage_stats_conversation (conversation_id),
    CONSTRAINT usage_stats_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT usage_stats_ibfk_2 FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add conversation statistics columns
ALTER TABLE conversations 
    ADD COLUMN last_message_at timestamp NULL DEFAULT NULL,
    ADD COLUMN is_archived tinyint(1) DEFAULT '0',
    ADD COLUMN metadata JSON DEFAULT NULL;

-- Create indexes for better performance
CREATE INDEX idx_conversations_user_archived ON conversations(user_id, is_archived);
CREATE INDEX idx_conversations_last_message ON conversations(last_message_at); 
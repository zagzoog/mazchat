-- Chat Functionality Migration

-- Create conversations table
CREATE TABLE IF NOT EXISTS conversations (
    id int unsigned NOT NULL AUTO_INCREMENT,
    user_id int unsigned NOT NULL,
    title varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'محادثة جديدة',
    message_count int DEFAULT '0',
    total_words int DEFAULT '0',
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_conversations_user (user_id),
    CONSTRAINT conversations_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create messages table
CREATE TABLE IF NOT EXISTS messages (
    id int unsigned NOT NULL AUTO_INCREMENT,
    conversation_id int unsigned NOT NULL,
    role enum('user','assistant') COLLATE utf8mb4_unicode_ci NOT NULL,
    content text COLLATE utf8mb4_unicode_ci NOT NULL,
    word_count int DEFAULT '0',
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_conversation (conversation_id),
    KEY idx_conversation_role (conversation_id,role),
    CONSTRAINT messages_ibfk_1 FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 
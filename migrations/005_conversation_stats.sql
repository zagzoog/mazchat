-- Add conversation statistics columns
ALTER TABLE conversations
ADD COLUMN message_count INT NOT NULL DEFAULT 0,
ADD COLUMN last_message_at TIMESTAMP NULL,
ADD COLUMN is_archived BOOLEAN DEFAULT FALSE,
ADD COLUMN metadata JSON NULL;

-- Create index for better performance
CREATE INDEX idx_conversations_user_archived ON conversations(user_id, is_archived);
CREATE INDEX idx_conversations_last_message ON conversations(last_message_at); 
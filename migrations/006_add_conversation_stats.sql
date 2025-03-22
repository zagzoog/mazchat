-- Add columns to conversations table
ALTER TABLE conversations
ADD COLUMN message_count INT UNSIGNED NOT NULL DEFAULT 0,
ADD COLUMN total_words INT UNSIGNED NOT NULL DEFAULT 0; 
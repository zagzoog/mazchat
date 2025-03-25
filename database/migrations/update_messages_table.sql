-- Update messages table to use role instead of is_user
ALTER TABLE messages DROP COLUMN IF EXISTS is_user;
ALTER TABLE messages ADD COLUMN IF NOT EXISTS role ENUM('user', 'assistant') NOT NULL DEFAULT 'user';

-- Update any existing records if needed
UPDATE messages SET role = 'assistant' WHERE role IS NULL;

-- Add indexes for better performance
ALTER TABLE messages ADD INDEX idx_conversation_role (conversation_id, role); 
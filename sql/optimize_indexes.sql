-- Add indexes for messages table
ALTER TABLE messages ADD INDEX idx_conversation_role (conversation_id, role);
ALTER TABLE messages ADD INDEX idx_created_at (created_at);

-- Add indexes for conversations table
ALTER TABLE conversations ADD INDEX idx_user_updated (user_id, updated_at);
ALTER TABLE conversations ADD INDEX idx_created_at (created_at);

-- Add indexes for memberships table
ALTER TABLE memberships ADD INDEX idx_user_end_date (user_id, end_date);
ALTER TABLE memberships ADD INDEX idx_type (type);

-- Add indexes for usage_stats table
ALTER TABLE usage_stats ADD INDEX idx_user_message (user_id, message_id);
ALTER TABLE usage_stats ADD INDEX idx_conversation (conversation_id);
ALTER TABLE usage_stats ADD INDEX idx_created_at (created_at);

-- Add indexes for admin_settings table
ALTER TABLE admin_settings ADD INDEX idx_setting_key (setting_key);

-- Add indexes for users table
ALTER TABLE users ADD INDEX idx_username (username);
ALTER TABLE users ADD INDEX idx_email (email); 
-- First ensure the role column exists
ALTER TABLE messages ADD COLUMN IF NOT EXISTS role ENUM('user', 'assistant') NOT NULL DEFAULT 'user';

-- If is_user column exists, migrate the data and then remove it
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'messages'
    AND COLUMN_NAME = 'is_user'
);

SET @sql = IF(
    @column_exists > 0,
    'UPDATE messages SET role = IF(is_user = 1, "user", "assistant"); ALTER TABLE messages DROP COLUMN is_user;',
    'SELECT "is_user column does not exist, skipping migration" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for better performance
ALTER TABLE messages ADD INDEX IF NOT EXISTS idx_conversation_role (conversation_id, role); 
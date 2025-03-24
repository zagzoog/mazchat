-- Add API token columns to users table
ALTER TABLE users
ADD COLUMN api_token VARCHAR(255) NULL,
ADD COLUMN api_token_expires TIMESTAMP NULL; 
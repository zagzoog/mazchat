-- Create test database if it doesn't exist
CREATE DATABASE IF NOT EXISTS mychat_test;

-- Grant all privileges on test database to mychat user
GRANT ALL PRIVILEGES ON mychat_test.* TO 'mychat'@'localhost';
GRANT ALL PRIVILEGES ON mychat_test.* TO 'mychat'@'%';

-- Flush privileges to apply changes
FLUSH PRIVILEGES;
-- Check if tables exist and show their structure
SHOW TABLES;

-- Check users table
DESCRIBE users;

-- Check memberships table
DESCRIBE memberships;

-- Check usage_stats table
DESCRIBE usage_stats;

-- Check admin_settings table
DESCRIBE admin_settings;

-- Check if we have any data in these tables
SELECT COUNT(*) as user_count FROM users;
SELECT COUNT(*) as membership_count FROM memberships;
SELECT COUNT(*) as usage_stats_count FROM usage_stats;
SELECT COUNT(*) as admin_settings_count FROM admin_settings;

-- Check if we have any settings
SELECT * FROM admin_settings; 
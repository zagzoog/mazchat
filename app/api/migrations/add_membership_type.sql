ALTER TABLE users
ADD COLUMN membership_type ENUM('free', 'premium', 'enterprise') NOT NULL DEFAULT 'free'; 
-- Insert PayPal configuration settings
INSERT INTO admin_settings (setting_key, setting_value, created_at, updated_at) VALUES
('paypal_client_id', 'YOUR_PAYPAL_CLIENT_ID', NOW(), NOW()),
('paypal_secret', 'YOUR_PAYPAL_SECRET', NOW(), NOW()),
('paypal_mode', 'sandbox', NOW(), NOW())
ON DUPLICATE KEY UPDATE
setting_value = VALUES(setting_value),
updated_at = NOW(); 
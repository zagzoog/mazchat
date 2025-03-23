-- Insert PayPal settings if they don't exist
INSERT IGNORE INTO admin_settings (setting_key, setting_value) VALUES
('paypal_client_id', 'YOUR_PAYPAL_CLIENT_ID'),
('paypal_secret', 'YOUR_PAYPAL_SECRET'),
('paypal_mode', 'sandbox'); 
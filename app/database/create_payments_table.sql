-- Drop the table if it exists
DROP TABLE IF EXISTS payments;

-- Create payments table without foreign key
CREATE TABLE IF NOT EXISTS payments (
    id VARCHAR(50) PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    membership_type ENUM('basic', 'premium') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') NOT NULL,
    paypal_order_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL
);

-- Create index for faster queries
CREATE INDEX idx_payments_user_status ON payments(user_id, status);
CREATE INDEX idx_payments_created_at ON payments(created_at);

-- Add foreign key constraint
ALTER TABLE payments
ADD CONSTRAINT payments_ibfk_1
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE; 
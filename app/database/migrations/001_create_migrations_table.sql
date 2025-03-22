-- Create migrations table
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    batch INT NOT NULL DEFAULT 1
);

-- Create index for faster lookups
CREATE INDEX idx_migrations_name ON migrations(migration_name);

DROP INDEX idx_migrations_name ON migrations;

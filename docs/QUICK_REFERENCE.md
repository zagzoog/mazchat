# Quick Reference Guide

## Common Commands

### Database Management
```bash
# Run all pending migrations
php migrate.php up

# Rollback last migration
php migrate.php down

# Rollback last 3 migrations
php migrate.php down 3

# Show migration status
php migrate.php status

# Run database seeds
php migrate.php seed
```

### Development
```bash
# Create new migration
php migrate.php create add_new_feature

# Create new seed
php migrate.php create:seed add_test_data

# Refresh database
php migrate.php refresh

# Reset database
php migrate.php reset
```

### Validation & Reports
```bash
# Validate migrations
php migrate.php validate

# Generate report
php migrate.php report

# Check migration order
php migrate.php check:order

# Show statistics
php migrate.php stats
```

## Common Operations

### Database Backup
```bash
# Backup database
mysqldump -u user -p database > backup.sql

# Backup specific tables
mysqldump -u user -p database table1 table2 > backup.sql

# Backup with compression
mysqldump -u user -p database | gzip > backup.sql.gz
```

### File Management
```bash
# Backup config files
cp config/app_config.php config/app_config.php.backup
cp config/db_config.php config/db_config.php.backup

# Restore config files
cp config/app_config.php.backup config/app_config.php
cp config/db_config.php.backup config/db_config.php

# Clear cache
php clear_cache.php
```

### Version Control
```bash
# Create upgrade branch
git checkout -b upgrade/v1.0.0

# Create release branch
git checkout -b release/v1.0.0

# Create version tag
git tag -a v1.0.0 -m "Release v1.0.0"
```

## Common SQL Operations

### Table Management
```sql
-- Create table
CREATE TABLE table_name (
    id int unsigned NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add column
ALTER TABLE table_name ADD COLUMN new_column varchar(255) NOT NULL;

-- Add index
ALTER TABLE table_name ADD INDEX idx_name (name);

-- Add foreign key
ALTER TABLE table_name 
ADD CONSTRAINT fk_name 
FOREIGN KEY (foreign_id) 
REFERENCES other_table(id);
```

### Data Management
```sql
-- Insert data
INSERT INTO table_name (name, value) VALUES ('key', 'value');

-- Update data
UPDATE table_name SET value = 'new_value' WHERE name = 'key';

-- Delete data
DELETE FROM table_name WHERE id = 1;

-- Truncate table
TRUNCATE TABLE table_name;
```

## Common Configuration

### Database Configuration
```php
// config/db_config.php
$dbConfig = [
    'host' => 'localhost',
    'name' => 'database_name',
    'user' => 'username',
    'pass' => 'password',
    'charset' => 'utf8mb4'
];
```

### Application Configuration
```php
// config/app_config.php
$appConfig = [
    'version' => '1.0.0',
    'debug' => false,
    'timezone' => 'UTC',
    'maintenance_mode' => false
];
```

## Common File Structure

```
project/
├── config/
│   ├── app_config.php
│   └── db_config.php
├── migrations/
│   ├── versions/
│   └── seeds/
├── docs/
│   ├── CLI.md
│   ├── DEVELOPER_UPGRADE.md
│   └── USER_UPGRADE.md
└── src/
    ├── Controllers/
    ├── Models/
    └── Views/
```

## Common Issues & Solutions

### Database Connection
```bash
# Check database connection
php migrate.php status

# Verify credentials
mysql -u user -p database
```

### File Permissions
```bash
# Set directory permissions
chmod -R 755 /path/to/project

# Set file permissions
chmod 644 config/*.php

# Set ownership
chown -R www-data:www-data /path/to/project
```

### Migration Issues
```bash
# Check migration status
php migrate.php status

# Validate migrations
php migrate.php validate

# Generate report
php migrate.php report
```

## Support Resources

- [CLI Documentation](CLI.md)
- [Developer Upgrade Guide](DEVELOPER_UPGRADE.md)
- [User Upgrade Guide](USER_UPGRADE.md)
- [Troubleshooting Guide](TROUBLESHOOTING.md)
- [Feature Documentation](FEATURES.md) 
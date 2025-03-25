# Troubleshooting Guide

This guide provides solutions for common issues encountered during application development, upgrades, and maintenance.

## Database Issues

### Connection Problems

1. **Cannot Connect to Database**
   ```bash
   # Check database configuration
   cat config/db_config.php
   
   # Test database connection
   php migrate.php status
   
   # Verify MySQL service
   systemctl status mysql
   ```

   **Solutions**:
   - Verify database credentials
   - Check MySQL service status
   - Ensure database exists
   - Verify user permissions

2. **Access Denied**
   ```sql
   -- Check user permissions
   SHOW GRANTS FOR 'username'@'localhost';
   
   -- Grant necessary permissions
   GRANT ALL PRIVILEGES ON database_name.* TO 'username'@'localhost';
   FLUSH PRIVILEGES;
   ```

### Migration Issues

1. **Migration Fails**
   ```bash
   # Check migration status
   php migrate.php status
   
   # Validate migrations
   php migrate.php validate
   
   # Generate report
   php migrate.php report
   ```

   **Solutions**:
   - Review SQL syntax
   - Check dependencies
   - Verify foreign key constraints
   - Check transaction handling

2. **Rollback Fails**
   ```bash
   # Check applied migrations
   php migrate.php status
   
   # Try manual rollback
   php migrate.php down
   
   # Reset if needed
   php migrate.php reset
   ```

   **Solutions**:
   - Review rollback SQL
   - Check transaction state
   - Verify table existence
   - Check foreign key constraints

## File System Issues

### Permission Problems

1. **Cannot Write to Directory**
   ```bash
   # Check directory permissions
   ls -la /path/to/directory
   
   # Fix permissions
   chmod -R 755 /path/to/directory
   chown -R www-data:www-data /path/to/directory
   ```

2. **Cannot Read Files**
   ```bash
   # Check file permissions
   ls -la config/*.php
   
   # Fix file permissions
   chmod 644 config/*.php
   chown www-data:www-data config/*.php
   ```

### File Corruption

1. **Corrupted Migration Files**
   ```bash
   # Validate migrations
   php migrate.php validate
   
   # Check file integrity
   md5sum migrations/versions/*.sql
   ```

   **Solutions**:
   - Restore from backup
   - Recreate migration file
   - Validate SQL syntax
   - Check file encoding

## Configuration Issues

### Application Configuration

1. **Invalid Configuration**
   ```php
   // Check config file syntax
   php -l config/app_config.php
   
   // Verify required settings
   cat config/app_config.php
   ```

   **Solutions**:
   - Fix PHP syntax
   - Add missing settings
   - Update deprecated settings
   - Verify file permissions

2. **Environment Issues**
   ```bash
   # Check PHP version
   php -v
   
   # Check PHP extensions
   php -m
   
   # Check environment variables
   env | grep APP_
   ```

## Performance Issues

### Database Performance

1. **Slow Migrations**
   ```sql
   -- Check table sizes
   SELECT table_name, data_length/1024/1024 as size_mb 
   FROM information_schema.tables 
   WHERE table_schema = DATABASE();
   
   -- Check indexes
   SHOW INDEX FROM table_name;
   ```

   **Solutions**:
   - Optimize table structure
   - Add appropriate indexes
   - Split large migrations
   - Use transactions

2. **High Memory Usage**
   ```bash
   # Check PHP memory limit
   php -i | grep memory_limit
   
   # Monitor memory usage
   top -p $(pgrep -f "php migrate.php")
   ```

   **Solutions**:
   - Increase PHP memory limit
   - Optimize SQL queries
   - Split large operations
   - Use pagination

## Version Control Issues

### Git Problems

1. **Merge Conflicts**
   ```bash
   # Check status
   git status
   
   # Resolve conflicts
   git add .
   git commit -m "Resolve conflicts"
   ```

2. **Branch Issues**
   ```bash
   # List branches
   git branch
   
   # Switch branch
   git checkout branch_name
   
   # Create new branch
   git checkout -b new_branch
   ```

## Common Error Messages

### Database Errors

1. **"Table already exists"**
   ```sql
   -- Check if table exists
   SHOW TABLES LIKE 'table_name';
   
   -- Drop table if needed
   DROP TABLE IF EXISTS table_name;
   ```

2. **"Foreign key constraint fails"**
   ```sql
   -- Check foreign key constraints
   SHOW CREATE TABLE table_name;
   
   -- Disable foreign key checks
   SET FOREIGN_KEY_CHECKS = 0;
   ```

### PHP Errors

1. **"Class not found"**
   ```bash
   # Check autoloader
   composer dump-autoload
   
   # Verify file location
   find . -name "ClassName.php"
   ```

2. **"Permission denied"**
   ```bash
   # Check file permissions
   ls -la file.php
   
   # Fix permissions
   chmod 644 file.php
   chown www-data:www-data file.php
   ```

## Recovery Procedures

### Database Recovery

1. **Restore from Backup**
   ```bash
   # Restore database
   mysql -u user -p database < backup.sql
   
   # Verify restore
   php migrate.php status
   ```

2. **Reset Database**
   ```bash
   # Reset database
   php migrate.php reset
   
   # Run migrations
   php migrate.php up
   
   # Run seeds
   php migrate.php seed
   ```

### File Recovery

1. **Restore Configuration**
   ```bash
   # Restore config files
   cp config/app_config.php.backup config/app_config.php
   cp config/db_config.php.backup config/db_config.php
   ```

2. **Restore Migrations**
   ```bash
   # Restore migration files
   git checkout migrations/versions/
   
   # Validate migrations
   php migrate.php validate
   ```

## Support Resources

- [CLI Documentation](CLI.md)
- [Developer Upgrade Guide](DEVELOPER_UPGRADE.md)
- [User Upgrade Guide](USER_UPGRADE.md)
- [Quick Reference Guide](QUICK_REFERENCE.md)
- [Feature Documentation](FEATURES.md) 
# Developer Upgrade Guide

This guide explains how to upgrade the application's code and database structure when releasing new versions.

## Version Control

1. Create a new branch for the upgrade:
```bash
git checkout -b upgrade/v1.0.0
```

2. Update version numbers:
   - Update version in `config/app_config.php`
   - Update version in `composer.json` (if using Composer)
   - Update version in any other configuration files

## Database Migrations

### Creating New Migrations

1. Create a new migration file:
```bash
php migrate.php create add_new_feature
```

2. The migration file will be created with this template:
```sql
-- Migration: Add New Feature

-- Up:
-- Add your SQL here

-- Rollback:
-- Add rollback SQL here
-- End Rollback:
```

3. Add dependencies if needed:
```sql
-- Depends on: 001_core_tables.sql, 002_chat_tables.sql
```

### Migration Best Practices

1. **Naming Conventions**:
   - Use descriptive names: `add_user_preferences.sql`
   - Include version number: `001_add_user_preferences.sql`
   - Use timestamps for ordering: `20240315000000_add_user_preferences.sql`

2. **SQL Guidelines**:
   - Always include rollback SQL
   - Use transactions where appropriate
   - Include foreign key constraints
   - Add appropriate indexes
   - Use proper data types and lengths

3. **Testing Migrations**:
```bash
# Validate migrations
php migrate.php validate

# Test migrations
php migrate.php refresh

# Check for issues
php migrate.php report
```

### Example Migration

```sql
-- Migration: Add User Preferences
-- Depends on: 001_core_tables.sql

-- Up:
CREATE TABLE user_preferences (
    id int unsigned NOT NULL AUTO_INCREMENT,
    user_id int unsigned NOT NULL,
    theme varchar(20) DEFAULT 'light',
    notifications_enabled tinyint(1) DEFAULT '1',
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_preferences (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rollback:
DROP TABLE IF EXISTS user_preferences;
-- End Rollback:
```

## Code Updates

### PHP Files

1. **Class Updates**:
   - Maintain backward compatibility
   - Use deprecation notices for old methods
   - Document breaking changes

2. **Configuration Updates**:
   - Add new configuration options
   - Document default values
   - Include migration scripts for config changes

### Frontend Updates

1. **Asset Management**:
   - Update version numbers in asset files
   - Clear cache after updates
   - Update dependencies

2. **JavaScript Updates**:
   - Use feature detection
   - Maintain backward compatibility
   - Document API changes

## Testing

1. **Database Testing**:
```bash
# Reset database
php migrate.php reset

# Run migrations
php migrate.php up

# Run seeds
php migrate.php seed

# Validate
php migrate.php validate
```

2. **Code Testing**:
   - Run unit tests
   - Run integration tests
   - Test upgrade process

## Release Process

1. **Pre-release Checklist**:
   - [ ] Update version numbers
   - [ ] Create migration files
   - [ ] Update documentation
   - [ ] Run all tests
   - [ ] Test upgrade process
   - [ ] Create release notes

2. **Release Steps**:
   ```bash
   # Create release branch
   git checkout -b release/v1.0.0
   
   # Update version numbers
   # Create migration files
   # Update documentation
   
   # Commit changes
   git add .
   git commit -m "Release v1.0.0"
   
   # Create tag
   git tag -a v1.0.0 -m "Release v1.0.0"
   
   # Push changes
   git push origin release/v1.0.0
   git push origin v1.0.0
   ```

3. **Post-release Tasks**:
   - Update changelog
   - Update documentation
   - Monitor for issues
   - Plan next release

## Troubleshooting

### Common Issues

1. **Migration Failures**:
   - Check SQL syntax
   - Verify dependencies
   - Check foreign key constraints
   - Review transaction handling

2. **Version Conflicts**:
   - Check version numbers
   - Verify file permissions
   - Clear cache
   - Check dependencies

### Debug Tools

1. **Migration Debugging**:
```bash
# Show migration status
php migrate.php status

# Generate detailed report
php migrate.php report

# Check migration order
php migrate.php check:order
```

2. **Database Debugging**:
   - Check database size
   - Verify table structures
   - Review foreign keys
   - Check indexes

## Support

For additional support:
- Review the [CLI Documentation](CLI.md)
- Check the [User Upgrade Guide](USER_UPGRADE.md)
- Contact the development team
- Review the issue tracker 
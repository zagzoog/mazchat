# CLI Documentation

This guide documents all available CLI commands for managing the application's database migrations and maintenance.

## Basic Commands

### Run Migrations

```bash
php migrate.php up
```

Runs all pending migrations in order. This command will:
- Check for pending migrations
- Display the list of migrations to be applied
- Execute each migration in a transaction
- Show progress and results

### Rollback Migrations

```bash
php migrate.php down [n]
```

Rolls back the last `n` migrations (default: 1). This command will:
- Show which migrations will be rolled back
- Execute rollback SQL in reverse order
- Remove migration records
- Show progress and results

### Show Status

```bash
php migrate.php status
```

Displays the current migration status, including:
- List of applied migrations with timestamps
- List of pending migrations
- Current database size

### Run Seeds

```bash
php migrate.php seed
```

Runs all database seed files to populate the database with initial or test data.

## Development Commands

### Create Migration

```bash
php migrate.php create name
```

Creates a new migration file with a template:
```sql
-- Migration: Name

-- Up:
-- Add your SQL here

-- Rollback:
-- Add rollback SQL here
-- End Rollback:
```

### Create Seed

```bash
php migrate.php create:seed name
```

Creates a new seed file with a template:
```sql
-- Seed: Name

-- Add your seed data here
```

### Refresh Database

```bash
php migrate.php refresh
```

Resets the database by:
1. Rolling back all migrations
2. Running all migrations again
3. Running all seeds

### Reset Database

```bash
php migrate.php reset
```

Completely resets the database:
- Drops all tables
- Recreates migrations table
- Runs all migrations
- Runs all seeds

⚠️ **Warning**: This command requires confirmation as it will delete all data.

## Validation Commands

### Validate Migrations

```bash
php migrate.php validate
```

Validates all migration files for:
- Required sections (Up, Rollback)
- SQL syntax
- Dependencies
- File structure

### Generate Report

```bash
php migrate.php report
```

Generates a detailed report including:
- Migration statistics
- Database size
- Validation results
- Dependencies
- Applied/pending migrations

### Check Migration Order

```bash
php migrate.php check:order
```

Verifies that migration files are in correct chronological order:
- Checks timestamps
- Identifies out-of-order files
- Suggests corrections

### Show Statistics

```bash
php migrate.php stats
```

Displays migration statistics:
- Total files
- Total size
- Average size
- Largest file
- Migration progress

## Command Options

### Global Options

All commands support these options:
- `--help`: Show command help
- `--version`: Show version information
- `--debug`: Enable debug output
- `--quiet`: Suppress output

### Environment Options

```bash
php migrate.php --env=production up
php migrate.php --env=development seed
```

Specify the environment for the command:
- `production`: Production environment
- `development`: Development environment
- `testing`: Testing environment

## Examples

### Basic Usage

```bash
# Run migrations
php migrate.php up

# Rollback last 3 migrations
php migrate.php down 3

# Show status
php migrate.php status

# Run seeds
php migrate.php seed
```

### Development Workflow

```bash
# Create new migration
php migrate.php create add_user_preferences

# Create new seed
php migrate.php create:seed add_test_users

# Validate migrations
php migrate.php validate

# Generate report
php migrate.php report
```

### Database Management

```bash
# Refresh database
php migrate.php refresh

# Reset database (with confirmation)
php migrate.php reset

# Check migration order
php migrate.php check:order

# Show statistics
php migrate.php stats
```

## Error Handling

### Common Errors

1. **Database Connection**:
   - Check database credentials
   - Verify database exists
   - Check user permissions

2. **File Permissions**:
   - Check directory permissions
   - Verify file ownership
   - Check PHP user permissions

3. **SQL Errors**:
   - Review SQL syntax
   - Check foreign key constraints
   - Verify table existence

### Recovery

1. **Rollback on Error**:
   ```bash
   # Rollback failed migration
   php migrate.php down
   ```

2. **Reset Database**:
   ```bash
   # Reset to clean state
   php migrate.php reset
   ```

3. **Validate Fixes**:
   ```bash
   # Check for issues
   php migrate.php validate
   ```

## Best Practices

1. **Regular Maintenance**:
   - Run status checks regularly
   - Validate migrations before deployment
   - Keep migrations in order
   - Monitor database size

2. **Development Workflow**:
   - Create migrations for all changes
   - Include rollback SQL
   - Test migrations thoroughly
   - Document changes

3. **Production Deployment**:
   - Backup before migrations
   - Test in staging first
   - Monitor for errors
   - Keep logs

## Support

For additional help:
- Review the [Developer Upgrade Guide](DEVELOPER_UPGRADE.md)
- Check the [User Upgrade Guide](USER_UPGRADE.md)
- Contact technical support
- Visit the support forum 
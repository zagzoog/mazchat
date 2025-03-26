# Chat Application Test Suite

This directory contains the test suite for the chat application. The tests are organized into unit tests and integration tests.

## Directory Structure

```
tests/
├── bootstrap.php          # Test environment setup
├── setup_test_db.php      # Test database setup script
├── unit/                  # Unit tests
│   ├── UserTest.php
│   ├── ConversationTest.php
│   ├── MessageTest.php
│   ├── PluginTest.php
│   └── UsageStatsTest.php
├── integration/          # Integration tests
└── fixtures/            # Test data fixtures
```

## Prerequisites

1. PHP 7.4 or higher
2. PHPUnit 10.0 or higher
3. MySQL 5.7 or higher
4. Composer (for dependency management)

## Setup

1. Install PHPUnit:
```bash
composer require --dev phpunit/phpunit ^10.0
```

2. Set up the test database:
```bash
php tests/setup_test_db.php
```

## Running Tests

### Run all tests:
```bash
./vendor/bin/phpunit
```

### Run specific test file:
```bash
./vendor/bin/phpunit tests/unit/UserTest.php
```

### Run specific test method:
```bash
./vendor/bin/phpunit --filter testUserCreation tests/unit/UserTest.php
```

## Test Coverage

To generate test coverage reports:

```bash
./vendor/bin/phpunit --coverage-html coverage
```

The coverage report will be generated in the `coverage` directory.

## Test Categories

### Unit Tests

- `UserTest.php`: Tests user-related functionality
  - User creation
  - User login
  - User membership
  - User role validation

- `ConversationTest.php`: Tests conversation functionality
  - Conversation creation
  - Message management
  - Conversation deletion
  - User access control

- `MessageTest.php`: Tests message functionality
  - Message creation
  - Message ordering
  - Message updates
  - Message deletion

- `PluginTest.php`: Tests plugin system
  - Plugin creation
  - Plugin settings
  - User preferences
  - Plugin reviews

- `UsageStatsTest.php`: Tests usage statistics
  - Stats creation
  - Stats aggregation
  - Stats updates
  - User-specific stats

### Integration Tests

Integration tests will be added to test the interaction between different components of the system.

## Writing New Tests

1. Create a new test file in the appropriate directory (unit/ or integration/)
2. Extend the `TestCase` class
3. Implement test methods with the `test` prefix
4. Use the helper functions in `bootstrap.php` for common operations
5. Clean up test data in `tearDown()`

Example:
```php
class NewFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        cleanupTestData();
    }

    protected function tearDown(): void
    {
        cleanupTestData();
        parent::tearDown();
    }

    public function testNewFeature()
    {
        // Test implementation
    }
}
```

## Best Practices

1. Each test should be independent
2. Clean up test data after each test
3. Use meaningful test names
4. Test both success and failure cases
5. Test edge cases and boundary conditions
6. Keep tests focused and simple
7. Use assertions that provide clear failure messages 
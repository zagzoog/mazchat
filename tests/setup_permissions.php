<?php
// This script needs to be run as a MySQL user with GRANT privileges
// You can run it using: mysql -u root -p < tests/setup_permissions.sql

// Database root credentials
define('DB_ROOT_USER', 'root');
define('DB_ROOT_PASS', '');

// Create SQL commands
$sql = <<<SQL
CREATE DATABASE IF NOT EXISTS mychat_test;
GRANT ALL PRIVILEGES ON mychat_test.* TO 'mychat'@'localhost';
GRANT ALL PRIVILEGES ON mychat_test.* TO 'mychat'@'%';
FLUSH PRIVILEGES;
SQL;

// Write SQL to a temporary file
$tempFile = __DIR__ . '/temp_permissions.sql';
file_put_contents($tempFile, $sql);

// Execute MySQL commands
$command = sprintf(
    'mysql -u%s -p%s < %s',
    DB_ROOT_USER,
    DB_ROOT_PASS,
    $tempFile
);

exec($command, $output, $returnVar);

// Clean up temporary file
unlink($tempFile);

if ($returnVar === 0) {
    echo "Database permissions set up successfully.\n";
} else {
    echo "Error setting up database permissions.\n";
    echo "Output: " . implode("\n", $output) . "\n";
    exit(1);
} 
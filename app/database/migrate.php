<?php
require_once __DIR__ . '/MigrationManager.php';

try {
    $migrationManager = new MigrationManager();
    $migrationManager->runMigrations();
    echo "All migrations completed successfully!\n";
} catch (Exception $e) {
    echo "Error running migrations: " . $e->getMessage() . "\n";
    exit(1);
} 
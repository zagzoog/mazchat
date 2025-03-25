<?php
require_once 'db_config.php';

// List of migrations to run
$migrations = [
    'app/migrations/add_plugin_id_to_conversations.php',
    'app/migrations/update_conversations_table.php'
];

echo "Starting migrations...\n";

foreach ($migrations as $migration) {
    echo "\nRunning migration: $migration\n";
    echo "----------------------------------------\n";
    require_once $migration;
    echo "----------------------------------------\n";
}

echo "\nMigrations completed.\n"; 
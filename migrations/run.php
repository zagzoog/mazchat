<?php
require_once __DIR__ . '/../db_config.php';

try {
    $db = getDBConnection();
    
    // Create applied_migrations table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS applied_migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Get all migration files
    $migrationFiles = glob(__DIR__ . '/*.sql');
    sort($migrationFiles);
    
    // Get applied migrations
    $stmt = $db->query("SELECT migration_name FROM applied_migrations");
    $appliedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Apply new migrations
    foreach ($migrationFiles as $migrationFile) {
        $migrationName = basename($migrationFile);
        
        if (!in_array($migrationName, $appliedMigrations)) {
            echo "Applying migration: $migrationName\n";
            
            $sql = file_get_contents($migrationFile);
            $db->exec($sql);
            
            // Record applied migration
            $stmt = $db->prepare("INSERT INTO applied_migrations (migration_name) VALUES (?)");
            $stmt->execute([$migrationName]);
            
            echo "Migration applied successfully\n";
        }
    }
    
    echo "All migrations are up to date\n";
    
} catch (Exception $e) {
    echo "Error running migrations: " . $e->getMessage() . "\n";
    exit(1);
} 
<?php
require_once __DIR__ . '/../config/database.php';

class MigrationManager {
    private $db;
    private $migrationsPath;
    
    public function __construct() {
        $this->db = getDBConnection();
        $this->migrationsPath = __DIR__ . '/migrations';
        
        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();
    }
    
    private function createMigrationsTable() {
        $sql = file_get_contents($this->migrationsPath . '/001_create_migrations_table.sql');
        $this->db->exec($sql);
    }
    
    public function runMigrations() {
        $files = glob($this->migrationsPath . '/*.sql');
        sort($files);
        
        foreach ($files as $file) {
            $migrationName = basename($file);
            
            // Check if migration has been executed
            $stmt = $this->db->prepare('SELECT id FROM migrations WHERE migration_name = ?');
            $stmt->execute([$migrationName]);
            
            if (!$stmt->fetch()) {
                try {
                    $sql = file_get_contents($file);
                    $this->db->exec($sql);
                    
                    // Record migration
                    $stmt = $this->db->prepare(
                        'INSERT INTO migrations (migration_name, batch) VALUES (?, 1)'
                    );
                    $stmt->execute([$migrationName]);
                    
                    echo "Executed migration: $migrationName\n";
                } catch (Exception $e) {
                    echo "Error executing migration $migrationName: " . $e->getMessage() . "\n";
                    throw $e;
                }
            }
        }
    }
    
    public function rollback() {
        $stmt = $this->db->query('SELECT MAX(batch) as max_batch FROM migrations');
        $maxBatch = $stmt->fetch(PDO::FETCH_ASSOC)['max_batch'];
        
        if ($maxBatch) {
            $stmt = $this->db->prepare('DELETE FROM migrations WHERE batch = ?');
            $stmt->execute([$maxBatch]);
            echo "Rolled back batch $maxBatch\n";
        }
    }
} 
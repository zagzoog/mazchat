<?php
require_once __DIR__ . '/../config/db_config.php';

class MigrationRunner {
    private $pdo;
    private $migrationsDir;
    private $seedsDir;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->migrationsDir = __DIR__ . '/versions';
        $this->seedsDir = __DIR__ . '/seeds';
        
        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();
    }
    
    public function getMigrationsDir() {
        return $this->migrationsDir;
    }
    
    public function getSeedsDir() {
        return $this->seedsDir;
    }
    
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS applied_migrations (
            id varchar(36) NOT NULL,
            migration_name varchar(255) NOT NULL UNIQUE,
            applied_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
    }
    
    public function getDatabaseSize() {
        $sql = "SELECT SUM(data_length + index_length) as size 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['size'];
    }
    
    public function resetDatabase() {
        // Get all tables in the database
        $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        // Disable foreign key checks
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Drop all tables
        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
        }
        
        // Re-enable foreign key checks
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Recreate migrations table
        $this->createMigrationsTable();
        
        // Run all migrations and seeds
        $this->runMigrations();
        $this->runSeeds();
    }
    
    public function runMigrations() {
        // Get all SQL files in the migrations directory
        $files = glob($this->migrationsDir . '/*.sql');
        sort($files); // Ensure migrations run in order
        
        foreach ($files as $file) {
            $migrationName = basename($file);
            
            // Check if migration has been applied
            $stmt = $this->pdo->prepare("SELECT id FROM applied_migrations WHERE migration_name = ?");
            $stmt->execute([$migrationName]);
            
            if (!$stmt->fetch()) {
                try {
                    // Start transaction
                    $this->pdo->beginTransaction();
                    
                    // Read and execute the migration file
                    $sql = file_get_contents($file);
                    
                    // Extract the "Up:" section if it exists
                    if (preg_match('/-- Up:(.*?)(-- Rollback:|$)/s', $sql, $matches)) {
                        $upSql = trim($matches[1]);
                        if (!empty($upSql)) {
                            $this->pdo->exec($upSql);
                        }
                    } else {
                        // If no Up section, execute the entire file
                        $this->pdo->exec($sql);
                    }
                    
                    // Record the migration
                    $stmt = $this->pdo->prepare("INSERT INTO applied_migrations (id, migration_name) VALUES (UUID(), ?)");
                    $stmt->execute([$migrationName]);
                    
                    // Commit transaction
                    $this->pdo->commit();
                    
                    echo "Applied migration: $migrationName\n";
                } catch (PDOException $e) {
                    // Rollback transaction on error
                    $this->pdo->rollBack();
                    echo "Error applying migration $migrationName: " . $e->getMessage() . "\n";
                    throw $e;
                }
            } else {
                echo "Migration already applied: $migrationName\n";
            }
        }
        
        echo "\nAll migrations completed successfully!\n";
    }
    
    public function rollbackMigrations($steps = 1) {
        // Get the last n applied migrations
        $stmt = $this->pdo->prepare("
            SELECT migration_name 
            FROM applied_migrations 
            ORDER BY applied_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$steps]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($migrations)) {
            echo "No migrations to rollback.\n";
            return;
        }
        
        foreach ($migrations as $migrationName) {
            try {
                $this->pdo->beginTransaction();
                
                // Get the rollback SQL from the migration file
                $file = $this->migrationsDir . '/' . $migrationName;
                $sql = file_get_contents($file);
                
                // Extract rollback SQL (between -- Rollback: and -- End Rollback: comments)
                if (preg_match('/-- Rollback:(.*?)-- End Rollback:/s', $sql, $matches)) {
                    $rollbackSql = trim($matches[1]);
                    $this->pdo->exec($rollbackSql);
                } else {
                    echo "Warning: No rollback SQL found for migration: $migrationName\n";
                }
                
                // Remove the migration record
                $stmt = $this->pdo->prepare("DELETE FROM applied_migrations WHERE migration_name = ?");
                $stmt->execute([$migrationName]);
                
                $this->pdo->commit();
                echo "Rolled back migration: $migrationName\n";
            } catch (PDOException $e) {
                $this->pdo->rollBack();
                echo "Error rolling back migration $migrationName: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
        
        echo "\nRollback completed successfully!\n";
    }
    
    public function getAppliedMigrations() {
        $stmt = $this->pdo->query("SELECT migration_name, applied_at FROM applied_migrations ORDER BY applied_at");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPendingMigrations() {
        $files = glob($this->migrationsDir . '/*.sql');
        sort($files);
        
        $stmt = $this->pdo->query("SELECT migration_name FROM applied_migrations");
        $appliedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $pendingMigrations = [];
        foreach ($files as $file) {
            $migrationName = basename($file);
            if (!in_array($migrationName, $appliedMigrations)) {
                $pendingMigrations[] = $migrationName;
            }
        }
        
        return $pendingMigrations;
    }
    
    public function runSeeds() {
        // Create seeds directory if it doesn't exist
        if (!is_dir($this->seedsDir)) {
            mkdir($this->seedsDir, 0755, true);
        }
        
        // Get all SQL files in the seeds directory
        $files = glob($this->seedsDir . '/*.sql');
        sort($files);
        
        foreach ($files as $file) {
            $seedName = basename($file);
            try {
                $this->pdo->beginTransaction();
                
                // Read and execute the seed file
                $sql = file_get_contents($file);
                $this->pdo->exec($sql);
                
                $this->pdo->commit();
                echo "Applied seed: $seedName\n";
            } catch (PDOException $e) {
                $this->pdo->rollBack();
                echo "Error applying seed $seedName: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
        
        echo "\nAll seeds completed successfully!\n";
    }
    
    public function validateMigration($filename) {
        $filepath = $this->migrationsDir . '/' . $filename;
        if (!file_exists($filepath)) {
            throw new Exception("Migration file not found: $filename");
        }
        
        $sql = file_get_contents($filepath);
        $errors = [];
        
        // Check for required sections
        if (!preg_match('/-- Up:/', $sql)) {
            $errors[] = "Missing '-- Up:' section";
        }
        if (!preg_match('/-- Rollback:/', $sql)) {
            $errors[] = "Missing '-- Rollback:' section";
        }
        if (!preg_match('/-- End Rollback:/', $sql)) {
            $errors[] = "Missing '-- End Rollback:' section";
        }
        
        // Validate SQL syntax
        $upSql = '';
        if (preg_match('/-- Up:(.*?)(-- Rollback:|$)/s', $sql, $matches)) {
            $upSql = trim($matches[1]);
        }
        
        try {
            // Try to parse the SQL without executing it
            $this->pdo->prepare($upSql);
        } catch (PDOException $e) {
            $errors[] = "Invalid SQL in Up section: " . $e->getMessage();
        }
        
        // Validate rollback SQL
        $rollbackSql = '';
        if (preg_match('/-- Rollback:(.*?)-- End Rollback:/s', $sql, $matches)) {
            $rollbackSql = trim($matches[1]);
        }
        
        try {
            $this->pdo->prepare($rollbackSql);
        } catch (PDOException $e) {
            $errors[] = "Invalid SQL in Rollback section: " . $e->getMessage();
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'filename' => $filename
        ];
    }
    
    public function validateAllMigrations() {
        $files = glob($this->migrationsDir . '/*.sql');
        $results = [];
        
        foreach ($files as $file) {
            $filename = basename($file);
            $results[] = $this->validateMigration($filename);
        }
        
        return $results;
    }
    
    public function checkMigrationDependencies() {
        $files = glob($this->migrationsDir . '/*.sql');
        $dependencies = [];
        
        foreach ($files as $file) {
            $filename = basename($file);
            $sql = file_get_contents($file);
            
            // Look for dependency comments
            if (preg_match('/-- Depends on: (.*)/', $sql, $matches)) {
                $deps = array_map('trim', explode(',', $matches[1]));
                $dependencies[$filename] = $deps;
            }
        }
        
        return $dependencies;
    }
    
    public function generateMigrationReport() {
        $report = [
            'total_migrations' => count(glob($this->migrationsDir . '/*.sql')),
            'applied_migrations' => count($this->getAppliedMigrations()),
            'pending_migrations' => count($this->getPendingMigrations()),
            'database_size' => $this->getDatabaseSize(),
            'validation_results' => $this->validateAllMigrations(),
            'dependencies' => $this->checkMigrationDependencies()
        ];
        
        return $report;
    }
    
    public function checkMigrationOrder() {
        $files = glob($this->migrationsDir . '/*.sql');
        sort($files);
        
        $orderIssues = [];
        $lastTimestamp = '';
        
        foreach ($files as $file) {
            $filename = basename($file);
            $timestamp = substr($filename, 0, 14); // YmdHis format
            
            if ($lastTimestamp && $timestamp < $lastTimestamp) {
                $orderIssues[] = [
                    'file' => $filename,
                    'timestamp' => $timestamp,
                    'previous_timestamp' => $lastTimestamp
                ];
            }
            
            $lastTimestamp = $timestamp;
        }
        
        return $orderIssues;
    }
    
    public function getMigrationStats() {
        $files = glob($this->migrationsDir . '/*.sql');
        $stats = [
            'total_files' => count($files),
            'total_size' => 0,
            'largest_file' => ['name' => '', 'size' => 0],
            'average_size' => 0
        ];
        
        foreach ($files as $file) {
            $size = filesize($file);
            $stats['total_size'] += $size;
            
            if ($size > $stats['largest_file']['size']) {
                $stats['largest_file'] = [
                    'name' => basename($file),
                    'size' => $size
                ];
            }
        }
        
        $stats['average_size'] = $stats['total_size'] / $stats['total_files'];
        
        return $stats;
    }
}

// Only run migrations if this script is executed directly
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $runner = new MigrationRunner($pdo);
        
        echo "Checking for pending migrations...\n\n";
        
        $pendingMigrations = $runner->getPendingMigrations();
        if (empty($pendingMigrations)) {
            echo "No pending migrations found.\n";
        } else {
            echo "Found " . count($pendingMigrations) . " pending migration(s):\n";
            foreach ($pendingMigrations as $migration) {
                echo "- $migration\n";
            }
            echo "\nRunning migrations...\n\n";
            $runner->runMigrations();
        }
        
        echo "\nCurrent migration status:\n";
        $appliedMigrations = $runner->getAppliedMigrations();
        foreach ($appliedMigrations as $migration) {
            echo "- {$migration['migration_name']} (applied at: {$migration['applied_at']})\n";
        }
        
    } catch (PDOException $e) {
        die("Migration failed: " . $e->getMessage() . "\n");
    }
} 
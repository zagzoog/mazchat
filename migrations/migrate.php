<?php
require_once __DIR__ . '/../config/db_config.php';

class MigrationCLI {
    private $runner;
    
    public function __construct($pdo) {
        $this->runner = new MigrationRunner($pdo);
    }
    
    public function run($args) {
        $command = $args[1] ?? 'help';
        
        switch ($command) {
            case 'up':
                $this->runMigrations();
                break;
            case 'down':
                $this->rollbackMigrations();
                break;
            case 'status':
                $this->showStatus();
                break;
            case 'seed':
                $this->runSeeds();
                break;
            case 'refresh':
                $this->refreshDatabase();
                break;
            case 'create':
                $this->createMigration();
                break;
            case 'create:seed':
                $this->createSeed();
                break;
            case 'reset':
                $this->resetDatabase();
                break;
            case 'validate':
                $this->validateMigrations();
                break;
            case 'report':
                $this->generateReport();
                break;
            case 'check:order':
                $this->checkMigrationOrder();
                break;
            case 'stats':
                $this->showMigrationStats();
                break;
            case 'help':
            default:
                $this->showHelp();
                break;
        }
    }
    
    private function runMigrations() {
        echo "Checking for pending migrations...\n\n";
        
        $pendingMigrations = $this->runner->getPendingMigrations();
        if (empty($pendingMigrations)) {
            echo "No pending migrations found.\n";
            return;
        }
        
        echo "Found " . count($pendingMigrations) . " pending migration(s):\n";
        foreach ($pendingMigrations as $migration) {
            echo "- $migration\n";
        }
        
        echo "\nRunning migrations...\n\n";
        $this->runner->runMigrations();
    }
    
    private function rollbackMigrations() {
        $steps = isset($_SERVER['argv'][2]) ? (int)$_SERVER['argv'][2] : 1;
        echo "Rolling back last $steps migration(s)...\n\n";
        $this->runner->rollbackMigrations($steps);
    }
    
    private function showStatus() {
        echo "Migration Status:\n\n";
        
        $appliedMigrations = $this->runner->getAppliedMigrations();
        echo "Applied Migrations:\n";
        foreach ($appliedMigrations as $migration) {
            echo "- {$migration['migration_name']} (applied at: {$migration['applied_at']})\n";
        }
        
        $pendingMigrations = $this->runner->getPendingMigrations();
        echo "\nPending Migrations:\n";
        if (empty($pendingMigrations)) {
            echo "No pending migrations.\n";
        } else {
            foreach ($pendingMigrations as $migration) {
                echo "- $migration\n";
            }
        }
        
        // Show database size
        $this->showDatabaseSize();
    }
    
    private function showDatabaseSize() {
        $size = $this->runner->getDatabaseSize();
        echo "\nDatabase Size: " . number_format($size / 1024 / 1024, 2) . " MB\n";
    }
    
    private function runSeeds() {
        echo "Running database seeds...\n\n";
        $this->runner->runSeeds();
    }
    
    private function refreshDatabase() {
        echo "Refreshing database...\n\n";
        
        // Get total number of migrations
        $totalMigrations = count($this->runner->getAppliedMigrations());
        
        // Rollback all migrations
        $this->runner->rollbackMigrations($totalMigrations);
        
        // Run migrations again
        $this->runner->runMigrations();
        
        // Run seeds
        $this->runner->runSeeds();
        
        echo "\nDatabase refreshed successfully!\n";
    }
    
    private function createMigration() {
        if (!isset($_SERVER['argv'][2])) {
            echo "Error: Please provide a migration name.\n";
            echo "Usage: php migrate.php create migration_name\n";
            return;
        }
        
        $name = $_SERVER['argv'][2];
        $timestamp = date('YmdHis');
        $filename = $timestamp . '_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name)) . '.sql';
        $filepath = $this->runner->getMigrationsDir() . '/' . $filename;
        
        $template = "-- Migration: {$name}\n\n";
        $template .= "-- Up:\n\n";
        $template .= "-- Rollback:\n\n";
        $template .= "-- End Rollback:\n";
        
        if (file_put_contents($filepath, $template)) {
            echo "Created migration: {$filename}\n";
        } else {
            echo "Error creating migration file.\n";
        }
    }
    
    private function createSeed() {
        if (!isset($_SERVER['argv'][2])) {
            echo "Error: Please provide a seed name.\n";
            echo "Usage: php migrate.php create:seed seed_name\n";
            return;
        }
        
        $name = $_SERVER['argv'][2];
        $timestamp = date('YmdHis');
        $filename = $timestamp . '_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name)) . '.sql';
        $filepath = $this->runner->getSeedsDir() . '/' . $filename;
        
        $template = "-- Seed: {$name}\n\n";
        $template .= "-- Add your seed data here\n";
        
        if (file_put_contents($filepath, $template)) {
            echo "Created seed: {$filename}\n";
        } else {
            echo "Error creating seed file.\n";
        }
    }
    
    private function resetDatabase() {
        echo "WARNING: This will drop all tables and data!\n";
        echo "Are you sure you want to continue? (yes/no): ";
        
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim($line) !== 'yes') {
            echo "Operation cancelled.\n";
            return;
        }
        
        echo "\nResetting database...\n\n";
        $this->runner->resetDatabase();
        echo "Database reset successfully!\n";
    }
    
    private function validateMigrations() {
        echo "Validating migrations...\n\n";
        
        $results = $this->runner->validateAllMigrations();
        $hasErrors = false;
        
        foreach ($results as $result) {
            if (!$result['valid']) {
                $hasErrors = true;
                echo "❌ {$result['filename']}:\n";
                foreach ($result['errors'] as $error) {
                    echo "  - $error\n";
                }
                echo "\n";
            } else {
                echo "✓ {$result['filename']} is valid\n";
            }
        }
        
        if (!$hasErrors) {
            echo "\nAll migrations are valid! ✨\n";
        } else {
            echo "\nSome migrations have validation errors. Please fix them before running migrations.\n";
        }
    }
    
    private function generateReport() {
        echo "Generating migration report...\n\n";
        
        $report = $this->runner->generateMigrationReport();
        
        echo "Migration Report\n";
        echo "===============\n\n";
        
        echo "Overview:\n";
        echo "- Total Migrations: {$report['total_migrations']}\n";
        echo "- Applied Migrations: {$report['applied_migrations']}\n";
        echo "- Pending Migrations: {$report['pending_migrations']}\n";
        echo "- Database Size: " . number_format($report['database_size'] / 1024 / 1024, 2) . " MB\n\n";
        
        echo "Dependencies:\n";
        foreach ($report['dependencies'] as $file => $deps) {
            echo "- $file depends on: " . implode(', ', $deps) . "\n";
        }
        echo "\n";
        
        echo "Validation Status:\n";
        $hasErrors = false;
        foreach ($report['validation_results'] as $result) {
            if (!$result['valid']) {
                $hasErrors = true;
                echo "❌ {$result['filename']}:\n";
                foreach ($result['errors'] as $error) {
                    echo "  - $error\n";
                }
                echo "\n";
            } else {
                echo "✓ {$result['filename']} is valid\n";
            }
        }
        
        if (!$hasErrors) {
            echo "\nAll migrations are valid! ✨\n";
        }
    }
    
    private function checkMigrationOrder() {
        echo "Checking migration order...\n\n";
        
        $issues = $this->runner->checkMigrationOrder();
        
        if (empty($issues)) {
            echo "✓ All migrations are in correct chronological order.\n";
            return;
        }
        
        echo "⚠️ Found " . count($issues) . " migration(s) out of order:\n\n";
        foreach ($issues as $issue) {
            echo "- {$issue['file']} (timestamp: {$issue['timestamp']})\n";
            echo "  Should be after: {$issue['previous_timestamp']}\n\n";
        }
        
        echo "Please rename the migration files to maintain chronological order.\n";
    }
    
    private function showMigrationStats() {
        echo "Migration Statistics\n";
        echo "==================\n\n";
        
        $stats = $this->runner->getMigrationStats();
        
        echo "File Statistics:\n";
        echo "- Total Files: {$stats['total_files']}\n";
        echo "- Total Size: " . number_format($stats['total_size'] / 1024, 2) . " KB\n";
        echo "- Average Size: " . number_format($stats['average_size'] / 1024, 2) . " KB\n";
        echo "- Largest File: {$stats['largest_file']['name']} (" . 
             number_format($stats['largest_file']['size'] / 1024, 2) . " KB)\n\n";
        
        $applied = count($this->runner->getAppliedMigrations());
        $pending = count($this->runner->getPendingMigrations());
        
        echo "Migration Status:\n";
        echo "- Applied: $applied\n";
        echo "- Pending: $pending\n";
        echo "- Progress: " . round(($applied / $stats['total_files']) * 100) . "%\n";
    }
    
    private function showHelp() {
        echo "Migration CLI Usage:\n\n";
        echo "  php migrate.php up              Run pending migrations\n";
        echo "  php migrate.php down [n]        Rollback last n migrations (default: 1)\n";
        echo "  php migrate.php status          Show migration status and database size\n";
        echo "  php migrate.php seed            Run database seeds\n";
        echo "  php migrate.php refresh         Rollback all migrations, run them again, and seed\n";
        echo "  php migrate.php reset           Drop all tables and reset database (requires confirmation)\n";
        echo "  php migrate.php create name     Create a new migration file\n";
        echo "  php migrate.php create:seed name Create a new seed file\n";
        echo "  php migrate.php validate        Validate all migration files\n";
        echo "  php migrate.php report          Generate detailed migration report\n";
        echo "  php migrate.php check:order     Check migration file order\n";
        echo "  php migrate.php stats           Show migration statistics\n";
        echo "  php migrate.php help            Show this help message\n\n";
    }
}

// Only run if this script is executed directly
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $cli = new MigrationCLI($pdo);
        $cli->run($_SERVER['argv']);
        
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage() . "\n");
    }
} 
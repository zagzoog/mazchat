<?php

class VersionManager {
    private static $instance = null;
    private $currentVersion;
    private $db;
    
    private function __construct() {
        $this->db = getDBConnection();
        $this->currentVersion = $this->getCurrentVersion();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function getCurrentVersion() {
        $stmt = $this->db->query("SELECT value FROM system_settings WHERE name = 'version'");
        return $stmt->fetchColumn() ?: '1.0.0';
    }
    
    public function checkForUpdates() {
        // In a real application, this would check against a remote repository
        // For now, we'll just return the current version
        return [
            'current_version' => $this->currentVersion,
            'latest_version' => '1.0.0',
            'update_available' => false
        ];
    }
    
    public function performUpdate() {
        try {
            $this->db->beginTransaction();
            
            // Get all migration files
            $migrationsDir = dirname(__DIR__, 2) . '/migrations';
            $migrationFiles = glob($migrationsDir . '/*.sql');
            sort($migrationFiles);
            
            // Get applied migrations
            $stmt = $this->db->query("SELECT migration_name FROM applied_migrations");
            $appliedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Apply new migrations
            foreach ($migrationFiles as $migrationFile) {
                $migrationName = basename($migrationFile);
                
                if (!in_array($migrationName, $appliedMigrations)) {
                    $sql = file_get_contents($migrationFile);
                    $this->db->exec($sql);
                    
                    // Record applied migration
                    $stmt = $this->db->prepare("INSERT INTO applied_migrations (migration_name) VALUES (?)");
                    $stmt->execute([$migrationName]);
                }
            }
            
            // Update version
            $stmt = $this->db->prepare("UPDATE system_settings SET value = ? WHERE name = 'version'");
            $stmt->execute(['1.0.0']);
            
            // Update plugins
            $pluginManager = PluginManager::getInstance();
            foreach ($pluginManager->getActivePlugins() as $plugin) {
                $plugin->activate(); // This will update plugin tables if needed
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Update failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUpdateHistory() {
        $stmt = $this->db->query("
            SELECT migration_name, applied_at 
            FROM applied_migrations 
            ORDER BY applied_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function createBackup() {
        $backupDir = dirname(__DIR__, 2) . '/backups';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . '/backup_' . $timestamp . '.sql';
        
        // Get database credentials
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $dbConfig = $config['db'];
        
        // Create backup using mysqldump
        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s',
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['user']),
            escapeshellarg($dbConfig['pass']),
            escapeshellarg($dbConfig['name']),
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $returnVar);
        return $returnVar === 0;
    }
    
    public function restoreBackup($backupFile) {
        if (!file_exists($backupFile)) {
            throw new Exception("Backup file not found");
        }
        
        try {
            $this->db->beginTransaction();
            
            // Get database credentials
            $config = require dirname(__DIR__, 2) . '/config/config.php';
            $dbConfig = $config['db'];
            
            // Restore backup using mysql
            $command = sprintf(
                'mysql -h %s -u %s -p%s %s < %s',
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['user']),
                escapeshellarg($dbConfig['pass']),
                escapeshellarg($dbConfig['name']),
                escapeshellarg($backupFile)
            );
            
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                throw new Exception("Failed to restore backup");
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Restore failed: " . $e->getMessage());
            return false;
        }
    }
} 
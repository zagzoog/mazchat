<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Get all tables
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $schema = "-- Generated schema on " . date('Y-m-d H:i:s') . "\n\n";
    $schema .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        // Get create table statement
        $createStmt = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $schema .= $createStmt['Create Table'] . ";\n\n";
        
        // Get any default data (for settings tables, etc.)
        if ($table === 'admin_settings') {
            $settings = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($settings)) {
                $schema .= "-- Default settings\n";
                $schema .= "INSERT INTO `$table` (`setting_key`, `setting_value`) VALUES\n";
                $values = [];
                foreach ($settings as $setting) {
                    $values[] = "('" . addslashes($setting['setting_key']) . "', '" . addslashes($setting['setting_value']) . "')";
                }
                $schema .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        // Get indexes
        $indexes = $db->query("SHOW INDEX FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        $customIndexes = [];
        foreach ($indexes as $index) {
            if ($index['Key_name'] !== 'PRIMARY' && !str_starts_with($index['Key_name'], 'fk_')) {
                $unique = $index['Non_unique'] == 0 ? 'UNIQUE ' : '';
                $customIndexes[$index['Key_name']][] = $index['Column_name'];
            }
        }
        
        if (!empty($customIndexes)) {
            $schema .= "-- Indexes for table `$table`\n";
            foreach ($customIndexes as $indexName => $columns) {
                $schema .= "CREATE INDEX `$indexName` ON `$table` (" . implode(', ', array_map(function($col) { return "`$col`"; }, $columns)) . ");\n";
            }
            $schema .= "\n";
        }
    }
    
    $schema .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Save to schema.sql
    $schemaPath = __DIR__ . '/app/database/schema.sql';
    if (!is_dir(dirname($schemaPath))) {
        mkdir(dirname($schemaPath), 0777, true);
    }
    file_put_contents($schemaPath, $schema);
    
    echo "Schema file generated successfully at: $schemaPath\n";
    
} catch (Exception $e) {
    echo "Error generating schema: " . $e->getMessage() . "\n";
    exit(1);
} 
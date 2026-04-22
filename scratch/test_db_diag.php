<?php
// Test script to verify DB diagnostic error message

define('SPP_BASE_DIR', dirname(__DIR__) . '/spp');
require_once dirname(__DIR__) . '/spp/sppinit.php';

use SPPMod\SPPDB\SPPDB;
use SPP\Scheduler;

try {
    echo "--- Testing with invalid app context ---\n";
    Scheduler::setContext('non_existent_app');
    $db = new SPPDB();
} catch (\Exception $e) {
    echo "Caught expected exception:\n";
    echo $e->getMessage() . "\n";
}

try {
    echo "\n--- Testing with default context (if config is missing) ---\n";
    
    $possiblePaths = [
        \SPP\Module::getExpectedConfigPath('sppdb', 'default'),
        dirname(__DIR__) . '/spp/etc/apps/default/modsconf/sppdb/config.yml'
    ];
    
    $movedFiles = [];
    foreach ($possiblePaths as $configPath) {
        if (file_exists($configPath)) {
            $backupPath = $configPath . '.bak';
            rename($configPath, $backupPath);
            $movedFiles[$configPath] = $backupPath;
            echo "Temporarily moved: $configPath\n";
        }
    }
    
    Scheduler::setContext('default');
    echo "Instantiating SPPDB for default context...\n";
    $db = new SPPDB();
    echo "Connection successful (unexpected if config is missing)\n";
    
} catch (\Exception $e) {
    echo "Caught expected exception:\n";
    echo $e->getMessage() . "\n";
} finally {
    foreach ($movedFiles as $configPath => $backupPath) {
        rename($backupPath, $configPath);
        echo "Restored: $configPath\n";
    }
}

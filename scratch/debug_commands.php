<?php
require_once dirname(__DIR__) . '/spp/sppinit.php';

$commands = \SPP\CLI\CommandManager::discover();
echo "Discovered Commands:\n";
foreach (array_keys($commands) as $name) {
    echo "- $name\n";
}

$coreCmdDir = dirname(__DIR__) . '/spp/commands';
echo "\nCore Command Dir: $coreCmdDir\n";
if (is_dir($coreCmdDir)) {
    echo "Dir exists.\n";
    $files = glob($coreCmdDir . '/*.php');
    echo "Files found: " . count($files) . "\n";
    foreach ($files as $f) {
        echo "  - " . basename($f) . "\n";
    }
} else {
    echo "Dir NOT found.\n";
}

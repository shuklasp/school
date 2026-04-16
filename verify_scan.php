<?php
/**
 * Verification script for SPP Module Resolution
 */
require_once __DIR__ . '/spp/sppinit.php';

$modname = 'spplogger';
echo "Resolving manifest for '{$modname}'...\n";

$manifest = \SPP\Module::findManifestPath($modname);

if ($manifest) {
    echo "SUCCESS: Found manifest at: {$manifest}\n";
    
    // Check if it loads
    try {
        $mod = new \SPP\Module($manifest);
        echo "Module Internal Name: " . $mod->InternalName . "\n";
        echo "Module Version: " . $mod->Version . "\n";
    } catch (Exception $e) {
        echo "ERROR loading module: " . $e->getMessage() . "\n";
    }
} else {
    echo "FAILED: Manifest not found for '{$modname}'\n";
    
    // Debug: list paths check
    echo "\nDebug Info:\n";
    echo "SPP_MODULES_DIR: " . SPP_MODULES_DIR . "\n";
    $categories = ['spp', 'school', 'custom'];
    foreach ($categories as $cat) {
        $path = SPP_MODULES_DIR . DIRECTORY_SEPARATOR . $cat . DIRECTORY_SEPARATOR . $modname;
        echo "Checking path: {$path} - " . (is_dir($path) ? 'EXISTS' : 'MISSING') . "\n";
    }
}

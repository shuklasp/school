<?php
require_once 'spp/sppinit.php';

echo "SPP_APP_DIR: " . SPP_APP_DIR . "\n";
echo "APP_ETC_DIR: " . APP_ETC_DIR . "\n";

$appname = 'default';
try {
    $app = new \SPP\App($appname);
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "App '$appname' already initialized. Continuing test...\n";
        \SPP\Scheduler::setContext($appname);
    } else {
        throw $e;
    }
}

// Check database config retrieval
$dbConfig = \SPP\Module::getConfig('db_user', 'sppdb');
echo "Database User from Config: " . ($dbConfig ?: "NOT FOUND") . "\n";

if ($dbConfig === 'root' || $dbConfig === 'school') { // 'school' was old, 'root' is new
    echo "SUCCESS: Config retrieved correctly.\n";
} else {
    echo "FAILURE: Config not retrieved correctly. Got: " . var_export($dbConfig, true) . "\n";
}

// Check module loading
\SPP\Module::loadAllModules();
$modPath = \SPP\Registry::get('__mods=>schperson');
echo "schperson Path: " . ($modPath ?: "NOT FOUND") . "\n";

if ($modPath && (strpos($modPath, 'modules/default/schperson') !== false || strpos($modPath, 'modules\\default\\schperson') !== false)) {
    echo "SUCCESS: schperson module loaded from new root modules directory.\n";
} else {
    echo "FAILURE: schperson module not found or path incorrect.\n";
}

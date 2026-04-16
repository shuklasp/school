<?php
// Bootstrap the framework core
require_once('spp/core/class.sppobject.php');
require_once('spp/core/class.sppexception.php');
require_once('spp/core/class.module.php');
require_once('spp/core/class.scheduler.php');
require_once('spp/core/class.app.php');
require_once('spp/core/sppfuncs.php');
require_once('spp/modules/spp/sppentity/class.sppentity.php');

use SPPMod\SPPEntity\SPPEntity;
use SPP\Exceptions\EntityConfigurationException;

// 1. Create a dummy entity YAML with an EMPTY table definition
$appname = 'default';
if (class_exists('\\SPP\\Scheduler')) {
    \SPP\Scheduler::setContext($appname);
}

$entitiesDir = APP_ETC_DIR . '/' . $appname . '/entities';
if (!is_dir($entitiesDir)) mkdir($entitiesDir, 0777, true);

// Set table to empty string to trigger the exception
$yamlContent = "table: \"\"\nattributes:\n  name: varchar(255)";
file_put_contents($entitiesDir . '/brokentable.yml', $yamlContent);

// 2. Define the class
if (!class_exists('BrokenTable')) {
    class BrokenTable extends SPPEntity {}
}

echo "Testing Entity Configuration Hardening...\n";

try {
    echo "Attempting to load configuration for BrokenTable...\n";
    // This will trigger loadEntityConfig in the constructor
    $entity = new BrokenTable();
    echo "FAILED: Instance created without exception. Resolved table: " . $entity->getTable() . "\n";
} catch (EntityConfigurationException $e) {
    echo "SUCCESS: Caught expected exception: " . $e->getMessage() . "\n";
} catch (\Throwable $e) {
    echo "FAILED: Caught unexpected exception: " . get_class($e) . " - " . $e->getMessage() . "\n";
}

// Cleanup
if (file_exists($entitiesDir . '/brokentable.yml')) {
    unlink($entitiesDir . '/brokentable.yml');
}
echo "Test complete.\n";

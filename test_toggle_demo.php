<?php
$_SERVER['DOCUMENT_ROOT'] = 'c:/projects/apache/school1';
require 'spp/sppinit.php';

// Set app context to demo for Scheduler
$_GET['appname'] = 'demo';

$mod = 'spplogger';

// Toggle to active
\SPP\Module::toggleModuleStatus($mod, 'active');

$xmlPath = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . 'modules.xml';

echo "--- After toggle to active ---\n";
if (file_exists($xmlPath)) {
    $xml = simplexml_load_file($xmlPath);
    foreach ($xml->module as $m) {
        if ((string)$m->modname === $mod) {
            echo "Status: " . (string)$m->status . "\n";
        }
    }
}

// Toggle to inactive
\SPP\Module::toggleModuleStatus($mod, 'inactive');
$xml = simplexml_load_file($xmlPath);
foreach ($xml->module as $m) {
    if ((string)$m->modname === $mod) {
        echo "Status after inactive: " . (string)$m->status . "\n";
    }
}
?>

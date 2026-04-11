<?php
require_once 'vendor/autoload.php';
require_once 'spp/sppinit.php';

try {
    $db = new \SPPMod\SPPDB\SPP_DB();
    $tables = $db->execute_query('SHOW TABLES');
    echo "Tables Found:\n";
    print_r($tables);
} catch (\Exception $e) {
    echo "Connection Error: " . $e->getMessage() . "\n";
}

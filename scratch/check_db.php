<?php
require_once dirname(__DIR__) . '/spp/sppinit.php';
$db = new \SPPMod\SPPDB\SPPDB();
try {
    echo "COLUMNS sppgroups:\n";
    print_r($db->execute_query("SHOW COLUMNS FROM sppgroups"));
    echo "\nCOLUMNS sppgroupmembers:\n";
    print_r($db->execute_query("SHOW COLUMNS FROM sppgroupmembers"));
    echo "\nCOLUMNS staffs:\n";
    print_r($db->execute_query("SHOW COLUMNS FROM staffs"));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

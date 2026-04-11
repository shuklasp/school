<?php
require_once('spp/sppinit.php');
require_once('global.php');
require_once('vendor/autoload.php');

$db = new \SPPMod\SPPDB\SPP_DB();
try {
    $res = $db->execute_query("DESCRIBE userroles");
    echo "Columns in 'userroles':\n";
    print_r($res);
} catch (Exception $e) {
    echo "Error describing 'userroles': " . $e->getMessage() . "\n";
}
try {
    $res = $db->execute_query("DESCRIBE roles");
    echo "Columns in 'roles':\n";
    print_r($res);
} catch (Exception $e) {
    echo "Error describing 'roles': " . $e->getMessage() . "\n";
}

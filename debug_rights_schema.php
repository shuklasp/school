<?php
require_once('spp/sppinit.php');
require_once('global.php');
require_once('vendor/autoload.php');

$db = new \SPPMod\SPPDB\SPP_DB();
try {
    $res = $db->execute_query("DESCRIBE rights");
    echo "Columns in 'rights':\n";
    print_r($res);
} catch (Exception $e) {
    echo "Error describing 'rights': " . $e->getMessage() . "\n";
}
try {
    $res = $db->execute_query("DESCRIBE roleright");
    echo "Columns in 'roleright':\n";
    print_r($res);
} catch (Exception $e) {
    echo "Error describing 'roleright': " . $e->getMessage() . "\n";
}

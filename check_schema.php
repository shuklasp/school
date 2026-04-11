<?php
require_once('spp/sppinit.php');
require_once('global.php');
require_once('vendor/autoload.php');

$db = new \SPPMod\SPPDB\SPP_DB();
$tables = $db->execute_query("SHOW TABLES");
echo "Tables in DB:\n";
print_r($tables);

try {
    $res = $db->execute_query("DESCRIBE users");
    echo "Columns in 'users':\n";
    print_r($res);
} catch (Exception $e) {
    echo "Error describing 'users': " . $e->getMessage() . "\n";
}

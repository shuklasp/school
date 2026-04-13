<?php
require_once('spp/sppinit.php');
require_once('global.php');
require_once('vendor/autoload.php');

$db = new \SPPMod\SPPDB\SPPDB();
$res = $db->execute_query('SHOW TABLES');
echo "All Tables:\n";
foreach ($res as $row) {
    echo array_values($row)[0] . "\n";
}

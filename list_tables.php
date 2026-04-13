<?php
require_once 'vendor/autoload.php';
require_once 'spp/sppinit.php';

$db = new \SPPMod\SPPDB\SPPDB();
$res = $db->execute_query('SHOW TABLES');
foreach ($res as $row) {
    echo current($row) . "\n";
}

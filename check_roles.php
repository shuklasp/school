<?php
require_once 'vendor/autoload.php';
require_once 'spp/sppinit.php';

$db = new \SPPMod\SPPDB\SPP_DB();
$res = $db->execute_query('SELECT * FROM roles LIMIT 5');
echo "Roles Found:\n";
print_r($res);

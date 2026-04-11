<?php
require_once('spp/sppinit.php');
require_once('global.php');
require_once('vendor/autoload.php');

$db = new \SPPMod\SPPDB\SPP_DB();
$res = $db->execute_query("SELECT * FROM users");
echo "Users table content:\n";
print_r($res);

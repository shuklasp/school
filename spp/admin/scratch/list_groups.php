<?php
require 'c:/projects/apache/school1/spp/sppinit.php';
$db = new \SPPMod\SPPDB\SPPDB();
$table = \SPPMod\SPPDB\SPPDB::sppTable('sppgroups');
$res = $db->execute_query('SELECT id, name FROM ' . $table);
echo json_encode($res, JSON_PRETTY_PRINT);

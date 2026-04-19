<?php
require 'c:/projects/apache/school1/spp/sppinit.php';
$db = new \SPPMod\SPPDB\SPPDB();
$table = \SPPMod\SPPDB\SPPDB::sppTable('sppgroupmembers');
$res = $db->execute_query('SELECT * FROM ' . $table . ' ORDER BY added_at DESC LIMIT 10');
echo json_encode($res, JSON_PRETTY_PRINT);

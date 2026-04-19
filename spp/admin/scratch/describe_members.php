<?php
require 'c:/projects/apache/school1/spp/sppinit.php';
$db = new \SPPMod\SPPDB\SPPDB();
$table = \SPPMod\SPPDB\SPPDB::sppTable('sppgroupmembers');
$res = $db->execute_query('DESCRIBE ' . $table);
echo json_encode($res, JSON_PRETTY_PRINT);

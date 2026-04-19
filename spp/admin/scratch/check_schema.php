<?php
require 'c:/projects/apache/school1/spp/sppinit.php';
$db = new \SPPMod\SPPDB\SPPDB();
$table = \SPPMod\SPPDB\SPPDB::sppTable('group_members');
$res = $db->execute_query('DESC ' . $table);
print_r($res);

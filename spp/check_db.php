<?php
require_once('sppinit.php');
$db = new \SPPMod\SPPDB\SPPDB();

function describeTable($db, $table) {
    echo "Structure of $table:\n";
    $res = $db->execute_query("DESCRIBE $table");
    print_r($res);
}

describeTable($db, \SPPMod\SPPDB\SPPDB::sppTable('users'));
describeTable($db, \SPPMod\SPPDB\SPPDB::sppTable('loginrec'));

$res = $db->execute_query("SELECT * FROM " . \SPPMod\SPPDB\SPPDB::sppTable('users') . " LIMIT 1");
echo "\nExample row from users:\n";
print_r($res);

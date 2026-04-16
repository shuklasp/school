<?php
require_once('spp/sppinit.php');
use SPPMod\SPPDB\SPPDB;

$db = new SPPDB();
$table = $db->sppTable('staffs');

// Create table manually
if (!$db->tableExists($table)) {
    $db->execute_query("CREATE TABLE $table (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), department VARCHAR(100))");
}

// Clear and insert test data
$db->execute_query("DELETE FROM $table");
$db->execute_query("INSERT INTO $table (name, department) VALUES (?, ?)", ['John Principal', 'Administration']);
$db->execute_query("INSERT INTO $table (name, department) VALUES (?, ?)", ['Staff Member B', 'Science']);

echo "Test data inserted into 'staffs' table.\n";

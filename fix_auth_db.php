<?php
require_once('spp/sppinit.php');
require_once('global.php');
require_once('vendor/autoload.php');

$db = new \SPPMod\SPPDB\SPP_DB();

echo "Step: Creating missing authentication structure...\n";

// Rights table
$db->execute_query("CREATE TABLE IF NOT EXISTS rights (
    rightid INTEGER PRIMARY KEY AUTO_INCREMENT, 
    rightname VARCHAR(100) UNIQUE
) ENGINE=INNODB");

// RoleRight link table
$db->execute_query("CREATE TABLE IF NOT EXISTS roleright (
    roleid INTEGER, 
    rightid INTEGER
) ENGINE=INNODB");

// Populate standard rights for Admin
$db->execute_query("INSERT IGNORE INTO rights (rightname) VALUES ('manage_modules'), ('manage_entities'), ('manage_forms'), ('manage_groups')");

// Link rights to Admin role (ID 1)
$res = $db->execute_query("SELECT rightid FROM rights");
foreach ($res as $row) {
    $db->execute_query("INSERT IGNORE INTO roleright (roleid, rightid) VALUES (1, ?)", array($row['rightid']));
}

echo "Success: Authentication structure established.\n";

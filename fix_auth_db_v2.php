<?php
require_once('spp/sppinit.php');
require_once('global.php');
require_once('vendor/autoload.php');

$db = new \SPPMod\SPPDB\SPP_DB();

echo "Step: Creating/Connecting authentication structure...\n";

// Populate standard rights for Admin using 'name' column
$db->execute_query("INSERT IGNORE INTO rights (name) VALUES ('manage_modules'), ('manage_entities'), ('manage_forms'), ('manage_groups')");

// Link rights to Admin role (ID 1)
$res = $db->execute_query("SELECT id FROM rights");
foreach ($res as $row) {
    $db->execute_query("INSERT IGNORE INTO roleright (roleid, rightid) VALUES (1, ?)", array($row['id']));
}

echo "Success: Authentication structure established.\n";

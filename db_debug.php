<?php
require 'spp/sppinit.php';
$db = new \SPPMod\SPPDB\SPPDB();
try {
    echo "USERS TABLE SCHEMA:\n";
    print_r($db->execute_query('DESCRIBE users'));
    echo "\nUSER ROLES TABLE SCHEMA:\n";
    print_r($db->execute_query('DESCRIBE userroles'));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

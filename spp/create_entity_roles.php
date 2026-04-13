<?php
require_once('sppinit.php');
$db = new \SPPMod\SPPDB\SPPDB();
echo "Creating entity_roles table...\n";
$db->execute_query("CREATE TABLE IF NOT EXISTS entity_roles (
    target_class VARCHAR(255),
    target_id VARCHAR(100),
    role_id INT,
    PRIMARY KEY (target_class, target_id, role_id)
) ENGINE=InnoDB");
echo "Done.\n";

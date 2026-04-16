<?php
require_once __DIR__ . '/../spp/sppinit.php';
$db = new \SPPMod\SPPDB\SPPDB();

try {
    echo "Seeding groups...\n";
    $tableName = \SPPMod\SPPDB\SPPDB::sppTable('sppgroups');
    
    // Check if group exists
    $res = $db->execute_query("SELECT id FROM {$tableName} WHERE id = 'admins'");
    if (empty($res)) {
        $db->execute_query("INSERT INTO {$tableName} (id, name, description) VALUES ('admins', 'SPP Administrators', 'Full system access')");
        echo "Inserted 'admins' group into plural table.\n";
    } else {
        echo "Group 'admins' already exists in plural table.\n";
    }
    
    // Seed Staff
    $staffTable = \SPPMod\SPPDB\SPPDB::sppTable('staffs');
    $staffCheck = $db->execute_query("SELECT id FROM {$staffTable} WHERE name LIKE 'Satya%'");
    if (empty($staffCheck)) {
        $db->execute_query("INSERT INTO {$staffTable} (id, name, department) VALUES ('staff1', 'Satya Prakash', 'IT Department')");
        echo "Inserted 'Satya Prakash' into staffs table.\n";
    } else {
        echo "Staff 'Satya Prakash' already exists.\n";
    }
    
    // Verify
    $all = $db->execute_query("SELECT * FROM {$tableName}");
    echo "Total groups in table: " . count($all) . "\n";
    print_r($all);

} catch (Exception $e) {
    echo "SEED ERROR: " . $e->getMessage() . "\n";
}

<?php
/**
 * SPP Admin Bootstrap Script
 * 
 * This script initializes the administration environment by ensuring a default
 * administrator account exists in the database. 
 * 
 * Usage: php spp/admin/bootstrap_admin.php
 */

require_once dirname(__DIR__, 2) . '/spp/sppinit.php';
require_once dirname(__DIR__, 2) . '/global.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use SPPMod\SPPAuth\SPPUser;
use SPPMod\SPPDB\SPP_DB;
use SPP\SPPBase;

try {
    echo "--- SPP Admin Bootstrap ---\n";
    
    $db = new SPP_DB();
    $tableName = SPPBase::sppTable('users');
    
    // Check if admin already exists
    if (SPPUser::userExists('admin')) {
        echo "Check: Administrator 'admin' already exists.\n";
    } else {
        echo "Step: Creating default administrator 'admin'...\n";
        
        // Ensure standard 'Admin' role exists
        $roleCheck = $db->execute_query("SELECT id FROM roles WHERE role_name='Admin'");
        if (empty($roleCheck)) {
            echo "Step: Creating 'Admin' role...\n";
            $db->execute_query("INSERT INTO roles (role_name, description) VALUES ('Admin', 'System Administrator with full access')");
            $roleId = $db->lastInsertId();
        } else {
            $roleId = $roleCheck[0]['id'];
        }
        
        // Create user
        if (SPPUser::createUser('admin', 'admin123', 'active')) {
            $adminUser = new SPPUser('admin');
            $adminUser->assignRole('Admin');
            echo "Success: Created 'admin' with password 'admin123' and assigned 'Admin' role.\n";
            echo "IMPORTANT: Please change this password immediately after login.\n";
        } else {
            echo "Error: Failed to create admin user.\n";
        }
    }
    
    echo "--- Bootstrap Complete ---\n";
} catch (Exception $e) {
    echo "Fatal Error during bootstrap: " . $e->getMessage() . "\n";
    exit(1);
}

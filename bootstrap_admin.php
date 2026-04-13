<?php
require_once('spp/sppinit.php');
require_once('global.php');
require_once('vendor/autoload.php');

use SPPMod\SPPDB\SPPDB;
use SPPMod\SPPAuth\SPPUser;
use SPP\Scheduler;

try {
    $db = new SPPDB();
    $table = \SPP\SPPBase::sppTable('users');

    if (!$db->tableExists($table)) {
        echo "Table $table does not exist.\n";
        exit;
    }

    $res = $db->execute_query("SELECT * FROM $table");
    echo "Users in DB:\n";
    print_r($res);

    if (empty($res)) {
        echo "Creating default admin user...\n";
        // Check if sequence exists or create it
        if (!\SPPMod\SPPDB\SPPSequence::sequenceExists('sppuid')) {
            \SPPMod\SPPDB\SPPSequence::createSequence('sppuid', 1, 1);
        }

        if (SPPUser::createUser('admin', 'admin123')) {
            echo "Admin user created successfully (admin/admin123)\n";
        } else {
            echo "Failed to create admin user.\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

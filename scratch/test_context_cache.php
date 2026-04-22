<?php
// Enhanced verification script for context-aware configuration

define('SPP_BASE_DIR', dirname(__DIR__) . '/spp');
require_once dirname(__DIR__) . '/spp/sppinit.php';

use SPP\Module;
use SPP\Scheduler;

function testContext($app) {
    echo "--- Testing Context: $app ---\n";
    try {
        Scheduler::setContext($app);
        $dbuser = Module::getConfig('dbuser', 'sppdb');
        $dbhost = Module::getConfig('dbhost', 'sppdb');
        echo "Resolved Config for $app: user=$dbuser, host=$dbhost\n";
    } catch (\Exception $e) {
        echo "Error in context $app: " . $e->getMessage() . "\n";
    }
}

// 1. Rig up a fake context if needed, but let's just use existing ones
// We know 'default' exists.
testContext('default');

// 2. Test a non-existent context to see if it triggers our diagnostic (if we tried to connect)
// But here we just test getConfig.
testContext('sppadmin');

echo "\nVerification complete.\n";

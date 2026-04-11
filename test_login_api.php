<?php
require_once('spp/sppinit.php');
require_once('global.php');
require_once('vendor/autoload.php');

use SPPMod\SPPAuth\SPPAuth;

try {
    echo "Simulating login for 'admin'...\n";
    if (SPPAuth::login('admin', 'admin123')) {
        echo "SUCCESS: Logged in.\n";
        echo "Session data: ";
        print_r(\SPP\SPPSession::getSessionVar('__sppauth__'));
    } else {
        echo "FAILED: Login returned false.\n";
    }
} catch (Exception $e) {
    echo "ERROR: Exception caught: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

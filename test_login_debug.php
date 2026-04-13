<?php
define('SPP_BASE_DIR', __DIR__ . '/spp');
require_once SPP_BASE_DIR . '/sppinit.php';
require_once SPP_BASE_DIR . '/modules/spp/sppauth/class.sppuser.php';
require_once SPP_BASE_DIR . '/modules/spp/sppauth/class.sppusersession.php';
require_once SPP_BASE_DIR . '/modules/spp/sppauth/class.sppauth.php';

use SPPMod\SPPAuth\SPPAuth;

try {
    echo "Attempting login for 'admin'...\n";
    $session = SPPAuth::login('admin', 'admin');
    echo "Login Successful!\n";
    echo "User ID: " . $session->get('UserId') . "\n";
    echo "User Name: " . $session->get('UserName') . "\n";
} catch (\Exception $e) {
    echo "Login Failed: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

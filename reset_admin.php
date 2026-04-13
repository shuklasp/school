<?php
define('SPP_BASE_DIR', __DIR__ . '/spp');
require_once SPP_BASE_DIR . '/sppinit.php';
require_once SPP_BASE_DIR . '/modules/spp/sppauth/class.sppuser.php';

use SPPMod\SPPAuth\SPPUser;

try {
    echo "Resetting password for 'admin'...\n";
    $user = new SPPUser('admin');
    $user->setPassword('admin');
    echo "Password reset successful!\n";
} catch (\Exception $e) {
    echo "Reset failed: " . $e->getMessage() . "\n";
}

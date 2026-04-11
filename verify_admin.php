<?php
require_once('spp/sppinit.php');
require_once('global.php');
require_once('vendor/autoload.php');

$db = new \SPPMod\SPPDB\SPP_DB();
$res = $db->execute_query("SELECT * FROM users WHERE username='admin'");
echo "Admin user record:\n";
print_r($res);
if (!empty($res)) {
    echo "Verifying password 'admin123'...\n";
    if (password_verify('admin123', $res[0]['password_hash'])) {
        echo "Password verification: SUCCESS\n";
    } else {
        echo "Password verification: FAILED\n";
    }
}

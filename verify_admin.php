<?php
require_once('spp/sppinit.php');
require_once('global.php');
require_once('vendor/autoload.php');

$db = new \SPPMod\SPPDB\SPPDB();
$res = $db->execute_query("SELECT * FROM users WHERE username='admin'");
echo "Admin user record:\n";
print_r($res);
if (!empty($res)) {
    echo "Verifying password 'admin'...\n";
    if (password_verify('admin', $res[0]['password_hash'])) {
        echo "Password verification: SUCCESS\n";
    } else {
        echo "Password verification: FAILED\n";
    }
}

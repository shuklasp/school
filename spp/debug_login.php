<?php
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once('sppinit.php');

use SPPMod\SPPAuth\SPPUser;

$uname = 'admin';
$passwd = 'admin';

try {
    $user = new SPPUser($uname);
    echo "User found: " . $user->username . " (ID: " . $user->id . ")\n";
    echo "Status: " . ($user->status ?? 'N/A') . "\n";
    echo "Password Hash in DB: " . ($user->password_hash ?? 'N/A') . "\n";
    
    $verify = $user->verifyPassword($passwd);
    echo "Password verification: " . ($verify ? "SUCCESS" : "FAILURE") . "\n";
    
    if (!$verify) {
        $manual_hash = password_hash($passwd, PASSWORD_DEFAULT);
        echo "Manual hash of '$passwd': $manual_hash\n";
        echo "Does manual hash match DB? " . (password_verify($passwd, $user->password_hash) ? "YES" : "NO") . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

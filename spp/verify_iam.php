<?php
require_once('sppinit.php');
$db = new \SPPMod\SPPDB\SPPDB();

echo "--- IAM Verification ---\n";

// 1. Tables check
$tables = ['rights', 'roles', 'userroles', 'roleright', 'entity_roles'];
foreach ($tables as $t) {
    if ($db->tableExists($t)) {
        echo "Table '{$t}': EXISTS\n";
    } else {
        echo "Table '{$t}': MISSING\n";
    }
}

// 2. Data check / Seeding
if (\SPPMod\SPPAuth\SPPRight::count() == 0) {
    echo "Seeding test right...\n";
    $rt = new \SPPMod\SPPAuth\SPPRight();
    $rt->name = 'test.verify';
    $rt->description = 'Verification permission';
    $rt->save();
}

if (\SPPMod\SPPAuth\SPPRole::count() == 0) {
    echo "Seeding test role...\n";
    $rl = new \SPPMod\SPPAuth\SPPRole();
    $rl->role_name = 'Verifier';
    $rl->description = 'Role for testing';
    $rl->save();
}

// 3. Entity check
echo "Rights count: " . \SPPMod\SPPAuth\SPPRight::count() . "\n";
echo "Roles count: " . \SPPMod\SPPAuth\SPPRole::count() . "\n";
echo "Users count: " . \SPPMod\SPPAuth\SPPUser::count() . "\n";

echo "--- Done ---\n";

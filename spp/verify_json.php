<?php
require_once('sppinit.php');
$users = \SPPMod\SPPAuth\SPPUser::find_all();
echo "--- JSON Serialization Test ---\n";
if (count($users) > 0) {
    $firstUser = $users[0];
    $json = json_encode($firstUser);
    echo "JSON: " . $json . "\n";
    if (strpos($json, '"username"') !== false) {
        echo "SUCCESS: Attributes found in JSON.\n";
    } else {
        echo "FAILURE: Attributes NOT found in JSON. Got: " . $json . "\n";
    }
} else {
    echo "No users found to test.\n";
}
echo "--- Done ---\n";

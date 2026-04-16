<?php
require_once 'sppinit.php';
use SPPMod\SPPGroup\SPPGroupMember;

try {
    $gm = new SPPGroupMember();
    echo "Attributes: " . print_r($gm->getAttributes(), true) . "\n";
    $gm->groupid = 1;
    echo "Successfully set groupid\n";
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

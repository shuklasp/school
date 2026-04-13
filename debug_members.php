<?php
require_once __DIR__ . '/spp/sppinit.php';
require_once __DIR__ . '/spp/modules/spp/sppgroup/class.sppgrouploader.php';
require_once __DIR__ . '/spp/modules/spp/sppgroup/class.sppgroup.php';

use SPPMod\SPPGroup\SPPGroup;

$id = "studentclass"; // From user's open document
echo "Testing Load for: $id\n";

try {
    $group = new SPPGroup();
    $group->load($id);
    echo "Group Loaded: " . $group->get('name') . "\n";
    
    echo "Fetching members...\n";
    $members = $group->getMembers(true);
    echo "Found " . count($members) . " members.\n";
    
    foreach ($members as $m) {
        $ent = $m['entity'];
        echo "- " . get_class($ent) . " #" . $ent->getId() . " (Direct: " . ($m['direct'] ? "YES" : "NO") . ")\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

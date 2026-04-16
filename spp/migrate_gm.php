<?php
require_once 'sppinit.php';
$db = new \SPPMod\SPPDB\SPPDB();

try {
    echo "Altering groupid column...\n";
    $db->execute_query('ALTER TABLE sppgroupmembers MODIFY groupid VARCHAR(100)');
    
    echo "Dropping legacy columns...\n";
    // Using try-catch for drops in case they've already been dropped
    try { $db->execute_query('ALTER TABLE sppgroupmembers DROP COLUMN group_id'); } catch (Exception $e) { echo "Note: group_id drop failed or already dropped.\n"; }
    try { $db->execute_query('ALTER TABLE sppgroupmembers DROP COLUMN member_entity'); } catch (Exception $e) { echo "Note: member_entity drop failed or already dropped.\n"; }
    
    echo "Migration complete.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}

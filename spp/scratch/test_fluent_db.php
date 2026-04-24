<?php
/**
 * Fluent Query Builder Verification Script
 */
require_once dirname(__DIR__) . '/sppinit.php';

use SPPMod\SPPDB\SPPDB;

$db = new SPPDB();

echo "--- Test 1: Simple Select with WHERE ---\n";
$query = $db->table('users')->where('id', 1);
echo "SQL: " . $query->toSql() . "\n";
print_r($query->getBindings());

echo "\n--- Test 2: Nested Grouping ---\n";
$query = $db->table('users')
    ->where('status', 'active')
    ->where(function($q) {
        $q->where('role', 'admin')
          ->orWhere('role', 'editor');
    });
echo "SQL: " . $query->toSql() . "\n";
print_r($query->getBindings());

echo "\n--- Test 3: Join and OrderBy ---\n";
$query = $db->table('users as u')
    ->select('u.name', 'p.title')
    ->join('posts as p', 'u.id', '=', 'p.user_id')
    ->orderBy('u.created_at', 'DESC')
    ->limit(5);
echo "SQL: " . $query->toSql() . "\n";

echo "\n--- Test 4: Raw SQL ---\n";
$query = $db->table('logs')
    ->whereRaw('created_at > NOW() - INTERVAL 1 DAY')
    ->selectRaw('count(*) as count');
echo "SQL: " . $query->toSql() . "\n";

echo "\n--- Test 5: Execution Check (Database required) ---\n";
try {
    $count = $db->table('users')->count();
    echo "User Count result: $count\n";
} catch (\Exception $e) {
    echo "Execution failed (this is expected if DB is empty/missing): " . $e->getMessage() . "\n";
}

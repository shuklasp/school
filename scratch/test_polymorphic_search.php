<?php
require_once dirname(__DIR__) . '/spp/sppinit.php';

$q = 'A';
$results = [];

// 1. Search Users
$db = new \SPPMod\SPPDB\SPPDB();
try {
    $userTable = \SPPMod\SPPDB\SPPDB::sppTable('users');
    $users = $db->execute_query("SELECT id, username as name FROM {$userTable} WHERE username LIKE ?", ["%{$q}%"]);
    foreach ($users as $u) {
        $results[] = [
            'id' => $u['id'],
            'name' => $u['name'],
            'class' => 'SPPMod\SPPAuth\SPPUser',
            'icon' => '👤',
            'type' => 'User'
        ];
    }
} catch (Exception $e) {
    echo "User search error: " . $e->getMessage() . "\n";
}

// 2. Search Groups
try {
    $groupTable = \SPPMod\SPPDB\SPPDB::sppTable('sppgroup');
    $groups = $db->execute_query("SELECT id, groupname as name FROM {$groupTable} WHERE groupname LIKE ?", ["%{$q}%"]);
    foreach ($groups as $g) {
        $results[] = [
            'id' => $g['id'],
            'name' => $g['name'],
            'class' => 'SPPMod\SPPEntity\SPPGroup',
            'icon' => '👥',
            'type' => 'Group'
        ];
    }
} catch (Exception $e) {
    echo "Group search error: " . $e->getMessage() . "\n";
}

// 3. Search Login-Enabled Entities (Staff, etc.)
$entityConfDir = SPP_BASE_DIR . '/../etc/apps/default/entities';
if (is_dir($entityConfDir)) {
    $files = glob($entityConfDir . '/*.yml');
    foreach ($files as $file) {
        $config = \SPPMod\SPPConfig\SPPConfig::loadYaml($file);
        if ($config && isset($config['login_enabled']) && $config['login_enabled']) {
            $tableName = $config['table'];
            $entityClass = 'App\Default\Entities\\' . ucfirst(basename($file, '.yml'));
            $icon = $config['icon'] ?? '🏷️';
            $labelField = $config['label_field'] ?? 'name';
            
            try {
                $table = \SPPMod\SPPDB\SPPDB::sppTable($tableName);
                $entities = $db->execute_query("SELECT id, {$labelField} as name FROM {$table} WHERE {$labelField} LIKE ?", ["%{$q}%"]);
                foreach ($entities as $e) {
                    $results[] = [
                        'id' => $e['id'],
                        'name' => $e['name'],
                        'class' => $entityClass,
                        'icon' => $icon,
                        'type' => basename($file, '.yml')
                    ];
                }
            } catch (Exception $e) {
                echo "Entity search error for {$entityClass}: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "Search Results for '{$q}':\n";
print_r($results);

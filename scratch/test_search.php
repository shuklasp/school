<?php
define('SPP_BASE_DIR', dirname(__DIR__) . '/spp');
require_once dirname(__DIR__) . '/spp/sppinit.php';

use SPPMod\SPPDB\SPPDB;

echo "--- CHECKING USERS ---\n";
$db = new SPPDB();
$table = SPPDB::sppTable('users');
$users = $db->execute_query("SELECT id, username, email FROM {$table}");
print_r($users);

echo "\n--- CHECKING ENTITIES ---\n";
$entitiesDir = __DIR__ . '/etc/apps/default/entities';
if (is_dir($entitiesDir)) {
    $files = glob($entitiesDir . '/*.yml');
    foreach ($files as $file) {
        echo "Found: " . basename($file) . "\n";
    }
} else {
    echo "Entities dir not found: $entitiesDir\n";
}

echo "\n--- TESTING SEARCH LOGIC (SIMULATED) ---\n";
$query = 'Staff';
$results = [];

$entitiesDir = __DIR__ . '/etc/apps/default/entities';
if (is_dir($entitiesDir)) {
    $files = glob($entitiesDir . '/*.yml');
    foreach ($files as $file) {
        $name = basename($file, '.yml');
        $config = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
        
        if (empty($config['login_enabled'])) {
            echo "Skipping $name (login_enabled: false)\n";
            continue;
        }

        echo "Searching in $name...\n";
        $table = $config['table'] ?? '';
        if (empty($table)) continue;

        $searchCol = 'name';
        try {
            $columns = $db->execute_query("SHOW COLUMNS FROM {$table}");
            $foundCol = false;
            foreach (['name', 'title', 'label', 'username', 'id'] as $candidate) {
                foreach ($columns as $col) {
                    if ($col['Field'] === $candidate) {
                        $searchCol = $candidate;
                        $foundCol = true;
                        break 2;
                    }
                }
            }
            
            $sql = "SELECT id, {$searchCol} as display_name FROM {$table} WHERE {$searchCol} LIKE ? LIMIT 5";
            $data = $db->execute_query($sql, ["%{$query}%"]);
            echo "Found " . count($data) . " results in $table\n";
            foreach ($data as $r) {
                $results[] = [
                    'id' => $r['id'],
                    'name' => $r['display_name'],
                    'type' => 'custom',
                    'class' => "App\\Default\\Entities\\" . ucfirst($name)
                ];
            }
        } catch (Exception $e) {
            echo "Error searching $table: " . $e->getMessage() . "\n";
        }
    }
}

print_r($results);

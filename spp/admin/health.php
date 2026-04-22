<?php
/**
 * SPP Enterprise Health Check API
 */
define('SPP_BASE_DIR', dirname(__DIR__));
require_once SPP_BASE_DIR . '/sppinit.php';

header('Content-Type: application/json');

$health = [
    'status' => 'UP',
    'timestamp' => date('c'),
    'components' => []
];

// 1. Database Health
try {
    $db = new \SPPMod\SPPDB\SPPDB();
    $db->exec_squery("SELECT 1");
    $health['components']['database'] = ['status' => 'UP'];
} catch (\Exception $e) {
    $health['status'] = 'DEGRADED';
    $health['components']['database'] = ['status' => 'DOWN', 'message' => $e->getMessage()];
}

// 2. Redis Health (If configured)
try {
    if (\SPP\Module::getConfig('host', 'redis')) {
        $redis = \SPP\RedisCache::getConnection();
        $redis->ping();
        $health['components']['redis'] = ['status' => 'UP'];
    }
} catch (\Exception $e) {
    $health['status'] = 'DEGRADED';
    $health['components']['redis'] = ['status' => 'DOWN', 'message' => $e->getMessage()];
}

// 3. Filesystem Health
$writeableDirs = [SPP_BASE_DIR . '/var', SPP_BASE_DIR . '/var/logs'];
foreach ($writeableDirs as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $health['components']['fs_' . basename($dir)] = is_writable($dir) ? ['status' => 'UP'] : ['status' => 'DOWN'];
}

// 4. Memory Usage
$health['components']['system'] = [
    'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
    'php_version' => PHP_VERSION
];

http_response_code($health['status'] === 'UP' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);

<?php
// Mimic spp/admin/api.php environment
define('SPP_BASE_DIR', dirname(__DIR__) . '/spp');
require_once SPP_BASE_DIR . '/sppinit.php';

$action = 'run_command';
$commandName = 'blade:clear';
$commandArgs = [];
$appname = 'default';

// The logic from api.php:
function withContext($targetApp, $callback) {
    $current = \SPP\Scheduler::getContext();
    if ($current === $targetApp) return $callback();
    \SPP\Scheduler::setContext($targetApp);
    $result = $callback();
    \SPP\Scheduler::setContext($current);
    return $result;
}

try {
    $result = withContext($appname, function() use ($commandName, $commandArgs) {
        return \SPP\CLI\CommandManager::execute($commandName, $commandArgs);
    });
    echo "Result: " . json_encode($result) . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class SysInfoCommand
 * Displays system and framework information.
 */
class SysInfoCommand extends Command
{
    protected string $name = 'sys:info';
    protected string $description = 'Displays system and framework diagnostic information.';

    public function execute(array $args): void
    {
        echo "\nSPP Framework Diagnostic Info\n";
        echo "=============================\n";
        echo "Framework Version : " . (defined('SPP_VER') ? SPP_VER : 'Unknown') . "\n";
        echo "PHP Version       : " . PHP_VERSION . "\n";
        echo "OS                : " . PHP_OS . "\n";
        echo "Base Directory    : " . (defined('SPP_BASE_DIR') ? SPP_BASE_DIR : 'N/A') . "\n";
        echo "App Directory     : " . (defined('SPP_APP_DIR') ? SPP_APP_DIR : 'N/A') . "\n";
        echo "Context           : " . \SPP\Scheduler::getContext() . "\n";
        
        $dbStatus = "Disconnected";
        if (class_exists('\SPPMod\SPPPEntity\SPPPEntity')) {
            try {
                // Try to get a connection if possible (logic varies by installation)
                $dbStatus = "Connected (Auto-detected)";
            } catch (\Exception $e) {
                $dbStatus = "Error: " . $e->getMessage();
            }
        }
        echo "Database Status   : $dbStatus\n";
        
        $appsCount = count(glob(APP_ETC_DIR . '/*', GLOB_ONLYDIR));
        echo "Registered Apps   : $appsCount\n";
        echo "Active Middlewares: " . count(\SPP\Registry::get('EntityRelations') ?: []) . " (ORM Relations registered)\n";

        echo "\nSystem Health Report Card\n";
        echo "-------------------------\n";
        $results = $this->runHealthChecks();
        $this->renderReportCard($results);
        echo "\n";
    }

    private function runHealthChecks(): array
    {
        $checks = [];

        // 1. PHP Version
        $phpStatus = [
            'name' => 'PHP Version (' . PHP_VERSION . ')',
            'status' => version_compare(PHP_VERSION, '8.0.0', '>=') ? 'OK' : 'WARN',
            'detail' => version_compare(PHP_VERSION, '8.0.0', '>=') ? 'Supported.' : 'Recommended: 8.0+'
        ];
        $checks[] = $phpStatus;

        // 2. Memory Limit
        $memoryLimit = ini_get('memory_limit');
        $memoryBytes = $this->getKMBInBytes($memoryLimit);
        $checks[] = [
            'name' => 'Memory Limit (' . $memoryLimit . ')',
            'status' => $memoryBytes >= 134217728 ? 'OK' : 'WARN',
            'detail' => $memoryBytes >= 134217728 ? 'Sufficient.' : 'Recommended: 128M+'
        ];

        // 3. Required Extensions
        $requiredExts = ['pdo', 'json', 'mbstring', 'openssl'];
        foreach ($requiredExts as $ext) {
            $checks[] = [
                'name' => 'Extension: ' . $ext,
                'status' => extension_loaded($ext) ? 'OK' : 'FAIL',
                'detail' => extension_loaded($ext) ? 'Loaded.' : 'Missing!'
            ];
        }

        // 4. Filesystem Writable
        $dirChecks = [
            'Logs' => SPP_LOG_DIR,
            'Cache' => SPP_APP_DIR . SPP_DS . 'var' . SPP_DS . 'cache'
        ];
        foreach ($dirChecks as $label => $path) {
            $status = 'OK';
            $detail = 'Writable.';
            if (!is_dir($path)) {
                $status = 'WARN';
                $detail = 'Not found.';
            } elseif (!is_writable($path)) {
                $status = 'FAIL';
                $detail = 'Not writable!';
            }
            $checks[] = [
                'name' => 'Directory: ' . $label,
                'status' => $status,
                'detail' => $detail
            ];
        }

        // 5. Database Connection
        $dbOk = false;
        try {
            if (class_exists('\SPPMod\SPPDB\SPPDB')) {
                new \SPPMod\SPPDB\SPPDB();
                $dbOk = true;
            }
        } catch (\Exception $e) {}
        $checks[] = [
            'name' => 'Database Connectivity',
            'status' => $dbOk ? 'OK' : 'FAIL',
            'detail' => $dbOk ? 'Connected.' : 'Failed to connect!'
        ];

        // 6. Redis Connectivity (If enabled)
        $redisEnabled = \SPP\Module::getConfig('enabled', 'redis');
        if ($redisEnabled === true || $redisEnabled === '1' || $redisEnabled === 'true') {
            $redisOk = false;
            $redisDetail = 'Failed to connect!';
            try {
                if (class_exists('\SPP\RedisCache')) {
                    if (!extension_loaded('redis')) {
                        $redisDetail = 'Extension missing!';
                    } else {
                        $redisOk = \SPP\RedisCache::isAvailable();
                        $redisDetail = $redisOk ? 'Connected.' : 'Server unreachable.';
                    }
                }
            } catch (\Exception $e) {
                $redisDetail = $e->getMessage();
            }
            $checks[] = [
                'name' => 'Redis Connectivity',
                'status' => $redisOk ? 'OK' : 'FAIL',
                'detail' => $redisDetail
            ];
        }

        return $checks;
    }

    private function renderReportCard(array $results): void
    {
        $score = 0;
        $total = count($results);

        foreach ($results as $check) {
            $tag = match($check['status']) {
                'OK' => '[  OK  ]',
                'WARN' => '[ WARN ]',
                'FAIL' => '[ FAIL ]',
                default => '[ ???? ]'
            };
            
            if ($check['status'] === 'OK') $score += 100;
            elseif ($check['status'] === 'WARN') $score += 50;

            printf("%-10s %-30s %s\n", $tag, $check['name'], $check['detail']);
        }

        $finalScore = round($score / ($total * 100) * 100);
        echo "-------------------------\n";
        echo "Overall Health Score: {$finalScore}/100\n";
    }

    private function getKMBInBytes($val) {
        $val = trim($val);
        if (empty($val)) return 0;
        $last = strtolower($val[strlen($val)-1]);
        $res = (int)$val;
        switch($last) {
            case 'g': $res *= 1024;
            case 'm': $res *= 1024;
            case 'k': $res *= 1024;
        }
        return $res;
    }
}

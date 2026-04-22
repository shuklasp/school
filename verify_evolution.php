<?php
namespace App\Test\Services {
    class LoggerService {
        public function log($msg) { echo "[DI-LOG] $msg\n"; }
    }

    class UserService {
        protected $logger;
        public function __construct(LoggerService $logger) {
            $this->logger = $logger;
        }
        public function hello() { $this->logger->log("Hello from UserService with auto-wired Logger!"); }
    }
}

namespace {
    require_once(__DIR__ . '/spp/sppinit.php');
    
    echo "--- SPP Service Container & CLI Verification ---\n";

    $app = \SPP\App::getApp();
    echo "Testing DI Container (Auto-wiring)...\n";
    
    try {
        // Test auto-wiring
        $userService = $app->make(\App\Test\Services\UserService::class);
        $userService->hello();
        echo "  [OK] Successfully auto-wired UserService dependencies.\n";

        // Test singleton behavior
        $app->singleton('config', function() {
            return (object)['site_name' => 'SPP Framework'];
        });
        
        $config1 = $app->make('config');
        $config2 = $app->make('config');
        
        if ($config1 === $config2) {
            echo "  [OK] Singleton resolution verified.\n";
        } else {
            echo "  [ERROR] Singleton resolution failed.\n";
        }

    } catch (\Exception $e) {
        echo "  [ERROR] " . $e->getMessage() . "\n";
    }

    echo "\nTesting CLI Discovery...\n";
    \SPP\CLI\CommandManager::discover();
    $commands = \SPP\Registry::get('CLI_COMMANDS');
    if (isset($commands['list']) && isset($commands['sys:info'])) {
        echo "  [OK] Registry contains 'list' and 'sys:info' command classes.\n";
    } else {
        echo "  [ERROR] CLI commands not found in Registry.\n";
    }
}

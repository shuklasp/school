<?php
namespace SPP\CLI;

/**
 * Class CommandManager
 * Handles the discovery and registration of SPP CLI commands.
 */
class CommandManager
{
    /**
     * Discovers all available commands from core and apps.
     *
     * @return array<string, Command>
     */
    public static function discover(): array
    {
        $discoveredCommands = [];

        // 1. Scan CORE Commands
        $coreCmdDir = dirname(__DIR__) . '/commands';
        if (is_dir($coreCmdDir)) {
            foreach (glob($coreCmdDir . '/*.php') as $file) {
                require_once $file;
                $className = basename($file, '.php');
                $class = 'SPP\\CLI\\Commands\\' . $className;
                if (class_exists($class)) {
                    $cmdObj = new $class();
                    if ($cmdObj instanceof Command) {
                        $discoveredCommands[$cmdObj->getName()] = $cmdObj;
                    }
                }
            }
        }

        // 2. Scan APP Commands (for active app)
        if (class_exists('\SPP\App')) {
            $context = \SPP\Scheduler::getContext();
            if ($context && $context !== 'default') {
                $appCmdDir = SPP_APP_DIR . "/src/{$context}/commands";
                if (is_dir($appCmdDir)) {
                    foreach (glob($appCmdDir . '/*.php') as $file) {
                        require_once $file;
                        $className = basename($file, '.php');
                        $class = "App\\" . ucfirst($context) . "\\Commands\\" . $className;
                        if (class_exists($class)) {
                            $cmdObj = new $class();
                            if ($cmdObj instanceof Command) {
                                $discoveredCommands[$cmdObj->getName()] = $cmdObj;
                            }
                        }
                    }
                }
            }
        }

        \SPP\Registry::register('CLI_COMMANDS', $discoveredCommands);
        return $discoveredCommands;
    }
}

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
        $coreCmdDir = defined('SPP_BASE_DIR') ? SPP_BASE_DIR . '/commands' : dirname(__DIR__) . '/commands';
        $coreCmdDir = str_replace('\\', '/', $coreCmdDir);
        if (is_dir($coreCmdDir)) {
            $files = glob($coreCmdDir . '/*.php');
            foreach ($files as $file) {
                try {
                    require_once $file;
                    $className = basename($file, '.php');
                    $class = 'SPP\\CLI\\Commands\\' . $className;
                    if (class_exists($class)) {
                        $reflection = new \ReflectionClass($class);
                        if ($reflection->isAbstract()) continue;
                        $cmdObj = new $class();
                        if ($cmdObj instanceof Command) {
                            $discoveredCommands[$cmdObj->getName()] = $cmdObj;
                        }
                    }
                } catch (\Throwable $e) {
                    error_log("Failed to load command from {$file}: " . $e->getMessage());
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

        if (class_exists('\SPP\Module')) {
            \SPP\Module::loadAllModules();
            $modules = \SPP\Registry::get('__modobj');
            if (is_array($modules)) {
                foreach ($modules as $modSlug => $modObj) {
                    if (!($modObj instanceof \SPP\Module)) {
                        // Handle case where Registry stores indices instead of objects
                        $modObj = \SPP\Module::getModule($modSlug);
                    }
                    
                    $modDir = $modObj->ModPath ?? null;
                    if (!$modDir || !is_dir($modDir . '/commands')) continue;

                    foreach (glob($modDir . '/commands/*.php') as $file) {
                        require_once $file;
                        $className = basename($file, '.php');
                        
                        // Attempt namespace resolution based on standard module structure
                        // SPPMod\{ModuleName}\Commands\{ClassName}
                        $modName = $modObj->InternalName ?? basename($modDir);
                        $nsMod = str_replace('.', '\\', ucwords($modName, '.'));
                        $class = "SPPMod\\{$nsMod}\\Commands\\{$className}";

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

    /**
     * Executes a command by name with given arguments.
     * 
     * @param string $name
     * @param array $args
     * @return array Result with status and output
     */
    public static function execute(string $name, array $args = []): array
    {
        $commands = self::discover();
        if (!isset($commands[$name])) {
            return ['success' => false, 'error' => "Command '{$name}' not found."];
        }

        $command = $commands[$name];
        
        // Capture output
        ob_start();
        try {
            // Prepare $args as if it were from the CLI
            $cliArgs = ['spp.php', $name];
            foreach ($args as $k => $v) {
                if (is_numeric($k)) $cliArgs[] = $v;
                else $cliArgs[] = "--{$k}={$v}";
            }
            
            $command->execute($cliArgs);
            $output = ob_get_clean();
            return ['success' => true, 'output' => $output];
        } catch (\Exception $e) {
            ob_end_clean();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

<?php
namespace SPP\CLI\Commands;

use SPP\CLI\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * Class DebugCommand
 * Toggles the global SPP debug state.
 */
class DebugCommand extends Command
{
    protected string $name = 'sys:debug';
    protected string $description = 'Toggle global framework debug mode (on|off)';

    public function execute(array $args): void
    {
        $state = isset($args[2]) ? strtolower($args[2]) : null;
        
        if ($state !== 'on' && $state !== 'off') {
            echo "Usage: php spp.php sys:debug on|off\n";
            return;
        }

        $settingsFile = SPP_ETC_DIR . '/global-settings.yml';
        if (!file_exists($settingsFile)) {
            echo "Error: global-settings.yml not found in " . SPP_ETC_DIR . "\n";
            return;
        }

        $config = Yaml::parseFile($settingsFile);
        $enabled = ($state === 'on');
        
        // Ensure settings block exists
        if (!isset($config['settings'])) {
            $config['settings'] = [];
        }
        
        $config['settings']['debug'] = $enabled;
        
        $yaml = Yaml::dump($config, 4, 4);
        file_put_contents($settingsFile, $yaml);

        echo "Global Debug Mode: " . ($enabled ? "ENABLED" : "DISABLED") . "\n";
        echo "Diagnostics and API Flight Recorder " . ($enabled ? "activated" : "deactivated") . ".\n";
    }
}

<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * Class DeleteAppCommand
 * Removes an SPP application context and its resources.
 */
class DeleteAppCommand extends Command
{
    protected string $name = 'delete:app';
    protected string $description = 'Delete an SPP application context and all its data';

    public function execute(array $args): void
    {
        // Support both CLI positional (2) and API associative/mapped args
        $appName = $args['AppNameToConfirm'] ?? $args[2] ?? $args[0] ?? null;
        $force = in_array('--force', $args) || isset($args['--force']) || isset($args[1]) && $args[1] === '--force';

        if (!$appName) {
            echo "Usage: php spp.php delete:app <AppName> [--force]\n";
            return;
        }

        if ($appName === 'default' || $appName === 'admin') {
            echo "Error: Cannot delete system applications.\n";
            return;
        }

        if (!$force) {
            echo "Confirmation Required: Use --force to confirm deletion of '{$appName}'.\n";
            return;
        }

        // 1. Remove Directories
        $dirs = [
            SPP_APP_DIR . "/etc/apps/{$appName}",
            SPP_APP_DIR . "/src/{$appName}",
            SPP_APP_DIR . "/resources/{$appName}",
        ];

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $this->recursiveDelete($dir);
                echo "Deleted directory: {$dir}\n";
            }
        }

        // 2. Remove from global-settings.yml
        $settingsPath = SPP_APP_DIR . "/spp/etc/global-settings.yml";
        if (file_exists($settingsPath)) {
            $settings = Yaml::parseFile($settingsPath);
            if (isset($settings['apps'][$appName])) {
                unset($settings['apps'][$appName]);
                file_put_contents($settingsPath, Yaml::dump($settings, 10, 2));
                echo "Removed '{$appName}' from global-settings.yml.\n";
            }
        }

        echo "Success: Application '{$appName}' deleted.\n";
    }

    private function recursiveDelete($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveDelete("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}

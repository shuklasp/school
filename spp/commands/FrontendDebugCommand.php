<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class FrontendDebugCommand
 * Bugfixing pillar for React/Vue: Toggles CDN versions between production and development.
 */
class FrontendDebugCommand extends Command
{
    protected string $name = 'frontend:debug';
    protected string $description = 'Toggle Frontend CDN development mode (on|off)';

    public function execute(array $args): void
    {
        $state = $args[2] ?? 'on';
        $file = SPP_APP_DIR . '/spp/admin/js/spp-loader.js';

        if (!file_exists($file)) {
            echo "Error: spp-loader.js not found.\n";
            return;
        }

        $content = file_get_contents($file);
        if ($state === 'on') {
            $content = str_replace('https://esm.sh/', 'https://esm.sh/?dev=', $content);
            echo "Frontend Development Mode Enabled (using development builds from CDN).\n";
        } else {
            $content = str_replace('https://esm.sh/?dev=', 'https://esm.sh/', $content);
            echo "Frontend Production Mode Enabled.\n";
        }

        file_put_contents($file, $content);
    }
}

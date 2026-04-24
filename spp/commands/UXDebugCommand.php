<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class UXDebugCommand
 * Bugfixing pillar for SPP-UX: Toggles diagnostic logging in the browser.
 */
class UXDebugCommand extends Command
{
    protected string $name = 'ux:debug';
    protected string $description = 'Toggle SPP-UX verbose logging (on|off)';

    public function execute(array $args): void
    {
        $state = $args[2] ?? 'on';
        $file = SPP_APP_DIR . '/spp/admin/js/sppux.js';

        if (!file_exists($file)) {
            echo "Error: sppux.js not found.\n";
            return;
        }

        $content = file_get_contents($file);
        if ($state === 'on') {
            if (strpos($content, 'window.SPP_UX_DEBUG = true;') === false) {
                $content = "window.SPP_UX_DEBUG = true;\n" . $content;
            }
            echo "SPP-UX Debugging Enabled.\n";
        } else {
            $content = str_replace("window.SPP_UX_DEBUG = true;\n", '', $content);
            echo "SPP-UX Debugging Disabled.\n";
        }

        file_put_contents($file, $content);
    }
}

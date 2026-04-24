<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class AppServeCommand
 * Universal Execution pillar: Starts a development server for Native, Blade, or Admin apps.
 */
class AppServeCommand extends Command
{
    protected string $name = 'serve';
    protected string $description = 'Start a local development server for the current application';

    public function execute(array $args): void
    {
        $port = 8000;
        foreach ($args as $arg) {
            if (strpos($arg, '--port=') === 0) {
                $port = (int)substr($arg, 7);
            }
        }

        $context = \SPP\Scheduler::getContext() ?: 'default';
        $root = SPP_APP_DIR;

        echo "\n\033[32mSPP Development Server Started\033[0m\n";
        echo "------------------------------\n";
        echo "Context:    \033[36m{$context}\033[0m\n";
        echo "Local URL:  \033[34mhttp://localhost:{$port}\033[0m\n";
        echo "Admin URL:  \033[34mhttp://localhost:{$port}/spp/admin/\033[0m\n";
        echo "------------------------------\n";
        echo "Press Ctrl+C to stop.\n\n";

        // Use PHP's built-in server as the primary driver for Native/Blade
        $cmd = "php -S localhost:{$port} -t {$root}";
        passthru($cmd);
    }
}

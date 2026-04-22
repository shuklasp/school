<?php
namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class ListCommand
 * Lists all available SPP CLI commands.
 */
class ListCommand extends Command
{
    protected string $name = 'list';
    protected string $description = 'Lists all discovered SPP CLI commands.';

    public function execute(array $args): void
    {
        echo "\nSPP Framework CLI Utility\n";
        echo "---------------------------\n";
        echo "Available Commands:\n\n";

        $commands = \SPP\Registry::get('CLI_COMMANDS') ?: [];
        ksort($commands);

        foreach ($commands as $name => $cmd) {
            echo "  " . str_pad($name, 25) . $cmd->getDescription() . "\n";
        }
        echo "\n";
    }
}

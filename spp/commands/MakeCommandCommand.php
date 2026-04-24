<?php

namespace SPP\CLI\Commands;

/**
 * Class MakeCommandCommand
 * Scaffolds a new CLI command class.
 */
class MakeCommandCommand extends BaseMakeCommand
{
    protected string $name = 'make:command';
    protected string $description = 'Create a new CLI command class';

    public function execute(array $args): void
    {
        $name = $args[2] ?? null;
        if (!$name) {
            echo "Usage: php spp.php make:command <name> [--app=appname] [--command=cmd:name]\n";
            return;
        }

        $app = $this->getContext($args);
        $className = ucfirst($name);
        if (strpos(strtolower($className), 'command') === false) {
             $className .= 'Command';
        }

        $cmdName = '';
        foreach ($args as $arg) {
            if (strpos($arg, '--command=') === 0) {
                $cmdName = substr($arg, 10);
                break;
            }
        }
        if (!$cmdName) {
            $cmdName = strtolower($name);
        }

        $namespace = $this->getNamespace('Commands', $app);
        $targetDir = $this->getTargetDir('commands', $app);
        $targetPath = "{$targetDir}/{$className}.php";

        $success = $this->buildFromStub('command', $targetPath, [
            'namespace' => $namespace,
            'className' => $className,
            'commandName' => $cmdName,
            'description' => "Command description for {$cmdName}"
        ]);

        if ($success) {
            echo "Success: Command {$className} created at {$targetPath}\n";
            echo "Tip: The command will be automatically discovered by SPP CLI.\n";
        }
    }
}

<?php
namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class MakePythonCommand
 * Scaffolds a new Python service for SPP.
 */
class MakePythonCommand extends BaseMakeCommand
{
    protected string $name = 'make:python-service';
    protected string $description = 'Create a new Python service script';

    public function execute(array $args): void
    {
        $name = $args[2] ?? null;
        if (!$name) {
            echo "Usage: spp make:python-service <name> [--app=context]\n";
            return;
        }

        $app = $this->getContext($args);
        $className = ucfirst($name);
        $targetDir = $this->getTargetDir('services/python', $app);
        $targetPath = "{$targetDir}/service." . strtolower($name) . ".py";

        $success = $this->buildFromStub('python_service', $targetPath, [
            'CLASS_NAME' => $className
        ]);

        if ($success) {
            echo "Success: Python service {$className} created at {$targetPath}\n";
        }
    }
}

<?php
namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class MakeGoCommand
 * Scaffolds a new Go service for SPP.
 */
class MakeGoCommand extends BaseMakeCommand
{
    protected string $name = 'make:go-service';
    protected string $description = 'Create a new Go service script';

    public function execute(array $args): void
    {
        $name = $args[2] ?? null;
        if (!$name) {
            echo "Usage: spp make:go-service <name> [--app=context]\n";
            return;
        }

        $app = $this->getContext($args);
        $className = ucfirst($name);
        $targetDir = $this->getTargetDir('services/go', $app);
        $targetPath = "{$targetDir}/service." . strtolower($name) . ".go";

        $success = $this->buildFromStub('go_service', $targetPath, [
            'CLASS_NAME' => $className
        ]);

        if ($success) {
            echo "Success: Go service {$className} created at {$targetPath}\n";
        }
    }
}

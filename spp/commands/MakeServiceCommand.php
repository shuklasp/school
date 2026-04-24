<?php

namespace SPP\CLI\Commands;

/**
 * Class MakeServiceCommand
 * Scaffolds a new service class.
 */
class MakeServiceCommand extends BaseMakeCommand
{
    protected string $name = 'make:service';
    protected string $description = 'Create a new service class';

    public function execute(array $args): void
    {
        $name = $args[2] ?? null;
        if (!$name) {
            echo "Usage: php spp.php make:service <name> [--app=appname]\n";
            return;
        }

        $app = $this->getContext($args);
        $className = ucfirst($name);
        $namespace = $this->getNamespace('Services', $app);
        $targetDir = $this->getTargetDir('services', $app);
        $targetPath = "{$targetDir}/class." . strtolower($className) . ".php";

        $success = $this->buildFromStub('service', $targetPath, [
            'namespace' => $namespace,
            'className' => $className
        ]);

        if ($success) {
            echo "Success: Service {$className} created at {$targetPath}\n";
        }
    }
}

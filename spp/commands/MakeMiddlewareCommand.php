<?php

namespace SPP\CLI\Commands;

/**
 * Class MakeMiddlewareCommand
 * Scaffolds a new middleware class.
 */
class MakeMiddlewareCommand extends BaseMakeCommand
{
    protected string $name = 'make:middleware';
    protected string $description = 'Create a new middleware class';

    public function execute(array $args): void
    {
        $name = $args[2] ?? null;
        if (!$name) {
            echo "Usage: php spp.php make:middleware <name> [--app=appname]\n";
            return;
        }

        $app = $this->getContext($args);
        $className = ucfirst($name);
        
        $namespace = $this->getNamespace('Middleware', $app);
        $targetDir = $this->getTargetDir('middleware', $app);
        $targetPath = "{$targetDir}/class." . strtolower($className) . ".php";

        $success = $this->buildFromStub('middleware', $targetPath, [
            'namespace' => $namespace,
            'className' => $className
        ]);

        if ($success) {
            echo "Success: Middleware {$className} created at {$targetPath}\n";
            echo "Tip: Register it in spp/etc/middleware.yml or app-specific config.\n";
        }
    }
}

<?php

namespace SPP\CLI\Commands;

/**
 * Class MakeControllerCommand
 * Scaffolds a new controller class.
 */
class MakeControllerCommand extends BaseMakeCommand
{
    protected string $name = 'make:controller';
    protected string $description = 'Create a new controller class';

    public function execute(array $args): void
    {
        $name = $args[2] ?? null;
        if (!$name) {
            echo "Usage: php spp.php make:controller <name> [--app=appname] [--resource]\n";
            return;
        }

        $app = $this->getContext($args);
        $isResource = in_array('--resource', $args);
        $className = ucfirst($name);
        if (strpos(strtolower($className), 'controller') === false) {
             $className .= 'Controller';
        }

        $namespace = $this->getNamespace('Controllers', $app);
        $targetDir = $this->getTargetDir('controllers', $app);
        $targetPath = "{$targetDir}/class." . strtolower($className) . ".php";

        $success = $this->buildFromStub('controller', $targetPath, [
            'namespace' => $namespace,
            'className' => $className
        ]);

        if ($success) {
            echo "Success: Controller {$className} created at {$targetPath}\n";
        }
    }
}

<?php

namespace SPP\CLI\Commands;

/**
 * Class MakeModelCommand
 * Scaffolds a new model class.
 */
class MakeModelCommand extends BaseMakeCommand
{
    protected string $name = 'make:model';
    protected string $description = 'Create a new model class (Fluent-ready)';

    public function execute(array $args): void
    {
        $name = $args[2] ?? null;
        if (!$name) {
            echo "Usage: php spp.php make:model <name> [--app=appname] [--table=tablename]\n";
            return;
        }

        $app = $this->getContext($args);
        $className = ucfirst($name);
        
        $tableName = '';
        foreach ($args as $arg) {
            if (strpos($arg, '--table=') === 0) {
                $tableName = substr($arg, 8);
                break;
            }
        }
        if (!$tableName) {
            $tableName = strtolower($className) . 's';
        }

        $namespace = $this->getNamespace('Models', $app);
        $targetDir = $this->getTargetDir('models', $app);
        $targetPath = "{$targetDir}/class." . strtolower($className) . ".php";

        $success = $this->buildFromStub('model', $targetPath, [
            'namespace' => $namespace,
            'className' => $className,
            'tableName' => $tableName
        ]);

        if ($success) {
            echo "Success: Model {$className} created at {$targetPath}\n";
            echo "Tip: This model is pre-configured to use the Fluent Query Builder.\n";
        }
    }
}

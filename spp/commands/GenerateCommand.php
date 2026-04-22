<?php
namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class GenerateCommand
 * Scaffolds code components for the SPP framework.
 */
class GenerateCommand extends Command
{
    public function getName(): string
    {
        return 'make';
    }

    public function getDescription(): string
    {
        return 'Scaffold components (module, middleware, service)';
    }

    public function execute(array $args): void
    {
        $type = $args[2] ?? null;
        if (!$type) {
            echo "Usage: php spp.php make <module|middleware|service> <name>\n";
            return;
        }

        $name = $args[3] ?? null;
        if (!$name) {
            echo "Error: Name is required.\n";
            return;
        }

        switch ($type) {
            case 'module':
                $this->makeModule($name);
                break;
            case 'middleware':
                $this->makeMiddleware($name);
                break;
            default:
                echo "Error: Unknown type '{$type}'.\n";
        }
    }

    private function makeModule(string $name)
    {
        $modName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $modDir = SPP_APP_DIR . '/spp/modules/spp/' . $modName;
        
        if (is_dir($modDir)) {
            echo "Error: Module '{$modName}' already exists.\n";
            return;
        }

        mkdir($modDir, 0777, true);
        
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<module>\n    <name>{$modName}</name>\n    <namespace>SPPMod\\" . ucfirst($modName) . "</namespace>\n    <autoload>\n        <class name=\"" . ucfirst($modName) . "\" file=\"class.{$modName}.php\"/>\n    </autoload>\n</module>";
        file_put_contents($modDir . '/module.xml', $xml);

        $className = ucfirst($modName);
        $php = "<?php\nnamespace SPPMod\\{$className};\n\nclass {$className} extends \\SPP\\SPPObject\n{\n    public function __construct() {\n        // Initialize module\n    }\n}\n";
        file_put_contents($modDir . "/class.{$modName}.php", $php);

        echo "Success: Module {$modName} created at {$modDir}\n";
    }

    private function makeMiddleware(string $name)
    {
        $className = ucfirst(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $file = SPP_APP_DIR . '/spp/core/middleware/class.' . strtolower($className) . '.php';

        if (file_exists($file)) {
            echo "Error: Middleware '{$className}' already exists.\n";
            return;
        }

        $php = "<?php\nnamespace SPP\\Core\\Middleware;\n\nclass {$className} implements \\SPP\\Core\\MiddlewareInterface\n{\n    public function handle(array \$request, \\Closure \$next)\n    {\n        // Logic before\n        \$response = \$next(\$request);\n        // Logic after\n        return \$response;\n    }\n}\n";
        file_put_contents($file, $php);

        echo "Success: Middleware {$className} created at {$file}\n";
        echo "Tip: Register it in spp/etc/middleware.yml\n";
    }
}

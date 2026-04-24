<?php

namespace SPP\CLI\Commands;

/**
 * Class MakeModuleCommand
 * Scaffolds a new SPP module.
 */
class MakeModuleCommand extends BaseMakeCommand
{
    protected string $name = 'make:module';
    protected string $description = 'Create a new SPP module';

    public function execute(array $args): void
    {
        $name = $args[2] ?? null;
        if (!$name) {
            echo "Usage: php spp.php make:module <name>\n";
            return;
        }

        $modName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $modDir = SPP_APP_DIR . '/spp/modules/spp/' . $modName;

        if (is_dir($modDir)) {
            echo "Error: Module '{$modName}' already exists.\n";
            return;
        }

        mkdir($modDir, 0777, true);

        // Module Manifest (XML)
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<module>\n";
        $xml .= "    <name>{$modName}</name>\n";
        $xml .= "    <author>SPP CLI</author>\n";
        $xml .= "    <version>1.0</version>\n";
        $xml .= "    <description>Auto-generated {$modName} module.</description>\n";
        $xml .= "    <namespace>SPPMod\\" . ucfirst($modName) . "</namespace>\n";
        $xml .= "    <autoload>\n";
        $xml .= "        <class name=\"" . ucfirst($modName) . "\" file=\"class.{$modName}.php\"/>\n";
        $xml .= "    </autoload>\n";
        $xml .= "</module>\n";
        file_put_contents($modDir . '/module.xml', $xml);

        // Core Module Class
        $className = ucfirst($modName);
        $php = "<?php\n";
        $php .= "namespace SPPMod\\{$className};\n\n";
        $php .= "class {$className} extends \\SPP\\SPPObject\n";
        $php .= "{\n    public function __construct() {\n        // Initialize module\n    }\n}\n";
        file_put_contents($modDir . "/class.{$modName}.php", $php);

        echo "Success: Module {$modName} created at {$modDir}\n";
    }
}

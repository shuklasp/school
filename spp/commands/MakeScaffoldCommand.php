<?php

namespace SPP\CLI\Commands;

/**
 * Class MakeScaffoldCommand
 * Full stack scaffolding command for SPP.
 */
class MakeScaffoldCommand extends BaseMakeCommand
{
    protected string $name = 'make:scaffold';
    protected string $description = 'Create a full stack scaffold (Entity, DB, Controller, View)';

    public function execute(array $args): void
    {
        $entityName = $args[2] ?? null;
        if (!$entityName) {
            echo "Scaffold Entity Name (e.g. Product): ";
            $entityName = trim(fgets(STDIN));
        }
        
        if (!$entityName) {
            echo "Error: Entity name is required.\n";
            return;
        }

        $entityName = preg_replace('/[^a-zA-Z0-9_]/', '', $entityName);
        
        echo "Application/Context [default]: ";
        $appname = trim(fgets(STDIN)) ?: "default";
        
        echo "Database Table [" . strtolower($entityName) . "s]: ";
        $tableName = trim(fgets(STDIN)) ?: strtolower($entityName) . "s";
        
        $config = [
            'table' => $tableName,
            'id_field' => 'id',
            'sequence' => $tableName . '_seq',
            'extends' => '',
            'login_enabled' => false,
            'attributes' => [],
            'relations' => []
        ];

        echo "\nAttributes (e.g. name:varchar(255), price:decimal(10,2)):\n";
        echo "Attribute Name: ";
        while($attrName = trim(fgets(STDIN))) {
            echo "  Type [varchar(255)]: ";
            $attrType = trim(fgets(STDIN)) ?: "varchar(255)";
            $config['attributes'][$attrName] = $attrType;
            echo "Attribute Name: ";
        }

        // 1. Save Entity Definition
        echo "\nSaving Entity Definition... ";
        if (!class_exists('\SPPMod\SPPEntity\SPPEntity')) {
            require_once dirname(__DIR__) . '/sppinit.php';
        }
        \SPPMod\SPPEntity\SPPEntity::saveEntityDefinition($entityName, $appname, $config);
        echo "OK\n";

        // 2. Generate Controller
        echo "Generating Controller... ";
        $controllerName = ucfirst($entityName) . "Controller";
        $targetDir = SPP_APP_DIR . "/src/" . $appname . "/controllers";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $stub = file_get_contents(__DIR__ . '/stubs/scaffold_controller.stub');
        $content = str_replace(
            ['{{appname}}', '{{controllerName}}', '{{entityName}}'],
            [ucfirst($appname), $controllerName, ucfirst($entityName)],
            $stub
        );
        
        file_put_contents($targetDir . "/" . $controllerName . ".php", $content);
        echo "OK\n";

        // 3. Generate Views (Stub)
        echo "Generating Views (Skeleton)... ";
        $viewDir = SPP_APP_DIR . "/src/" . $appname . "/views/" . strtolower($entityName);
        if (!is_dir($viewDir)) mkdir($viewDir, 0777, true);
        file_put_contents($viewDir . "/index.php", "<!-- Scaffolded Index View for " . $entityName . " -->\n<h1>" . $entityName . " List</h1>");
        echo "OK\n";

        echo "\nSuccess: Full stack scaffold for {$entityName} created in {$appname} context.\n";
        
        // Final Sync hint
        $globalSettingsPath = SPP_ETC_DIR . '/global-settings.yml';
        if (file_exists($globalSettingsPath)) {
            $settings = \Symfony\Component\Yaml\Yaml::parseFile($globalSettingsPath);
            if (($settings['prototyping']['auto_evolution'] ?? 'manual') === 'manual') {
                echo "NOTE: Run 'php spp.php db:sync' to create the database table.\n";
            } else {
                echo "NOTE: Database table was auto-created/updated.\n";
            }
        }
    }
}

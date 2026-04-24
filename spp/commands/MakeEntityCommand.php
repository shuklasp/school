<?php

namespace SPP\CLI\Commands;

/**
 * Class MakeEntityCommand
 * Scaffolds a new SPPEntity definition.
 */
class MakeEntityCommand extends BaseMakeCommand
{
    protected string $name = 'make:entity';
    protected string $description = 'Create a new SPPEntity definition';

    public function execute(array $args): void
    {
        $entityName = $args[2] ?? null;
        if (!$entityName) {
            echo "Entity Name (e.g. Student): ";
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
        
        echo "Extends (Parent Entity class, optional): ";
        $extends = trim(fgets(STDIN));
        
        echo "Enable Login Support? (y/n) [n]: ";
        $login = strtolower(trim(fgets(STDIN))) === 'y';
        
        $config = [
            'table' => $tableName,
            'id_field' => 'id',
            'sequence' => $tableName . '_seq',
            'extends' => $extends,
            'login_enabled' => $login,
            'attributes' => [],
            'relations' => []
        ];

        echo "\nEntity Attributes (Press Enter on empty Name to finish):\n";
        while(true) {
            echo "  Attribute Name: ";
            $attrName = trim(fgets(STDIN));
            if (!$attrName) break;
            echo "  Type (e.g. varchar(255), int, text, timestamp) [varchar(255)]: ";
            $attrType = trim(fgets(STDIN)) ?: "varchar(255)";
            $config['attributes'][$attrName] = $attrType;
        }

        echo "\nEntity Relationships (Press Enter on empty Target to finish):\n";
        while(true) {
            echo "  Target Entity (e.g. \\App\\Entities\\Course): ";
            $target = trim(fgets(STDIN));
            if (!$target) break;
            echo "  Relation Type (OneToMany / ManyToMany) [OneToMany]: ";
            $type = trim(fgets(STDIN)) ?: "OneToMany";
            echo "  Foreign Key Field [" . strtolower($entityName) . "_id]: ";
            $fk = trim(fgets(STDIN)) ?: strtolower($entityName) . "_id";
            
            $rel = [
                'child_entity' => $target,
                'relation_type' => $type,
                'child_entity_field' => $fk
            ];
            
            if ($type === 'ManyToMany') {
                echo "  Pivot Table Name [" . strtolower($entityName) . "_" . strtolower(basename(str_replace('\\', '/', $target))) . "]: ";
                $rel['pivot_table'] = trim(fgets(STDIN)) ?: strtolower($entityName) . "_" . strtolower(basename(str_replace('\\', '/', $target)));
            }
            
            $config['relations'][] = $rel;
        }

        try {
            // Ensure SPP environment is bootstrapped enough for this
            if (!class_exists('\SPPMod\SPPEntity\SPPEntity')) {
                require_once dirname(__DIR__) . '/sppinit.php';
            }
            \SPPMod\SPPEntity\SPPEntity::saveEntityDefinition($entityName, $appname, $config);
            echo "\nSuccess: Entity {$entityName} saved and scaffolded in {$appname} context.\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

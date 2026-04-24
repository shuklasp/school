<?php
namespace SPP\CLI\Commands;

use SPPMod\SPPEntity\SPPEntity;

/**
 * Class DbSyncCommand
 * Synchronizes all entity definitions with the database schema.
 */
class DbSyncCommand extends BaseMakeCommand
{
    protected string $name = 'db:sync';
    protected string $description = 'Synchronize all entity definitions with the database schema';

    public function execute(array $args): void
    {
        echo "Scanning for entities...\n";
        $entities = SPPEntity::listAvailableEntities();
        
        if (empty($entities)) {
            echo "No entities found.\n";
            return;
        }

        echo "Found " . count($entities) . " entities. Starting sync...\n\n";

        foreach ($entities as $name => $info) {
            echo "Syncing {$name}... ";
            try {
                // We need to resolve the class or at least call install via a temporary object
                // The listAvailableEntities gives us the YAML context.
                
                // For a proper sync, we need the app context.
                // entities[$name]['path'] tells us where it is.
                // E.g. .../etc/apps/default/entities/product.yml
                
                $pathParts = explode(DIRECTORY_SEPARATOR, $info['path']);
                // Try to find the app name in the path
                $appIndex = array_search('apps', $pathParts);
                $appname = ($appIndex !== false) ? $pathParts[$appIndex + 1] : 'default';

                $config = \Symfony\Component\Yaml\Yaml::parseFile($info['path']);
                
                // Mock class metadata for the installer
                $className = "App\\" . ucfirst($appname) . "\\Entities\\" . ucfirst($name);
                
                // Trigger the install logic
                $db = new \SPPMod\SPPDB\SPPDB();
                $table = $config['table'] ?? strtolower($name).'s';
                $attributes = $config['attributes'] ?? [];

                if (!$db->tableExists($table)) {
                    $sql = 'create table %tab% (' . ($config['id_field'] ?? 'id') . ' varchar(20))';
                    $db->exec_squery($sql, $table);
                    echo "[CREATED TABLE] ";
                }
                
                $db->add_columns($table, $attributes);
                echo "[COLUMNS SYNCED] OK\n";
                
            } catch (\Exception $e) {
                echo "[ERROR] " . $e->getMessage() . "\n";
            }
        }

        echo "\nDatabase synchronization complete.\n";
    }
}

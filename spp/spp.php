#!/usr/bin/env php
<?php
/**
 * SPP CLI Toolkit (Developer Workbench)
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

define('SPP_APP_DIR', dirname(__DIR__, 1));

if ($argc < 2) {
    // Bootstrap for discovery
    require_once __DIR__ . '/sppinit.php';
    $commands = \SPP\CLI\CommandManager::discover();
    if (isset($commands['list'])) {
        $commands['list']->execute($argv);
    } else {
        echo "SPP CLI: Use 'php spp.php list' to see available commands.\n";
    }
    exit(1);
}

$command = $argv[1];

// Load Composer autoloader for Yaml support
require_once __DIR__ . '/sppinit.php';

// Load CLI settings
$cliSettingsPath = __DIR__ . '/etc/cli-settings.yml';
$cliSettings = file_exists($cliSettingsPath) 
    ? \Symfony\Component\Yaml\Yaml::parseFile($cliSettingsPath) 
    : [];
$cliDefaultApp = $cliSettings['default_app'] ?? 'default';

if ($cliDefaultApp !== 'default' && class_exists('\SPP\App')) {
    try {
        // Instantiating the App automatically registers it with the Scheduler
        new \SPP\App($cliDefaultApp);
        \SPP\Scheduler::setContext($cliDefaultApp);
    } catch (\Exception $e) {
        // Fallback silently if the app doesn't exist or loading fails
    }
}

// Function to read interactive input
function prompt($text, $default = '') {
    $extra = ($default !== '') ? " [{$default}]" : "";
    echo $text . $extra . ": ";
    $input = trim(fgets(STDIN));
    return ($input === '') ? $default : $input;
}

// Basic Table Formatter for CLI
function printTable($headers, $rows) {
    if (empty($rows)) {
        echo "(Empty set)\n";
        return;
    }
    $widths = array();
    foreach ($headers as $i => $h) $widths[$i] = strlen($h);
    foreach ($rows as $row) {
        $rValues = array_values($row);
        foreach ($rValues as $i => $v) {
            $widths[$i] = max($widths[$i] ?? 0, strlen((string)$v));
        }
    }

    $line = "+";
    foreach ($widths as $w) $line .= str_repeat("-", $w + 2) . "+";
    echo $line . "\n";

    echo "|";
    foreach ($headers as $i => $h) echo " " . str_pad($h, $widths[$i]) . " |";
    echo "\n" . $line . "\n";

    foreach ($rows as $row) {
        echo "|";
        $rValues = array_values($row);
        foreach ($rValues as $i => $v) echo " " . str_pad((string)substr($v, 0, 50), $widths[$i]) . " |";
        echo "\n";
    }
    echo $line . "\n";
}

/**
 * COMMAND DISCOVERY & EXECUTION (Evolution Phase 3)
 */
$discoveredCommands = \SPP\CLI\CommandManager::discover();

// Execution logic
if (isset($discoveredCommands[$command])) {
    try {
        $discoveredCommands[$command]->execute($argv);
        exit(0);
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}


/**
 * LEGACY COMMAND DISPATCHER (Refactoring target)
 */
switch ($command) {
    case 'make:entity':
    case 'ent:create':
        echo "Error: 'make:entity' has been migrated to a modern command class.\n";
        echo "Please ensure SPP\\CLI\\Commands\\MakeEntityCommand is properly loaded.\n";
        break;

    case 'ent:list':
        require_once __DIR__ . '/sppinit.php';
        $entities = \SPPMod\SPPEntity\SPPEntity::listAvailableEntities();
        echo "Detected Entity Definitions:\n";
        $rows = [];
        foreach ($entities as $e) {
            $rows[] = [$e['name'], $e['table'], $e['modified']];
        }
        printTable(['Name', 'Table', 'Last Modified'], $rows);
        break;

    case 'ent:show':
        $entityName = $argv[2] ?? null;
        if (!$entityName) die("Error: Entity name required.\n");
        require_once __DIR__ . '/sppinit.php';
        
        $cfgFile = \SPPMod\SPPEntity\SPPEntity::getEntityConfigFile($entityName);
        if (!$cfgFile) die("Error: Entity '{$entityName}' not found.\n");
        
        echo "Entity Definition: {$entityName}\n";
        echo "Path: " . realpath($cfgFile) . "\n";
        echo "------------------------------------------\n";
        echo file_get_contents($cfgFile);
        echo "------------------------------------------\n";
        break;

    case 'ent:query':
        $entityName = $argv[2] ?? null;
        $limit = $argv[3] ?? 10;
        if (!$entityName) die("Error: Entity name required.\n");
        require_once __DIR__ . '/sppinit.php';
        
        try {
            $db = new \SPPMod\SPPDB\SPPDB();
            // Resolve table from YAML
            $config = @\Symfony\Component\Yaml\Yaml::parseFile(\SPPMod\SPPEntity\SPPEntity::getEntityConfigFile($entityName));
            $table = $config['table'] ?? strtolower($entityName).'s';
            
            $results = $db->exec_squery("SELECT * FROM %tab% LIMIT ?", $table, [(int)$limit]);
            if (!empty($results)) {
                printTable(array_keys($results[0]), $results);
            } else {
                echo "No records found in table '{$table}'.\n";
            }
        } catch (\Exception $e) {
            echo "Query Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'ent:manage':
        $entityName = $argv[2] ?? null;
        if (!$entityName) die("Error: Entity name required.\n");
        require_once __DIR__ . '/sppinit.php';
        
        $className = "\\SPPMod\\SPPEntity\\" . ucfirst($entityName);
        if (!class_exists($className)) {
             echo "Warning: Native entity class '{$className}' not loaded. Using generic SPPEntity.\n";
             $className = "\\SPPMod\\SPPEntity\\SPPEntity";
             // Force load config
             try {
                // This is a hack to set the static scope
                $reflection = new \ReflectionMethod($className, 'loadEntityConfig');
                $reflection->setAccessible(true);
                $reflection->invoke(null, $entityName);
             } catch(\Exception $e){}
        }

        echo "Interactive Management: {$entityName}\n";
        while(true) {
            echo "\nCommands: [L] List [V] View <id> [D] Delete <id> [S] Sync Schema [Q] Quit\n";
            $input = prompt("Manage");
            $parts = explode(' ', $input);
            $cmd = strtoupper($parts[0] ?? '');
            $id = $parts[1] ?? null;

            if ($cmd === 'Q') break;
            
            try {
                $db = new \SPPMod\SPPDB\SPPDB();
                if ($cmd === 'L') {
                    $table = \SPPMod\SPPDB\SPPDB::sppTable(strtolower($entityName).'s');
                    $res = $db->exec_squery("SELECT * FROM %tab% LIMIT 20", $table);
                    if ($res) printTable(array_keys($res[0]), $res);
                    else echo "No records.\n";
                } elseif ($cmd === 'V' && $id) {
                    $res = $db->exec_squery("SELECT * FROM %tab% WHERE id=?", \SPPMod\SPPDB\SPPDB::sppTable(strtolower($entityName).'s'), [$id]);
                    if ($res) print_r($res[0]);
                    else echo "Record not found.\n";
                } elseif ($cmd === 'D' && $id) {
                    $db->exec_squery("DELETE FROM %tab% WHERE id=?", \SPPMod\SPPDB\SPPDB::sppTable(strtolower($entityName).'s'), [$id]);
                    echo "Record {$id} deleted.\n";
                } elseif ($cmd === 'S') {
                    // Force install/sync
                    call_user_func([$className, 'install']);
                    echo "Schema synchronized successfully.\n";
                }
            } catch (\Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
        break;

    case 'ent:edit':
        $entityName = $argv[2] ?? null;
        if (!$entityName) {
            require_once __DIR__ . '/sppinit.php';
            $entities = \SPPMod\SPPEntity\SPPEntity::listAvailableEntities();
            echo "Available Entities:\n";
            foreach (array_keys($entities) as $name) echo "  - $name\n";
            $entityName = prompt("\nEntity Name to Edit");
        }
        
        require_once __DIR__ . '/sppinit.php';
        $cfgFile = \SPPMod\SPPEntity\SPPEntity::getEntityConfigFile($entityName);
        if (!$cfgFile) die("Error: Entity '{$entityName}' not found.\n");
        
        try {
            $config = \Symfony\Component\Yaml\Yaml::parseFile($cfgFile);
            $appname = strpos($cfgFile, 'apps/') !== false ? explode('/', explode('apps/', $cfgFile)[1])[0] : 'default';

            while(true) {
                echo "\n--- Editing Entity: {$entityName} ---\n";
                echo "1) Edit Metadata (Table: {$config['table']}, Parent: " . ($config['extends'] ?? 'None') . ", Login: " . ($config['login_enabled'] ? 'Yes' : 'No') . ")\n";
                echo "2) Manage Attributes (" . count($config['attributes'] ?? []) . " defined)\n";
                echo "3) Manage Relationships (" . count($config['relations'] ?? []) . " defined)\n";
                echo "4) Save & Quit\n";
                echo "5) Quit without Saving\n";
                
                $choice = prompt("Choice", "4");
                
                if ($choice == '1') {
                    $config['table'] = prompt("  Database Table", $config['table']);
                    $config['extends'] = prompt("  Extends (Parent)", $config['extends'] ?? '');
                    $config['login_enabled'] = strtolower(prompt("  Enable Login Support? (y/n)", ($config['login_enabled'] ?? false) ? 'y' : 'n')) === 'y';
                } elseif ($choice == '2') {
                    while(true) {
                        echo "\nAttributes:\n";
                        foreach (($config['attributes'] ?? []) as $k => $v) echo "  - $k: $v\n";
                        $act = strtolower(prompt("  (A)dd, (E)dit, (R)emove, (B)ack", "b"));
                        if ($act === 'b') break;
                        if ($act === 'a') {
                            $name = prompt("    Name");
                            if ($name) $config['attributes'][$name] = prompt("    Type", "varchar(255)");
                        } elseif ($act === 'e') {
                            $name = prompt("    Attribute Name to Edit");
                            if (isset($config['attributes'][$name])) {
                                $newName = prompt("      New Name", $name);
                                $type = prompt("      Type", $config['attributes'][$name]);
                                if ($newName !== $name) unset($config['attributes'][$name]);
                                $config['attributes'][$newName] = $type;
                            } else {
                                echo "    Error: Attribute '{$name}' not found.\n";
                            }
                        } elseif ($act === 'r') {
                            $name = prompt("    Name to Remove");
                            if (isset($config['attributes'][$name])) unset($config['attributes'][$name]);
                        }
                    }
                } elseif ($choice == '3') {
                    while(true) {
                        echo "\nRelationships:\n";
                        foreach (($config['relations'] ?? []) as $idx => $rel) {
                            echo "  $idx) {$rel['relation_type']} -> {$rel['child_entity']} ({$rel['child_entity_field']})\n";
                        }
                        $act = strtolower(prompt("  (A)dd, (E)dit, (R)emove, (B)ack", "b"));
                        if ($act === 'b') break;
                        if ($act === 'a') {
                            $target = prompt("    Target Entity");
                            if ($target) {
                                $rel = [
                                    'child_entity' => $target,
                                    'relation_type' => prompt("    Type", "OneToMany"),
                                    'child_entity_field' => prompt("    FK Field", strtolower($entityName) . "_id")
                                ];
                                if ($rel['relation_type'] === 'ManyToMany') {
                                    $rel['pivot_table'] = prompt("    Pivot Table", strtolower($entityName) . "_" . strtolower(basename(str_replace('\\', '/', $target))));
                                }
                                $config['relations'][] = $rel;
                            }
                        } elseif ($act === 'e') {
                            $idx = prompt("    Index to Edit");
                            if (isset($config['relations'][$idx])) {
                                $rel = &$config['relations'][$idx];
                                $rel['child_entity'] = prompt("      Target Entity", $rel['child_entity']);
                                $rel['relation_type'] = prompt("      Type", $rel['relation_type']);
                                $rel['child_entity_field'] = prompt("      FK Field", $rel['child_entity_field']);
                                if ($rel['relation_type'] === 'ManyToMany') {
                                    $rel['pivot_table'] = prompt("      Pivot Table", $rel['pivot_table'] ?? '');
                                } else {
                                    unset($rel['pivot_table']);
                                }
                            } else {
                                echo "    Error: Relation index '{$idx}' not found.\n";
                            }
                        } elseif ($act === 'r') {
                            $idx = prompt("    Index to Remove");
                            if (isset($config['relations'][$idx])) array_splice($config['relations'], $idx, 1);
                        }
                    }
                } elseif ($choice == '4') {
                    \SPPMod\SPPEntity\SPPEntity::saveEntityDefinition($entityName, $appname, $config);
                    echo "Success: Entity definition updated.\n";
                    break;
                } elseif ($choice == '5') {
                    break;
                }
            }
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'ent:delete':
        $entityName = $argv[2] ?? null;
        if (!$entityName) die("Error: Entity name required.\n");
        require_once __DIR__ . '/sppinit.php';
        
        $cfgFile = \SPPMod\SPPEntity\SPPEntity::getEntityConfigFile($entityName);
        if ($cfgFile && file_exists($cfgFile)) {
            $confirm = prompt("Are you sure you want to delete entity '{$entityName}' configuration? (y/N)", "n");
            if (strtolower($confirm) === 'y') {
                unlink($cfgFile);
                echo "Success: Entity definition deleted.\n";
            }
        } else {
            echo "Error: Entity definition not found for '{$entityName}'.\n";
        }
        break;

    case 'make:module':
        echo "Error: 'make:module' has been migrated to a modern command class.\n";
        break;

    case 'build:edge':
        echo "SPPNexus: Initiating Edge Compiler gracefully inherently logically explicitly organically.\n";
        $buildDir = SPP_APP_DIR . '/build';
        if (!is_dir($buildDir)) {
            mkdir($buildDir, 0777, true);
        }
        $targetFile = $buildDir . '/spp_edge_core.phar';
        if (file_exists($targetFile)) {
            unlink($targetFile);
        }
        try {
            $phar = new \Phar($targetFile);
            $phar->buildFromDirectory(SPP_APP_DIR . '/core');
            $phar->setStub(\Phar::createDefaultIndex('class.module.php'));
            echo "Success: Process completed successfully.\n";
        } catch (\Exception $e) {
            echo "Compiler Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'auth:user:list':
        require_once __DIR__ . '/sppinit.php';
        $users = \SPPMod\SPPAuth\SPPUser::find_all();
        $rows = [];
        foreach ($users as $u) {
            $rows[] = [$u->id, $u->username, $u->email, $u->status];
        }
        printTable(['ID', 'Username', 'Email', 'Status'], $rows);
        break;

    case 'auth:user:create':
        require_once __DIR__ . '/sppinit.php';
        $uname = prompt("Username");
        if (!$uname) die("Username required.\n");
        $email = prompt("Email");
        $pass = prompt("Password");
        
        try {
            $id = \SPPMod\SPPAuth\SPPUser::saveUserInfo([
                'username' => $uname,
                'email' => $email,
                'password' => $pass,
                'status' => 'active',
                'role_ids' => $roleIds
            ]);
            echo "\nSuccess: User '{$uname}' created with ID {$id}.\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'auth:user:edit':
        $uid = $argv[2] ?? null;
        if (!$uid) die("Usage: php spp.php auth:user:edit <userid>\n");
        require_once __DIR__ . '/sppinit.php';
        
        try {
            $user = new \SPPMod\SPPAuth\SPPUser($uid);
            echo "Editing User: {$user->username} (ID: {$user->id})\n";
            $uname = prompt("Username", $user->username);
            $email = prompt("Email", $user->email);
            $pass = prompt("New Password (leave empty to keep current)");
            $status = prompt("Status", $user->status);

            // Fetch Current Roles
            $currentRoles = $user->getRoles();
            $roles = \SPPMod\SPPAuth\SPPRole::find_all();
            echo "\nRoles (Current: " . implode(',', $currentRoles) . "):\n";
            foreach ($roles as $i => $r) {
                $indicator = in_array($r->id, $currentRoles) ? " [*]" : " [ ]";
                echo "  [" . ($i+1) . "]{$indicator} " . $r->role_name . "\n";
            }
            $selected = prompt("Update Roles (comma-separated indices, or Enter to keep)", implode(',', array_keys($currentRoles)));
            $roleIds = $currentRoles;
            if ($selected !== implode(',', array_keys($currentRoles))) {
                $roleIds = [];
                $indices = explode(',', $selected);
                foreach ($indices as $idx) {
                    $idx = (int)trim($idx) - 1;
                    if (isset($roles[$idx])) $roleIds[] = $roles[$idx]->id;
                }
            }

            \SPPMod\SPPAuth\SPPUser::saveUserInfo([
                'id' => $user->id,
                'username' => $uname,
                'email' => $email,
                'password' => $pass,
                'status' => $status,
                'role_ids' => $roleIds
            ]);
            echo "\nSuccess: User updated.\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'auth:user:assign':
    case 'auth:user:unassign':
        $uid = $argv[2] ?? null;
        $roleid = $argv[3] ?? null;
        if (!$uid || !$roleid) die("Usage: php spp.php {$command} <userid> <roleid>\n");
        require_once __DIR__ . '/sppinit.php';
        try {
            if ($command === 'auth:user:assign') {
                \SPPMod\SPPAuth\SPPUser::assignRole((int)$uid, (int)$roleid);
                echo "Success: Role assigned to user.\n";
            } else {
                \SPPMod\SPPAuth\SPPUser::unassignRole((int)$uid, (int)$roleid);
                echo "Success: Role unassigned from user.\n";
            }
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'auth:role:list':
        require_once __DIR__ . '/sppinit.php';
        $roles = \SPPMod\SPPAuth\SPPRole::find_all();
        $rows = [];
        foreach ($roles as $r) {
            $rights = $r->getRights();
            $rows[] = [$r->id, $r->role_name, count($rights)];
        }
        printTable(['ID', 'Role Name', 'Rights Count'], $rows);
        break;

    case 'auth:role:create':
        require_once __DIR__ . '/sppinit.php';
        $name = prompt("Role Name");
        if (!$name) die("Role name required.\n");
        $desc = prompt("Description");

        // Select Rights
        $rightsData = \SPPMod\SPPAuth\SPPRight::find_all();
        echo "\nAvailable Rights:\n";
        foreach ($rightsData as $i => $rt) {
            echo "  [" . ($i+1) . "] " . $rt->name . "\n";
        }
        $selected = prompt("Select Rights (comma-separated indices)", "");
        $rightIds = [];
        if ($selected) {
            $indices = explode(',', $selected);
            foreach ($indices as $idx) {
                $idx = (int)trim($idx) - 1;
                if (isset($rightsData[$idx])) $rightIds[] = $rightsData[$idx]->id;
            }
        }

        try {
            $id = \SPPMod\SPPAuth\SPPRole::saveRoleInfo([
                'name' => $name,
                'description' => $desc,
                'right_ids' => $rightIds
            ]);
            echo "\nSuccess: Role '{$name}' created with ID {$id}.\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'auth:role:edit':
        $rid = $argv[2] ?? null;
        if (!$rid) die("Usage: php spp.php auth:role:edit <roleid>\n");
        require_once __DIR__ . '/sppinit.php';
        
        try {
            $role = new \SPPMod\SPPAuth\SPPRole($rid);
            echo "Editing Role: {$role->role_name} (ID: {$role->id})\n";
            $name = prompt("Role Name", $role->role_name);
            $desc = prompt("Description", $role->description);

            // Rights Management
            $currentRights = $role->getRights();
            $rights = \SPPMod\SPPAuth\SPPRight::find_all();
            echo "\nRights (Current: " . implode(',', $currentRights) . "):\n";
            foreach ($rights as $i => $rt) {
                $indicator = in_array($rt->id, $currentRights) ? " [*]" : " [ ]";
                echo "  [" . ($i+1) . "]{$indicator} " . $rt->name . "\n";
            }
            $selected = prompt("Update Rights (comma-separated indices, or Enter to keep)");
            $rightIds = $currentRights;
            if ($selected !== "") {
                $rightIds = [];
                $indices = explode(',', $selected);
                foreach ($indices as $idx) {
                    $idx = (int)trim($idx) - 1;
                    if (isset($rights[$idx])) $rightIds[] = $rights[$idx]->id;
                }
            }

            \SPPMod\SPPAuth\SPPRole::saveRoleInfo([
                'id' => $role->id,
                'name' => $name,
                'description' => $desc,
                'right_ids' => $rightIds
            ]);
            echo "\nSuccess: Role updated.\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'auth:role:assign':
    case 'auth:role:unassign':
        $rid = $argv[2] ?? null;
        $rightid = $argv[3] ?? null;
        if (!$rid || !$rightid) die("Usage: php spp.php {$command} <roleid> <rightid>\n");
        require_once __DIR__ . '/sppinit.php';
        try {
            if ($command === 'auth:role:assign') {
                \SPPMod\SPPAuth\SPPRole::assignRight((int)$rid, (int)$rightid);
                echo "Success: Right assigned to role.\n";
            } else {
                \SPPMod\SPPAuth\SPPRole::unassignRight((int)$rid, (int)$rightid);
                echo "Success: Right unassigned from role.\n";
            }
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'auth:group:assign':
    case 'auth:group:unassign':
        $slug = $argv[2] ?? null;
        $roleid = $argv[3] ?? null;
        if (!$slug || !$roleid) die("Usage: php spp.php {$command} <group_slug> <roleid>\n");
        require_once __DIR__ . '/sppinit.php';
        try {
            $group = new \SPPMod\SPPGroup\SPPGroup();
            $group->load($slug);
            if (!$group->id) throw new \Exception("Group '{$slug}' not found.");
            
            if ($command === 'auth:group:assign') {
                \SPPMod\SPPAuth\SPPRole::assignToEntity(get_class($group), $group->id, (int)$roleid);
                echo "Success: Role assigned to group '{$group->id}'.\n";
            } else {
                \SPPMod\SPPAuth\SPPRole::unassignFromEntity(get_class($group), $group->id, (int)$roleid);
                echo "Success: Role unassigned from group '{$group->id}'.\n";
            }
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'auth:group:list':
        require_once __DIR__ . '/sppinit.php';
        $groups = \SPPMod\SPPGroup\SPPGroupLoader::listAllGroups();
        $rows = [];
        foreach ($groups as $g) {
            $groupObj = new \SPPMod\SPPGroup\SPPGroup();
            $groupObj->load($g['name']);
            $members = $groupObj->getMembers(false);
            $rows[] = [$g['name'], $groupObj->get('name'), $g['source'], count($members)];
        }
        printTable(['Slug/ID', 'Name', 'Source', 'Direct Members'], $rows);
        break;

    case 'auth:group:create':
        require_once __DIR__ . '/sppinit.php';
        $name = prompt("Group Name");
        if (!$name) die("Name required.\n");
        $desc = prompt("Description");
        echo "\nChoose Storage Source:\n";
        echo "  [1] database (Shared across instances)\n";
        echo "  [2] global   (YAML in framework etc)\n";
        echo "  [3] app      (YAML in app etc)\n";
        $srcIdx = (int)prompt("Selection", "1");
        $source = ($srcIdx === 2) ? 'global' : (($srcIdx === 3) ? 'app' : 'database');

        try {
            $id = \SPPMod\SPPGroup\SPPGroup::saveGroupInfo([
                'name' => $name,
                'description' => $desc,
                'source' => $source
            ]);
            echo "\nSuccess: Group created with ID/Slug: {$id}\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'auth:group:edit':
        $slug = $argv[2] ?? null;
        if (!$slug) die("Usage: php spp.php auth:group:edit <slug>\n");
        require_once __DIR__ . '/sppinit.php';
        try {
            $group = new \SPPMod\SPPGroup\SPPGroup();
            $group->load($slug);
            if (!$group->id) throw new \Exception("Group not found.");
            
            echo "Editing Group: {$group->id}\n";
            $name = prompt("Name", $group->get('name'));
            $desc = prompt("Description", $group->get('description'));

            \SPPMod\SPPGroup\SPPGroup::saveGroupInfo([
                'slug' => $group->id,
                'name' => $name,
                'description' => $desc
            ]);
            echo "\nSuccess: Group updated.\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'auth:group:member:list':
        $slug = $argv[2] ?? null;
        if (!$slug) die("Usage: php spp.php auth:group:member:list <slug>\n");
        require_once __DIR__ . '/sppinit.php';
        try {
            $group = new \SPPMod\SPPGroup\SPPGroup();
            $group->load($slug);
            if (!$group->id) throw new \Exception("Group not found.");

            $members = $group->getMembers(false);
            $rows = [];
            foreach ($members as $m) {
                $ent = $m['entity'];
                $rows[] = [$ent->getId(), get_class($ent), $m['role']];
            }
            echo "Direct Members of '{$slug}':\n";
            printTable(['ID', 'Entity Class', 'Group Role'], $rows);
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'auth:group:member:add':
    case 'auth:group:member:remove':
        $slug = $argv[2] ?? null;
        $mid = $argv[3] ?? null;
        $class = $argv[4] ?? '\SPPMod\SPPAuth\SPPUser';
        if (!$slug || !$mid) die("Usage: php spp.php {$command} <slug> <member_id> [member_class]\n");
        require_once __DIR__ . '/sppinit.php';
        try {
            if ($command === 'auth:group:member:add') {
                \SPPMod\SPPGroup\SPPGroup::addMemberToGroup($slug, $class, $mid);
                echo "Success: Member added to group.\n";
            } else {
                \SPPMod\SPPGroup\SPPGroup::removeMemberFromGroup($slug, $class, $mid);
                echo "Success: Member removed from group.\n";
            }
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        break;

    case 'view:page:list':
        require_once __DIR__ . '/sppinit.php';
        $pages = \SPPMod\SPPView\Pages::listPages();
        $rows = [];
        foreach ($pages as $p) {
            $rows[] = [$p['name'], $p['url']];
        }
        printTable(['Name', 'URL'], $rows);
        break;

    case 'view:page:add':
        $name = $argv[2] ?? null;
        if ($argv[3] ?? null) \SPP\Scheduler::setContext($argv[3]);
        if (!$name) $name = prompt("Page Name");
        if (!$name) die("Name required.\n");
        require_once __DIR__ . '/sppinit.php';
        
        $existing = \SPPMod\SPPView\Pages::listPages();
        $defaultUrl = '';
        foreach ($existing as $p) {
            if ($p['name'] === $name) {
                $defaultUrl = $p['url'];
                break;
            }
        }
        
        $source = 'yaml';
        foreach ($existing as $p) {
            if ($p['name'] === $name) {
                $source = $p['source'];
                break;
            }
        }

        $url = prompt("URL", $defaultUrl);
        if (!($argv[2] ?? null)) {
            $source = prompt("Storage Source (yaml/db)", $source);
        }

        \SPPMod\SPPView\Pages::savePage($name, $url, $source);
        echo "Success: Page route saved to {$source}.\n";
        break;

    case 'view:page:remove':
        $name = $argv[2] ?? null;
        if (!$name) die("Usage: php spp.php view:page:remove <name>\n");
        require_once __DIR__ . '/sppinit.php';
        if (\SPPMod\SPPView\Pages::removePage($name, 'yaml')) {
            echo "Success: Removed from YAML.\n";
        } else if (\SPPMod\SPPView\Pages::removePage($name, 'db')) {
            echo "Success: Removed from DB.\n";
        } else {
            echo "Error: Page route not found.\n";
        }
        break;

    case 'view:service:list':
        require_once __DIR__ . '/sppinit.php';
        $services = \SPPMod\SPPAjax\SPPAjax::listServices();
        $rows = [];
        foreach ($services as $s) {
            $rows[] = [$s['name'], $s['script'], $s['method'] ?? 'POST'];
        }
        printTable(['Name', 'Script', 'Method'], $rows);
        break;

    case 'view:service:add':
        $name = $argv[2] ?? null;
        if ($argv[3] ?? null) \SPP\Scheduler::setContext($argv[3]);
        if (!$name) $name = prompt("Service Name");
        if (!$name) die("Name required.\n");
        require_once __DIR__ . '/sppinit.php';

        $existing = \SPPMod\SPPAjax\SPPAjax::listServices();
        $defaultScript = '';
        $defaultMethod = 'POST';
        foreach ($existing as $s) {
            if ($s['name'] === $name) {
                $defaultScript = $s['script'];
                $defaultMethod = $s['method'] ?? 'POST';
                break;
            }
        }

        $source = 'yaml';
        foreach ($existing as $s) {
            if ($s['name'] === $name) {
                $source = $s['source'];
                break;
            }
        }

        $script = prompt("Script filename", $defaultScript);
        $method = prompt("Allowed Method (GET/POST)", $defaultMethod);
        if (!($argv[2] ?? null)) {
            $source = prompt("Storage Source (yaml/db)", $source);
        }
        
        \SPPMod\SPPAjax\SPPAjax::registerService($name, $script, $method, $source);
        echo "Success: Service registered in {$source}.\n";
        break;

    case 'view:service:remove':
        $name = $argv[2] ?? null;
        if (!$name) die("Usage: php spp.php view:service:remove <name>\n");
        require_once __DIR__ . '/sppinit.php';
        if (\SPPMod\SPPAjax\SPPAjax::unregisterService($name, 'yaml')) {
            echo "Success: Removed from YAML.\n";
        } else if (\SPPMod\SPPAjax\SPPAjax::unregisterService($name, 'db')) {
            echo "Success: Removed from DB.\n";
        } else {
            echo "Error: Service not found.\n";
        }
        break;

    case 'auth:right:list':
        require_once __DIR__ . '/sppinit.php';
        $rights = \SPPMod\SPPAuth\SPPRight::find_all();
        $rows = [];
        foreach ($rights as $rt) {
            $rows[] = [$rt->id, $rt->name, $rt->get('description')];
        }
        printTable(['ID', 'Right Name', 'Description'], $rows);
        break;

    case 'sys:update':
        // Mock environment for sppinit.php
        $_SERVER['DOCUMENT_ROOT'] = SPP_APP_DIR;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        
        require_once __DIR__ . '/sppinit.php';

        echo "SPP System Update Tool\n";
        echo "======================\n";
        
        echo "Scanning and applying updates...\n";
        try {
            // Ensure routing schemas are present
            echo "  [INFO] Initializing Routing schemas (Pages & Services)...\n";
            \SPPMod\SPPView\Pages::ensureDbSchema();
            \SPPMod\SPPAjax\SPPAjax::ensureDbSchema();

            $log = \SPP\Module::runSystemUpdate();
            if (empty($log)) {
                echo "System is already up to date.\n";
            } else {
                foreach ($log as $line) {
                    echo "  [OK] {$line}\n";
                }
                echo "\nSuccess: System update completed.\n";
            }
        } catch (\Exception $e) {
            echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
            exit(1);
        }
        break;

    case 'sys:info':
        require_once __DIR__ . '/sppinit.php';
        echo "SPP System Information\n";
        echo "======================\n";

        $db_status = "Disconnected";
        $db_server = "N/A";
        $db_name = "N/A";
        $db_tables = "N/A";
        try {
            $db = new \SPPMod\SPPDB\SPPDB();
            $db_status = "Connected (" . $db->getAttribute(\PDO::ATTR_DRIVER_NAME) . ")";
            $db_server = $db->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $db_name = \SPP\Module::getConfig('dbname', 'sppdb') ?: 'N/A';
            $tableCount = $db->execute_query("SELECT count(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE()");
            $db_tables = $tableCount[0]['cnt'] ?? '0';
        } catch (\Exception $e) {
            $db_status = "Error: " . $e->getMessage();
        }

        $stats = ['apps' => 0, 'modules' => 0, 'entities' => 0, 'forms' => 0];
        if (defined('APP_ETC_DIR') && is_dir(APP_ETC_DIR)) {
            $apps = array_filter(scandir(APP_ETC_DIR), function ($d) {
                return $d !== '.' && $d !== '..' && is_dir(APP_ETC_DIR . DIRECTORY_SEPARATOR . $d);
            });
            $stats['apps'] = count($apps);
            $entDir = APP_ETC_DIR . '/default/entities';
            if (is_dir($entDir)) {
                $ents = glob($entDir . '/*.yml');
                $stats['entities'] = count($ents ?: []);
            }
            $formDir = APP_ETC_DIR . '/default/forms';
            if (is_dir($formDir)) {
                $forms = glob($formDir . '/*.{yml,xml}', GLOB_BRACE);
                $stats['forms'] = count($forms ?: []);
            }
        }
        if (class_exists('\\SPP\\Module')) {
            \SPP\Module::loadAllModules();
            $mods = \SPP\Registry::get('__mods');
            $stats['modules'] = is_array($mods) ? count($mods) : 0;
        }

        $info = [
            'SPP Version'      => defined('SPP_VER') ? SPP_VER : 'Unknown',
            'PHP Version'      => PHP_VERSION,
            'PHP SAPI'         => php_sapi_name(),
            'OS'               => PHP_OS,
            'Database Status'  => $db_status,
            'DB Name'          => $db_name,
            'DB Server'        => $db_server,
            'DB Total Tables'  => $db_tables,
            'Base Directory'   => SPP_BASE_DIR,
            'App Directory'    => SPP_APP_DIR,
            'Registered Apps'  => $stats['apps'],
            'Loaded Modules'   => $stats['modules'],
            'Entities (default)' => $stats['entities'],
            'Forms (default)'    => $stats['forms']
        ];

        echo "\nFramework & Environment:\n";
        foreach ($info as $metric => $value) {
            echo "  " . str_pad($metric, 20) . ": " . $value . "\n";
        }

        $php_metrics = [
            'Memory Limit'       => ini_get('memory_limit'),
            'Max Execution Time' => ini_get('max_execution_time') . 's',
            'Upload Max Size'    => ini_get('upload_max_filesize'),
            'Post Max Size'      => ini_get('post_max_size'),
            'Display Errors'     => ini_get('display_errors') ? 'On' : 'Off',
            'Error Log'          => ini_get('error_log') ?: 'Syslog'
        ];
        foreach ($php_metrics as $metric => $value) {
            echo "  " . str_pad($metric, 20) . ": " . $value . "\n";
        }

        echo "\nPolyglot Runtimes:\n";
        $bridgeInfo = [];
        if (class_exists('\SPP\PolyglotBridge')) {
             $runtimes = \SPP\PolyglotBridge::discoverRuntimes();
             foreach ($runtimes as $id => $info) {
                 echo "  " . str_pad($info['name'], 20) . ": " . ($info['path'] ?: "Not Found") . ($info['version'] ? " (" . $info['version'] . ")" : "") . "\n";
             }
        }

        echo "\nResource Bridge status:\n";
        $sharedDir = \SPP\Module::getConfig('shared_dir', 'bridge') ?: 'var/shared';
        if (!str_starts_with($sharedDir, '/') && !str_contains($sharedDir, ':')) {
            $sharedDir = SPP_BASE_DIR . SPP_DS . '..' . SPP_DS . $sharedDir;
        }

        $bridge_file = $sharedDir . SPP_DS . 'bridge_config.json';
        $bridge_ready = file_exists($bridge_file);
        
        echo "  " . str_pad("Shared Directory", 20) . ": " . realpath($sharedDir) . " (" . (is_dir($sharedDir) ? "Ready" : "Missing") . ")\n";
        echo "  " . str_pad("Config Export", 20) . ": " . ($bridge_ready ? "Active (Generated)" : "Inactive") . "\n";

        echo "\n";
        break;

    case 'sys:bridge:info':
         require_once __DIR__ . '/sppinit.php';
         if (!class_exists('\SPP\PolyglotBridge')) die("Error: PolyglotBridge core not found.\n");
         
         echo "SPP Polyglot Bridge Diagnostics\n";
         echo "===============================\n";
         $runtimes = \SPP\PolyglotBridge::discoverRuntimes();
         echo "\nDetected Runtimes:\n";
         foreach ($runtimes as $id => $info) {
             echo "  " . strtoupper($id) . ":\n";
             echo "    Path    : " . ($info['path'] ?: "NOT DETECTED") . "\n";
             echo "    Version : " . ($info['version'] ?: "N/A") . "\n";
         }
         
         $sharedDir = \SPP\Module::getConfig('shared_dir', 'bridge') ?: 'var/shared';
         if (!str_starts_with($sharedDir, '/') && !str_contains($sharedDir, ':')) {
             $sharedDir = SPP_BASE_DIR . SPP_DS . '..' . SPP_DS . $sharedDir;
         }
         $bridgeFile = $sharedDir . SPP_DS . 'bridge_config.json';
         echo "\nBridge Status:\n";
         echo "  Shared Dir: " . realpath($sharedDir) . "\n";
         echo "  Config    : " . (file_exists($bridgeFile) ? "AVAILABLE" : "MISSING") . "\n";
         if (file_exists($bridgeFile)) {
              echo "  Last Sync : " . date("Y-m-d H:i:s", filemtime($bridgeFile)) . "\n";
         }
         echo "\n";
         break;

    case 'sys:bridge:setup':
         require_once __DIR__ . '/sppinit.php';
         if (!class_exists('\SPP\PolyglotBridge')) die("Error: PolyglotBridge core not found.\n");
         
         echo "Initiating Polyglot Bridge Setup...\n";
         $res = \SPP\PolyglotBridge::setup();
         if ($res['success']) {
             foreach ($res['log'] as $line) {
                 echo "  [OK] {$line}\n";
             }
             echo "\nSuccess: Bridge environment refreshed.\n";
         } else {
             echo "\nError during setup: " . ($res['error'] ?? 'Unknown error') . "\n";
         }
         break;

    case 'cli:app:default':
        $appName = $argv[2] ?? prompt("Default Application Name", $cliDefaultApp);
        
        require_once __DIR__ . '/sppinit.php';
        $globalSettingsPath = __DIR__ . '/etc/global-settings.yml';
        $settings = \Symfony\Component\Yaml\Yaml::parseFile($globalSettingsPath);
        
        if (!isset($settings['apps'][$appName])) {
            die("Error: Application '{$appName}' is not registered in global-settings.yml.\n");
        }

        $cliSettings['default_app'] = $appName;
        file_put_contents($cliSettingsPath, \Symfony\Component\Yaml\Yaml::dump($cliSettings));
        echo "Success: '{$appName}' set as the default CLI application context.\n";
        break;

    case 'app:create':
        require_once __DIR__ . '/sppinit.php';
        $appName = $argv[2] ?? prompt("Application Name (slug)");
        $appType = prompt("Application Type (javascript/php)", "php");
        $baseUrl = prompt("Base URL", "/" . $appName);
        
        $appDir = SPP_APP_DIR . "/etc/apps/{$appName}";
        if (is_dir($appDir)) die("Error: Application '{$appName}' already exists.\n");

        echo "Initializing directory structure for '{$appName}'...\n";
        $app = new \SPP\App($appName, true, 1); // Level 1 init to create dirs
        
        // Define additional directories
        $dirs = ['entities', 'forms', 'modsconf', 'pages'];
        foreach ($dirs as $d) {
            $path = "{$appDir}/{$d}";
            if (!is_dir($path)) mkdir($path, 0777, true);
        }

        $srcDir = SPP_APP_DIR . "/src/{$appName}";
        $subDirs = ($appType === 'php') ? ['pages', 'serv', 'components'] : ['comp', 'serv', 'store'];
        foreach ($subDirs as $sd) {
            $path = "{$srcDir}/{$sd}";
            if (!is_dir($path)) mkdir($path, 0777, true);
        }

        // Create manifest.yml
        $manifest = [
            'app' => [
                'name' => $appName,
                'type' => $appType,
                'version' => '1.0.0',
                'description' => "Auto-generated {$appType}-SPA application."
            ]
        ];
        file_put_contents("{$appDir}/manifest.yml", \Symfony\Component\Yaml\Yaml::dump($manifest));

        // Create initial modules.yml
        $modules = [
            'modules' => [
                ['name' => 'sppview', 'path' => 'spp/sppview'],
                ['name' => 'sppajax', 'path' => 'spp/sppajax']
            ]
        ];
        file_put_contents("{$appDir}/modules.yml", \Symfony\Component\Yaml\Yaml::dump($modules));

        // Registry update
        echo "Registering application in global-settings.yml...\n";
        $globalSettingsPath = __DIR__ . '/etc/global-settings.yml';
        $settings = \Symfony\Component\Yaml\Yaml::parseFile($globalSettingsPath);
        $settings['apps'][$appName] = [
            'base_url' => $baseUrl,
            'table_prefix' => $appName . '_',
            'shared_group' => 'core',
            'etc_path' => "etc/apps/{$appName}",
            'src_path' => "src/{$appName}"
        ];
        file_put_contents($globalSettingsPath, \Symfony\Component\Yaml\Yaml::dump($settings, 4, 2));

        echo "Success: Application '{$appName}' created.\n";
        break;

    case 'ui:comp:php':
        $name = $argv[2] ?? prompt("Component Name (e.g. UserProfile)");
        $app = $argv[3] ?? $cliDefaultApp;
        if ($app !== \SPP\Scheduler::getContext()) \SPP\Scheduler::setContext($app);
        $targetDir = SPP_APP_DIR . "/src/{$app}/components";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $filename = "{$targetDir}/{$name}.php";
        if (file_exists($filename)) die("Error: Component '{$name}' already exists in '{$app}'.\n");
        
        $tpl = "<?php\n\nnamespace App\\" . ucfirst($app) . "\\Components;\n\n";
        $tpl .= "use SPPMod\\SPPView\\PHPComponent;\n\n";
        $tpl .= "class {$name} extends PHPComponent {\n";
        $tpl .= "    public \$state = [\n        'title' => 'Hello from {$name}'\n    ];\n\n";
        $tpl .= "    public function render(): string {\n";
        $tpl .= "        return \"<div>\\n            <h1>{\$title}</h1>\\n            <p>Ready to build.</p>\\n        </div>\";\n";
        $tpl .= "    }\n}\n";
        
        file_put_contents($filename, $tpl);
        echo "Success: Created PHP Component in {$filename}\n";
        break;

    case 'ui:build':
        $appName = $argv[2] ?? $cliDefaultApp;
        if ($appName !== \SPP\Scheduler::getContext()) \SPP\Scheduler::setContext($appName);
        $compDir = SPP_APP_DIR . "/src/{$appName}/components";
        $genDir = SPP_APP_DIR . "/res/apps/{$appName}/generated";
        
        if (!is_dir($compDir)) die("Error: Component directory not found for '{$appName}'.\n");
        if (!is_dir($genDir)) mkdir($genDir, 0777, true);
        
        echo "Building components for '{$appName}'...\n";
        $files = glob("{$compDir}/*.php");
        foreach ($files as $file) {
            $className = "App\\" . ucfirst($appName) . "\\Components\\" . basename($file, '.php');
            echo "  Generating JS for {$className}...\n";
            try {
                $js = \SPPMod\SPPView\JSGenerator::generate($className);
                file_put_contents("{$genDir}/" . basename($file, '.php') . ".js", $js);
            } catch (\Exception $e) {
                echo "  [ERROR] " . $e->getMessage() . "\n";
            }
        }
        echo "Build completed.\n";
        break;

    case 'ui:watch':
        $appName = $argv[2] ?? $cliDefaultApp;
        if ($appName !== \SPP\Scheduler::getContext()) \SPP\Scheduler::setContext($appName);
        $compDir = SPP_APP_DIR . "/src/{$appName}/components";
        $genDir = SPP_APP_DIR . "/res/apps/{$appName}/generated";

        echo "Starting watcher for '{$appName}' (Ctrl+C to stop)...\n";
        $mtimes = [];
        
        while (true) {
            $files = glob("{$compDir}/*.php");
            foreach ($files as $file) {
                $mtime = filemtime($file);
                if (!isset($mtimes[$file]) || $mtimes[$file] != $mtime) {
                    $className = "App\\" . ucfirst($appName) . "\\Components\\" . basename($file, '.php');
                    echo "  [" . date('H:i:s') . "] Rebuilding {$className}...\n";
                    try {
                        if (!is_dir($genDir)) mkdir($genDir, 0777, true);
                        $js = \SPPMod\SPPView\JSGenerator::generate($className);
                        file_put_contents("{$genDir}/" . basename($file, '.php') . ".js", $js);
                        $mtimes[$file] = $mtime;
                    } catch (\Exception $e) {
                        echo "    [ERROR] " . $e->getMessage() . "\n";
                    }
                }
            }
            usleep(500000); // 500ms
        }
        break;

    case 'ui:view':
        $name = $argv[2] ?? prompt("Component Name (e.g. Dashboard)");
        $app = $argv[3] ?? $cliDefaultApp;
        if ($app !== \SPP\Scheduler::getContext()) \SPP\Scheduler::setContext($app);
        $targetDir = SPP_APP_DIR . "/src/{$app}/comp";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $filename = "{$targetDir}/" . strtolower($name) . ".js";
        if (file_exists($filename)) die("Error: View '{$name}' already exists in '{$app}'.\n");
        
        $tpl = "/**\n * " . ucfirst($name) . " View Component\n */\n";
        $tpl .= "export default class " . ucfirst($name) . "View extends BaseComponent {\n";
        $tpl .= "    async onInit() {\n        this.state = { loading: true };\n        await this.loadData();\n    }\n\n";
        $tpl .= "    async loadData() {\n        this.setState({ loading: false });\n    }\n\n";
        $tpl .= "    render() {\n        return html`\n            <div class=\"" . strtolower($name) . "-view fade-in\">\n";
        $tpl .= "                <h1>" . ucfirst($name) . "</h1>\n                <p>Auto-generated component template.</p>\n";
        $tpl .= "            </div>\n        `;\n    }\n}\n";
        
        file_put_contents($filename, $tpl);
        echo "Success: Created View Component in {$filename}\n";
        break;

    case 'ui:serv':
        $name = $argv[2] ?? prompt("Service Name (e.g. list_stats)");
        $app = $argv[3] ?? $cliDefaultApp;
        if ($app !== \SPP\Scheduler::getContext()) \SPP\Scheduler::setContext($app);
        $targetDir = SPP_APP_DIR . "/src/{$app}/serv";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $filename = "{$targetDir}/{$name}.php";
        if (file_exists($filename)) die("Error: Service '{$name}' already exists in '{$app}'.\n");
        
        $tpl = "<?php\n/**\n * Service: {$name}\n * Application: {$app}\n */\n\n";
        $tpl .= "try {\n    // Implementation logic here\n";
        $tpl .= "    \$data = ['status' => 'success', 'timestamp' => time()];\n    \n";
        $tpl .= "    echo json_encode(['success' => true, 'data' => \$data]);\n";
        $tpl .= "} catch (\\Exception \$e) {\n";
        $tpl .= "    echo json_encode(['success' => false, 'message' => \$e->getMessage()]);\n}\n";
        
        file_put_contents($filename, $tpl);
        echo "Success: Created Backend Service in {$filename}\n";
        break;

    case 'ui:store':
        $name = $argv[2] ?? prompt("Store Name (e.g. UserStore)");
        $app = $argv[3] ?? $cliDefaultApp;
        if ($app !== \SPP\Scheduler::getContext()) \SPP\Scheduler::setContext($app);
        $targetDir = SPP_APP_DIR . "/src/{$app}/store";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $filename = "{$targetDir}/" . strtolower($name) . ".js";
        if (file_exists($filename)) die("Error: Store '{$name}' already exists in '{$app}'.\n");
        
        $tpl = "/**\n * " . ucfirst($name) . " Store\n */\n";
        $tpl .= "const " . ucfirst($name) . " = new SPPStore({\n    initialized: Date.now()\n});\n\n";
        $tpl .= "export default " . ucfirst($name) . ";\n";
        
        file_put_contents($filename, $tpl);
        echo "Success: Created Global Store in {$filename}\n";
        break;

    default:
        echo "Command \"{$command}\" is not defined.\n";
        exit(1);
}

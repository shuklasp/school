<?php
namespace SPPMod\SPPGroup;

use SPPMod\SPPEntity\SPPEntity;

/**
 * class SPPGroupLoader
 * Manages discovery and priority-based resolution of file-backed groups.
 */
class SPPGroupLoader {

    /**
     * Scans for all available groups in current app context, global context, and database.
     * @param string $appname
     * @return array
     */
    public static function listAllGroups(string $appname = 'default') {
        $groups = [];
        
        // 1. Scan App-Specific groups
        $appDir = static::getAppGroupDir($appname);
        if (is_dir($appDir)) {
            foreach (glob($appDir . "/*.yml") as $file) {
                $name = basename($file, ".yml");
                $groups[$name] = [
                    'name' => $name,
                    'source' => 'app',
                    'path' => $file
                ];
            }
        }
        
        // 2. Scan Global groups - Only if context is default
        if ($appname === 'default') {
            $globalDir = static::getGlobalGroupDir();
            if (is_dir($globalDir)) {
                foreach (glob($globalDir . "/*.yml") as $file) {
                    $name = basename($file, ".yml");
                    if (!isset($groups[$name])) {
                        $groups[$name] = [
                            'name' => $name,
                            'source' => 'global',
                            'path' => $file
                        ];
                    }
                }
            }
        }
        
        // 3. Scan DB groups (Fallback) - Allow only if default context or shared DB logic required
        if ($appname === 'default' || true) { // Kept 'true' for now to show DB groups per requirements
            try {
                $db = new \SPPMod\SPPDB\SPPDB();
                $table = \SPPMod\SPPDB\SPPDB::sppTable('sppgroups');
                if ($db->tableExists($table)) {
                    $results = $db->execute_query("SELECT name FROM $table");
                    foreach ($results as $row) {
                        $name = $row['name'];
                        if (!isset($groups[$name])) {
                            $groups[$name] = [
                                'name' => $name,
                                'source' => 'database'
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {}
        }
        
        return array_values($groups);
    }

    /**
     * Resolves a group name to its primary storage metadata.
     * @param string $name
     * @param string $appname
     * @return array|null
     */
    public static function resolveGroup(string $name, string $appname = 'default') {
        // Priority 1: App-specific
        $appFile = static::getAppGroupDir($appname) . DIRECTORY_SEPARATOR . $name . ".yml";
        if (file_exists($appFile)) {
            return ['source' => 'app', 'path' => $appFile];
        }
        
        // Priority 2: Global
        $globalFile = static::getGlobalGroupDir() . DIRECTORY_SEPARATOR . $name . ".yml";
        if (file_exists($globalFile)) {
            return ['source' => 'global', 'path' => $globalFile];
        }
        
        // Priority 3: Database
        try {
            $db = new \SPPMod\SPPDB\SPPDB();
            $table = \SPPMod\SPPDB\SPPDB::sppTable('sppgroups');
            if ($db->tableExists($table)) {
                $result = $db->execute_query("SELECT id FROM $table WHERE name=?", [$name]);
                if (count($result) > 0) {
                    return ['source' => 'database', 'id' => $result[0]['id']];
                }
            }
        } catch (\Exception $e) {}
        
        return null;
    }

    public static function getAppGroupDir(string $appname) {
        return APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'groups';
    }

    public static function getGlobalGroupDir() {
        return SPP_ETC_DIR . SPP_DS . 'groups';
    }
}

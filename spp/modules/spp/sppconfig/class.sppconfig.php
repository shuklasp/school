<?php

namespace SPPMod\SPPConfig;
use SPP\Exceptions\UnknownConfigVarException;
use SPP\Exceptions\UnknownConfigTabVarException;
use SPP\Exceptions\ReadonlyConfigVarException;
//use SPP\Exceptions\UnknownConfigTabVarException;

/*require_once 'sppconstants.php';
require_once 'class.sppdatabase.php';
require_once 'class.sppobject.php';
require_once 'settings.php';
require_once 'sppsystemexceptions.php';
require_once 'class.sppbase.php';
require_once SPP_BASE_DIR.SPPUS.'appsettings.php';*/
/**
 * class Config
 *
 * get and set configuration variables
 *
 * @author Satya Prakash Shukla
 */
class SPPConfig extends \SPP\SPPObject
{
    private static $configcache = array();
    private static $feelinglucky = true;

    /**
     * Resolves dot-notation mapped YAML routing configuration
     */
    public static function resolveYamlLocation($propname) {
        $parts = explode('.', $propname);
        $filePath = '';
        $ymlKeys = [];
        
        $appname = \SPP\Scheduler::getContext();
        if (empty($appname) || $appname === 'undefined') $appname = 'default';

        if ($parts[0] === 'mod') {
            // mod.* -> SPP_ETC_DIR/apps/<appname>/modsconf/<modname>/config.yml
            if (count($parts) < 3) return null;
            $modname = $parts[1];
            $filePath = SPP_ETC_DIR . '/apps/' . $appname . '/modsconf/' . $modname . '/config.yml';
            $ymlKeys = array_slice($parts, 2);
        } elseif ($parts[0] === 'app' && isset($parts[1]) && $parts[1] === 'mod') {
            // app.mod.* -> APP_ETC_DIR/<appname>/modsconf/<modname>/config.yml
            if (count($parts) < 4) return null;
            $modname = $parts[2];
            $filePath = APP_ETC_DIR . '/' . $appname . '/modsconf/' . $modname . '/config.yml';
            $ymlKeys = array_slice($parts, 3);
        } elseif ($parts[0] === 'app') {
            // app.* -> APP_ETC_DIR/<appname>/settings.yml
            if (count($parts) < 2) return null;
            $filePath = APP_ETC_DIR . '/' . $appname . '/settings.yml';
            $ymlKeys = array_slice($parts, 1);
        } elseif ($parts[0] === 'spp') {
            // spp.* -> SPP_ETC_DIR/settings.yml
            if (count($parts) < 2) return null;
            $filePath = SPP_ETC_DIR . '/settings.yml';
            $ymlKeys = array_slice($parts, 1);
        } else {
            $filePath = SPP_ETC_DIR . '/settings.yml';
            $ymlKeys = $parts; 
        }
        
        // Normalize slashes
        $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
        
        return ['path' => $filePath, 'keys' => $ymlKeys];
    }

    private static function getYamlNestedProperty($array, $keys) {
        $curr = $array;
        foreach ($keys as $k) {
            if (is_array($curr) && array_key_exists($k, $curr)) {
                $curr = $curr[$k];
            } else {
                return null;
            }
        }
        return $curr;
    }

    private static function setYamlNestedProperty(&$array, $keys, $value) {
        $curr = &$array;
        foreach ($keys as $k) {
            if (!is_array($curr)) {
                $curr = [];
            }
            if (!array_key_exists($k, $curr)) {
                $curr[$k] = [];
            }
            $curr = &$curr[$k];
        }
        $curr = $value;
    }
    /**
     * static function get()
     * Gets the values of a property.
     *
     * @param string $propname
     * @param string $storage Native persistence routing engine
     * @return mixed propval
     */
    public static function get($propname, $storage = 'yaml')
    {
        if ($storage === 'yaml' && class_exists('\Symfony\Component\Yaml\Yaml')) {
            $loc = self::resolveYamlLocation($propname);
            if ($loc !== null && file_exists($loc['path'])) {
                $yamlData = \Symfony\Component\Yaml\Yaml::parseFile($loc['path']);
                $val = self::getYamlNestedProperty($yamlData, $loc['keys']);
                if ($val !== null) {
                    return $val;
                }
            }
        }
        global $confarray;
        global $appconf;
        if (array_key_exists($propname, self::$configcache)) {
            if (self::$feelinglucky) {
                return self::$configcache[$propname];
            }
        }
        if (is_array($confarray) && array_key_exists($propname, $confarray)) {
            self::$configcache[$propname] = $confarray[$propname];
            return $confarray[$propname];
        }
        if (is_array($appconf) && array_key_exists($propname, $appconf)) {
            self::$configcache[$propname] = $appconf[$propname];
            return $appconf[$propname];
        } else {
            $db = new \SPPMod\SPPDB\SPPDB();
            $query = 'select * from ' . \SPPMod\SPPDB\SPPDB::sppTable('config') . ' where propname=?';
            $result = $db->execute_query($query, array($propname));
            if (sizeof($result) <= 0) {
                throw new UnknownConfigVarException('Unknown config variable accessed ' . $propname);
            } else {
                $res = $result[0];
                if ($res['propval'] != 'fromtabs') {
                    self::$configcache[$propname] = $res['propval'];
                    return $res['propval'];
                } else {
                    $colName = preg_replace('/[^a-zA-Z0-9_]/', '', $res['colname']);
                    $tabName = preg_replace('/[^a-zA-Z0-9_]/', '', $res['tabname']);
                    $pkName = preg_replace('/[^a-zA-Z0-9_]/', '', $res['pkname']);

                    $sql = 'select ' . $colName . ' from ' . \SPPMod\SPPDB\SPPDB::sppTable($tabName) . ' where ' . $pkName . '=?';
                    $values = array($res['pkval']);
                    $result = $db->execute_query($sql, $values);
                    if (sizeof($result) <= 0) {
                        throw new UnknownConfigTabVarException('Unknown config tab variable accessed ' . $propname);
                    } else {
                        self::$configcache[$propname] = $result[0][$colName];
                        return $result[0][$colName];
                    }
                }
            }
        }
    }

    /**
     * static function set()
     * Set the values of a property
     *
     * @param string $propname
     * @param mixed $propval
     * @param string $storage
     */
    public static function set($propname, $propval, $storage = 'yaml')
    {
        if ($storage === 'yaml' && class_exists('\Symfony\Component\Yaml\Yaml')) {
            $loc = self::resolveYamlLocation($propname);
            if ($loc !== null) {
                $yamlData = [];
                if (file_exists($loc['path'])) {
                    $yamlData = \Symfony\Component\Yaml\Yaml::parseFile($loc['path']) ?? [];
                } else {
                    $dir = dirname($loc['path']);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                }
                self::setYamlNestedProperty($yamlData, $loc['keys'], $propval);
                file_put_contents($loc['path'], \Symfony\Component\Yaml\Yaml::dump($yamlData, 4, 4));
                self::$configcache[$propname] = $propval;
                return;
            }
        }

        global $confarray;
        if (is_array($confarray) && array_key_exists($propname, $confarray)) {
            throw new ReadonlyConfigVarException('Config var in settings.php was tried to be modified!');
        }
        $db = new \SPPMod\SPPDB\SPPDB();
        $query = 'select * from ' . \SPPMod\SPPDB\SPPDB::sppTable('config') . ' where propname=?';
        $result = $db->execute_query($query, array($propname));
        if (sizeof($result) <= 0) {
            throw new UnknownConfigVarException('Unknown config variable accessed ' . $propname);
        } else {
            $res = $result[0];
            if ($res['propval'] != 'fromtabs') {
                $sql = 'update ' . \SPPMod\SPPDB\SPPDB::sppTable('config') . ' set propval=? where propname=?';
                $values = array($propval, $propname);
                $result = $db->execute_query($sql, $values);
                self::$configcache[$propname] = $propval;
            } else {
                $colName = preg_replace('/[^a-zA-Z0-9_]/', '', $res['colname']);
                $tabName = preg_replace('/[^a-zA-Z0-9_]/', '', $res['tabname']);
                $pkName = preg_replace('/[^a-zA-Z0-9_]/', '', $res['pkname']);

                $sql = 'update ' . \SPPMod\SPPDB\SPPDB::sppTable($tabName) . ' set ' . $colName . '=? where ' . $pkName . '=?';
                $values = array($propval, $res['pkval']);
                $result = $db->execute_query($sql, $values);
                self::$configcache[$propname] = $propval;
            }
        }
    }

    /**
     * static function enableCache()
     *
     * Enables the caching.
     */
    public static function enableCache()
    {
        self::$feelinglucky = true;
    }

    /**
     * static function disableCache()
     *
     * Disables the caching.
     */
    public static function disableCache()
    {
        self::$feelinglucky = false;
    }

    /**
     * static function varExists()
     * Returns:
     *          1 if variable exists and is a normal config variable.
     *          2 if variable exists and is a tab variable.
     *          0 if variable does not exist.
     * 
     * @param string $propname
     * @return integer
     */
    public static function varExists($propname)
    {
        $db = new \SPPMod\SPPDB\SPPDB();
        $sql = 'select * from ' . \SPPMod\SPPDB\SPPDB::sppTable('config') . ' where propname=?';
        $values = array($propname);
        $result = $db->execute_query($sql, $values);
        if (sizeof($result) > 0) {
            if ($result[0]['propval'] == 'fromtabs') {
                return 2;
            } else {
                return 1;
            }
        } else {
            return 0;
        }
    }

    /**
     * static function createVar()
     * Creates a normal config variable.
     * Returns false if variable already exists.
     * 
     * @param string $propname
     * @param string $propval
     * @return bool
     */
    public static function createVar($propname, $propval)
    {
        $db = new \SPPMod\SPPDB\SPPDB();
        if (self::varExists($propname)) {
            return false;
        } else {
            $sql = 'insert into ' . \SPPMod\SPPDB\SPPDB::sppTable('config') . '(propname, propval) values(?,?)';
            $values = array($propname, $propval);
            $db->execute_query($sql, $values);
            return true;
        }
    }

    /**
     * static function createTabVar()
     * Creates a tab variable
     * Returns false if variable already exists.
     *
     * @param string $propname
     * @param string $tabname
     * @param string $colname
     * @param string $pkname
     * @param string $pkval
     * @return bool
     */
    public static function createTabVar($propname, $tabname, $colname, $pkname, $pkval)
    {
        $db = new \SPPMod\SPPDB\SPPDB();
        if (self::varExists($propname)) {
            return false;
        } else {
            $sql = 'insert into ' . \SPPMod\SPPDB\SPPDB::sppTable('config') . ' values(?,?,?,?,?,?)';
            $values = array($propname, 'fromtabs', $tabname, $colname, $pkname, $pkval);
            $db->execute_query($sql, $values);
            return true;
        }
    }

    /**
     * static function dropVar()
     * Drops an existing config variable.
     * Returns false if variable does not exist.
     *
     * @param string $propname
     * @return bool
     */
    public static function dropVar($propname)
    {
        $db = new \SPPMod\SPPDB\SPPDB();
        if (self::varExists($propname) == 0) {
            return false;
        } else {
            $sql = 'delete from ' . \SPPMod\SPPDB\SPPDB::sppTable('config') . ' where propname=?';
            $values = array($propname);
            $db->execute_query($sql, $values);
            return true;
        }
    }
}

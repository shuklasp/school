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
        $root = dirname(__DIR__, 4);
        $parts = explode('.', $propname);
        $filePath = '';
        $ymlKeys = [];
        
        if ($parts[0] === 'mod') {
            if (count($parts) < 3) return null;
            $modname = $parts[1];
            $filePath = $root . '/spp/etc/apps/default/modsconf/' . $modname . '/config.yml';
            $ymlKeys = array_slice($parts, 2);
        } elseif ($parts[0] === 'spp') {
            if (count($parts) < 3) return null;
            $filePath = $root . '/etc/settings/app/settings.yml';
            $ymlKeys = array_slice($parts, 1);
        } elseif ($parts[0] === 'app') {
            if (count($parts) < 3) return null;
            $appname = $parts[1];
            if ($appname === 'default') $appname = 'app';
            $filePath = $root . '/etc/settings/' . $appname . '/settings.yml';
            $ymlKeys = array_slice($parts, 2);
        } else {
            $filePath = $root . '/etc/settings/app/settings.yml';
            $ymlKeys = $parts; 
        }
        
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
            $db = new \SPPMod\SPPDB\SPP_DB();
            $query = 'select * from ' . \SPP\SPPBase::sppTable('config') . ' where propname=?';
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

                    $sql = 'select ' . $colName . ' from ' . \SPP\SPPBase::sppTable($tabName) . ' where ' . $pkName . '=?';
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
        $db = new \SPPMod\SPPDB\SPP_DB();
        $query = 'select * from ' . \SPP\SPPBase::sppTable('config') . ' where propname=?';
        $result = $db->execute_query($query, array($propname));
        if (sizeof($result) <= 0) {
            throw new UnknownConfigVarException('Unknown config variable accessed ' . $propname);
        } else {
            $res = $result[0];
            if ($res['propval'] != 'fromtabs') {
                $sql = 'update ' . \SPP\SPPBase::sppTable('config') . ' set propval=? where propname=?';
                $values = array($propval, $propname);
                $result = $db->execute_query($sql, $values);
                self::$configcache[$propname] = $propval;
            } else {
                $colName = preg_replace('/[^a-zA-Z0-9_]/', '', $res['colname']);
                $tabName = preg_replace('/[^a-zA-Z0-9_]/', '', $res['tabname']);
                $pkName = preg_replace('/[^a-zA-Z0-9_]/', '', $res['pkname']);

                $sql = 'update ' . \SPP\SPPBase::sppTable($tabName) . ' set ' . $colName . '=? where ' . $pkName . '=?';
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
        $db = new \SPPMod\SPPDB\SPP_DB();
        $sql = 'select * from ' . \SPP\SPPBase::sppTable('config') . ' where propname=?';
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
        $db = new \SPPMod\SPPDB\SPP_DB();
        if (self::varExists($propname)) {
            return false;
        } else {
            $sql = 'insert into ' . \SPP\SPPBase::sppTable('config') . '(propname, propval) values(?,?)';
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
        $db = new \SPPMod\SPPDB\SPP_DB();
        if (self::varExists($propname)) {
            return false;
        } else {
            $sql = 'insert into ' . \SPP\SPPBase::sppTable('config') . ' values(?,?,?,?,?,?)';
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
        $db = new \SPPMod\SPPDB\SPP_DB();
        if (self::varExists($propname) == 0) {
            return false;
        } else {
            $sql = 'delete from ' . \SPP\SPPBase::sppTable('config') . ' where propname=?';
            $values = array($propname);
            $db->execute_query($sql, $values);
            return true;
        }
    }
}

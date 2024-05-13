<?php

namespace SPPMod;
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
class SPP_Config extends \SPP\SPPObject{
    private static $configcache=array();
    private static $feelinglucky=true;
    /**
     * static function get()
     * Gets the values of a property.
     *
     * @param string $propname
     * @return mixed propval
     */
    public static function get($propname)
    {
        global $confarray;
        global $appconf;
        if(array_key_exists($propname, self::$configcache))
        {
            if(self::$feelinglucky)
            {
                return self::$configcache[$propname];
            }
        }
        if(array_key_exists($propname, $confarray))
        {
            self::$configcache[$propname]=$confarray[$propname];
            return $confarray[$propname];
        }
        if(array_key_exists($propname, $appconf))
        {
            self::$configcache[$propname]=$appconf[$propname];
            return $appconf[$propname];
        }
        else
        {
            $db=new SPP_DB();
            $query='select * from '.\SPP\SPPBase::sppTable('config').' where propname=?';
            $result=$db->execute_query($query, Array($propname));
            if(sizeof($result)<=0)
            {
                throw new UnknownConfigVarExecption('Unknown config variable accessed '.$propname);
            }
            else
            {
                $res=$result[0];
                if($res['propval']!='fromtabs')
                {
                    self::$configcache[$propname]=$res['propval'];
                    return $res['propval'];
                }
                else
                {
                    $sql='select '.$res['colname'].' from '.$res['tabname'].' where '.$res['pkname'].'=?';
                    $values=Array($res['pkval']);
                    $result=$db->execute_query($sql, $values);
                    if(sizeof($result)<=0)
                    {
                        throw new UnknownConfigTabVarExecption('Unknown config tab variable accessed '.$propname);
                    }
                    else
                    {
                        self::$configcache[$propname]=$result[0][$res['colname']];
                        return $result[0][$res['colname']];
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
     */
    public static function set($propname, $propval)
    {
        global $confarray;
        if(array_key_exists($propname, $confarray))
        {
            throw new ReadonlyConfigVarException('Config var in settings.php was tried to be modified!');
        }
        $db=new SPP_DB();
        $query='select * from '.\SPP\SPPBase::sppTable('config').' where propname=?';
        $result=$db->execute_query($query, Array($propname));
        if(sizeof($result)<=0)
        {
            throw new UnknownConfigVarException('Unknown config variable accessed '.$propname);
        }
        else
        {
            $res=$result[0];
            if($res['propval']!='fromtabs')
            {
                $sql='update '.\SPP\SPPBase::sppTable('config').' set propval=? where propname=?';
                $values=Array($propval,$propname);
                $result=$db->execute_query($sql, $values);
                self::$configcache[$propname]=$propval;
            }
            else
            {
                $sql='update '.\SPP\SPPBase::sppTable($res['tabname']).' set '.$res['colname'].'=? where '.$res['pkname'].'=?';
                $values=Array($propval,$res['pkval']);
                $result=$db->execute_query($sql, $values);
                self::$configcache[$propname]=$propval;
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
        self::$feelinglucky=true;
    }

    /**
     * static function disableCache()
     *
     * Disables the caching.
     */
    public static function disableCache()
    {
        self::$feelinglucky=false;
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
        $db=new SPP_DB();
        $sql='select * from '.\SPP\SPPBase::sppTable('config').' where propname=?';
        $values=array($propname);
        $result=$db->execute_query($sql, $values);
        if(sizeof($result)>0)
        {
            if($result[0]['propval']=='fromtabs')
            {
                return 2;
            }
            else
            {
                return 1;
            }
        }
        else
        {
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
        $db=new SPP_DB();
        if(self::varExists($propname))
        {
            return false;
        }
        else
        {
            $sql='insert into '.\SPP\SPPBase::sppTable('config').'(propname, propval) values(?,?)';
            $values=array($propname,$propval);
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
        $db=new SPP_DB();
        if(self::varExists($propname))
        {
            return false;
        }
        else
        {
            $sql='insert into '.\SPP\SPPBase::sppTable('config').' values(?,?,?,?,?,?)';
            $values=array($propname,'fromtabs',$tabname,$colname,$pkname,$pkval);
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
        $db=new SPP_DB();
        if(self::varExists($propname)==0)
        {
            return false;
        }
        else
        {
            $sql='delete from '.\SPP\SPPBase::sppTable('config').' where propname=?';
            $values=array($propname);
            $db->execute_query($sql, $values);
            return true;
        }
    }
}
?>
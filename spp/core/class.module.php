<?php
namespace SPP;
use SPP\Exceptions\DuplicateModuleException;
/*require_once 'class.sppregistry.php';
require_once 'sppsystemexceptions.php';*/

/**
 * class \SPP\Module
 * Defines a new module in Satya Portal Pack.
 *
 * @author Satya Prakash Shukla
 */

class Module extends \SPP\SPPObject {
    //private $int_name, $pub_name, $pub_desc, $inc_files, $req_mods, $ver,$group;
    //private $install_script, $uninstall_script, $modpath, $specxml;
    protected $_setprops=array('PublicName','PublicDesc','InternalName','Version','InstallScript','UninstallScript','ModuleGroup','IncludeFiles','Dependencies','ModPath','ConfigFile');
    protected $_getprops=array('PublicName', 'PublicDesc', 'InternalName','InstallScript', 'UninstallScript', 'ModuleDir', 'Version','IncludeFiles','Dependencies','ModPath','ConfigFile','ModuleGroup');
    //private static $ws=null;
    
    /**
     * Constructor
     *
     * @param string $int_name
     */
    /*public function __construct($int_name, $ver)
    {
        parent::__construct();
        if(\SPP\Registry::get('__mods=>'.$int_name)!==false)
        {
            \SPP\Registry::register('__mods=>'.$int_name, 1);
            $this->int_name=$int_name;
            $this->ver=$ver;
        }
        else
        {
            throw new DuplicateModuleException('Duplicate Module '.$int_name.' included!');
        }
    }*/
    public function __construct($file)
    {
        $this->readXML($file);
   //     $this->_getprops=array('PublicName', 'PublicDesc', 'InternalName','InstallScript', 'UninstallScript', 'ModuleDir', 'Version');
   //     $this->_setprops=array('PublicName','PublicDesc','InternalName','Version','InstallScript','UninstallScript','ModuleGroup');
    }

    private function readXML($file)
    {
        $xml=simplexml_load_file($file);
        $arr=$xml->xpath('/module');
        $path=dirname($file);
        $this->ModPath=$path;
        $arr=(array)$arr[0];
        //print_r($arr);
        foreach($arr as $key=>$val)
        {
            switch($key)
            {
                case 'name':
                    $this->InternalName=$val;
                    break;
                case 'version':
                    $this->Version=$val;
                    break;
                case 'pubname':
                    $this->PublicName=$val;
                    break;
                case 'modgroup':
                    $this->ModuleGroup=$val;
                    break;
                case 'pubdesc':
                    $this->PublicDesc=$val;
                    break;
                case 'config':
                    $this->ConfigFile=$val;
                    break;
                case 'includes':
                    $val=(array)$val;
                    $this->IncludeFiles=$val['include'];
                    break;
                case 'deps':
                    $val=(array)$val;
                    $this->Dependencies=$val['depends'];
                    break;
                default:
                    throw new \SPP\SPPException('module parse error!');
                    break;
            }
        }
    }

    /**
     * function getConfig()
     * Gets a config variable for the module.
     * 
     * @param string $varname
     * @return mixed 
     */
    public static function getConfig($varname,$modname)
    {
        //$mod=self::getModule($modname);
        //var_dump(\SPP\Registry::$reg);
        //echo '<br />';
        //var_dump($mod);
        $modpath=\SPP\Registry::get('__mods=>'.$modname);
        //var_dump($_SESSION);
        $spp_ds=SPP_DS;
        //var_dump($spp_ds);
        $modfile=$modpath.$spp_ds.'module.xml';
        //print($modpath.$spp_ds);
        //print($modfile);
        //echo $varname.' '.$modname;
        $xml=simplexml_load_file($modfile);
        //var_dump($xml);
        $arr=$xml->xpath('/module');
        //$path=dirname($file);
        //$this->ModPath=$path;
        $arr=(array)$arr[0];
        if(array_key_exists('config', $arr) /*isset($mod->ConfigFile)*/)
        {
            $xml=simplexml_load_file($modpath.SPP_DS.$arr['config']);
            $arr=$xml->xpath('/config/variables/variable[name=\''.$varname.'\']/value');
            //var_dump($arr);
            //print_r($arr);
            $arr=$arr[0];
//            var_dump($arr);
            return (string)$arr;
        }
        else
        {
            $proc=\SPP\Scheduler::getActiveProc();
            $confdir=$proc->getModsConfDir();
            $confdir.=SPP_DS.$modname;
            //$xml=simplexml_load_file(SPP_MODSCONF_DIR.SPP_DS.$modname.SPP_DS.'config.xml');
            $xml=simplexml_load_file($confdir.SPP_DS.'config.xml');
            $arr=$xml->xpath('/config/variables/variable[name=\''.$varname.'\']/value');
            //var_dump($arr);
            //print_r($arr);
            $arr=$arr[0];
//            var_dump($arr);
            return (string)$arr;
//            return false;
        }
    }

    /**
     * function getConfDir()
     * Gets the path of config dir for supplied module.
     * 
     * @param <type> $modname
     * @return <type>
     */
    public static function getConfDir($modname)
    {
        $dir=\SPP\Scheduler::getModsConfDir();
        $dir.=SPP_DS.$modname;
        return $dir;
    }

    /**
     * function getConfFile()
     * Returns path of reqired config file.
     * 
     * @param string $modname
     * @param string $filename
     * @return string
     */
    public static function getConfFile($modname,$filename)
    {
        $file=self::getConfDir($modname);
        $file.=SPP_DS.$filename;
        return $file;
    }

    /**
     * static function getModule()
     * Gets a \SPP\Module object for modname.
     * 
     * @return \SPP\Module
     */
    public static function getModule($modname)
    {
        //var_dump(\SPP\Registry::get('__mods'));
        $modpath=\SPP\Registry::get('__mods=>'.$modname);
        $modpath.=SPP_DS.'module.xml';
        $mod=new \SPP\Module($modpath);
        return $mod;
    }

    /**
     * static function scanModules()
     * Scans the modules directory for available modules.
     * 
     * @return <type>
     */
    public static function scanModules()
    {
        return SPPFS::findFile('module.xml', SPP_MODULES_DIR);
    }

    /**
     * function includeFiles()
     * Includes required files.
     */
    public function includeFiles()
    {
        $arr=(array)$this->IncludeFiles;
        //print_r($arr);
        foreach($arr as $file)
        {
          //  echo $this->ModPath.SPP_DS.$file.'<br />';
            require_once($this->ModPath.SPP_DS.$file);
        }
        if(file_exists($this->ModPath.SPP_DS.'modinit.php'))
        {
            require_once($this->ModPath.SPP_DS.'modinit.php');
        }
    }

    /**
     * function register()
     * Registers the module to SPP registery.
     */
    public function register()
    {
        if(\SPP\Registry::get('__mods=>'.$this->InternalName)===false)
        {
            \SPP\Registry::register('__mods=>'.$this->InternalName, $this->ModPath);
        }
        else
        {
            throw new DuplicateModuleException('Duplicate Module '.$this->InternalName.' included!');
        }
    }

    /**
     * function isRegistered()
     * Returns true if module is registered with the registry
     */
    public function isRegistered()
    {
        if(\SPP\Registry::get('__mods=>'.$this->InternalName)===false)
        {
            return false;
        }
        else
        {
            return true;
        }
    }


    /**
     * function loadAllModules()
     * Loads all the modules for current context.
     */
    public static function loadAllModules()
    {
        $xml_file='';
        $mods_xml='';
        if(\SPP\Scheduler::getContext()!='')
        {
            $xml_file=SPP_ETC_DIR.SPP_DS.'apps'.SPP_DS.\SPP\Scheduler::getContext().SPP_DS.'modules.xml';
        }
        else
        {
            $xml_file=SPP_ETC_DIR.SPP_DS.'modules.xml';
        }
        if(file_exists($xml_file))
        {
            $mods_xml=simplexml_load_file($xml_file);
        }
        else
        {
            throw new \SPP\SPPException('Modules config file not found : '.$xml_file);
        }
        $mods=$mods_xml->xpath('/modules/module[status=\'active\']');
        foreach($mods as $mod)
        {
            //$module=SPPXml::xml2phpArray($mod);
            //print_r($mod);
            //echo '<br />';
            $mod=(array)$mod;
            $path=$mod['modpath'];
            if(SPP_DS != '/')
            {
                $path=str_replace('/', '\\', $path);
            }
            $path=SPP_MODULES_DIR.SPP_DS.$path.SPP_DS.'module.xml';
            $module=new \SPP\Module($path);
            $module->register();
            $module->includeFiles();
        }
    }

    /**
     * function isEnabled()
     * Returns true if module is enabled.
     * 
     * @param <type> $mod
     * @return <type> 
     */
    public static function isEnabled($mod)
    {
        if(\SPP\Registry::get('__mods=>'.$mod)!==false)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
?>
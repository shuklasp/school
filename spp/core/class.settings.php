<?php
namespace SPP;

use Symfony\Component\Yaml\Yaml;

/**
 * class SPP\Settings
 * Displayes and gets settings from settings.xml
 *
 * @author Satya Prakash Shukla
 */
class Settings extends \SPP\SPPObject {
    private $setxml;
    private $yamlfile = 'settings.yml';
    private $settings;
    
    private function __construct()
    {
        $dir=SPP_BASE_DIR;
        $this->settings=Yaml::parseFile($dir.$this->yamlfile);
        //$this->setxml=simplexml_load_file($dir.'/settings.xml');
    }


    private static function isValidType($ymldata, $stype)
    {
        if(!array_key_exists('modes', $ymldata))
        {
            return true;
        }
        $modes=$ymldata['modes'];
        if($stype=='default')
        {
            return true;
        }
        foreach($modes as $mode)
        {
            if($mode['name']===$stype)
            {
                return array('format'=>$mode['format'], 'ref'=>$mode['ref']);
            }
        }
        return false;
    }

/**
 * TODO: Write private static function getSettingFromDb()
 * Retrive settings from database.
 *
 */

    private static function getSettingFromDb($sval, $dir)
    {
    }

    /**
     * private static function getSettingFromYaml
     * Retrive settings from yaml file.
     *
     * @param mixed $sval
     * @param mixed $dir
     * @throws \SPP\SPPException
     */
    private static function getSettingFromYaml($sval, $dir, $filename='settings.yml', $root='settings')
    {
        //echo '<br />'.$sval.' '.$dir.'<br />';
        $allset = Yaml::parseFile($dir . '/' . $filename);
        $allset = $root==null? $allset :$allset[$root];
        //var_dump($allset);
        $setkey = explode('/', $sval);
        foreach($setkey as $key)
        {
            if(!array_key_exists($key, $allset))
            {
                throw new \SPP\SPPException('Invalid setting '.$key.' accessed in '.$dir.'/'. $filename.'.');
                //return false;
            }
            $allset = $allset[$key];
        }
        return $allset;
    }


    /**
     * Get setting
     * 
     * public static function getSetting()
     *
     * @param mixed $sval           The setting to get
     * @return mixed                The setting value
     */
    public static function getSetting($sval, $root = 'settings', $ref='settings.yml', $dir=null)
    {
        //echo 'Returning settings';
        $defdir=$dir;
        $filename=$ref;
        $dir= $dir==null ? SPP_BASE_DIR: $dir;
        $sformat=null;
        $sref=null;
        $mode=null;
        $allset=Yaml::parseFile($dir . '/' . $filename);
        $setkey=explode('\\',$sval);
        $pos = strpos($sval, ':');
        $stype = $pos !== false ? trim(substr($sval, 0, $pos)) : trim('default');
        $sval = $pos !== false ? trim(substr($sval, $pos+1)) : trim($sval);

        if(!$mode=self::isValidType($allset, $stype /* $setkey[0] */))
        {
            throw new \SPP\SPPException(message: 'Invalid setting type '.$stype.'.');
        }
        if(is_array($mode))
        {
            $sformat= array_key_exists('format', $mode) ? $mode['format']: 'yaml';
            $sref=$mode['ref'];
            if($mode['format']=='yaml')
            {
                if ($defdir != null) {
                    return self::getSettingFromYaml($sval, $defdir, $filename, $root);
                }
                return self::getSettingFromYaml($sval, $dir, $filename, $root);
            }
/*             else if($mode['format']=='json')
            {
                return self::getSettingFromJson($sval, $sref);
            }
            elseif($mode['format']=='xml')
            {
                return self::getSettingFromXml($sval, $sref);
            }
 */
            elseif($mode['format']=='db')
            {
                return self::getSettingFromDb($sval, SPP_APP_DIR.$sref);
            }
            else
            {
                throw new \SPP\SPPException('Invalid setting format '.$mode['format'].' was accessed.');
            }
        }
        else
        {
            return self::getSettingFromYaml($sval, $dir, $filename, $root);
        }

   }


    /**
     * private static function getSettingFromYaml
     * Retrive settings from yaml file.
     *
     * @param mixed $sval
     * @param mixed $dir
     * @throws \SPP\SPPException
     */
    private static function putSettingToYaml($sval, $dir, $value, $filename='settings.yml', $root='settings')
    {
        $fullset = Yaml::parseFile($dir .'/'. $filename);
        $allset = $root==null?$fullset :$fullset['settings'];
        $setkey = explode('/', $sval);
        $setting=null;
        $numkeys=count($setkey);
        $setting[$setkey[$numkeys-1]]=$value;
        for($i=$numkeys-2; $i>=1; $i--)
        {
            if(!array_key_exists($setkey[$i], $allset))
            {
                throw new \SPP\SPPException('Invalid setting ' . $sval . ' accessed in ' . $dir . '/'. $filename.'.');
                //return false;
            }
            $set=$setting;
            $setting=null;
            $setting[$setkey[$i]]=$set;
        }
        $allset[$setkey[0]]=$setting;
        if($root!=null )
        {
            $fullset[$root] = $allset;
        }
        print_r($fullset);
        $yamlData = Yaml::dump($fullset, 8,  4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $dir1=$dir;
        str_replace('/','-', $dir1);
        copy($dir . '/' . $filename, SPP_LOG_DIR . '/settings/settings-' . date("Y-m-d-H-i-s") . $filename);

        file_put_contents($dir . '/' . $filename, $yamlData);
        return true;
    }




    /**
     * Put setting
     * 
     * public static function putSetting()
     *
     * @param mixed $sval           The setting to get
     * @param mixed $format         Format of the file
     * @param mixed $stype          Type of the setting 'app|sys|mod|umod'
     * @return mixed                The setting value
     */
    public static function putSetting($sval,  $value, $root = 'settings', $ref='settings.yml', $dir=null)
    {
        //echo 'Returning settings';
        $filename=$ref;
        $defdir=$dir;
        $dir = $dir == null ? SPP_BASE_DIR : $dir;
        $sformat = null;
        $sref = null;
        $mode = null;
        $allset = Yaml::parseFile($dir . '/' . $filename);
        $setkey = explode('\\', $sval);
        $pos = strpos($sval, ':');
        $stype = $pos !== false ? trim(substr($sval, 0, $pos)) : trim('default');
        $sval = $pos !== false ? trim(substr($sval, $pos + 1)) : trim($sval);

        if (!$mode = self::isValidType($allset, $stype /* $setkey[0] */)) {
            throw new \SPP\SPPException(message: 'Invalid setting type ' . $stype . '.');
        }
        if (is_array($mode)) {
            $sformat = array_key_exists('format', $mode) ? $mode['format'] : 'yaml';
            $sref = $mode['ref'];
            if ($mode['format'] == 'yaml') {
                if ($defdir != null) {
                    return self::putSettingToYaml($sval, $defdir, $value, $filename, $root);
                }
                return self::putSettingToYaml($sval, $dir, $value, $filename, $root);
            }
            /*             else if($mode['format']=='json')
            {
                return self::getSettingFromJson($sval, $sref);
            }
            elseif($mode['format']=='xml')
            {
                return self::getSettingFromXml($sval, $sref);
            }
 */ 
            elseif ($mode['format'] == 'db') {
                return self::getSettingFromDb($sval, SPP_APP_DIR.$sref);
            } else {
                throw new \SPP\SPPException('Invalid setting format ' . $mode['format'] . ' was accessed.');
            }
        } else {
            return self::putSettingToYaml($sval, $dir, $value, $filename, $root);
        }
    }
}

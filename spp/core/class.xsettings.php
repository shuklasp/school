<?php
namespace SPP;
/**
 * class SPP\Settings
 * Displayes and gets settings from settings.xml
 *
 * @author Satya Prakash Shukla
 */
class XSettings extends \SPP\SPPObject {
    private $setxml;
    
    public function __construct()
    {
        $dir=$GLOBALS['app']->getAppConfDir();
        $this->setxml=simplexml_load_file($dir.'/settings.xml');
    }

    public function getProfile()
    {
        $prof=$this->setxml->xpath('/settings/profile');
        return $prof[0];
    }

    public function getProfileSettings()
    {
        $prof=$this->getProfile();
        $path='/settings/profiles/profile[pname=\''.$prof.'\']';
        $settings=$this->setxml->xpath($path);
        return SPPXml::xml2phpArray($settings[0]);
    }

    public function getProfileSetting($sval)
    {
        $set=$this->getProfileSettings();
        if(array_key_exists($sval, $set))
        {
            return $set[$sval];
        }
        else
        {
            return false;
        }
    }
    public static function getSetting($sval)
    {
        /*$path='/settings//'.$sval;
        $settings=$this->setxml->xpath($path);
        $value=SPPXml::xml2phpArray($settings[0]);*/

        $dir=$GLOBALS['app']->getAppConfDir();
        $xml=simplexml_load_file($dir.'/settings.xml');
        //print_r($xml);
        $setval=$xml->$sval;
        if(sizeof($setval)<1)
        {
            return false;
        }
        else
        {
            return $setval;
        }
    }
}
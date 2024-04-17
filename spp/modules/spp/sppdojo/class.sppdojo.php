<?php
/*
 * File class.sppdojo.php
 * Defines the SPP_Dojo class.
 */
//include('dojoinit.php');
/**
 * class SPP_Dojo
 * Defines the use of dojo in SPP
 *
 * @author Satya Prakash Shukla
 * @package \SPP\Modules
 * @subpackage SPP_Dojo
 */
class SPP_Dojo extends \SPP\SPP_Object {
    private static $source='spp';
    private static $srcver='';
    private static $src_url='';
    private static $theme='tundra', $theme_url='';
    private static $dojocss='';
    private static $init=false;
    private static $djconfig=array();
    private static $djonload=array();
    private static $require=array();
    private static $dijits=array();
    private static $dijit_reg=array();
    
    public function __construct()
    {
        ;
    }

    public static function exportDijit($dijit, $file)
    {
        $dpath=dirname(__FILE__).SPP_DS.'sppdijit'.SPP_DS.'dijits'.SPP_DS.$file;
        self::$dijit_reg[$dijit]=$dpath;
    }

    public static function includeDijit($dijit)
    {
        $file=self::$dijit_reg[$dijit];
        require_once($file);
    }

    public static function registerDijit(SPP_Dijit $dijit)
    {
        $did=$dijit->getId();
        if(array_key_exists($did, self::$dijits))
        {
            throw new \SPP\SPP_Exception('Duplicate dijit declaration : '.$did);
        }
        else
        {
            self::$dijits[$did]=$dijit;
        }
    }

    public static function getDijit($did)
    {
        if(array_key_exists($did, self::$dijits))
        {
            return self::$dijits[$did];
        }
        else
        {
            return false;
        }
    }

    public static function getDijits()
    {
        return self::$dijits;
    }

    /**
     * static function init()
     * Initiates the dojo environment.
     *
     * @internal
     */
    public static function init($source_name='',$version='',$url='')
    {
        //echo 'init dojo <br>';
        if(!self::$init)
        {
            self::$init=true;
            \SPP\SPP_Event::setDefaultEventHandler('event_spp_core_dojo_inc', 'SPP_Dojo::includeDojoJS', 'instead');
            \SPP\SPP_Event::addEventHandler('event_spp_include_js_files', 'SPP_Dojo::includeDojoLoadJS', 'after');
            \SPP\SPP_Event::addEventHandler('event_spp_include_js_files', 'SPP_Dojo::includeDojoOptions', 'before');
            \SPP\SPP_Event::addEventHandler('event_spp_include_css_files', 'SPP_Dojo::includeDojoStyle', 'before');
            if($source_name=='')
            {
                self::setSource('spp', $version, $url);
            }
            else
            {
                self::setSource($source_name, $version, $url);
            }
        }
        //echo 'dojo inited';
    }

    public static function addRequire($mod)
    {
        self::$require[]=$mod;
    }

    public static function addOnLoad($str)
    {
        self::$djonload[]=$str;
    }

    public static function includeDojoJS()
    {
        if(self::$src_url!='')
        {
            SPP_HTML_Page::addJsIncludeFile(self::$src_url);
        }
        else
        {
            throw new \SPP\SPP_Exception('Dojo source not set.');
        }
    }

    public static function includeDojoLoadJS()
    {
        $str='
                <script type="text/javascript">
                ';
        foreach(self::$require as $req)
        {
            $str.='dojo.require("'.$req.'");
                    ';
        }
        $str.='dojo.addOnLoad(function(){
                ';
        foreach(self::$djonload as $onload)
        {
            $str.=$onload;
        }
        $str.='});
            </script>';
        echo $str;
    }


    public static function includeDojoOptions()
    {
        $str='<script type="text/javascript">
                djConfig = {
                    ';
        $flag=0;
        foreach(self::$djconfig as $conf=>$value)
        {
            if($flag==0)
            {
                $str.=$conf.' : '.$value;
                $flag=1;
            }
            else
            {
                $str.=',
                    '.$conf.' : '.$value;
            }
        }
        $str.='
                }
                </script>
                ';
        echo $str;
    }

    public static function includeDojoStyle()
    {
        if(self::$src_url!='')
        {
            if(self::$dojocss!='')
            {
                SPP_HTML_Page::addCssIncludeFile(self::$dojocss);
            }
            if(self::$theme_url!='')
            {
                SPP_HTML_Page::addCssIncludeFile(self::$theme_url);
            }
        }
        else
        {
            throw new \SPP\SPP_Exception('Dojo source not set.');
        }
    }

    public static function setConfig($config, $val)
    {
        self::$djconfig[$config]=$val;
    }

    public static function getConfig($config='')
    {
        if($config=='')
        {
            return self::$djconfig;
        }
        else
        {
            return self::$djconfig[$config];
        }
    }

    public static function setSource($source_name, $version='', $url='')
    {
        self::init();
        if($url!='')
        {
            self::$source=$source_name;
            self::$src_url=$url;
        }
        elseif($version!='')
        {
            self::$srcver=$version;
            $conffile=\SPP\Module::getConfFile('sppdojo', 'dojosources.xml');
            $xml=simplexml_load_file($conffile);
            //$xml=simplexml_load_file(SPP_MODSCONF_DIR.SPP_DS.'sppdojo'.SPP_DS.'dojosources.xml');
            $src=$xml->xpath('/dojosrc/src[name=\''.$source_name.'\']/version[@value=\''.$version.'\']');
            if(sizeof($src)<1)
            {
                throw new \SPP\SPP_Exception('Invalid source name or version: Source - '.$source_name.', Version - '.$version);
            }
            else
            {
                $src=(array)$src[0];
                if($source_name=='spp')
                {
                    self::$source=$source_name;
                    self::$src_url=SPP_DOJO_URI.$src['source'];
                    self::$dojocss=SPP_DOJO_URI.$src['dojocss'];
                }
                else
                {
                    self::$source=$source_name;
                    self::$src_url=$src['source'];
                    self::$dojocss=$src['dojocss'];
                }
                //var_dump($this);
            }
        }
        else
        {
            $conffile=\SPP\Module::getConfFile('sppdojo', 'dojosources.xml');
            $xml=simplexml_load_file($conffile);
            //$moddir=\SPP\Scheduler::getModsConfDir();
            //echo $moddir;
            //echo '<br>'.$moddir.SPP_DS.'sppdojo'.SPP_DS.'dojosources.xml';
            //$xml=simplexml_load_file($moddir.SPP_DS.'sppdojo'.SPP_DS.'dojosources.xml');
            $src=$xml->xpath('/dojosrc/src[name=\''.$source_name.'\']/version');
            $src=(array)$src;
            if(sizeof($src)<1)
            {
                throw new \SPP\SPP_Exception('Invalid source name or version: Source - '.$source_name.', Version - '.$version);
            }
            else
            {
                $hsrc=(array)$src[0];
                foreach($src as $csrc)
                {
                    $csrc=(array)$csrc;
                    if($csrc['@attributes']['value']>$hsrc['@attributes']['value'])
                    {
                        $hsrc=$csrc;
                    }
                }
                if($source_name=='spp')
                {
                    self::$srcver=$hsrc['@attributes']['value'];
                    self::$source=$source_name;
                    self::$src_url=SPP_DOJO_URI.$hsrc['source'];
                    self::$dojocss=SPP_DOJO_URI.$hsrc['dojocss'];
                }
                else
                {
                    self::$srcver=$hsrc['@attributes']['value'];
                    self::$source=$source_name;
                    self::$src_url=$hsrc['source'];
                    self::$dojocss=$hsrc['dojocss'];
                }
            }
        }
    }

    public static function setTheme($theme_name,$url='',$dojostyle='')
    {
        //$this->theme=$theme_name;
        //$this->theme_url=$url;
        self::init();
        if(self::$source=='')
        {
            throw new \SPP\SPP_Exception('Dojo source not set.');
        }
        elseif($dojostyle==''&&self::$dojocss=='')
        {
            throw new \SPP\SPP_Exception('Dojo css path not set.');
        }
        elseif($url!='')
        {
            self::$theme=$theme_name;
            self::$theme_url=$url;
        }
        elseif(self::$srcver=='')
        {
            throw new \SPP\SPP_Exception('Dojo version not set.');
        }
        elseif(self::$srcver!='')
        {
            //$moddir=\SPP\Scheduler::getModsConfDir();
            $conffile=\SPP\Module::getConfFile('sppdojo', 'dojosources.xml');
            //$xml=simplexml_load_file($moddir.SPP_DS.'sppdojo'.SPP_DS.'dojosources.xml');
            $xml=simplexml_load_file($conffile);
            $src=$xml->xpath('/dojosrc/src[name=\''.self::$source.'\']/version[@value=\''.self::$srcver.'\']/themes/theme[name=\''.$theme_name.'\']');
            $src=(array)$src[0];
            //var_dump($src);
            if(sizeof($src)<1)
            {
                throw new \SPP\SPP_Exception('Invalid theme name : '.$theme_name);
            }
            else
            {
                if(self::$source=='spp')
                {
                    self::$theme=$theme_name;
                    self::$theme_url=SPP_DOJO_URI.$src['style'];
                }
                else
                {
                    self::$theme=$theme_name;
                    self::$theme_url=$src['style'];
                }
                //var_dump($this);
            }
        }
        $str='dojo.query("body").addClass("'.self::$theme.'");';
        self::addOnLoad($str);
    }

    public static function get($prop)
    {
        switch($prop)
        {
            case 'Theme':
                return self::$theme;
                break;
            case 'ThemeURL':
                return self::$theme_url;
                break;
            case 'Source':
                return self::$source;
                break;
            case 'SourceURL':
                return self::$src_url;
                break;
            default:
                throw new \SPP\SPP_Exception('Illegal property : '.$prop);
                break;
        }
    }
}
?>
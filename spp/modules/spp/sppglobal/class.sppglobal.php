<?php
namespace SPPMod\SPPGlobal;
global $___globals;
$___globals=array();
namespace SPPMod;
class SPPGlobal{
    public function __construct()
    {
        
    }

    public static function registerGlobal($var, $val)
    {
        $GLOBALS['___globals'][$var]=$val;
    }

    public static function getGlobal($var){
        return($GLOBALS['___globals'][$var]);
    }
}
?>
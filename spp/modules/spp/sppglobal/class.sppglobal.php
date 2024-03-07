<?php
global $___globals;
$___globals=array();
class SPP_Global{
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
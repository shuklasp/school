<?php
require_once('ajaxexceptions.php');

/**
 * class SPP_Ajax
 * extends \SPP\SPPObject
 * Deals with ajax calls
 * 
 * SPP Services are defined in SPP_DIR/services.php
 */
class SPP_Ajax extends \SPP\SPPObject{
    function __construct()
    {
        
    }
    
    /**
     * function callService()
     * Calls a service defined in services.php
     * to be executed by server script.
     */
    public static function callService()
    {
        $serv=$_REQUEST['service'];
        //global $services, $servdir;
        $services=SPPGlobal::get('services');      // Gets the list of pages for services defined
        $servdir=SPPGlobal::get('servdir');        // Gets directory of server scripts of services.
        require_once($servdir.$services[$serv]);    // Call the page of service to be called.
    }

    /**
     * function getPageLocation($page)
     * Gets page location defined in services.php
     * 
     */
    public static function getPageLocation($page)
    {
        $pages=SPPGlobal::get('pages');
        $pagedir=SPPGlobal::get('pagedir');
        return($pagedir.$pages[$page]['page']);
    }

    /**
     * function loadPageContent()
     * Loads the content of the page.
     */
    public static function loadPageComponent()
    {
        $page=$_REQUEST['component'];
        require(self::getPageLocation($page));
    }

    /**
     * function isServiceRequest()
     * Returns true if the call is actually a service request and not a direct call
     */
    public static function isServiceRequest(){
        if(array_key_exists('service',$_REQUEST)){
            return true;
        }
        else{
            return false;
        }
    }

    public static function isComponentRequest(){
        if(array_key_exists('component',$_REQUEST)){
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * function callRoutine
     * Calls the Ajax routine supplied by calling script.
     *
     * @return void
     */
    public static function callRoutine()
    {
        $rout=$_REQUEST['rout'];
        if(function_exists($rout)){
            call_user_func($rout);
        }
        else{
            throw new AjaxRoutineNotFoundException('Ajax routine '.$rout.' not found.');
        }
    }

    /**
     * function existsVar
     * returns true if supplied variable has been supplied by ajax call.
     *
     * @param string $var
     * Variable to be checked
     * @return boolean
     */
    public static function existsVar($var): bool
    {
        if(array_key_exists("$var",$_REQUEST)){
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * function getValue
     * gets a REQUEST parameter suppied to Ajax call.
     *
     * @param string $var
     * Ajax variable for which value is required.
     * 
     * @return string
     * Value of the variable
     */
    public static function getValue($var){
        if(array_key_exists($var,$_REQUEST))
        {
            return $_REQUEST["$var"];
        }
        else{
            throw new AjaxVariableNotFoundException('Ajax variable '.$var.' not found.');
        }
    }

    /**
     * function returnAjax
     * Returns result of ajax call in json format
     *
     * @param array $arr
     * Result in array format
     * @return void
     */
    public static function returnAjax($arr){
        print(json_encode($arr));
    }

    public static function getScript($path, $print = FALSE)
    {
        //ob_end_flush();
        ob_start();

        if( is_readable($path) && $path )
        {
            include $path;
        }
        else
        {
            return FALSE;
        }

        //echo('Done output');

        if( $print == FALSE )
            return ob_get_contents();
        else
            echo ob_get_clean();
    }
}
?>
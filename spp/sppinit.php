<?php
/**
 * File sppinit.php
 * Initiates the SPP.
 */
if(!defined('SPP_VER'))
{
    session_start();

    /**
     * Store the old working directory.
     */
    define('SPP_VER','0.5');
    //define('SPP_DS',DIRECTORY_SEPARATOR);
    define('SPP_DS','/');
    define('SPP_US','/');
    define('SPP_BASE_DIR',dirname(__FILE__));
    define('SPP_DOC_ROOT',$_SERVER['DOCUMENT_ROOT']);
    $rstrlen=strlen(SPP_DOC_ROOT);
    $str=substr(SPP_BASE_DIR, $rstrlen);
    $str=str_replace('\\', '/', $str);
    define('SPP_CORE_DIR',SPP_BASE_DIR.SPP_DS.'core');
    define('SPP_RES_URI',$str.SPP_US.'resources');
    define('SPP_JS_URI',SPP_RES_URI.SPP_US.'js');
    define('SPP_DOJO_URI',SPP_JS_URI.SPP_US.'dojotoolkit');
    define('SPP_DEV_DIR',SPP_BASE_DIR.SPP_DS.'dev');
    define('SPP_CSS_URI',SPP_RES_URI.SPP_US.'css');
    define('SPP_IMG_URI',SPP_RES_URI.SPP_US.'images');
    define('SPP_MODULES_DIR', SPP_BASE_DIR.SPP_DS.'modules');
    define('SPP_ETC_DIR',SPP_BASE_DIR.SPP_DS.'etc');
    //define('SPP_APP_ETC',SPP_ETC_DIR.SPP_DS.'apps');
    //define('SPP_MODSCONF_DIR',SPP_ETC_DIR.SPP_DS.'modsconf');
    define('SPP_RC_DIR',SPP_ETC_DIR.SPP_DS.'rc.d');

    /**
     * Include core files.
     */

    require_once SPP_CORE_DIR.SPP_DS.'sppfuncs.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppobject.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppapp.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppscheduler.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppglobal.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppxml.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppstack.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppsettings.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppsession.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppbase.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppregistry.php';
    //require_once SPP_CORE_DIR.SPP_DS.'class.spphtmlelement.php';
    //require_once SPP_CORE_DIR.SPP_DS.'class.spphtmltable.php';
    //require_once SPP_CORE_DIR.SPP_DS.'class.sppformelement.php';
    //require_once SPP_CORE_DIR.SPP_DS.'class.sppvalidator.php';
    //require_once SPP_CORE_DIR.SPP_DS.'class.sppsinglevalidator.php';
    //require_once SPP_CORE_DIR.SPP_DS.'class.sppmultiplevalidator.php';
    //require_once SPP_CORE_DIR.SPP_DS.'classes.sppvalidators.php';
    //require_once SPP_CORE_DIR.SPP_DS.'classes.formelements.php';
    //require_once SPP_CORE_DIR.SPP_DS.'classes.htmlelements.php';
    //require_once SPP_CORE_DIR.SPP_DS.'class.sppform.php';
    //require_once SPP_CORE_DIR.SPP_DS.'class.spphtmlpage.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.spperror.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppevent.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppexception.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppmodule.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.spputils.php';
    require_once SPP_CORE_DIR . SPP_DS . 'int.sppimodule.php';
    require_once SPP_CORE_DIR . SPP_DS . 'int.sppientity.php';
    //require_once SPP_CORE_DIR.SPP_DS.'class.sppentity.php';
    require_once SPP_CORE_DIR.SPP_DS.'class.sppfs.php';
    require_once SPP_CORE_DIR.SPP_DS.'sppsystemexceptions.php';
    //require_once SPP_CORE_DIR.SPP_DS.'class.sppdev.php';
    //require_once SPP_CORE_DIR.SPP_DS.'class.sppbase.php';


    //\SPP\SPP_Event::registerEvent('spp_init');
  //  \SPP\SPP_Event::startEvent('spp_init');
//    define('SPP_SRC_URI',$str.SPP_DS.SPP_Setti)
    /**
     * Initiate SPP_Session and SPP_Error
     */
    //\SPP\SPP_Base::initSession();
    $app=new \SPP\App('');
  //  \SPP\SPP_Event::endEvent('spp_init');

    //$spperror=new SPP_Error();
    //\SPP\Module::loadAllModules();
    //SPP_Error::init();
}
//\SPP\App::getAppConfDir()
//session_destroy();
//print SPP_Error::getUlErrors('Line: !linenum!, File: !filename!, Error No.: !errno!, Error message: !errmsg!');
/**
 * Load all active modules.
 */
require_once('services.php');
?>
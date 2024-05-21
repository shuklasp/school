<?php
namespace SPP;
/* 
 * file class.sppapp.php
 * Defines \SPP\App class.
*/

/**
 * class \SPP\App
 * Defines application in SPP.
 *
 * @author Satya Prakash Shukla
 */
class App extends \SPP\SPPObject {
    private $appname='';
    private $modsloaded=false;
    private $errobj=0;
    private $app_status=self::APP_EXEC;
    public const APP_EXEC=1;
    public const APP_WAITING=2;
    public const APP_STOPPED=3;
    public const APP_ERROR=4;
    private static $data_dir;
    private static $log_dir;
    private static $cache_dir;
    private static $tmp_dir;
    private static $conf_dir;
    private static $mod_dir;

    /**
     * function __construct()
     * Constructor
     *
     * @param string $appname Application name
     * @param integer $init_level Init Level
     */
    public function __construct($appname='',$handleerror=true,$init_level=4) {
        if($appname=='')
        {
            $appname='default';
        }
        $this->appname=$appname;
        self::$data_dir=SPP_ETC_DIR.SPP_DS.'apps'.SPP_DS.$appname.SPP_DS.'data'.SPP_DS;
        self::$log_dir=SPP_ETC_DIR.SPP_DS.'apps'.SPP_DS.$appname.SPP_DS.'logs'.SPP_DS;
        self::$cache_dir=SPP_ETC_DIR.SPP_DS.'apps'.SPP_DS.$appname.SPP_DS.'cache'.SPP_DS;
        self::$tmp_dir=SPP_ETC_DIR.SPP_DS.'apps'.SPP_DS.$appname.SPP_DS.'tmp'.SPP_DS;
        self::$conf_dir=SPP_ETC_DIR.SPP_DS.'apps'.SPP_DS.$appname.SPP_DS.'conf'.SPP_DS;
        self::$mod_dir=SPP_ETC_DIR.SPP_DS.'apps'.SPP_DS.$appname.SPP_DS.'mods'.SPP_DS;
        if(\SPP\Registry::isRegistered('__apps'.$appname.'=>status')) {
            throw new \SPP\SPPException('Application '.$appname.' already exists.');
        }
        else {
            if($init_level>=1) {
                \SPP\Scheduler::regProc($this);
                \SPP\Scheduler::setContext($appname);
            }
            if($init_level>=2) {
                $this->loadModules();
            }
            if($init_level>=3) {
                if(!SPPSession::sessionExists()) {
                    $ssn=new SPPSession();
                    $_SESSION['__'.$appname.'_sppsession']=serialize($ssn);
                }
            }
            if($init_level>=4) {
                $this->errobj=new SPPError($handleerror);
            }
        }
        \SPP\SPPEvent::registerDirs();
        \SPP\SPPEvent::scanHandlers();
    }

    public static function getApp()
    {
        $context=\SPP\Scheduler::getContext();
        $app=new \SPP\App($context);
        return $app;
    }

    public static function getActiveApp()
    {
        $context=\SPP\Scheduler::getContext();
        return $context;
    }

    public function isModsLoaded()
    {
        return $this->modsloaded;
    }

    /**
     * function setStatus()
     * Sets the status of application.
     *
     * @param integer $status Status of the application.
     * @throws \SPP\SPPException If the status is invalid.
     */
    public function setStatus($status)
    {
        if($status==self::APP_EXEC||$status==self::APP_STOPPED||$status==self::APP_WAITING||$status==self::APP_ERROR)
        {
            $this->app_status = $status;
        }
        else {
            throw new \SPP\SPPException('Invalid application status.');
        }
    }

    /**
     * function getStatus()
     * Gets the status of application.
     *
     * @return integer Status of the application.
     */
    public function getStatus()
    {
        return $this->app_status;
    }



    public function getLogDir()
    {
        return self::$log_dir;
    }

    public function getCacheDir()
    {
        return self::$cache_dir;
    }

    public function getTmpDir()
    {
        return self::$tmp_dir;
    }

    public function getConfDir()
    {
        return self::$conf_dir;
    }

    public function getModDir()
    {
        return self::$mod_dir;
    }

    public function getDataDir()
    {
        return self::$data_dir;
    }

    /**
     * function getErrorObj()
     * Gets the error object for this application.
     * 
     * @return SPPError The error object.
     */
    public function getErrorObj() {
        return $this->errobj;
    }

    /**
     * function getName()
     * Returns the name of application.
     *
     * @return string Name of the application.
     */
    public function getName() {
        return $this->appname;
    }

    /**
     * function getModsConfDir()
     * Gets the mods conf dir.
     *
     * @return string Mods conf dir.
     */
    public function getModsConfDir()
    {
        $dir=SPP_ETC_DIR.SPP_DS.'apps'.SPP_DS.$this->appname.SPP_DS.'modsconf';
        return $dir;
    }

    /**
     * function getAppConfDir()
     * Gets the app conf dir.
     *
     * @return string App conf dir.
     */
    public function getAppConfDir()
    {
        $dir=SPP_ETC_DIR.SPP_DS.'apps'.SPP_DS.$this->appname;
        return $dir;
    }

    /**
     * function initSession()
     * Initiates a spp session.
     */
    public static function initSession()
    {
        $ssname=self::getSessionName();
        if(!SPPSession::sessionExists())
        {
            $ssn=new SPPSession();
            $_SESSION[$ssname]=serialize($ssn);
        }
    }

    /**
     * function killSession()
     * Kills the present spp session.
     */
    public static function killSession()
    {
        $ssname=self::getSessionName();
        \SPP\SPPEvent::startEvent('ev_kill_session');
        $ssname=self::getSessionName();
        if(SPPSession::sessionExists())
        {
            unset($_SESSION[$ssname]);
            //session_destroy();
        }
        \SPP\SPPEvent::endEvent('spp_event_kill_session');
    }

    /**
     * function getSessionName()
     * Gets the name of present spp session variable.
     *
     * @return string Name of the session.
     */
    public static function getSessionName()
    {
        $context=\SPP\Scheduler::getContext();
        $ssnname='__'.$context.'_sppsession';
        return $ssnname;
    }
    /*    public function setStatus($status)
    {
        if($status==self::APP_EXEC||$status==self::APP_WAITING)
        {
            $oldcontext=\SPP\Scheduler::getContext();
            \SPP\Scheduler::setContext($this->appname);
            \SPP\Registry::register('status', $status);
            \SPP\Scheduler::setContext($oldcontext);
        }
    }

    public function getStatus($status)
    {
        if($status==self::APP_EXEC||$status==self::APP_WAITING)
        {
            $oldcontext=\SPP\Scheduler::getContext();
            \SPP\Scheduler::setContext($this->appname);
            \SPP\Registry::register('status', $status);
            \SPP\Scheduler::setContext($oldcontext);
        }
    }*/

    /**
     * function loadModules()
     * Loads all the currently active modules.
     */
    public function loadModules() {
        if($this->modsloaded===false) {
            $oldcontext=\SPP\Scheduler::getContext();
            \SPP\Scheduler::setContext($this->appname);
            \SPP\Module::loadAllModules();
            \SPP\Scheduler::setContext($oldcontext);
            $this->modsloaded=true;
        }
    }
}
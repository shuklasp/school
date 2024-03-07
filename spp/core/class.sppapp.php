<?php
/* 
 * file class.sppapp.php
 * Defines SPP_App class.
*/

/**
 * class SPP_App
 * Defines application in SPP.
 *
 * @author Satya Prakash Shukla
 */
class SPP_App extends SPP_Object {
    private $appname='';
    private $modsloaded=false;
    private $errobj=0;

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
        if(SPP_Registry::isRegistered('__apps'.$appname.'=>status')) {
            throw new SPP_Exception('Application '.$appname.' already exists.');
        }
        else {
            if($init_level>=1) {
                SPP_Scheduler::regProc($this);
                SPP_Scheduler::setContext($appname);
            }
            if($init_level>=2) {
                $this->loadModules();
            }
            if($init_level>=3) {
                if(!SPP_Session::sessionExists()) {
                    $ssn=new SPP_Session();
                    $_SESSION['__'.$appname.'_sppsession']=serialize($ssn);
                }
            }
            if($init_level>=4) {
                $this->errobj=new SPP_Error($handleerror);
            }
        }
    }

    /**
     * function getErrorObj()
     * Gets the error object for this application.
     * 
     * @return SPP_Error The error object.
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

    public function getModsConfDir()
    {
        $dir=SPP_ETC_DIR.SPP_DS.'apps'.SPP_DS.$this->appname.SPP_DS.'modsconf';
        return $dir;
    }

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
        if(!SPP_Session::sessionExists())
        {
            $ssn=new SPP_Session();
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
        SPP_Event::startEvent('ev_kill_session',array('sname'=>$ssname));
        if(SPP_Session::sessionExists())
        {
            unset($_SESSION[$ssname]);
            //session_destroy();
        }
        SPP_Event::endEvent('spp_event_kill_session');
    }

    /**
     * function getSessionName()
     * Gets the name of present spp session variable.
     *
     * @return string Name of the session.
     */
    public static function getSessionName()
    {
        $context=SPP_Scheduler::getContext();
        $ssnname='__'.$context.'_sppsession';
        return $ssnname;
    }
    /*    public function setStatus($status)
    {
        if($status==self::APP_EXEC||$status==self::APP_WAITING)
        {
            $oldcontext=SPP_Scheduler::getContext();
            SPP_Scheduler::setContext($this->appname);
            SPP_Registry::register('status', $status);
            SPP_Scheduler::setContext($oldcontext);
        }
    }

    public function getStatus($status)
    {
        if($status==self::APP_EXEC||$status==self::APP_WAITING)
        {
            $oldcontext=SPP_Scheduler::getContext();
            SPP_Scheduler::setContext($this->appname);
            SPP_Registry::register('status', $status);
            SPP_Scheduler::setContext($oldcontext);
        }
    }*/

    /**
     * function loadModules()
     * Loads all the currently active modules.
     */
    public function loadModules() {
        if($this->modsloaded===false) {
            $oldcontext=SPP_Scheduler::getContext();
            SPP_Scheduler::setContext($this->appname);
            SPP_Module::loadAllModules();
            SPP_Scheduler::setContext($oldcontext);
            $this->modsloaded=true;
        }
    }
}
?>
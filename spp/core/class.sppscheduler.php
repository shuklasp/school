<?php
namespace SPP;
/* 
 * class.sppscheduler.php
 * Defines the class \SPP\Scheduler
 */

/**
 * class \SPP\Scheduler
 *
 * @author Satya Prakash Shukla
 */
class Scheduler extends \SPP\SPP_Object {
    private static $AppContext='';
    private static $procs=array();
    const APP_CREATED = 1;
    const APP_EXEC = 2;
    const APP_WAITING = 3;

    /**
     * function setContext()
     * Sets an application context.
     * 
     * @param string $context name of application context.
     */
    public static function setContext($context)
    {
        if($context=='')
        {
            $context='default';
        }
        if(array_key_exists($context, self::$procs))
        {
            if(self::$AppContext=='')
            {
                self::$AppContext=$context;
            }
            else{
                $curr_proc = self::getActiveProc();
                $new_proc = self::getProcObj($context);
                $curr_proc->setStatus(\SPP\Scheduler::APP_WAITING);
                $new_proc->setStatus(\SPP\Scheduler::APP_EXEC);
                self::$AppContext=$context;
            }
        }
        else
        {
            throw new \SPP\SPP_Exception('Unregistered context : '.$context);
        }
    }


    /**
     * function regProc()
     * Registers a new process.
     *
     * @param \SPP\App  $proc \SPP\App object.
     */
    public static function regProc(\SPP\App $proc)
    {
        $pname=$proc->getName();
        if(array_key_exists($pname, self::$procs))
        {
            throw new \SPP\SPP_Exception('Duplicate process registration : '.$pname);
        }
        else
        {
            if(is_a($proc, '\SPP\App'))
            {
                self::$procs[$pname]=$proc;
            }
            else
            {
                throw new \SPP\SPP_Exception('Invalid process registration : '.$pname);
            }
        }
    }

    /**
     * function getContext()
     * Returns currently active application context.
     *
     * @return string context name.
     */
    public static function getContext()
    {
        return self::$AppContext;
    }

    public static function getModsConfDir()
    {
        $proc=self::getActiveProc();
        return $proc->getModsConfDir();
    }

    /**
     * function getProcObj($pname)
     * Returns \SPP\App object for given process name.
     *
     * @param string $pname process name.
     * @return \SPP\App object.
     * @throws \SPP\SPP_Exception if process not registered.
     */
    public static function getProcObj($pname)
    {
        if(array_key_exists($pname, self::$procs))
        {
            return self::$procs[$pname];
        }
        else
        {
            throw new \SPP\SPP_Exception('Unregistered process : '.$pname);
        }
    }

    
    /**
     * function getActiveProc()
     * Returns \SPP\App object for active application context.
     *
     * @return \SPP\App object.
     * @throws \SPP\SPP_Exception if context not set.
     */
    public static function getActiveProc()
    {
        if(self::$AppContext=='')
        {
            throw new \SPP\SPP_Exception('Application context not set.');
        }
        else
        {
            $pname=self::$AppContext;
            return self::$procs[$pname];
        }
    }

    /**
     * function getActiveErrorObj()
     * Returns SPP_Error object for active application context.
     *
     * @return SPP_Error object.
     * @throws \SPP\SPP_Exception if context not set.
     */
    public static function getActiveErrorObj()
    {
        $proc=self::getActiveProc();
        return $proc->getErrorObj();
    }
}
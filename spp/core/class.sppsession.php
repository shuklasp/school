<?php
namespace SPP;
/*require_once 'class.sppobject.php';
require_once 'sppsystemexceptions.php';
require_once 'class.spperror.php';*/
/**
 * class SPP_Session
 * Handles session variables in SPP
 *
 * @author Satya Prakash Shukla
 */

class SPP_Session extends \SPP\SPP_Object {
    private $sessvars=array();

    /**
     * private function startSession()
     * Start a session if it does not already exist.
     */
    public function __construct()
    {
        $ssname=\SPP\App::getSessionName();
        if(!array_key_exists($ssname, $_SESSION))
        {
         //   $ssn=new SPP_Session();
            $this->setVar('__wizards__', array());
            //$this->setVar('__errors__', SPP_Error::getErrors());
        }
    }

    /**
     * public function sessionExists()
     * Checks whether a SPP session exists or not.
     *
     * @return bool
     */
    public static function sessionExists()
    {
        //$app=new \SPP\App();
        $ssname=\SPP\App::getSessionName();
        if(array_key_exists($ssname, $_SESSION))
        {
            return true;
        }
        else
        {
            return false;
        }
    }



    /**
     * static function validSessionVarExists()
     * Checks whether a variable exists or not.
     *
     * @param string $varname
     * @return bool
     */
    public static function validSessionVarExists($varname)
    {
        $ssname=\SPP\App::getSessionName();
        //self::startSession();
        if(!self::sessionExists())
        {
            throw new \SessionDoesNotExistException('No session exists!');
        }
        $ssn=unserialize($_SESSION[$ssname]);
        return $ssn->validVarExists($varname);
    }



    /**
     * static function sessionVarExists()
     * Checks whether a variable exists or not.
     *
     * @param string $varname
     * @return bool
     */
    public static function sessionVarExists($varname)
    {
        $ssname=\SPP\App::getSessionName();
        //self::startSession();
        if(!self::sessionExists())
        {
            throw new \SessionDoesNotExistException('No session exists!');
        }
        $ssn=unserialize($_SESSION[$ssname]);
        return $ssn->varExists($varname);
    }


    /**
     * static function getSessionVar()
     * Gets a session variable.
     *
     * @param string $varname
     * @return mixed
     */
    public static function getSessionVar($varname)
    {
        $ssname=\SPP\App::getSessionName();
        //self::startSession();
        if(!self::sessionExists())
        {
            throw new \SessionDoesNotExistException('No session exists!');
        }
        $ssn=unserialize($_SESSION[$ssname]);
        return $ssn->getVar($varname);
    }

    /**
     * static function setSessionVar()
     * Sets a custom session variable.
     *
     * @param string $varname
     * @param mixed $varval
     */
    public static function setSessionVar($varname, $varval)
    {
        $ssname=\SPP\App::getSessionName();
        //self::startSession();
        if(!self::sessionExists())
        {
            throw new \SessionDoesNotExistException('No session exists!');
        }
        $ssn=unserialize($_SESSION[$ssname]);
        $ssn->setVar($varname,$varval);
        $_SESSION[$ssname]=serialize($ssn);
    }

    /**
     * static function unsetSessionVar()
     * Unsets a custom session variable.
     *
     * @param string $varname
     */
    public static function unsetSessionVar($varname)
    {
        $ssname=\SPP\App::getSessionName();
        //self::startSession();
        if(!self::sessionExists())
        {
            throw new \SessionDoesNotExistException('No session exists!');
        }
        $ssn=unserialize($_SESSION[$ssname]);
        $ssn->unsetVar($varname);
        $_SESSION[$ssname]=serialize($ssn);
    }


    /**
     * static function invalidateSessionVar()
     * Invalidates a custom session variable.
     *
     * @param string $varname
     */
    public static function invalidateSessionVar($varname)
    {
        $ssname=\SPP\App::getSessionName();
        //self::startSession();
        if(!self::sessionExists())
        {
            throw new \SessionDoesNotExistException('No session exists!');
        }
        $ssn=unserialize($_SESSION[$ssname]);
        $ssn->invalidateVar($varname);
        $_SESSION[$ssname]=serialize($ssn);
    }


    /**
     * function setVar()
     * Sets the value of an application defined session variable.
     *
     * @param string $varname
     * @param mixed $varval
     */
    public function setVar($varname, $varval)
    {
        $this->sessvars[$varname]['val']=$varval;
        $this->sessvars[$varname]['isactive']=true;
    }

    /**
     * function unsetVar()
     * Unsets a spp session variable.
     *
     * @param mixed $varname
     */
    public function unsetVar($varname)
    {
        unset($this->sessvars[$varname]);
    }

    /**
     * function varExists()
     * Returns true if session variable exists.
     * 
     * @param <type> $varname
     * @return <type>
     */
    public function varExists($varname)
    {
        if(array_key_exists($varname, $this->sessvars))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * function validVarExists()
     * Finds if a particular valid variable exists or not.
     *
     * @param string $varname
     * @return bool
     */
    public function validVarExists($varname)
    {
        if(array_key_exists($varname, $this->sessvars))
        {
            if($this->sessvars[$varname]['isactive'])
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * function getVar()
     * Gets the value of a custom variable.
     *
     * @param <type> $varname
     * @return <type>
     */
    public function getVar($varname)
    {
        if($this->validVarExists($varname))
        {
            return $this->sessvars[$varname]['val'];
        }
        else
        {
            throw new \UnknownSessionVarException('Undefined session variable '.$varname.' accessed.');
        }
    }

    /**
     * function invalidateVar()
     * Invalidate a session registered variable.
     *
     * @param <type> $varname
     */
    public function invalidateVar($varname)
    {
        if($this->validVarExists($varname))
        {
            $this->sessvars[$varname]['isactive']=false;
        }
        else
        {
            throw new \UnknownSessionVarException('Undefined session variable '.$varname.' accessed.');
        }
    }

}
?>
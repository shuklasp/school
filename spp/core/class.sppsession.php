<?php
namespace SPP;
use SPP\Exceptions;
use SPP\Exceptions\SessionDoesNotExistException;
use SPP\Exceptions\UnknownSessionVarException;
/*require_once 'class.sppobject.php';
require_once 'sppsystemexceptions.php';
require_once 'class.spperror.php';*/
/**
 * class SPPSession
 * Handles session variables in SPP
 *
 * @author Satya Prakash Shukla
 */

class SPPSession extends \SPP\SPPObject
{
    private $sessvars = array();

    /** @var ?SPPSession Local memory cache to prevent duplicate deserialization */
    private static ?SPPSession $cache = null;

    private static function fetchSession(): SPPSession
    {
        $ssname = \SPP\App::getSessionName();
        if (!self::sessionExists()) {
            self::$cache = null;
            throw new SessionDoesNotExistException('No session exists!');
        }
        if (self::$cache !== null) {
            return self::$cache;
        }
        // Restrict allowed classes to prevent POI vectors, but allow the user session classes
        self::$cache = unserialize($_SESSION[$ssname], ['allowed_classes' => true]);
        return self::$cache;
    }

    private static function saveSession(): void
    {
        if (self::$cache !== null) {
            $_SESSION[\SPP\App::getSessionName()] = serialize(self::$cache);
        }
    }

    /**
     * private function startSession()
     * Start a session if it does not already exist.
     */
    public function __construct()
    {
        $ssname = \SPP\App::getSessionName();
        if (!array_key_exists($ssname, $_SESSION)) {
            //   $ssn=new SPPSession();
            $this->setVar('__wizards__', array());
            //$this->setVar('__errors__', SPPError::getErrors());
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
        $ssname = \SPP\App::getSessionName();
        if (isset($_SESSION) && array_key_exists($ssname, $_SESSION)) {
            return true;
        } else {
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
        return self::fetchSession()->validVarExists($varname);
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
        return self::fetchSession()->varExists($varname);
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
        return self::fetchSession()->getVar($varname);
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
        $ssn = self::fetchSession();
        $ssn->setVar($varname, $varval);
        self::saveSession();
    }

    /**
     * static function unsetSessionVar()
     * Unsets a custom session variable.
     *
     * @param string $varname
     */
    public static function unsetSessionVar($varname)
    {
        $ssn = self::fetchSession();
        $ssn->unsetVar($varname);
        self::saveSession();
    }


    /**
     * static function invalidateSessionVar()
     * Invalidates a custom session variable.
     *
     * @param string $varname
     */
    public static function invalidateSessionVar($varname)
    {
        $ssn = self::fetchSession();
        $ssn->invalidateVar($varname);
        self::saveSession();
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
        $this->sessvars[$varname]['val'] = $varval;
        $this->sessvars[$varname]['isactive'] = true;
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
        if (array_key_exists($varname, $this->sessvars)) {
            return true;
        } else {
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
        if (array_key_exists($varname, $this->sessvars)) {
            if ($this->sessvars[$varname]['isactive']) {
                return true;
            } else {
                return false;
            }
        } else {
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
        if ($this->validVarExists($varname)) {
            return $this->sessvars[$varname]['val'];
        } else {
            throw new UnknownSessionVarException('Undefined session variable ' . $varname . ' accessed.');
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
        if ($this->validVarExists($varname)) {
            $this->sessvars[$varname]['isactive'] = false;
        } else {
            throw new UnknownSessionVarException('Undefined session variable ' . $varname . ' accessed.');
        }
    }

}
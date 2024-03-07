<?php
/*require_once 'class.sppusersession.php';
require_once 'sppsystemevents.php';
require_once 'sppfuncs.php';*/
/**
 * class SPP_Auth
 * Handles authentication system.
 *
 * @author Satya Prakash Shukla
 */
class SPP_Auth extends SPP_Object{
    /**
     * static function login()
     * Authenticates a userid/password and creates session.
     * 
     * @param string $uname
     * @param string $passwd
     */
    public static function login($uname,$passwd)
    {
        $ssn=new SPP_User_Session($uname,$passwd);
        SPP_Session::setSessionVar('__sppauth__', $ssn);
        return $ssn;
        /*$ev=new LoginEvent();
        $ev->handle();*/
    }

    /**
     * static function logout()
     * Logs the user out.
     */
    public static function logout()
    {
        /*$ev=new LogoutEvent();
        $ev->handle();*/
        if(self::authSessionExists())
        {
            $ssn=SPP_Session::getSessionVar('__sppauth__');
            $ssn->kill();
            session_destroy();
        }
    }

    /**
     * static function authSessionExists()
     * Checks whether an authorised session exists or not.
     *
     * @return bool
     */
    public static function authSessionExists($consider_timeout=false)
    {
        if(SPP_Session::sessionVarExists('__sppauth__'))
        {
            //echo 'sessvar';
            /*$ssn=SPP_Session::getSessionVar('__sppauth__');
            if($ssn->isValid($consider_timeout))
            {*/
                return true;
            /*}
            else
            {
                return false;
            }*/
        }
        else
        {
            //echo 'no sess var';
            return false;
        }
    }

    /**
     * static function get()
     * Gets the value of a property.
     * Valid Properties:
     *          UserName
     *          UserId
     *          Session Variable
     * 
     * @param string $propname
     * @return mixed
     */
    public static function get($propname)
    {
        if(self::authSessionExists())
        {
            $ssn=SPP_Session::getSessionVar('__sppauth__');
            switch($propname)
            {
                case 'UserName':
                    return $ssn->get('UserName');
                    break;
                case 'UserId':
                    return $ssn->get('UserId');
                    break;
                default:
                    throw new UnknownPropertyException('Unknown property '.$propname.' accessed in SPP_Auth.');
                    break;
            }
        }
        else
        {
            throw new NoAuthSessionException('No Authenticated Session Exists!');
        }
    }


    /**
     * static function validVarExists()
     * Checks whether a variable exists or not.
     *
     * @param string $varname
     * @return bool
     */
    public static function validVarExists($varname)
    {
        if(self::authSessionExists())
        {
            $ssn=SPP_Session::getSessionVar('__sppauth__');
            return $ssn->validVarExists($varname);
        }
        else
        {
            throw new NoAuthSessionException('No Authenticated Session Exists!');
        }
    }

    /**
     * static function getVar()
     * Gets the value of a custom session variable.
     *
     * @param string $varname
     * @return mixed
     */
    public static function getVar($varname)
    {
        if(self::authSessionExists())
        {
            $ssn=SPP_Session::getSessionVar('__sppauth__');;
            return $ssn->getVar($varname);
        }
        else
        {
            throw new NoAuthSessionException('No Authenticated Session Exists!');
        }
    }

    /**
     * static function hasRight()
     * Determines whether session has a particular right or not.
     *
     * @param string $rt
     * @return bool
     */
    public static function hasRight($rt)
	{
        if(self::authSessionExists())
        {
            $ssn=SPP_Session::getSessionVar('__sppauth__');;
            return $ssn->hasRight($rt);
        }
        else
        {
            throw new NoAuthSessionException('No Authenticated Session Exists!');
        }
	}

}
?>
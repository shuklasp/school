<?php
/**
 * class SPP_Wizard
 * Defines and handles wizards in SPP
 *
 * @author Satya Prakash Shukla
 */
// require_once 'class.sppbase.php';
 
class SPP_Wizard extends SPP_Object {
    /**
     * static function get()
     * Gets the value of a wizard variable.
     * 
     * @param string $varname
     * @param string $wname
     * @return mixed
     */
    public static function get($varname, $wname)
    {
        if(!SPP_Session::sessionExists())
        {
            throw new SessionDoesNotExistException('No session exists!');
        }
        $wizards=SPP_Session::getSessionVar('__wizards__');
        return $wizards[$wname];
    }

    /**
     * Function cleanup()
     * Cleans up all the wizard data.
     */
    public static function cleanup()
    {
        if(!SPP_Session::sessionExists())
        {
            throw new SessionDoesNotExistException('No session exists!');
        }
        $wizards=SPP_Session::setSessionVar('__wizards__',array());
    }

    /**
     * static function dropWizard()
     * Drops a wizard.
     *
     * @param string $wname
     */
    public static function dropWizard($wname) {
        //self::startSession();
        if(!SPP_Session::sessionExists())
        {
            throw new SessionDoesNotExistException('No session exists!');
        }
/*        elseif(!self::existsWizard($wname))
        {
            self::createWizard($wname);
        }*/
        $wizards=SPP_Session::getSessionVar('__wizards__');
        unset($wizards[$wname]);
        SPP_Session::setSessionVar('__wizards__',$wizards);
    }

    /**
     * static function existsWizard()
     * Tests a wizard for existence.
     *
     * @param string $wname
     * @return bool
     */
    public static function existsWizard($wname) {
        //self::startSession();
        if(!SPP_Session::sessionExists())
        {
            throw new SessionDoesNotExistException('No session exists!');
        }
/*        elseif(!self::existsWizard($wname))
        {
            self::createWizard($wname);
        }*/
        $wizards=SPP_Session::getSessionVar('__wizards__');
        if(array_key_exists($wname,$wizards))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Function processWizard()
     * Process the wizard.
     */

    public static function processWizard($wname)
    {
        if(self::existsWizard($wname))
        {
            $callfunc=$wname.'_processor';
            if(function_exists($callfunc))
            {
                $callfunc();
            }
        }
    }

    /**
     * Function collectSubmittedVars()
     * Collects all the form post data on this page.
     * 
     * @param string $wname
     * @param string $method
     */
    public static function collectSubmittedVars($wname,$method='post')
    {
        if(!SPP_Session::sessionExists())
        {
            throw new SessionDoesNotExistException('No session exists!');
        }
/*        elseif(!self::existsWizard($wname))
        {
            self::createWizard($wname);
        }*/
        $wizards=SPP_Session::getSessionVar('__wizards__');
        if($method=='post')
        {
            foreach($_POST as $key=>$val)
            {
                $wizards[$key]=$val;
            }
        }
        elseif($method=='get')
        {
            foreach($_GET as $key=>$val)
            {
                $wizards[$key]=$val;
            }
        }
        elseif($method=='request')
        {
            foreach($_REQUEST as $key=>$val)
            {
                $wizards[$key]=$val;
            }
        }
        else
        {
            throw new UnknownRequestTypeException('Unknown request type '.$method.' used');
        }
    }
}
?>
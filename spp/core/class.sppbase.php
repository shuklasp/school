<?php
/*require_once 'class.sppsession.php';
require_once 'sppsystemexceptions.php';
require_once 'sppconstants.php';*/
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * class SPP_Base
 *
 * @author Satya Prakash Shukla
 */
class SPP_Base extends SPP_Object {
    /*public static function useModule($modname)
    {
        switch($modname)
        {
            case 'SPP_Auth':
                require_once SPP_BASE_DIR.SPPUS.'sppauth.php';
                break;
            case 'SPPHtml':
                require_once SPP_BASE_DIR.SPPUS.'spphtml.php';
                break;
            case 'SPP_Profile':
                require_once SPP_BASE_DIR.SPPUS.'sppprofile.php';
                break;
            case 'SPP_Dev':
                require_once SPP_BASE_DIR.SPPUS.'sppdev.php';
                break;
            case 'SPPDB':
                require_once SPP_BASE_DIR.SPPUS.'sppdb.php';
                break;
            case 'SPP_Session':
                require_once SPP_BASE_DIR.SPPUS.'sppsession.php';
                break;
            case 'SPP_Wizard':
                require_once SPP_BASE_DIR.SPPUS.'sppwizard.php';
                break;
            default:
                throw new SPP_Exception('Illegal module inclusion :'.$modname);
        }
    }*/

    public static function initSession()
    {
        if(!SPP_Session::sessionExists())
        {
            $ssn=new SPP_Session();
            $_SESSION['sppsession']=serialize($ssn);
        }
    }

    public static function killSession()
    {
        if(SPP_Session::sessionExists())
        {
            unset($_SESSION['sppsession']);
            session_destroy();
        }
    }

    /*public static function sppLink($link)
    {
        if(strstr($link,":"))
        {
            return $link;
        }
        else
        {
            if(array_key_exists('sppmode', $_GET))
            {
                return 'index.php?sppmode='.$_GET['sppmode'].'&spppage='.$link;
            }
            else
            {
                return 'index.php?spppage='.$link;
            }
        }
    }

    public static function isAppSetup()
    {
        $currdir=dirname(__FILE__);
        if(file_exists($currdir.SPPDS.'settings.php'))
        {
            return true;
        }
        else
        {
            return false;
        }
    }*/

    public static function sppTable($tname)
    {
/*        if(self::isAppSetup())
        {
            require 'settings.php';
            global $confarray;
            return $confarray['tableprefix'].$tname;
        }*/
        return $tname;
    }
}
?>
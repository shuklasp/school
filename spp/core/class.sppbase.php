<?php
namespace SPP;
/*require_once 'class.sppsession.php';
require_once 'sppsystemexceptions.php';
require_once 'sppconstants.php';*/
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * class \SPP\SPPBase
 *
 * @author Satya Prakash Shukla
 */
class SPPBase extends \SPP\SPPObject {
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
            case 'SPPDev':
                require_once SPP_BASE_DIR.SPPUS.'sppdev.php';
                break;
            case 'SPPDB':
                require_once SPP_BASE_DIR.SPPUS.'sppdb.php';
                break;
            case 'SPPSession':
                require_once SPP_BASE_DIR.SPPUS.'sppsession.php';
                break;
            case 'SPP_Wizard':
                require_once SPP_BASE_DIR.SPPUS.'sppwizard.php';
                break;
            default:
                throw new \SPP\SPPException('Illegal module inclusion :'.$modname);
        }
    }*/

    public static function initSession()
    {
        if(!SPPSession::sessionExists())
        {
            $ssn=new SPPSession();
            $_SESSION['sppsession']=serialize($ssn);
        }
    }

    public static function killSession()
    {
        if(SPPSession::sessionExists())
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
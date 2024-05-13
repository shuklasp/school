<?php
namespace SPP;
use SPP\Exceptions\UnknownConfigVarException;
/**
 * class SPPDev
 * Encapsulates all the developer functionality.
 *
 * @author Satya Prakash Shukla
 */
//require_once 'sppconstants.php';
require_once 'sppsystemexceptions.php';
$currdir=dirname(__FILE__);
if(file_exists($currdir.SPP_DS.'devsettings.php'))
{
    require_once 'devsettings.php';;
}

class SPPDev extends \SPP\SPPObject {
    //put your code here
    public function __construct()
    {
        ;
    }

    public static function isDevEnvSetup()
    {
        $currdir=dirname(__FILE__);
        if(file_exists($currdir.SPP_DS.'settings.php')&&file_exists($currdir.SPP_DS.'devsettings.php'))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function getSetting($sname)
    {
        if(self::isDevEnvSetup())
        {
            global $devconfarray;
            if(array_key_exists($sname, $devconfarray))
            {
                return $devconfarray[$sname];
            }
            else
            {
                throw new UnknownConfigVarException('Unknown Development Config variable '.$sname.' accessed');
            }
        }
        else
        {
            return null;
        }
    }
}
?>
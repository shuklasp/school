<?php
namespace SPPMod\SPPLogger;
/*require_once 'class.sppdatabase.php';
require_once 'class.sppusersession.php';
require_once 'sppfuncs.php';
require_once 'class.sppsequence.php';
require_once 'class.sppbase.php';*/
/**
 * class Logger
 * Handles logging in the system.
 *
 * @author Satya Prakash Shukla
 */
class SPP_Logger extends \SPP\SPPObject{
    //put your code here

    public static function write_to_log($logtxt)
    {
        $db=new \SPPMod\SPPDB\SPP_DB();
        $uid='';
        $uname='';
        if(\SPPMod\SPPAuth\SPPAuth::authSessionExists())
        {
            $uid=\SPPMod\SPPAuth\SPPAuth::get('UserId');
            $uname=\SPPMod\SPPAuth\SPPAuth::get('UserName');
        }
        $ip=getVisitorIP();
        $logtime=date('Y-m-d H:i:s',time());
        $sessid=session_id();
        $sql='insert into '.\SPP\SPPBase::sppTable('logger').'(loggerid,uid,uname,ip,logtime,sessid,descr) values(?,?,?,?,?,?,?)';
        $values=Array(date('Ymd',time()).\SPPMod\SPPDB\SPP_Sequence::next('loggerid',true),$uid,$uname,$ip,$logtime,$sessid,$logtxt);
//        echo $sql;
//        print_r($values);
        $result=$db->execute_query($sql, $values);
//        echo 'Log Written';
    }
}
?>
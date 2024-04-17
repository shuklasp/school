<?php
/*
 * class.sppuserprofile.php
 * Defines SPP_UserProfile class.
 */

/**
 * Description of SPP_UserProfile
 * Defines user profiles.
 *
 * @author Satya Prakash Shukla
 */
class SPP_UserProfile {

    public static function install() {
        if(!SPP_Profile::doesProfileExist('users'))
            SPP_Profile::createProfile ('users');
    }
    function getValue($fld,$unm='') {
        if($unm=='')
        {
            $unm=SPP_Auth::get('UserName');
        }
        $usr=new SPP_User($unm);
        $uid=$usr->get('UserId');
        $db=new SPP_DB();
        $sql='select profid from userprofiles where uid=?';
        $result=$db->execute_query($sql, array($uid));
        $profid='';
        if(sizeof($result)>0)
        {
            $profid=$result[0]['profid'];
        }
        else
        {
            throw(new \SPP\SPP_Exception('Profile for this user does not exist!'));
        }
        $sql='select profval from profiles where profid=? and profkey=?';
    }
}
?>
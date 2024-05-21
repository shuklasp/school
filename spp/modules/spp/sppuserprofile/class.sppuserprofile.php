<?php

namespace SPPMod\SPPUserProfile;
/*
 * class.sppuserprofile.php
 * Defines SPPMod\SPPUserProfile\SPPUserProfile class.
 */

/**
 * Description of SPPMod\SPPUserProfile\SPPUserProfile
 * Defines user profiles.
 *
 * @author Satya Prakash Shukla
 */
class SPPMod\SPPUserProfile\SPPUserProfile {

    public static function install() {
        if(!\SPPMod\SPPProfile\SPPProfile::doesProfileExist('users'))
            \SPPMod\SPPProfile\SPPProfile::createProfile ('users');
    }
    function getValue($fld,$unm='') {
        if($unm=='')
        {
            $unm=\SPPMod\SPPAuth\SPPAuth::get('UserName');
        }
        $usr=new \SPPMod\SPPAuth\SPPUser($unm);
        $uid=$usr->get('UserId');
        $db=new \SPPMod\SPPDB\SPP_DB();
        $sql='select profid from userprofiles where uid=?';
        $result=$db->execute_query($sql, array($uid));
        $profid='';
        if(sizeof($result)>0)
        {
            $profid=$result[0]['profid'];
        }
        else
        {
            throw(new \SPP\SPPException('Profile for this user does not exist!'));
        }
        $sql='select profval from profiles where profid=? and profkey=?';
    }
}
?>
<?php

namespace SPPMod\SPPUserProfile;

/**
 * Description of SPPUserProfile
 * Defines user profiles that bridge the Auth subsystem with the dynamic SPPProfile extension schema.
 *
 * @author Satya Prakash Shukla
 */
class SPPUserProfile
{

    /**
     * Initializes the user profiles table integration
     */
    public static function install()
    {
        if (!\SPPMod\SPPProfile\SPPProfile::doesProfileExist('users')) {
            // Re-architected: Added strict 'uid' column map to safely link Auth User IDs to generated Profile IDs
            \SPPMod\SPPProfile\SPPProfile::createProfile('users', array('uid' => 20));
        }
    }

    /**
     * Retrieves a dynamic profile extension value for a specific user.
     * 
     * @param string $fld Sub-profile key name
     * @param string $unm Optional username. Self-resolves default authentication if left empty.
     * @throws \SPP\SPPException
     * @return mixed
     */
    public static function getValue($fld, $unm = '')
    {
        if ($unm == '') {
            $unm = \SPPMod\SPPAuth\SPPAuth::get('UserName');
        }

        if (empty($unm)) {
            throw new \SPP\SPPException('No user identity provided and no session authenticated.');
        }

        $usr = new \SPPMod\SPPAuth\SPPUser($unm);
        $uid = $usr->get('UserId');

        $profile = new \SPPMod\SPPProfile\SPPProfile('users');

        // Find profile via the linked native uid column
        if ($profile->seekValue('uid', $uid)) {
            return $profile->get($fld);
        } else {
            throw new \SPP\SPPException('Profile extension for this user does not exist!');
        }
    }

    /**
     * Updates or bootstraps a user's profile extension value.
     * 
     * @param string $fld Sub-profile key name
     * @param mixed $val Target value
     * @param string $unm Optional username
     */
    public static function setValue($fld, $val, $unm = '')
    {
        if ($unm == '') {
            $unm = \SPPMod\SPPAuth\SPPAuth::get('UserName');
        }

        if (empty($unm)) {
            throw new \SPP\SPPException('No user identity provided and no session authenticated.');
        }

        $usr = new \SPPMod\SPPAuth\SPPUser($unm);
        $uid = $usr->get('UserId');

        $profile = new \SPPMod\SPPProfile\SPPProfile('users');

        if ($profile->seekValue('uid', $uid)) {
            $profile->set($fld, $val);
            $profile->update();
        } else {
            // Bootstrap missing linked profile layer dynamically!
            $profile->appendSave(array(
                'uid' => $uid,
                $fld => $val
            ));
        }
    }
}

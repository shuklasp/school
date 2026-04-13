<?php
namespace SPPMod\SPPAuth;

use SPP\Exceptions\UserBannedException;
use SPP\Exceptions\UserAuthenticationException;
use SPP\Exceptions\InvalidUserSessionException;
use SPP\Exceptions\UnknownPropertyException;
use SPPMod\SPPDB\SPPDB;
use SPP\SPPBase;
use SPP\SPPSession;
use SPPMod\SPPConfig\SPPConfig;

/**
 * Class SPPUserSession
 * 
 * Manages the lifecycle of an authenticated user session. Stores session metadata
 * in the database to enable cross-request persistence and security checks.
 * 
 * Extends \SPP\SPPSession to provide user-specific context.
 * 
 * @package SPPMod\SPPAuth
 */
class SPPUserSession extends SPPSession
{
    /** @var SPPUser $user The authenticated user entity */
    private $user;
    
    /** @var string $sessid The physical PHP session identifier */
    private $sessid;

    /**
     * SPPUserSession Constructor.
     * 
     * Attempts to authenticate a user by username and password. On success, 
     * it initializes the session record in the database and rotates the session ID.
     *
     * @param string $unm The username.
     * @param string $pswd The plaintext password.
     * @throws UserBannedException if the user account is not active.
     * @throws UserAuthenticationException if credentials fail validation.
     */
    public function __construct($unm, $pswd)
    {
        $db = new SPPDB();
        $this->user = new SPPUser($unm);
        
        if ($this->user->verifyPassword($pswd)) {
            if (!$this->user->isEnabled()) {
                throw new UserBannedException("User '{$unm}' is restricted from accessing the system.");
            }
            
            // Session Fixation Defense: Rotate the ID on privilege change
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $this->sessid = session_id();
            
            // Record login event
            $sql = 'INSERT INTO ' . \SPPMod\SPPDB\SPPDB::sppTable('loginrec') . 
                   '(sessid, uid, logintime, ipaddr, lastaccess) VALUES (?, ?, NOW(), ?, NOW())';
            $values = array(
                $this->sessid, 
                $this->user->get('UserId'), 
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            );
            $db->execute_query($sql, $values);

            // Optional: Periodic cleanup of expired sessions
            if (mt_rand(1, 100) <= 5) {
                $this->purgeExpiredSessions();
            }
        } else {
            throw new UserAuthenticationException("Authentication failed for user '{$unm}'.");
        }
    }

    /**
     * Purge sessions that have exceeded the defined timeout period.
     */
    private function purgeExpiredSessions()
    {
        $db = new SPPDB();
        try {
            $timeout = (int)SPPConfig::get('spp.user_session_timeout', 'yaml') * 60; // seconds
        } catch (\Exception $e) {
            $timeout = 60 * 60;
        }
        $sql = 'DELETE FROM ' . \SPPMod\SPPDB\SPPDB::sppTable('loginrec') . 
               ' WHERE TIMESTAMPDIFF(SECOND, lastaccess, NOW()) > ?';
        $db->execute_query($sql, array($timeout));
    }

    /**
     * Validate the current session status.
     * 
     * Checks if the session still exists in the database and has not timed out.
     * Refreshes the 'lastaccess' timestamp on success.
     *
     * @param bool $consider_timeout Whether to enforce inactivity timeouts.
     * @return bool True if the session is alive and valid.
     */
    public function isValid($consider_timeout = true)
    {
        $db = new SPPDB();
        $sql = 'SELECT TIMESTAMPDIFF(SECOND, lastaccess, NOW()) as elapsed_time, NOW() as curr_time 
                FROM ' . \SPPMod\SPPDB\SPPDB::sppTable('loginrec') . ' WHERE sessid=?';
        $result = $db->execute_query($sql, array($this->sessid));
        
        if (count($result) > 0) {
            $elapsed = (int)$result[0]['elapsed_time'];
            
            try {
                $timeout = (int)SPPConfig::get('spp.user_session_timeout', 'yaml') * 60;
            } catch (\Exception $e) {
                $timeout = 60 * 60; // Default: 60 minutes
            }
            
            if ($consider_timeout && $elapsed > $timeout) {
                $this->kill();
                return false;
            }
            
            // Heartbeat: update activity time
            $sql = 'UPDATE ' . \SPPMod\SPPDB\SPPDB::sppTable('loginrec') . ' SET lastaccess=? WHERE sessid=?';
            $db->execute_query($sql, array($result[0]['curr_time'], $this->sessid));
            return true;
        }
        return false;
    }

    /**
     * Destroy the session both in the database and the PHP session superglobal.
     */
    public function kill()
    {
        $db = new SPPDB();
        $sql = 'DELETE FROM ' . \SPPMod\SPPDB\SPPDB::sppTable('loginrec') . ' WHERE sessid=?';
        $db->execute_query($sql, array($this->sessid));

        if (isset($_SESSION['__sppauth__'])) {
            unset($_SESSION['__sppauth__']);
        }
    }

    /**
     * Check if the authenticated user has a specific management right.
     *
     * @param string $rt The right identifier.
     * @return bool
     * @throws InvalidUserSessionException if called on an expired session.
     */
    public function hasRight($rt)
    {
        if ($this->isValid()) {
            return $this->user->hasRight($rt);
        }
        throw new InvalidUserSessionException("Action attempted on an invalid user session.");
    }

    /**
     * Retrieve user metadata associated with the session.
     * 
     * @param string $propname Supports 'UserName' and 'UserId'.
     * @return mixed
     * @throws InvalidUserSessionException if the session is no longer valid.
     * @throws UnknownPropertyException if an invalid key is requested.
     */
    public function get($propname)
    {
        if ($this->isValid()) {
            return $this->user->get($propname);
        }
        throw new InvalidUserSessionException("Data access attempted on an invalid user session.");
    }
}

?>

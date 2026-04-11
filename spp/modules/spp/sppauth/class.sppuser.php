<?php
namespace SPPMod\SPPAuth;

use SPP\Exceptions\UserNotFoundException;
use SPP\Exceptions\UnknownPropertyException;
use SPPMod\SPPDB\SPP_DB;
use SPP\SPPBase;

/**
 * Class SPPUser
 * 
 * Manages user entities within the SPP framework. Handles authentication,
 * profile retrieval, and role management using the modernized database schema.
 * 
 * Database Schema Mapping:
 * - id            => Primary Identity
 * - username      => Unique login handle
 * - password_hash => Securely hashed password (via password_hash)
 * - role_id       => Reference to the associated role in the 'roles' table
 * - status        => Administrative status ('active', 'inactive', 'banned')
 * 
 * @package SPPMod\SPPAuth
 */
class SPPUser extends \SPP\SPPObject
{
    /** @var string|null $username The unique username of the user */
    private $username;
    
    /** @var int|null $id The unique numeric identifier of the user */
    private $id;
    
    /** @var string $status The current status of the user */
    private $status;
    
    /** @var array $rights Cached permissions/rights for the user's role */
    private $rights = [];
    
    /** @var int|null $roleId The role ID associated with this user */
    private $roleId;

    /**
     * SPPUser Constructor.
     * 
     * Loads user data and permissions from the database based on the provided username.
     *
     * @param string $unm The username to load.
     * @throws UserNotFoundException if the username does not exist in the database.
     */
    public function __construct($unm)
    {
        $db = new SPP_DB();
        $this->rights = [];
        
        // Fetch user basic info
        $qry = 'SELECT id, status, role_id FROM ' . SPPBase::sppTable('users') . ' WHERE username=?';
        $res = $db->execute_query($qry, array($unm));
        
        if (count($res) > 0) {
            $this->id = $res[0]['id'];
            $this->status = $res[0]['status'];
            $this->roleId = $res[0]['role_id'];
            $this->username = $unm;
            
            // Fetch rights associated with the user's role
            // Modernized for 1-to-many role relationship
            if ($this->roleId) {
                // Assuming 'role_rights' exists or rights are tied to role. 
                // For now, mirroring old logic but adapted to role_id in users table.
                // We'll assume a schema where roles are tied to rights via a link table if exists.
                // If the system is strictly 'role' based, we might just use role names.
                // But following original pattern:
                // Updated for actual schema: rights table uses 'id' and 'name'
                $qry = 'SELECT rt.name as rightname FROM ' . SPPBase::sppTable('rights') . ' rt, ' . 
                       SPPBase::sppTable('roleright') . ' rr WHERE rr.roleid=? AND rt.id=rr.rightid';
                $rightRes = $db->execute_query($qry, array($this->roleId));
                foreach ($rightRes as $row) {
                    $this->rights[] = $row['rightname'];
                }
            }
        } else {
            throw new UserNotFoundException("User '{$unm}' not found.");
        }
    }

    /**
     * Retrieve user properties by name.
     * 
     * Supported properties:
     * - UserName: The login handle
     * - UserId: The numeric ID
     * - Enabled: Boolean status flag
     * - Status: Raw status string
     *
     * @param string $propname The property to retrieve.
     * @return mixed The value of the property.
     * @throws UnknownPropertyException if an invalid property is requested.
     */
    public function get($propname)
    {
        switch ($propname) {
            case 'UserName':
                return $this->username;
            case 'UserId':
                return $this->id;
            case 'Enabled':
                return $this->isEnabled();
            case 'Status':
                return $this->status;
            default:
                throw new UnknownPropertyException("Unknown property '{$propname}' accessed in SPPUser.");
        }
    }

    /**
     * Check if the user is currently active.
     * 
     * @return bool True if status is 'active', false otherwise.
     */
    public function isEnabled()
    {
        return strtolower($this->status) === 'active' || $this->status === 'Y';
    }

    /**
     * Securely verify a plaintext password against the stored hash.
     *
     * @param string $passwd The plaintext password to verify.
     * @return bool True if the password is correct.
     */
    public function verifyPassword($passwd)
    {
        $db = new SPP_DB();
        $qry = 'SELECT password_hash FROM ' . SPPBase::sppTable('users') . ' WHERE username=?';
        $res = $db->execute_query($qry, array($this->username));
        
        if (count($res) > 0) {
            return password_verify($passwd, $res[0]['password_hash']);
        }
        return false;
    }

    /**
     * Static check to see if a user exists in the system.
     *
     * @param string $uname The username to check.
     * @return bool True if existence is confirmed.
     */
    public static function userExists($uname)
    {
        $db = new SPP_DB();
        $qry = 'SELECT id FROM ' . SPPBase::sppTable('users') . ' WHERE username=?';
        $res = $db->execute_query($qry, array($uname));
        return count($res) > 0;
    }

    /**
     * Static shorthand to verify a user's password without instantiating an object.
     *
     * @param string $uname
     * @param string $passwd
     * @return bool
     */
    public static function verifyUserPassword($uname, $passwd)
    {
        $db = new SPP_DB();
        $qry = 'SELECT password_hash FROM ' . SPPBase::sppTable('users') . ' WHERE username=?';
        $res = $db->execute_query($qry, array($uname));
        if (count($res) > 0) {
            return password_verify($passwd, $res[0]['password_hash']);
        }
        return false;
    }

    /**
     * Check if the user possesses a specific privilege/right.
     * 
     * @param string $rt The right name to check.
     * @return bool True if the user has the right.
     */
    public function hasRight($rt)
    {
        return in_array($rt, $this->rights);
    }

    /**
     * Create a new user in the system.
     *
     * @param string $uname The username for the new account.
     * @param string $passwd The plaintext password (will be hashed).
     * @param string $status Initial account status (default: 'active').
     * @return bool True on success, false if user already exists.
     */
    public static function createUser($uname, $passwd, $status = 'active')
    {
        $db = new SPP_DB();
        if (self::userExists($uname)) {
            return false;
        }
        
        $hashed = password_hash($passwd, PASSWORD_DEFAULT);
        $sql = 'INSERT INTO ' . SPPBase::sppTable('users') . ' (username, password_hash, status, created_at) VALUES (?, ?, ?, NOW())';
        $db->execute_query($sql, array($uname, $hashed, $status));
        return true;
    }

    /**
     * Permanently remove a user from the system.
     *
     * @param string $uname
     * @return bool
     */
    public static function dropUser($uname)
    {
        $db = new SPP_DB();
        if (!self::userExists($uname)) {
            return false;
        }
        $sql = 'DELETE FROM ' . SPPBase::sppTable('users') . ' WHERE username=?';
        $db->execute_query($sql, array($uname));
        return true;
    }

    /**
     * Update the password for an existing user.
     *
     * @param string $passwd The new plaintext password.
     */
    public function setPassword($passwd)
    {
        $hashed = password_hash($passwd, PASSWORD_DEFAULT);
        $db = new SPP_DB();
        $sql = 'UPDATE ' . SPPBase::sppTable('users') . ' SET password_hash=? WHERE id=?';
        $db->execute_query($sql, array($hashed, $this->id));
    }

    /**
     * Set user status to 'active'.
     */
    public function enable()
    {
        $db = new SPP_DB();
        $sql = 'UPDATE ' . SPPBase::sppTable('users') . ' SET status=? WHERE id=?';
        $db->execute_query($sql, array('active', $this->id));
    }

    /**
     * Set user status to 'inactive'.
     */
    public function disable()
    {
        $db = new SPP_DB();
        $sql = 'UPDATE ' . SPPBase::sppTable('users') . ' SET status=? WHERE id=?';
        $db->execute_query($sql, array('inactive', $this->id));
    }

    /**
     * Check if the user is assigned a specific role by name.
     *
     * @param string $rolename
     * @return bool
     */
    public function hasRole($rolename)
    {
        $db = new SPP_DB();
        $sql = 'SELECT ur.id FROM ' . SPPBase::sppTable('users') . ' ur, ' . 
               SPPBase::sppTable('roles') . ' r WHERE ur.id=? AND ur.role_id=r.id AND r.role_name=?';
        $result = $db->execute_query($sql, array($this->id, $rolename));
        return count($result) > 0;
    }

    /**
     * Assign a specific role to the user by role name.
     * 
     * @param string $rolename
     * @return bool True if role was assigned or already possessed.
     */
    public function assignRole($rolename)
    {
        $db = new SPP_DB();
        // Get role identifier
        $sql = 'SELECT id FROM ' . SPPBase::sppTable('roles') . ' WHERE role_name=?';
        $res = $db->execute_query($sql, array($rolename));
        
        if (count($res) === 0) return false;
        
        $roleId = $res[0]['id'];
        $sql = 'UPDATE ' . SPPBase::sppTable('users') . ' SET role_id=? WHERE id=?';
        $db->execute_query($sql, array($roleId, $this->id));
        $this->roleId = $roleId;
        return true;
    }

    /**
     * Unset the user's role assignment.
     *
     * @return bool
     */
    public function removeRole()
    {
        $db = new SPP_DB();
        $sql = 'UPDATE ' . SPPBase::sppTable('users') . ' SET role_id=NULL WHERE id=?';
        $db->execute_query($sql, array($this->id));
        $this->roleId = null;
        return true;
    }
}
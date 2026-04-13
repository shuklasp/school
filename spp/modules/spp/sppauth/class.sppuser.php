<?php
namespace SPPMod\SPPAuth;

use SPPMod\SPPEntity\SPPEntity;
use SPP\Exceptions\UserNotFoundException;
use SPPMod\SPPDB\SPPDB;

/**
 * Class SPPUser
 * 
 * Manages user entities within the SPP framework. Handles authentication,
 * profile retrieval, and role management using the modernized SPPEntity architecture.
 */
class SPPUser extends SPPEntity
{
    /** @var array $role_ids Internal storage for many-to-many role IDs */
    protected $role_ids = [];

    /** @var array $rights Non-persistent storage for derived permissions */
    protected $rights = [];

    /**
     * Entity constructor. Supports loading by username or ID.
     */
    public function __construct($unm = null)
    {
        if ($unm !== null && !is_numeric($unm)) {
            try {
                parent::__construct();
                $this->loadByUsername($unm);
            } catch (\Exception $e) {
                throw new UserNotFoundException("User '{$unm}' not found.");
            }
        } else {
            parent::__construct($unm);
        }
    }
    
    /**
     * Returns the internal numeric ID of the user.
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Checks if the user account is active and enabled for login.
     * @return bool
     */
    public function isEnabled()
    {
        return ($this->status === 'active');
    }

    /**
     * Property getter with backward compatibility for legacy auth keys.
     * @param string $prop
     * @return mixed
     */
    public function get($prop)
    {
        if ($prop === 'UserId') return $this->id;
        if ($prop === 'UserName') return $this->username;
        if ($prop === 'rights') return $this->rights;
        return parent::get($prop);
    }

    /**
     * Hook called after entity data is loaded from the primary table.
     * Fetches multi-role assignments from the pivot table.
     */
    public function after_load()
    {
        $db = new SPPDB();
        $this->role_ids = [];
        
        // Pivot table resiliency during bootstrap
        if ($db->tableExists('userroles')) {
            $sql = 'SELECT roleid FROM ' . SPPDB::sppTable('userroles') . ' WHERE userid=?';
            $res = $db->execute_query($sql, [$this->id]);
            $this->role_ids = array_column($res, 'roleid');
        }
        
        // Populate 'rights' for BC
        $this->populateRights();
    }

    /**
     * Synchronizes rights from all assigned roles.
     */
    private function populateRights()
    {
        if (empty($this->role_ids)) {
            $this->rights = [];
            return;
        }

        $db = new SPPDB();
        if (!$db->tableExists('rights') || !$db->tableExists('roleright')) {
            $this->rights = [];
            return;
        }

        $placeholders = implode(',', array_fill(0, count($this->role_ids), '?'));
        $sql = "SELECT DISTINCT rt.name FROM " . SPPDB::sppTable('rights') . " rt 
                JOIN " . SPPDB::sppTable('roleright') . " rr ON rt.id = rr.rightid 
                WHERE rr.roleid IN ({$placeholders})";
        
        $res = $db->execute_query($sql, $this->role_ids);
        $this->rights = array_column($res, 'name');
    }

    /**
     * Hook called before database persistence.
     * Handles password hashing if a plaintext value is provided.
     */
    public function before_save()
    {
        if (isset($this->_values['password']) && !empty($this->_values['password'])) {
            $val = $this->_values['password'];
            // Hash only if it's not already a bcrypt hash
            if (strpos($val, '$2y$') !== 0) {
                $this->_values['password'] = password_hash($val, PASSWORD_DEFAULT);
            }
        }
        
        if (empty($this->id)) {
            $this->_values['created_at'] = date('Y-m-d H:i:s');
        }
        $this->_values['updated_at'] = date('Y-m-d H:i:s');
    }

    /**
     * Hook called after successful database persistence.
     * Synchronizes role assignments to the pivot table.
     */
    public function after_save()
    {
        if ($this->role_ids !== null) {
            $db = new SPPDB();
            if (!$db->tableExists('userroles')) {
                return;
            }
            
            // 1. Wipe current assignments
            $sql = 'DELETE FROM ' . SPPDB::sppTable('userroles') . ' WHERE userid=?';
            $db->execute_query($sql, [$this->id]);
            
            // 2. Re-insert new assignments
            foreach ($this->role_ids as $roleId) {
                $db->insertValues('userroles', ['userid' => $this->id, 'roleid' => $roleId]);
            }
        }
    }

    /**
     * Load user data by username.
     */
    public function loadByUsername($unm)
    {
        $db = new SPPDB();
        $sql = 'SELECT id FROM ' . SPPDB::sppTable('users') . ' WHERE username=?';
        $res = $db->execute_query($sql, [$unm]);
        if (count($res) > 0) {
            $this->load($res[0]['id']);
        } else {
            throw new UserNotFoundException("Username '{$unm}' not found.");
        }
    }

    /**
     * Set the assigned roles for the user.
     * @param array $roleIds
     */
    public function setRoles(array $roleIds)
    {
        $this->role_ids = array_map('intval', $roleIds);
    }

    /**
     * Returns assigned role IDs.
     */
    public function getRoles(): array
    {
        return $this->role_ids;
    }

    /**
     * Verify a plaintext password.
     */
    public function verifyPassword($passwd)
    {
        $hash = $this->password ?? $this->password_hash ?? '';
        if (empty($hash)) return false;

        if (strpos($hash, '$2y$') === 0) {
            return password_verify($passwd, $hash);
        }

        // Legacy plain-text fallback
        return $passwd === $hash;
    }

    /**
     * Checks if user has a specific right.
     */
    public function hasRight($rt)
    {
        return in_array($rt, $this->rights);
    }

    /**
     * Static helper for authentication.
     */
    public static function verifyUserPassword($uname, $passwd)
    {
        try {
            $user = new self($uname);
            return $user->verifyPassword($passwd);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Static helper for existence check.
     */
    public static function userExists($uname)
    {
        $db = new SPPDB();
        $res = $db->execute_query('SELECT id FROM ' . SPPDB::sppTable('users') . ' WHERE username=?', [$uname]);
        return count($res) > 0;
    }

    /**
     * Static helper for creation (for BC).
     */
    public static function createUser($uname, $passwd, $status = 'active')
    {
        $user = new self();
        $user->username = $uname;
        $user->password = $passwd;
        $user->status = $status;
        return $user->save();
    }
}

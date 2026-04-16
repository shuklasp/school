<?php
namespace SPPMod\SPPAuth;

use SPPMod\SPPEntity\SPPEntity;
use SPPMod\SPPDB\SPPDB;

/**
 * Class SPPRole
 * Manages user roles via SPPEntity.
 */
class SPPRole extends SPPEntity
{
    /**
     * Map to existing table name.
     */
    public function getTable()
    {
        return SPPDB::sppTable('roles');
    }

    /**
     * Determine if role has a specific right.
     */
    public function hasRight($rt)
    {
        $rtid = SPPRight::getRightId($rt);
        if ($rtid === -1) return false;

        $db = new SPPDB();
        $sql = 'SELECT count(*) as cnt FROM ' . SPPDB::sppTable('roleright') . ' WHERE roleid=? AND rightid=?';
        $res = $db->execute_query($sql, [$this->id, $rtid]);
        return (int)$res[0]['cnt'] > 0;
    }

    /**
     * Static helper for role ID lookup.
     */
    public static function getRoleId($rl)
    {
        $db = new SPPDB();
        $res = $db->execute_query('SELECT id FROM ' . SPPDB::sppTable('roles') . ' WHERE role_name=?', [$rl]);
        return count($res) > 0 ? $res[0]['id'] : -1;
    }

    /**
     * Synchronizes rights to this role (Pivot table: roleright).
     */
    public function setRights(array $rightIds)
    {
        $db = new SPPDB();
        $table = SPPDB::sppTable('roleright');
        
        // 1. Wipe current assignments
        $db->execute_query("DELETE FROM {$table} WHERE roleid=?", [$this->id]);
        
        // 2. Re-insert new assignments
        foreach ($rightIds as $rid) {
            $db->insertValues('roleright', ['roleid' => $this->id, 'rightid' => (int)$rid]);
        }
    }

    /**
     * Returns list of currently assigned right IDs.
     */
    public function getRights(): array
    {
        $db = new SPPDB();
        $sql = 'SELECT rightid FROM ' . SPPDB::sppTable('roleright') . ' WHERE roleid=?';
        $res = $db->execute_query($sql, [$this->id]);
        return array_column($res, 'rightid');
    }

    /**
     * Centralized orchestration to create or update a role.
     * Ensures identical logic for CLI and UI.
     */
    public static function saveRoleInfo(array $data)
    {
        $id = $data['id'] ?? null;
        $name = trim($data['role_name'] ?? $data['name'] ?? '');
        $desc = trim($data['description'] ?? '');
        $rightIds = $data['right_ids'] ?? $data['rights'] ?? [];

        if (empty($name) && !empty($id)) {
            $existingRole = new self($id);
            $name = $existingRole->role_name;
        }
        if (empty($name)) throw new \Exception("Role name is required.");

        $role = new self($id);
        $role->role_name = $name;
        $role->description = $desc;
        $role->save();

        if (is_array($rightIds)) {
            $role->setRights($rightIds);
        }

        return $role->id;
    }

    /**
     * Shorthand to assign a right to a role.
     */
    public static function assignRight(int $roleId, int $rightId)
    {
        $db = new \SPPMod\SPPDB\SPPDB();
        $table = \SPPMod\SPPDB\SPPDB::sppTable('roleright');
        $db->execute_query("DELETE FROM {$table} WHERE roleid=? AND rightid=?", [$roleId, $rightId]);
        $db->insertValues('roleright', ['roleid' => $roleId, 'rightid' => $rightId]);
    }

    /**
     * Shorthand to remove a right from a role.
     */
    public static function unassignRight(int $roleId, int $rightId)
    {
        $db = new \SPPMod\SPPDB\SPPDB();
        $db->execute_query("DELETE FROM " . \SPPMod\SPPDB\SPPDB::sppTable('roleright') . " WHERE roleid=? AND rightid=?", [$roleId, $rightId]);
    }

    /**
     * Polymorphic assignment of a role to any entity (e.g. Group, Student).
     */
    public static function assignToEntity(string $class, $id, int $roleId)
    {
        $db = new \SPPMod\SPPDB\SPPDB();
        $table = \SPPMod\SPPDB\SPPDB::sppTable('entity_roles');
        $db->execute_query("DELETE FROM {$table} WHERE target_class=? AND target_id=? AND role_id=?", [$class, $id, $roleId]);
        $db->insertValues('entity_roles', ['target_class' => $class, 'target_id' => $id, 'role_id' => $roleId]);
    }

    /**
     * Remove polymorphic role assignment.
     */
    public static function unassignFromEntity(string $class, $id, int $roleId)
    {
        $db = new \SPPMod\SPPDB\SPPDB();
        $db->execute_query("DELETE FROM " . \SPPMod\SPPDB\SPPDB::sppTable('entity_roles') . " WHERE target_class=? AND target_id=? AND role_id=?", [$class, $id, $roleId]);
    }
}

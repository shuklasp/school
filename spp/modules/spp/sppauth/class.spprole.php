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
}

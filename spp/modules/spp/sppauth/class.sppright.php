<?php
namespace SPPMod\SPPAuth;

use SPPMod\SPPEntity\SPPEntity;
use SPPMod\SPPDB\SPPDB;

/**
 * Class SPPRight
 * Manages system permissions/rights via SPPEntity.
 */
class SPPRight extends SPPEntity
{
    /**
     * Map to existing table name.
     */
    public function getTable()
    {
        return SPPDB::sppTable('rights');
    }

    /**
     * Static helper for existence check.
     */
    public static function rightExists($rt)
    {
        $db = new SPPDB();
        $res = $db->execute_query('SELECT id FROM ' . SPPDB::sppTable('rights') . ' WHERE name=?', [$rt]);
        return count($res) > 0;
    }

    /**
     * Static helper for right ID lookup.
     */
    public static function getRightId($rt)
    {
        $db = new SPPDB();
        $res = $db->execute_query('SELECT id FROM ' . SPPDB::sppTable('rights') . ' WHERE name=?', [$rt]);
        return count($res) > 0 ? $res[0]['id'] : -1;
    }
}

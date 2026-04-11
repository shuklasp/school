<?php

namespace SPPMod\SPPAuth;

/*require_once 'class.sppbase.php';
require_once 'class.sppdatabase.php';
require_once 'class.sppsequence.php';*/

/**
 * class SPPRight
 *
 * @author Satya Prakash Shukla
 */
class SPPRight extends \SPP\SPPObject
{

    /**
     * function rightExists()
     * Checks whether a right exists or not.
     * 
     * @param <type> $rt
     * @return <type>
     */
    public static function rightExists($rt)
    {
        $db = new \SPPMod\SPPDB\SPP_DB();
        $sql = 'select * from ' . \SPP\SPPBase::sppTable('rights') . ' where rightname=?';
        $values = array($rt);
        $result = $db->execute_query($sql, $values);
        if (sizeof($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * function createRight()
     * Creates a new right.
     * 
     * @param string $rt
     */
    public static function createRight($rt)
    {
        if (self::rightExists($rt)) {
            return false;
        }
        $db = new \SPPMod\SPPDB\SPP_DB();
        $sql = 'insert into ' . \SPP\SPPBase::sppTable('rights') . '(rightid,rightname) values(?,?)';
        $values = array(\SPPMod\SPPDB\SPP_Sequence::next('spprightid'), $rt);
        $db->execute_query($sql, $values);
        return true;
    }

    /**
     * static function dropRight()
     * Drops a right and permanently cascades deletion of all inherited role maps.
     *
     * @param string $rt
     */
    public static function dropRight($rt)
    {
        if (self::rightExists($rt)) {
            $rightid = self::getRightId($rt);
            $db = new \SPPMod\SPPDB\SPP_DB();

            // Wipe inherited relational role mapping first to prevent ghost permission vectors
            $rel_sql = 'delete from ' . \SPP\SPPBase::sppTable('roleright') . ' where rightid=?';
            $db->execute_query($rel_sql, array($rightid));

            // Clean the master right manifest
            $sql = 'delete from ' . \SPP\SPPBase::sppTable('rights') . ' where rightname=?';
            $values = array($rt);
            $db->execute_query($sql, $values);
            return true;
        }
        return false;
    }

    /**
     * static function getRightId()
     * Returns rightid of a right. -1 if right does not exist.
     *
     * @param string $rt
     * @return integer
     */
    public static function getRightId($rt)
    {
        $db = new \SPPMod\SPPDB\SPP_DB();
        $sql = 'select rightid from ' . \SPP\SPPBase::sppTable('rights') . ' where rightname=?';
        $values = array($rt);
        $result = $db->execute_query($sql, $values);
        if (sizeof($result) > 0) {
            return $result[0]['rightid'];
        } else {
            return -1;
        }
    }
}
?>
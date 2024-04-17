<?php
/*require_once 'class.sppbase.php';
require_once 'class.sppdatabase.php';
require_once 'class.sppsequence.php';*/

/**
 * class SPP_Right
 *
 * @author Satya Prakash Shukla
 */
class SPP_Right extends \SPP\SPP_Object {

    /**
     * function rightExists()
     * Checks whether a right exists or not.
     * 
     * @param <type> $rt
     * @return <type>
     */
    public static function rightExists($rt)
    {
        $db=new SPP_DB();
        $sql='select * from '.\SPP\SPP_Base::sppTable('rights').' where rightname=?';
        $values=array($rt);
        $result=$db->execute_query($sql, $values);
        if(sizeof($result)>0)
        {
            return true;
        }
        else
        {
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
        $db=new SPP_DB();
        $sql='insert into '.\SPP\SPP_Base::sppTable('rights').'(rightid,rightname) values(?,?)';
        $values=array(SPP_Sequence::next('spprightid'),$rt);
        $db->execute_query($sql, $values);
    }

    /**
     * function createRight()
     * Creates a new right.
     *
     * @param string $rt
     */
    public static function dropRight($rt)
    {
        $db=new SPP_DB();
        $sql='delete from '.\SPP\SPP_Base::sppTable('rights').' where rightname=?';
        $values=array($rt);
        $db->execute_query($sql, $values);
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
        $db=new SPP_DB();
        $sql='select rightid from '.\SPP\SPP_Base::sppTable('rights').' where rightname=?';
        $values=array($rt);
        $result=$db->execute_query($sql, $values);
        if(sizeof($result)>0)
        {
            return $result[0]['rightid'];
        }
        else
        {
            return -1;
        }
    }
}
?>
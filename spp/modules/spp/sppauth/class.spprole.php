<?php
/*require_once 'class.sppbase.php';
require_once 'class.sppdatabase.php';
require_once 'class.sppsequence.php';
require_once 'sppsystemexceptions.php';
require_once 'class.sppright.php';*/

/**
 * class SPP_Role
 * Manages roles in the system.
 *
 * @author Satya Prakash Shukla
 */
class SPP_Role extends \SPP\SPP_Object {
    private $rolename, $roleid;
    
    public function  __construct($rlnm) {
        $db=new SPP_DB();
        $sql='select * from '. \SPP\SPP_Base::sppTable('roles').' where rolename=?';
        $values=array($rlnm);
        $result=$db->execute_query($sql, $values);
        if(sizeof($result)>0)
        {
            $this->roleid=$result[0]['roleid'];
            $this->rolename=$result[0]['rolename'];
        }
        else
        {
            throw new UnknownRoleException('Unknown role '.$rlnm);
        }
    }

    /**
     * static function createRole()
     * Creates a role.
     * 
     * @param string $rlnm
     */
    public static function createRole($rlnm)
    {
        $db=new SPP_DB();
        $sql='insert into '. \SPP\SPP_Base::sppTable('roles').' values (?,?)';
        $values=array(SPP_Sequence::next('spproleid'),$rlnm);
        $db->execute_query($sql, $values);
    }

    /**
     * function hasRole()
     * Determines whether the role has a particuar right or not.
     *
     * @param string $rt
     * @return bool
     */
    public function hasRight($rt)
    {
        $db=new SPP_DB();
        $rtid=SPP_Right::getRightId($rt);
        $sql='select * from '.\SPP\SPP_Base::sppTable('roleright').' where roleid=? and rightid=?';
        $values=array($this->roleid,$rtid);
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
     * function assignRight()
     * Assigns a right to the role.
     * 
     * @param string $rt
     * @return bool
     */
    public function assignRight($rt)
    {
        $db=new SPP_DB();
        if($this->hasRight($rt))
        {
            return false;
        }
        else
        {
            $sql='insert into '.\SPP\SPP_Base::sppTable('roleright').' values(?,?)';
            $values=array($this->roleid,SPP_Right::getRightId($rt));
            $db->execute_query($sql, $values);
            return true;
        }
    }

    /**
     * static function getRoleId()
     * Returns roleid of a role. -1 if right does not exist.
     *
     * @param string $rl
     * @return integer
     */
    public static function getRoleId($rl)
    {
        $db=new SPP_DB();
        $sql='select roleid from '.\SPP\SPP_Base::sppTable('roles').' where rolename=?';
        $values=array($rl);
        $result=$db->execute_query($sql, $values);
        if(sizeof($result)>0)
        {
            return $result[0]['roleid'];
        }
        else
        {
            return -1;
        }
    }
}
?>
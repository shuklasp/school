<?php
namespace SPPMod\SPPAuth;
use SPP\Exceptions\UnknownRoleException;
/*require_once 'class.sppbase.php';
require_once 'class.sppdatabase.php';
require_once 'class.sppsequence.php';
require_once 'sppsystemexceptions.php';
require_once 'class.sppright.php';*/

/**
 * class SPPRole
 * Manages roles in the system.
 *
 * @author Satya Prakash Shukla
 */
class SPPRole extends \SPP\SPPObject {
    private $rolename, $roleid;
    
    public function  __construct($rlnm) {
        $db=new \SPPMod\SPPDB\SPP_DB();
        $sql='select * from '. \SPP\SPPBase::sppTable('roles').' where rolename=?';
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
        $db=new \SPPMod\SPPDB\SPP_DB();
        $sql='insert into '. \SPP\SPPBase::sppTable('roles').' values (?,?)';
        $values=array(\SPPMod\SPPDB\SPP_Sequence::next('spproleid'),$rlnm);
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
        $db=new \SPPMod\SPPDB\SPP_DB();
        $rtid=SPPRight::getRightId($rt);
        $sql='select * from '.\SPP\SPPBase::sppTable('roleright').' where roleid=? and rightid=?';
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
        $db=new \SPPMod\SPPDB\SPP_DB();
        if($this->hasRight($rt))
        {
            return false;
        }
        else
        {
            $sql='insert into '.\SPP\SPPBase::sppTable('roleright').' values(?,?)';
            $values=array($this->roleid,SPPRight::getRightId($rt));
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
        $db=new \SPPMod\SPPDB\SPP_DB();
        $sql='select roleid from '.\SPP\SPPBase::sppTable('roles').' where rolename=?';
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
<?php
/*require_once 'class.spprole.php';
require_once 'class.sppbase.php';*/
/**
 * Description of User
 *
 * @author Administrator
 */
class SPP_User extends \SPP\SPP_Object {
    private $uname,$uid,$enabled,$rights;

    /**
     * Constructor
     *
     * @param string $unm
     */
	public function __construct($unm)
	{
		$db=new SPP_DB();
		$res=array();
		$qry='select uid, enabled from '. \SPP\SPP_Base::sppTable('users').' where uname=?';
 		$res=$db->execute_query($qry,array($unm));
		if(sizeof($res)>0)
		{
        	$this->uid=$res[0]['uid'];
            $this->enabled=$res[0]['enabled'];
			$this->uname=$unm;
			$qry='select rightname from '. \SPP\SPP_Base::sppTable('rights').' rt, '. \SPP\SPP_Base::sppTable('roleright').' rr, '. \SPP\SPP_Base::sppTable('userroles').' ur where ur.uid=? and ur.roleid=rr.roleid and rt.rightid=rr.rightid';
			$res=$db->execute_query($qry,array($this->uid));
			foreach($res as $row)
			{
				$this->rights[]=$row['rightname'];
			}
		}
		else
		{
            throw new UserNotFoundException('User '.$unm.' not found.');
		}
	}

    /**
     * function get()
     * gets various properties of user.
     * Properties are:
     *          UserName
     *          UserId
     *          Enabled
     * 
     * @param <type> $propname
     * @return <type>
     */
    public function get($propname)
    {
        switch($propname)
        {
            case 'UserName':
                return $this->uname;
                break;
            case 'UserId':
                return $this->uid;
                break;
            case 'Enabled':
                return $this->enabled;
            default:
                throw new UnknownPropertyException('Unknown property '.$propname.' accessed in User.');
                break;
        }
    }

    /**
     * function isEnabled()
     * Returns true if user is enabled. Else returns false.
     * 
     * @return <type>
     */
    public function isEnabled()
    {
        if($this->enabled=='Y')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * function verifyPassword()
     * Verifies password of current user.
     * 
     * @param string $passwd
     * @return bool
     */
    public function verifyPassword($passwd)
    {
   		$db=new SPP_DB();
		$res=array();
		$qry='select uid, aes_decrypt(passwd,?) pswd, enabled from '. \SPP\SPP_Base::sppTable('users').' where uname=?';
 		$res=$db->execute_query($qry,array($passwd,$this->uname));
		if(sizeof($res)>0)
		{
			if($res[0]['pswd']==$passwd)
			{
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }

    }

    /**
     * static function userExists()
     * Returns true if user exists. Else false.
     * 
     * @param string $uname
     * @return bool
     */
    public static function userExists($uname)
    {
   		$db=new SPP_DB();
		$res=array();
		$qry='select uid, enabled from '.\SPP\SPP_Base::sppTable('users').' where uname=?';
 		$res=$db->execute_query($qry,array($uname));
		if(sizeof($res)>0)
		{
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * static function verifyUserPassowrd()
     * Verified password for a supplied user name.
     * 
     * @param string $uname
     * @param string $passwd
     * @return bool
     */
    public static function verifyUserPassword($uname,$passwd)
    {
   		$db=new SPP_DB();
		$res=array();
		$qry='select uid, aes_decrypt(passwd,?) pswd, enabled from '. \SPP\SPP_Base::sppTable('users').' where uname=?';
 		$res=$db->execute_query($qry,array($passwd,$uname));
		if(sizeof($res)>0)
		{
			if($res[0]['pswd']==$passwd)
			{
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }

    }

    /**
     * function hasRight()
     * Returns true if user has a particular right. Else returns false.
     * @param string $rt
     * @return bool
     */
    public function hasRight($rt)
	{
		$flag=false;
                foreach($this->rights as $rght)
		{
			if($rght==$rt)
			{
				$flag=true;
				break;
			}
		}
		return $flag;
	}

    /**
     * static function createUser()
     * Creates a user and sets password for it.
     * 
     * @param string $uname
     * @param string $passwd
     * @return bool
     */
    public static function createUser($uname,$passwd)
    {
        $db=new SPP_DB();
        if(self::userExists($uname))
        {
            return false;
        }
        else
        {
            $sql='insert into '. \SPP\SPP_Base::sppTable('users').'(uid,uname,passwd,enabled) values(?,?,aes_encrypt(?,?),?)';
            $values=array(SPP_Sequence::next('sppuid'),$uname,$passwd,$passwd,'Y');
            $db->execute_query($sql, $values);
            return true;
        }
    }

    /**
     * static function dropUser()
     * Drops a user from system.
     * 
     * @param string $uname
     * @return bool
     */
    public static function dropUser($uname)
    {
        $db=new SPP_DB();
        if(self::userExists($uname)==false)
        {
            return false;
        }
        else
        {
            $sql='delete from '. \SPP\SPP_Base::sppTable('users').' where uname=?';
            $values=array($uname);
            $db->execute_query($sql, $values);
            return true;
        }
    }

    /**
     * function setPassword()
     * Sets password for the user.
     * 
     * @param <type> $passwd
     */
    public function setPassword($passwd)
    {
        $db=new SPP_DB();
        $sql='update '. \SPP\SPP_Base::sppTable('users').' set passwd=aes_encrypt(?,?) where uid=?';
        $values=array($passwd,$passwd,$this->uid);
        $db->execute_query($sql, $values);
    }


    /**
     * function enable()
     * Enables the user.
     */
    public function enable()
    {
        $db=new SPP_DB();
        $sql='update '. \SPP\SPP_Base::sppTable('users').' set enabled=? where uid=?';
        $values=array('Y',$this->uid);
        $db->execute_query($sql, $values);
    }


    /**
     * function disable()
     * Disables the user.
     */
    public function disable()
    {
        $db=new SPP_DB();
        $sql='update '. \SPP\SPP_Base::sppTable('users').' set enabled=? where uid=?';
        $values=array('N',$this->uid);
        $db->execute_query($sql, $values);
    }

    /**
     * function hasRole()
     * Finds whether user has a particular role or not.
     * 
     * @param string $rolename
     * @return bool
     */
    public function hasRole($rolename)
    {
        $db=new SPP_DB();
        $sql='select * from '. \SPP\SPP_Base::sppTable('userroles').' where uid=? and roleid=?';
        $values=array($this->uid,SPP_Role::getRoleId($rolename));
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
     * function assignRole()
     * Assigns a particular role to the user.
     * 
     * @param <type> $rolename
     * @return <type>
     */
    public function assignRole($rolename)
    {
        $db=new SPP_DB();
        if($this->hasRole($rolename))
        {
            return false;
        }
        else
        {
            $sql='insert into '. \SPP\SPP_Base::sppTable('userroles').' values(?,?)';
            $values=array($this->uid,SPP_Role::getRoleId($rolename));
            $db->execute_query($sql, $values);
            return true;
        }
    }


    /**
     * function removeRole()
     * Removes a particular role from the user.
     *
     * @param <type> $rolename
     * @return <type>
     */
    public function removeRole($rolename)
    {
        $db=new SPP_DB();
        if($this->hasRole($rolename))
        {
            $sql='delete from '. \SPP\SPP_Base::sppTable('userroles').' where uid=? and roleid=?';
            $values=array($this->uid,SPP_Role::getRoleId($rolename));
            $db->execute_query($sql, $values);
            return true;
        }
        else
        {
            return false;
        }
    }

}
?>
<?php
require_once('class.sppuser.php');
/*require_once 'class.sppsession.php';
require_once 'class.sppbase.php';*/
/**
 * class SPP_User_Session
 * Manages and stores all the session variable for an authenticated user.
 *
 * @author Satya Parakash Shukla
 */
class SPP_User_Session extends SPP_Session
{
    private $user, $sessid;

    /**
     * Construct
     *
     * @param string $unm
     * @param string $pswd
     */
	public function __construct($unm,$pswd)
	{
        $db=new SPP_DB();
        $this->user=new SPP_User($unm);
        if($this->user->verifyPassword($pswd))
        {
            $db=new SPP_DB();
            $sql='select * from '.SPP_Base::sppTable('users').' where uname=?';
            $values=array($unm);
            $result=array();
            $result=$db->execute_query($sql, $values);
            if($result[0]['enabled']!='Y')
            {
                throw new UserBannedException('User '.$unm.' is banned from login.');
            }
            //session_regenerate_id();
            $this->sessid=session_id();
            $sql='select now() nowtime from '.SPP_Base::sppTable('users');
            $result=$db->execute_query($sql);
            $nowtime=$result[0]['nowtime'];
            //echo $nowtime;
            //$nowtime=date('Y-m-d G:i:s',time());
            $sql='insert into '.SPP_Base::sppTable('loginrec').'(sessid,uid,logintime,ipaddr,lastaccess) values(?,?,?,?,?)';
            $values=array($this->sessid,$this->user->get('UserId'),$nowtime,getVisitorIP(),$nowtime);
            $db->execute_query($sql, $values);
            //echo $sql;
            //print_r($values);
            //echo 'Value inserted';
        }
        else
        {
            throw new UserAuthenticationException('User name and passwords do not match for user '.$unm);
        }
	}



    /**
     * function isValid()
     * Determines whether session is valid or not.
     *
     * @return bool
     */
    public function isValid($consider_timeout=true)
    {
        $db=new SPP_DB();
        $sql='select time_to_sec(timediff(now(),lastaccess)) elapsed_time, lastaccess, now() currtime from '.SPP_Base::sppTable('loginrec').' where sessid=?';
        $values=array($this->sessid);
        $result=$db->execute_query($sql, $values);
        //echo $result[0]['lastaccess'].'::::::'.$result[0]['currtime'];
        if(sizeof($result)>0)
        {
            //echo 'fetched result';
            if($consider_timeout)
            {
                //echo 'considering timeout';
                if($result[0]['elapsed_time']<=(SPP_Config::get('user_session_timeout')*60))
                {
                    $sql='update loginrec set lastaccess=? where sessid=?';
                    $values=array($result[0]['currtime'],$this->sessid);
                    $db->execute_query($sql, $values);
                    return true;
                }
                else
                {
                    $this->kill();
                    return false;
                }
            }
            else
            {
                //echo 'not considering timeout';
                    $sql='update loginrec set lastaccess=? where sessid=?';
                    $values=array($result[0]['currtime'],$this->sessid);
                    $db->execute_query($sql, $values);
                    return true;
            }
        }
        else
        {
            return false;
        }
        //return true;
    }

    /**
     * function kill()
     * Kills the user session
     */
    public function kill()
    {
        $db=new SPP_DB();
        $sql='delete from '.SPP_Base::sppTable('loginrec').' where sessid=?';
        $values=array($this->sessid);
        $result=$db->execute_query($sql, $values);
    }

    /**
     * function hasRight()
     * Checks whether session has particular right or not.
     * 
     * @param <type> $rt
     * @return <type>
     */
    public function hasRight($rt)
	{
        if($this->isValid())
        {
            return $this->user->hasRight($rt);
        }
        else
        {
            throw new InvalidUserSessionException('Invalid user session');
        }
	}

    /**
     * function get()
     * Gets properties of session.
     * Valid properties are:
     *          UserName
     *          UserId
     * 
     * @param string $propname
     * @return mixed
     */
    public function get($propname)
    {
        if($this->isValid())
        {
            switch($propname)
            {
                case 'UserName':
                    return $this->user->get('UserName');
                    break;
                case 'UserId':
                    return $this->user->get('UserId');
                    break;
                default:
                    throw new UnknownPropertyException('Unknown property '.$propname.' accessed in UserSession.');
                    break;
            }
        }
        else
        {
            throw new InvalidUserSessionException('Invalid user session');
        }
    }

}
?>
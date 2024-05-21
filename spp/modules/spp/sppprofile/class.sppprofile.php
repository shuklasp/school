<?php
namespace SPPMod\SPPProfile;
use SPP\Exceptions\ProfileDoesNotExistException;
use SPP\Exceptions\NoProfileSelectedException;
use SPP\Exceptions\UnknownProfileFieldException;
use SPP\Exceptions\NotAssociativeArrayException;
use SPP\Exceptions\ProfileAlreadyExistsException;
use SPP\Exceptions\NotIntegerException;
/*require_once 'class.sppbase.php';
require_once 'sppsystemexceptions.php';
require_once 'class.sppdatabase.php';
require_once 'class.sppsequence.php';*/
/**
 * class \SPPMod\SPProfile\SPPProfile
 * defines the profile of users.
 *
 * @author Satya Prakash Shukla
 */
class SPPProfile extends \SPP\SPPObject{
    protected $profname,$proftabname,$selectedrow,$profid,$profseqname,$selectedprof,$valarray=null, $idfield;

    /**
     * Constructor
     * @param string $pname
     */
    public function  __construct($pname) {
        $db=new \SPPMod\SPPDB\SPP_DB();
        $sql='select * from '.\SPP\SPPBase::sppTable('profiletabs').' where profname=?';
        $values=array($pname);
        $result=$db->execute_query($sql, $values);
        if(sizeof($result)>0)
        {
            $this->profname=$result[0]['profname'];
            $this->proftabname=$result[0]['proftabname'];
            $this->profseqname=$result[0]['profseqname'];
            $this->idfield=$result[0]['idfield'];
            $this->selectedrow=null;
        }
        else
        {
            throw new ProfileDoesNotExistException('Profile '.$pname.' does not exist!');
        }
    }

    /**
     * function getFields()
     * Returns all fields of the profile
     * 
     * @return fields-list 
     */
    function getFields()
    {
        $db=new \SPPMod\SPPDB\SPP_DB();
        if($this->proftabname==null||$this->proftabname=='')
                throw(new \SPP\SPPException ('No profile tab!'));
        $sql='show columns from '.\SPP\SPPBase::sppTable($this->proftabname);
        $result=$db->execute_query($sql);
        if(sizeof($result)>0)
        {
            return $result;
        }
        else
        {
            return null;
        }
    }

    /**
     * function seekValue()
     * Not complete yet
     * 
     * @param <type> $fld
     * @param <type> $val
     * @return <type>
     */
    function seekValue($fld,$val)
    {
        $db=new \SPPMod\SPPDB\SPP_DB();
        if($this->proftabname==null||$this->proftabname=='')
                throw(new \SPP\SPPException ('No profile tab!'));
        $sql='select * from '.\SPP\SPPBase::sppTable($this->proftabname).' where '.$fld.'=?';
        $values=array($val);
        $result=$db->execute_query($sql, $values);
        if(sizeof($result)>0)
        {
            $this->valarray=$result;
            //return $result[0]['profname'];
            $this->selectedrow=$result[0];
            $this->profid=$result[0][$this->idfield];
            return true;
        }
        else
        {
            return false;
        }
    }

    function seekIntoValue($fld,$val)
    {
        if($this->valarray!=null)
        {
            foreach($this->valarray as $res)
            {
                if($res[$fld]==$val)
                {
                    $this->selectedrow=$res;
                    $this->profid=$res[$this->idfield];
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * function getAll()
     * Returns all the records of a profile.
     * 
     * @return array
     */
    public function getAll() {
        $db=new \SPPMod\SPPDB\SPP_DB();
        $sql=$sql='select * from '.\SPP\SPPBase::sppTable($this->proftabname);
        $result=$db->execute_query($sql);
        return $result;
    }

    /**
     * function seekProfile()
     * Seeks the profile corresponding to supplied profileid
     *
     * @param string $pid
     * @return bool
     */
    public function seekProfile($pid)
    {
        $db=new \SPPMod\SPPDB\SPP_DB();
        $sql='select * from '.\SPP\SPPBase::sppTable($this->proftabname).' where '.$this->idfield.'=?';
        $values=array($pid);
        $result=$db->execute_query($sql, $values);
        if(sizeof($result)>0)
        {
            $this->selectedrow=$result[0];
            $this->profid=$pid;
            return true;
        }
        else
        {
            $this->selectedrow=null;
            $this->profid=null;
            return false;
        }
    }

    /**
     * function get()
     * Gets the value of a particular field from selected row.
     *
     * @param string $fld
     * @return mixed
     */
    public function get($fld)
    {
        if($this->selectedrow==null)
        {
            throw new NoProfileSelectedException('No profile selected!');
        }
        else
        {
            if(array_key_exists($fld, $this->selectedrow))
            {
                return $this->selectedrow[$fld];
            }
            else
            {
                throw new UnknownProfileFieldException('No Profile field '.$fld.' found in profile.');
            }
        }
    }

    /**
     * function set()
     * Sets the value of a particular field from selected row.
     *
     * @param string $fld
     * @param string $val
     * @return string
     */
    public function set($fld,$val)
    {
        if(is_null($this->selectedrow))
        {
            throw new NoProfileSelectedException('No profile selected!');
        }
        else
        {
            if(array_key_exists($fld, $this->selectedrow))
            {
                return $this->selectedrow[$fld]=$val;
            }
            else
            {
                throw new UnknownProfileFieldException('No Profile field '.$fld.' found in profile.');
            }
        }
    }

    /**
     * function setMultiple()
     * Sets the value of a particular field from selected row.
     *
     * @param string $fld
     * @param string $val
     * @return string
     */
    public function setMultiple($flds)
    {
        if(is_null($this->selectedrow))
        {
            throw new NoProfileSelectedException('No profile selected!');
        }
        else
        {
            foreach($flds as $fld=>$val)
            {
                if(!is_int($fld))
                {
                    if(array_key_exists($fld, $this->selectedrow))
                    {
                        $this->selectedrow[$fld]=$val;
                    }
                    else
                    {
                        throw new \SPP\SPPException('Invalid field '.$fld);
                    }
                }
                else
                {
                    throw new NotAssociativeArrayException('Supplied array should be associative!');
                }
            }
        }
    }


    /**
     * function update()
     * Updates the present row.
     *
     * @return bool
     */
    public function update()
    {
        $db=new \SPPMod\SPPDB\SPP_DB();
    //$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        if(is_null($this->selectedrow))
        {
            return false;
        }
        else
        {
            $sql='update '.\SPP\SPPBase::sppTable($this->proftabname).' set ';
            $values=array();
            $i=0;
            foreach($this->selectedrow as $fld=>$val)
            {
                $sql.=$fld.'=?, ';
                $values[$i++]=$val;
            }
            $sql.=$this->idfield.'=? ';
            $values[$i++]=$this->profid;
            $sql.='where '.$this->idfield.'=?';
            $values[$i++]=$this->profid;
            //echo $sql;
            //var_dump($values);
            $result=$db->execute_query($sql, $values);
            return true;
        }
    }

    /**
     * function appendNew()
     * Appends a new record to the profile.
     *
     * @return integer
     */
    public function appendNew()
    {
        $db=new \SPPMod\SPPDB\SPP_DB();
        $nprofid=\SPPMod\SPPDB\SPP_Sequence::next($this->profseqname);
        $sql='insert into '.\SPP\SPPBase::sppTable($this->proftabname).'('.$this->idfield.') values(?)';
        $values=array($nprofid);
        $result=$db->execute_query($sql, $values);
        $this->seekProfile($nprofid);
        return $nprofid;
    }


    public function appendSave($flds)
    {
        $db=new \SPPMod\SPPDB\SPP_DB();
    //$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $db->beginTransaction();
        try{
        $nprofid=\SPPMod\SPPDB\SPP_Sequence::next($this->profseqname);
        $sql='insert into '.\SPP\SPPBase::sppTable($this->proftabname).' set ';
        $values=array();
        $i=0;
        foreach($flds as $fld=>$val)
        {
            if(!is_int($fld))
            {
                $sql.=$fld.'=?, ';
                $values[$i++]=$val;
            }
            else
            {
                throw new NotAssociativeArrayException('Supplied array should be associative!');
            }
        }
        $sql.=$this->idfield.'=? ';
        $values[$i++]=$nprofid;
        //var_dump($sql);
        //var_dump($values);
        $result=$db->execute_query($sql, $values);
        $db->commit();
        }
        catch(\Exception $ex)
        {
            $db->rollBack();
        }
        return $nprofid;
    }

    
    /**
     * function deleteMe()
     * Deletes a profile record
     *
     * @return bool
     */
    public function deleteMe()
    {
        $db=new \SPPMod\SPPDB\SPP_DB();
        if(is_null($this->selectedrow))
        {
            return false;
        }
        else
        {
            $sql='delete from '.\SPP\SPPBase::sppTable($this->proftabname).' where '.$this->idfield.'=?';
            $values=array($this->profid);
            $result=$db->execute_query($sql, $values);
            $this->selectedrow='';
            return true;
        }
    }

    /**
     * static function doesProfileExist()
     * Returns true if profile exists.
     *
     * @param string $pname
     * @return string
     */
    public static function doesProfileExist($pname)
    {
        $db=new \SPPMod\SPPDB\SPP_DB();
        $sql='select * from '.\SPP\SPPBase::sppTable('profiletabs').' where profname=?';
        $values=array($pname);
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
     * function dropProfile()
     * Drops an existing profile.
     *
     * @param string $pname
     * @return bool
     */
    public static function dropProfile($pname)
    {
        $db=new \SPPMod\SPPDB\SPP_DB();
        if(self::doesProfileExist($pname))
        {
            $sql='select * from '.\SPP\SPPBase::sppTable('profiletabs').' where profname=?';
            $values=array($pname);
            $result=$db->execute_query($sql, $values);
            $tabname=$result[0]['proftabname'];
            $seqname=$result[0]['profseqname'];
            //echo $tabname;
            $sql='drop table '.\SPP\SPPBase::sppTable($tabname);
            $result=$db->execute_query($sql);
            $sql='delete from '.\SPP\SPPBase::sppTable('profiletabs').' where profname=?';
            $result=$db->execute_query($sql, $values);
            \SPPMod\SPPDB\SPP_Sequence::dropSequence($seqname);
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * static function createProfile()
     * Creates a new profile.
     *
     * @param string $pname
     * @param array $flds
     */
    public static function createProfile($pname,$flds=array())
    {
        $db=new \SPPMod\SPPDB\SPP_DB();
        if(self::doesProfileExist($pname))
        {
            throw new ProfileAlreadyExistsException('Profile with this name already exists!');
        }
        else
        {
            $sql='create table '.\SPP\SPPBase::sppTable('prof_'.$pname).' (profid  bigint primary key';
            foreach($flds as $fld=>$fldsz)
            {
                if(!is_int($fld))
                {
                    if(!is_int($fldsz))
                    {
                        throw new NotIntegerException('Field sizes should be integers');
                    }
                    else
                    {
                        $sql.=', '.$fld.' varchar('.$fldsz.')';
                    }
                }
                else
                {
                    throw new NotAssociativeArrayException('Supplied array should be associative!');
                }
            }
            $sql.=')';
            \SPPMod\SPPDB\SPP_Sequence::createSequence('prof_'.$pname.'_seq', 1, 1);
            $result=$db->execute_query($sql);
            $sql='insert into '.\SPP\SPPBase::sppTable('profiletabs').' values(?,?,?,?)';
            $values=array($pname,'prof_'.$pname,'prof_'.$pname.'_seq','profid');
            $result=$db->execute_query($sql, $values);
        }
    }
}
?>
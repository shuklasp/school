<?php
/*require_once('class.sppdatabase.php');
require_once 'class.sppbase.php';
require_once('sppfuncs.php');
require_once 'sppsystemexceptions.php';*/

/**
 * class SPP_Sequence
 *
 * Handles all the sequences in the system.
 */
class SPP_Sequence extends \SPP\SPP_Object
{
    /**
     * function next()
     * Gets the next value of sequence.
     * 
     * @param string $seqname
     * @param bool $fortoday
     * @return integer
     */
	public static function next($seqname,$fortoday=false)
	{
		$db=new SPP_DB();
		$sql='select * from '.\SPP\SPP_Base::sppTable('sequences').' where seqname=?';
		$result=$db->execute_query($sql,Array($seqname));
		if(sizeof($result)>0)
		{
			$res=$result[0];
			$seq=0;
			if($fortoday)
			{
				$today=time();
				if(tsToD($today)==tsToD($res['lastaccess']))
				{
					$seq=$res['seqval'];
				}
				else
				{
					$seq=$res['initval'];
				}
			}
			else
			{
				$seq=$res['seqval'];
			}
            if($seq<$res['initval'])
            {
                $seq=$res['initval'];
            }
            else
            {
                $seq+=$res['incval'];
            }
			$acc=time();
			$sql='update '.\SPP\SPP_Base::sppTable('sequences').' set seqval=?, lastaccess=? where seqname=?';
			$db->execute_query($sql,Array($seq,$acc,$seqname));
			return $seq;
		}
		else
		{
			throw new SequenceDoesNotExistException('Sequence '.$seqname.' does not exist!');
		}
	}

    /**
     * function sequenceExists()
     * Finds wether a sequence exists or not
     * @param string $seqname
     * @return bool
     */
    public static function sequenceExists($seqname)
    {
        $db=new SPP_DB();
        $sql='select * from '.\SPP\SPP_Base::sppTable('sequences').' where seqname=?';
        $values=array($seqname);
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
     * function createSequence()
     * Creates a new sequence.
     * 
     * @param <type> $seqname
     * @param <type> $initval
     * @param <type> $incval
     */
    public static function createSequence($seqname, $initval, $incval)
    {
        $db= new SPP_DB();
        if(!self::sequenceExists($seqname))
        {
            $sql='insert into '.\SPP\SPP_Base::sppTable('sequences').' values(?,?,?,?,?)';
            $values=array($seqname,$initval,0,$incval,0);
            $db->execute_query($sql, $values);
        }
        else
        {
            throw new SequenceExistsException('Sequence '.$seqname.' already exists');
        }
    }

    /**
     * function dropSequence()
     * Drops a particular sequence.
     * 
     * @param string $seqname
     * @return bool
     */
    public static function dropSequence($seqname)
    {
        $db=new SPP_DB();
        if(self::sequenceExists($seqname))
        {
            $sql='delete from '.\SPP\SPP_Base::sppTable('sequences').' where seqname=?';
            $values=array($seqname);
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
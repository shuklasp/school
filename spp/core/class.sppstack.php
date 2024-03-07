<?php
//require_once 'class.sppobject.php';
/**
 * Class Stack
 * Handles stacks in system.
 *
 * @author Satya Prakash Shukla
 */
class SPP_Stack extends SPP_Object{
    private $values;
    private $curkey;
    
    public function  __construct() {
        $this->curkey=-1;
    }

    public function push($val)
    {
        $this->values[++$this->curkey]=$val;
    }

    public function pop()
    {
        if($this->curkey<0)
        {
            return false;
        }
        else
        {
            $val=$this->values[$this->curkey];
            unset($this->values[$this->curkey]);
            $this->curkey--;
            return $val;
        }
    }

    public function getTop()
    {
        if($this->curkey<0)
        {
            return false;
        }
        else
        {
            return $this->values[$this->curkey];
        }
    }

    public function __toString()
    {
        $var=implode('|', $this->values);
        return $var;
    }
}
?>
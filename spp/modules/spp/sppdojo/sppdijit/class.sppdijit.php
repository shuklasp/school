<?php
/**
 * class SPP_Dijit
 * Handles dojo widgets (digits) in SPP
 *
 * @author Satya Prakash Shukla
 */
abstract class SPP_Dijit {
    protected $digitprops=array();
    protected $ename;
    protected $id;
    protected $dtype;
    protected $finalized=false;
    protected $proplist=array();

    public function __construct($eid,$dtype)
    {
        //parent::__construct($ename);
        $this->ename=$eid;
        $this->id=$eid;
        $this->dtype=$dtype;
        SPP_Dojo::setConfig('parseOnLoad', 'true');
        SPP_Dojo::addRequire($dtype);
        SPP_Dojo::registerDijit($this);
    }

    public function getId()
    {
        return $this->id;
    }

    /*public function setDojoType($dtype)
    {
        $this->dtype=$dtype;
    }*/

    public function getHTML()
    {
        if($this->finalized===false)
        {
            throw new SPP_Exception('Digit not finalized. Cannot get digit source.');
        }
        //parent::getHTML();
    }

    protected function applyTheme($html)
    {
        $htm='<div class="'.SPP_Dojo::get('Theme').'">'.$html.'</div>';
        return $htm;
    }

    public function  __toString() {
        return $this->getHTML();
    }

    protected function printableval($val)
    {
        if(is_string($val))
        {
            if($val=='true'||$val=='false')
            {
                return $val;
            }
            else
            {
                return '"'.$val.'"';
            }
        }
        else
        {
            return $val;
        }
    }

    public function finalize()
    {
        if($this->finalized===false)
        {
            $flag=0;
            $str='var digit_'.$this->ename.'=new '.$this->dtype.'(
                {
                ';
            foreach($this->digitprops as $prop=>$val)
            {
                if($flag==0)
                {
                    $str.=$prop.' : '.$this->printableval($val);
                    $flag=1;
                }
                else
                {
                    $str.=',
                            '.$prop.' : '.$this->printableval($val);
                }
            }
            $str.='
                    }, "'.$this->ename.'");';
            SPP_Dojo::addOnLoad($str);
            $this->finalized=true;
        }
    }

    public function setDigitProperty($prop, $val)
    {
        if($this->finalized===false)
        {
            /*if(in_array($prop, $this->proplist))
            {*/
                $this->digitprops[$prop]=$val;
            /*}
            else
            {
                throw new SPP_Exception('Invalid porperty set for dijit '.$this->id.' : '.$prop);
            }*/
        }
        else
        {
            throw new SPP_Exception('Digit '.$this->id.' already finalized. Cannot set property.');
        }
    }

    public abstract function validate();
}
?>
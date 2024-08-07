<?php

namespace SPPMod\SPPView;

/**
 * class SPP_Single_validator
 *
 * @author Satya Prakash Shukla
 */
// require_once 'class.sppvalidator.php';

abstract class SPPMultipleValidator extends SPPValidator {
    protected $elements=array();

    public function __construct(array $elems)
    {
        parent::__construct();
        $this->elements=$elems;
    }

    public function getJsFunction()
    {
        $jsarr='[';
        foreach($this->elements as $elem)
        {
            if(strlen($jsarr)>1)
            {
                $jsarr.=',';
            }
            $jsarr.='\''.$elem->getAttribute('id').'\'';
        }
        $jsarr.=']';
//        $fn=$this->jsfunc.'('.$this->errorholder.','.$this->msg.','.$this->tagid.')';
        $fn=$this->jsfunc.'(\''.$this->errorholder.'\',\''.$this->msg.'\','.$jsarr.')';
        return $fn;
    }
}
?>
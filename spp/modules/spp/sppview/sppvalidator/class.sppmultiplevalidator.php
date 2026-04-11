<?php

namespace SPPMod\SPPView;

/**
 * class SPP_Single_validator
 *
 * @author Satya Prakash Shukla
 */
// require_once 'class.sppvalidator.php';

abstract class SPP_Multiple_Validator extends ViewValidator {
    protected $elements=array();

    public function __construct(array $elems, $errorholder = 'nameerror', $msg = 'Validation error', $jsfunc = 'undefined')
    {
        parent::__construct(null, $errorholder, $msg, $jsfunc);
        $this->elements=$elems;
    }

    public function getJsFunction(): string
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
        $fn=$this->jsfunc.'(\''.$this->errorholder.'\',\''.$this->msg.'\','.$jsarr.')';
        return $fn;
    }
}
?>
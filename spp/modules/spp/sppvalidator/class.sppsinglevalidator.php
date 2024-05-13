<?php

namespace SPPMod;

/**
 * class SPP_Single_validator
 *
 * @author Satya Prakash Shukla
 */
// require_once 'class.sppvalidator.php';
 
abstract class SPP_Single_validator extends SPP_Validator {
    protected $element;

    public function __construct(SPP_ViewTag $elem, $errorholder, $msg, $jsfunc) {
        parent::__construct($errorholder, $msg, $jsfunc);
        $this->element=$elem;
    }

    public function setElement(SPP_ViewTag $elem)
    {
        //parent::__construct();
        $this->element=$elem;
    }

    public function getJsFunction()
    {
        $fn=$this->jsfunc.'(\''.$this->errorholder.'\',\''.$this->msg.'\',\''.$this->element->getAttribute('id').'\')';
        return $fn;
    }
}
?>
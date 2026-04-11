<?php

namespace SPPMod\SPPView;

/**
 * abstract class SPP_Single_validator
 *
 * @author Satya Prakash Shukla
 */
 
abstract class SPP_Single_validator extends ViewValidator {
    protected $element;

    public function __construct(\SPPMod\SPPView\ViewTag $elem, $errorholder = 'nameerror', $msg = 'Validation error', $jsfunc = 'undefined') {
        parent::__construct(null, $errorholder, $msg, $jsfunc);
        $this->element=$elem;
    }

    public function setElement(\SPPMod\SPPView\ViewTag $elem)
    {
        //parent::__construct();
        $this->element=$elem;
    }

    public function getJsFunction(): string
    {
        $fn=$this->jsfunc.'(\''.$this->errorholder.'\',\''.$this->msg.'\',\''.$this->element->getAttribute('id').'\')';
        return $fn;
    }
}
?>
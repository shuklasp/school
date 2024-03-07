<?php
/**
 * class SPP_Single_validator
 *
 * @author Satya Prakash Shukla
 */
// require_once 'class.sppvalidator.php';
 
abstract class SPP_Single_validator extends SPP_Validator {
    protected $element;

    public function __construct(SPP_HTML_Element $elem)
    {
        parent::__construct();
        $this->element=$elem;
    }

    public function getJsFunction()
    {
        $fn=$this->jsfunc.'(\''.$this->errorholder.'\',\''.$this->msg.'\',\''.$this->element->getAttribute('id').'\')';
        return $fn;
    }
}
?>
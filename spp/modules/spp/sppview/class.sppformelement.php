<?php
//require_once 'class.spphtmlelement.php';

/**
 * class SPP_Form_Element
 * Represents an element of form.
 *
 * @author Satya Prakash Shukla
 */
class SPP_Form_Element extends SPP_ViewTag{
    protected $validators = array();
    protected $errors = array();

    /**
     * @param string $ename
     */
    public function  __construct($ename, SPP_Validator $validator=null) {
        parent::__construct($tagname='input', $ename);
        $this->eventattrlist[]='onselect';
        $this->eventattrlist[]='onchange';
        //$this->eventattrlist[]='onsubmit';
        //$this->eventattrlist[]='onreset';
        $this->eventattrlist[]='onblur';
        $this->eventattrlist[]='onfocus';
        if($validator){
            $this->addValidator($validator);
        }
    }

    public function addValidator(SPP_Validator $validator){
        $this->validators[] = $validator;
    }


}
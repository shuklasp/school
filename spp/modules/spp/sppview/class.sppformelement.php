<?php

namespace SPPMod\SPPView;;
//require_once 'class.spphtmlelement.php';

/**
 * class SPPViewForm_Element
 * Represents an element of form.
 *
 * @author Satya Prakash Shukla
 */
class SPPViewForm_Element extends \SPPMod\SPPView\ViewTag{
    protected $validators = array();
    protected $errors = array();

    /**
     * @param string $ename
     */
    public function  __construct($ename, SPPValidator $validator=null) {
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

    public function addValidator(SPPValidator $validator){
        $this->validators[] = $validator;
    }


}
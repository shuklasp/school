<?php

namespace SPPMod\SPPValidator;
/*require_once 'class.sppsinglevalidator.php';
require_once 'class.sppmultiplevalidator.php';
require_once 'class.spperror.php';*/

class SPP_Validator_RequiredValidator extends SPP_Single_validator {
    public function __construct(SPP_HTML_Element $elem) {
        parent::__construct($elem);
        $this->applicabletags=array('input');
        $this->jsfunc='validateRequired';
        $this->msg='A required field is left blank!';
    }

    public function validate() {
        if(trim($_POST[$this->element->getAttribute('id')])=='') {
            SPP_HTML_Page::addClass($this->element->getAttribute('id'),'errorclass');
            SPPError::triggerUserError($this->msg);
            return false;
        }
        else {
            return true;
        }
    }
}


class SPP_Validator_NumericValidator extends SPP_Single_validator {
    public function __construct(\SPPMod\SPPView\SPPViewTag $elem) {
        parent::__construct($elem);
        $this->applicabletags=array('input');
        $this->jsfunc='validateNumeric';
        $this->msg='The field should be numeric!';
    }

    public function validate() {
        if(trim($_POST[$this->element->getAttribute('id')])=='') {
            SPP_ViewPage::addClass($this->element->getAttribute('id'),'errorclass');
            \SPP\SPPError::triggerUserError($this->msg);
            return false;
        }
        else {
            return true;
        }
    }
}


class SPP_Validator_OneRequiredValidator extends SPP_Multiple_Validator {
    public function __construct(array $elems) {
        parent::__construct($elems);
        $this->applicabletags=array('input');
        $this->jsfunc='validateOneRequired';
        $this->msg='At least one of these fields must be filled';
    }

    public function validate() {
        $flag=false;
        foreach($this->elements as $elem) {
            if(trim($_POST[$elem->getAttribute('id')])!='')
            {
                $flag=true;
                break;
            }
        }
        if($flag)
        {
            return $flag;
        }
        else
        {
            foreach($this->elements as $elem){
                SPP_ViewPage::addClass($elem->getAttribute('id'),'errorclass');
            }
            \SPP\SPPError::triggerUserError($this->msg);
        }
    }
}
<?php
//require_once 'class.spphtmlelement.php';

/**
 * class SPP_Form_Element
 * Represents an element of form.
 *
 * @author Satya Prakash Shukla
 */
class SPP_Form_Element extends SPP_HTML_Element{
    public function  __construct($ename) {
        parent::__construct($ename);
        $this->eventattrlist[]='onselect';
        $this->eventattrlist[]='onchange';
        //$this->eventattrlist[]='onsubmit';
        //$this->eventattrlist[]='onreset';
        $this->eventattrlist[]='onblur';
        $this->eventattrlist[]='onfocus';
    }
}
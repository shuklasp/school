<?php
/* 
 * file Digit_ValidationTextBox
 * Declares the ValidationTextBox class.
 */
/**
 * class Dijit_ValidationTextBox
 *
 * @author Satya Prakash Shukla
 */
class Dijit_ValidationTextBox extends SPP_Form_Dijit {
    public function __construct($eid,$frmid)
    {
        parent::__construct($eid, 'dijit.form.ValidationTextBox', $frmid);
    }

    public function getHTML()
    {
        parent::getHTML();
        $str='<input type="text" id="'.$this->id.'" />';
        //return $this->applyTheme($str);
        return $str;
    }

    public function validate()
    {
        ;
    }
}
?>
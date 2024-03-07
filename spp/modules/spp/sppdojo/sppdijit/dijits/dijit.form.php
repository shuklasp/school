<?php
/* 
 * file class.sppdijitform.php
 * Defines the Digit_Form class.
 */
/**
 * class Dijit_Form
 * Defines a dijit form.
 *
 * @author Satya Prakash Shukla
 */
class Dijit_Form extends SPP_Dijit {
    private $form_elements=array();

    public function __construct($frmname)
    {
        parent::__construct($frmname, 'dijit.form.Form');
    }

    public function addElement(SPP_Form_Dijit $elem)
    {
        $id=$elem->getId();
        if(array_key_exists($id, $this->form_elements))
        {
            throw new SPP_Exception('Duplicate digit used : '.$elid);
        }
        else
        {
            $this->form_elements[$id]=$elem;
        }
    }

    public function getElement($elid)
    {
        if(array_key_exists($elid,$this->form_elements))
        {
            return false;
        }
        else
        {
            return $this->form_elements[$elid];
        }
    }

    public function validate()
    {
        foreach($this->form_elements as $eid=>$elem)
        {
            $elem->validate();
        }
    }
}
?>
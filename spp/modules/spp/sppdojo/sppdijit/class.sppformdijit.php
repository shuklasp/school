<?php
/* 
 * file class.sppformdijit.php
 * Defines SPP_Form_Dijit class.
 */

/**
 * class SPP_Form_Dijit
 * Top level class for form dijits.
 *
 * @author Satya Prakash Shukla
 */
abstract class SPP_Form_Dijit extends SPP_Dijit {
    public function __construct($eid,$dtype,$frmid)
    {
        parent::__construct($eid, $dtype);
        $frm=SPP_Dojo::getDijit($frmid);
        $frm->addElement($this);
    }
}
?>
<?php
require_once SPP_BASE_DIR.SPPUS.'spphtml.php';

$loginbox=new SPPViewForm_Input_Text('login');
$passwdbox=new SPPViewForm_Input_Password('passwd');
$loginsubmit=new SPPViewForm_Input_Submit('loginsubmit');
$loginsubmit->setAttribute('value', 'Login');
//$ldate=new SPPViewForm_DateChooser('ldate');
//$ldate->setDateAttr('TextLink', 'Click');
//$ldate->setDateAttr('DateFormat', 'm-d-Y');
?>
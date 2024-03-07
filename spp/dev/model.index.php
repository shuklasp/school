<?php
require_once SPP_BASE_DIR.SPPUS.'spphtml.php';

$loginbox=new SPP_Form_Input_Text('login');
$passwdbox=new SPP_Form_Input_Password('passwd');
$loginsubmit=new SPP_Form_Input_Submit('loginsubmit');
$loginsubmit->setAttribute('value', 'Login');
//$ldate=new SPP_Form_DateChooser('ldate');
//$ldate->setDateAttr('TextLink', 'Click');
//$ldate->setDateAttr('DateFormat', 'm-d-Y');
?>
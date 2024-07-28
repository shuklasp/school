<?php
/*\SPP\SPPBase::useModule('SPPHtml');
\SPP\SPPBase::useModule('SPPDB');
\SPP\SPPBase::useModule('SPPDev');
\SPP\SPPBase::useModule('SPPSession');
\SPP\SPPBase::useModule('SPP_Wizard');*/
SPP_HTML_Page::readXMLFile(SPP_DEV_DIR.SPP_US.'controls.devsetup.xml');
/*$appname=new SPPViewForm_Input('appname');
$appname->setAttribute('maxlength', '40');
$appname->setAttribute('size', '40');
$dbname=new SPPViewForm_Input('dbname');*/
//$dbtype=new SPPViewForm_Select('dbtype');
//$dbtype->setAttribute('size', '40');
//$dbtype->addOption('MySQL', 'mysql');
//$dbtype->addOption('Oracle', 'oracle');
/*$dbuname=new SPPViewForm_Input('dbuname');
$dbpasswd=new SPPViewForm_Input_Password('dbpasswd');
$formsubmit=new SPPViewForm_Input_Submit('formsubmit');
$formsubmit->setAttribute('value', 'Submit');
//$installform=new SPPViewForm('installform', \SPP\SPPBase::sppLink('devsetup.php'), 'post');
//$installform->addElement($appname);
/*$installform->addElement($dbname);
$installform->addElement($dbtype);
$installform->addElement($dbuname);
$installform->addElement($dbpasswd);
$installform->addElement($formsubmit);
$dbnameval=new SPP_Validator_RequiredValidator($dbname);
$dbunameval=new SPP_Validator_RequiredValidator($dbuname);
//$appnameval=new SPP_Validator_RequiredValidator($appname);
$installform->addValidator($dbnameval,'Database name should be supplied.');
$installform->addValidator($dbunameval,'Database user name should be supplied');
//$installform->addValidator(new SPP_Validator_RequiredValidator($dbpasswd),'Database password should be supplied');
$installform->addValidator($appnameval,'Application name should be supplied');
$installform->attachValidator($dbnameval, $dbname, 'onblur','dbnameerror');
$installform->attachValidator($dbunameval, $dbuname, 'onblur','dbunameerror');
//$installform->attachValidator($appnameval, $appname, 'onblur','appnameerror');
//SPP_Wizard::createWizard('newwiz');*/
SPP_Dojo::init();
SPP_Dojo::includeDijit('Dijit_Form');
SPP_Dojo::includeDijit('Dijit_ValidationTextBox');
SPP_Dojo::setTheme('tundra');
$frm=new Dijit_Form('form1');
$dgt=new Dijit_ValidationTextBox('example1', 'form1');
$dgt->setDigitProperty('lowercase', 'true');
$dgt->setDigitProperty('required', 'true');
$dgt->setDigitProperty('invalidMessage', 'Invalid value entered.');
$dgt->finalize();
SPP_HTML_Page::processForms();
\SPP\Registry::register('event=>attr=>attr1', 2);
//echo \SPP\Registry::get('event=>attr');
function installform_submitted()
{
    //SPP_Wizard::collectSubmittedVars('newwiz','post');
    //$installform=SPP_HTML_Page::getElement('installform');
    //$installform->doValidation();
    //SPPError::triggerError('Bad Form');
    //header('Location: '.\SPP\SPPBase::sppLink('devsetup1.php'));
}
?>
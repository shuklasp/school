<?php
/**
 * Test Form View
 */
use SPPMod\SPPView\ViewPage;

echo "<h1>SPP Base System Form Test</h1>";

// Load the form definition
$xmlPath = APP_ETC_DIR . '/forms/test_form.xml';
if (ViewPage::readXMLFile($xmlPath)) {
    ViewPage::processXMLForm();
} else {
    echo "<p style='color:red;'>Failed to read XML: $xmlPath</p>";
}

// Retrieve the form by its XML ID
$form = ViewPage::getForm('test_form');

if ($form) {
    echo $form->getStart();
    // Use the attribute-mapped IDs
    $nameElem = ViewPage::getElement('test_name_id');
    $btnElem = ViewPage::getElement('submit_btn_id');
    
    if ($nameElem) echo "<p>Name: " . $nameElem->renderHTML() . "</p>";
    if ($btnElem) echo "<p>" . $btnElem->renderHTML() . "</p>";
    
    echo $form->getEnd();
} else {
    echo "<p style='color:red;'>Error: Form 'test_form' not found in registry.</p>";
    echo "<pre>Registered forms: " . print_r(array_keys(ViewPage::getFormsList()), true) . "</pre>";
}
?>

<?php
require_once 'vendor/autoload.php';
require_once 'spp/sppinit.php';

// Manually include necessary classes (due to early CLI load)
require_once 'spp/modules/spp/sppview/class.viewtag.php';
require_once 'spp/modules/spp/sppview/class.viewpage.php';
require_once 'spp/modules/spp/sppview/class.pages.php';
require_once 'spp/modules/spp/sppview/class.viewform.php';
require_once 'spp/modules/spp/sppview/class.viewvalidator.php';
require_once 'spp/modules/spp/sppview/class.sppformelement.php';
require_once 'spp/modules/spp/sppview/formelements/classes.formelements.php';
require_once 'spp/modules/spp/sppview/sppvalidator/class.sppsinglevalidator.php';
require_once 'spp/modules/spp/sppview/sppvalidator/class.sppmultiplevalidator.php';
require_once 'spp/modules/spp/sppview/sppvalidator/classes.sppvalidators.php';
require_once 'spp/modules/spp/sppview/class.viewformbuilder.php';
require_once 'spp/modules/spp/sppview/class.formaugmentor.php';

use SPPMod\SPPView\ViewPage;
use SPPMod\SPPView\Pages;

echo "--- Testing Universal Page Augmentation ---\n";

// 1. Mock internal page registry for the test file
$testPath = 'test_drop.html';
// We'll simulate Pages::getPage() returning our test file
// (Mocking the registry might be hard, so we'll just manually set the SPPGlobal)
\SPP\SPPGlobal::set('page', ['url' => $testPath, 'params' => [], 'special' => 0]);

// 2. Execute showPage with augmentation EXPLCITLY enabled override
echo "Calling ViewPage::showPage with ['augment' => true]...\n\n";
ob_start();
ViewPage::showPage(null, ['augment' => true, 'inject_js' => true]);
$output = ob_get_clean();

echo "--- Augmented Output ---\n";
echo $output;
echo "\n--- End Output ---\n";

// 3. Verification checks
if (strpos($output, 'data-service="save_test_data"') !== false) {
    echo "\nPASS: data-service attribute injected.\n";
} else {
    echo "\nFAIL: data-service attribute NOT found.\n";
}

if (strpos($output, 'validateRequired') !== false) {
    echo "PASS: validateRequired JS call injected.\n";
} else {
    echo "FAIL: validateRequired JS call NOT found.\n";
}

if (strpos($output, 'value="100"') !== false) {
    echo "PASS: Pre-populated value '100' injected.\n";
} else {
    echo "FAIL: Pre-populated value '100' NOT found.\n";
}

if (strpos($output, 'spp-autoinit.js') !== false) {
    echo "PASS: JS includes injected.\n";
} else {
    echo "FAIL: JS includes NOT found.\n";
}

echo "\n--- Verification Complete ---\n";

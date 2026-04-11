<?php
require_once 'vendor/autoload.php';
require_once 'spp/sppinit.php';

// Manually include necessary classes since the autoloader is currently limited to core/
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

use SPPMod\SPPView\ViewFormBuilder;

echo "--- Testing Form Building from YAML ---\n";
try {
    $form = ViewFormBuilder::fromYaml('spp/etc/forms/login.yml');
    echo "Form Name: " . $form->getAttribute('name') . "\n";
    echo "Form Service: " . $form->getAttribute('data-service') . "\n";
    echo "Form on-ok: " . $form->getAttribute('data-on-ok') . "\n";
    
    echo "\n--- Testing HTML Rendering (Start) ---\n";
    ob_start();
    $form->startForm();
    $form->endForm();
    $html = ob_get_clean();
    echo "HTML snippet:\n" . $html . "\n";
    
    echo "\n--- Testing Server-Side Validation (Empty) ---\n";
    $res = ViewFormBuilder::validate('spp/etc/forms/login.yml', []);
    echo "Valid: " . ($res['valid'] ? 'YES' : 'NO') . "\n";
    echo "Errors: " . print_r($res['errors'], true) . "\n";

    echo "\n--- Testing Server-Side Validation (Valid) ---\n";
    $res = ViewFormBuilder::validate('spp/etc/forms/login.yml', ['username' => 'satya', 'password' => 'secret']);
    echo "Valid: " . ($res['valid'] ? 'YES' : 'NO') . "\n";
    echo "Errors: " . print_r($res['errors'], true) . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "LINE: " . $e->getLine() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}

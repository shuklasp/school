<?php
require_once 'vendor/autoload.php';
require_once 'spp/sppinit.php';

use SPPMod\SPPView\ViewPage;
use SPPMod\SPPView\FormAugmentor;

// Only register if not already registered
if (!\SPP\Registry::isRegistered('__apps=>default=>status')) {
    new \SPP\App('default');
}

// Mock a page
\SPP\SPPGlobal::set('page', ['url' => 'login.html', 'params' => [], 'special' => 0]);

echo "--- Debug Configuration ---\n";
$aug = \SPP\Module::getConfig('auto_page_augmentation', 'sppview');
$js = \SPP\Module::getConfig('auto_js_injection', 'sppview');
echo "Auto Augmentation: " . var_export($aug, true) . "\n";
echo "Auto JS Injection: " . var_export($js, true) . "\n";

echo "--- Rendering with Augmentation and JS Injection ---\n";
ob_start();
ViewPage::showPage(null, ['augment' => true, 'inject_js' => true]);
$output = ob_get_clean();

file_put_contents('render_output.html', $output);
echo "Output saved to render_output.html (" . strlen($output) . " bytes)\n";

if (strpos($output, '<script') !== false) {
    echo "Found <script> tags in output.\n";
} else {
    echo "NO <script> tags found in output!\n";
}

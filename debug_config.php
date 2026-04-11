require_once 'vendor/autoload.php';
require_once 'spp/sppinit.php';
use SPP\Module;

// Initialize app context to 'default'
new \SPP\App('default');

$aug = Module::getConfig('auto_page_augmentation', 'sppview');
$js = Module::getConfig('auto_js_injection', 'sppview');

echo "Auto Augmentation: " . var_export($aug, true) . " (Type: " . gettype($aug) . ")\n";
echo "Auto JS Injection: " . var_export($js, true) . " (Type: " . gettype($js) . ")\n";

$doAugment = (bool)$aug;
$doInjectJs = (bool)$js;

echo "Casted Aug: " . var_export($doAugment, true) . "\n";
echo "Casted JS:  " . var_export($doInjectJs, true) . "\n";

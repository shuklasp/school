<?php
require_once 'vendor/autoload.php';
require_once 'spp/sppinit.php';

// Mock the absolute minimum to get getConfig working
class MockProc {
    public function getModsConfDir() {
        return 'c:\projects\apache\school1\spp\etc\apps\default\modsconf';
    }
}
class MockScheduler {
    public static function getActiveProc() {
        return new MockProc();
    }
}

// Directly parse the YAML to be 100% sure what's in it
$file = 'c:\projects\apache\school1\spp\etc\apps\default\modsconf\sppview\config.yml';
$data = \Symfony\Component\Yaml\Yaml::parseFile($file);

echo "YAML Data:\n";
print_r($data);

$aug = $data['variables']['auto_page_augmentation'] ?? null;
$js = $data['variables']['auto_js_injection'] ?? null;

echo "\nRaw Values:\n";
echo "Aug: " . var_export($aug, true) . "\n";
echo "JS:  " . var_export($js, true) . "\n";

echo "\nCasted as Boolean:\n";
echo "Aug: " . var_export((bool)$aug, true) . "\n";
echo "JS:  " . var_export((bool)$js, true) . "\n";

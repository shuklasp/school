<?php
// Capture output
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');

define('SPP_BASE_DIR', __DIR__ . '/spp');
require_once SPP_BASE_DIR . '/sppinit.php';

// Mock getModuleStatusFromManifests
function getModuleStatusFromManifests(string $modname, string $appname, string $type = 'any'): string {
    return 'active'; // Simplified for test
}

$modules = [];
$appname = 'default';

// Reproduce the list_modules logic
$sys_yml = \SPP\SPPFS::findFile('module.yml', SPP_MODULES_DIR);
$sys_xml = \SPP\SPPFS::findFile('module.xml', SPP_MODULES_DIR);

$user_mod_dir = SPP_APP_DIR . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $appname;
$user_yml = is_dir($user_mod_dir) ? \SPP\SPPFS::findFile('module.yml', $user_mod_dir) : [];
$user_xml = is_dir($user_mod_dir) ? \SPP\SPPFS::findFile('module.xml', $user_mod_dir) : [];

$manifests = [];
foreach (array_merge($sys_yml, $sys_xml) as $f) {
    $name = basename(dirname($f));
    if (!isset($manifests[$name])) {
        $manifests[$name] = ['file' => $f, 'type' => 'system'];
    }
}
foreach (array_merge($user_yml, $user_xml) as $f) {
    $name = basename(dirname($f));
    $manifests[$name] = ['file' => $f, 'type' => 'user'];
}

foreach ($manifests as $name => $mInfo) {
    try {
        $file = $mInfo['file'];
        $type = $mInfo['type'];
        $mod = new \SPP\Module($file);
        
        $hasConfig = !empty($mod->ConfigVariables);
        $status = getModuleStatusFromManifests($name, $appname, $type);
        
        $modules[] = [
            'name' => $name,
            'active' => ($status === 'active'),
            'path' => $file
        ];
    } catch (Exception $e) {
        echo "Error loading $name: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "Fatal Error loading $name: " . $e->getMessage() . "\n";
    }
}

$output = ob_get_clean();
echo "OUTPUT:\n$output\n";
echo "MODULES FOUND: " . count($modules) . "\n";
foreach($modules as $m) {
    echo " - " . $m['name'] . ($m['active'] ? ' [ACTIVE]' : ' [INACTIVE]') . "\n";
}

<?php
// Capture output
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '1'); // Show errors in this debug script

define('SPP_BASE_DIR', __DIR__ . '/spp');
require_once SPP_BASE_DIR . '/sppinit.php';

// Copy REAL getModuleStatusFromManifests from api.php (lines 108-155)
function getModuleStatusFromManifests(string $modname, string $appname, string $type = 'any'): string {
    $candidates = [];
    if ($type === 'system' || $type === 'any') {
        if (defined('SPP_ETC_DIR')) {
            $candidates[] = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'modules.yml';
            $candidates[] = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'modules.xml';
            $candidates[] = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modules.yml';
            $candidates[] = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modules.xml';
        }
    }
    if ($type === 'user' || $type === 'any') {
        if (defined('APP_ETC_DIR') && $appname !== '') {
            $candidates[] = APP_ETC_DIR . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modsconf' . DIRECTORY_SEPARATOR . 'modules.yml';
            $candidates[] = APP_ETC_DIR . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modsconf' . DIRECTORY_SEPARATOR . 'modules.xml';
        }
    }
    foreach ($candidates as $file) {
        if (!file_exists($file)) continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === 'yml' || $ext === 'yaml') {
            try {
                $yml = \Symfony\Component\Yaml\Yaml::parseFile($file);
                $mods = $yml['modules'] ?? [];
                foreach ($mods as $m) {
                    $mArr = (array)$m;
                    if (($mArr['name'] ?? $mArr['modname'] ?? '') === $modname) {
                        return (string)($mArr['status'] ?? 'active');
                    }
                }
            } catch (\Exception $e) {}
        } else {
            $xml = @simplexml_load_file($file);
            if ($xml === false) continue;
            foreach ($xml->module as $mod) {
                $name = (string)($mod->modname ?? $mod->name ?? '');
                if ($name === $modname) {
                    return (string)($mod->status ?? 'active');
                }
            }
        }
    }
    return 'unknown';
}

$modules = [];
$appname = 'default';

$sys_yml = \SPP\SPPFS::findFile('module.yml', SPP_MODULES_DIR);
if ($sys_yml === false) { echo "sys_yml is FALSE\n"; $sys_yml = []; }

$sys_xml = \SPP\SPPFS::findFile('module.xml', SPP_MODULES_DIR);
if ($sys_xml === false) { echo "sys_xml is FALSE\n"; $sys_xml = []; }

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

foreach ($manifests as $name => $mInfo) {
    try {
        $file = $mInfo['file'];
        $type = $mInfo['type'];
        $mod = new \SPP\Module($file);
        $status = getModuleStatusFromManifests($name, $appname, $type);
        
        $modules[] = [
            'name' => $name,
            'active' => ($status === 'active'),
            'status' => $status,
            'path' => $file
        ];
    } catch (Throwable $e) {
        echo "Error loading $name: " . $e->getMessage() . "\n";
    }
}

$output = ob_get_clean();
echo "OUTPUT:\n$output\n";
echo "MODULES SUMMARY:\n";
foreach($modules as $m) {
    echo " - " . $m['name'] . ": " . $m['status'] . "\n";
}

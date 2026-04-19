<?php
$_SERVER['DOCUMENT_ROOT'] = 'c:/projects/apache/school1';
define('SPP_BASE_DIR', __DIR__ . '/spp');
require 'spp/sppinit.php';

$appname = 'default';

                $sys_yml = \SPP\SPPFS::findFile('module.yml', SPP_MODULES_DIR) ?: [];
                $sys_xml = \SPP\SPPFS::findFile('module.xml', SPP_MODULES_DIR) ?: [];
                $user_mod_dir = APP_ETC_DIR . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modules';
                $user_yml = is_dir($user_mod_dir) ? (\SPP\SPPFS::findFile('module.yml', $user_mod_dir) ?: []) : [];
                $user_xml = is_dir($user_mod_dir) ? (\SPP\SPPFS::findFile('module.xml', $user_mod_dir) ?: []) : [];

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

function getModuleStatusFromManifests(string $modname, string $appname, string $type = 'any'): string {
    $candidates = [];

    if ($type === 'system' || $type === 'any') {
        if (defined('SPP_ETC_DIR')) {
            $candidates[] = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modules.yml';
            $candidates[] = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modules.xml';
            $candidates[] = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'modules.yml';
            $candidates[] = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'modules.xml';
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
                    $mArr = (array) $m;
                    $n = $mArr['name'] ?? $mArr['modname'] ?? '';
                    if ($n === $modname) return (string) ($mArr['status'] ?? 'active');
                }
            } catch (\Exception $e) {}
        } else {
            $xml = @simplexml_load_file($file);
            if ($xml === false) continue;
            foreach ($xml->module as $mod) {
                $n = (string) ($mod->modname ?? $mod->name ?? '');
                if ($n === $modname) return (string) ($mod->status ?? 'active');
            }
        }
    }
    return 'inactive';
}

$modules = [];
foreach ($manifests as $name => $mInfo) {
    echo "Processing $name...\n";
    $file = $mInfo['file'];
    $type = $mInfo['type'];
    $mod = new \SPP\Module($file);
    $finalName = $mod->InternalName ?: $name;
    $status = getModuleStatusFromManifests($finalName, $appname, $type);
    echo " -> finalName: $finalName, status: $status\n";
    if ($finalName === 'spplogger') echo "   [DEBUG spplogger type=$type]\n";
}

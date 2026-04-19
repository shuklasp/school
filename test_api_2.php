<?php 
require 'spp/sppinit.php'; 

function getModuleStatusFromManifests(string $modname, string $appname, string $type = 'any'): string
{
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

    echo "Testing $modname in $appname...\n";
    foreach ($candidates as $file) {
        if (!file_exists($file))
            continue;

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        echo " -> Checking $file\n";
        if ($ext === 'yml' || $ext === 'yaml') {
            try {
                $yml = \Symfony\Component\Yaml\Yaml::parseFile($file);
                $mods = $yml['modules'] ?? [];
                foreach ($mods as $k => $m) {
                    $mArr = (array) $m;
                    $name = $mArr['name'] ?? $mArr['modname'] ?? '';
                    if ($name === $modname) {
                        return (string) ($mArr['status'] ?? 'active');
                    }
                }
            } catch (\Exception $e) {
            }
        } else {
            $xml = @simplexml_load_file($file);
            if ($xml === false)
                continue;
            foreach ($xml->module as $mod) {
                $name = (string) ($mod->modname ?? $mod->name ?? '');
                if ($name === $modname) {
                    return (string) ($mod->status ?? 'active');
                }
            }
        }
    }
    
    // Fallback: If not explicitly found in manifests, check framework registry status
    if (class_exists('\\SPP\\Module') && \SPP\Module::isEnabled($modname)) {
        return 'active (fallback registry)';
    }

    return 'inactive (end)';
}

echo "--- DEMO ---\n";
foreach(['spplogger', 'sppdb', 'sppprofile'] as $mod) { 
    echo $mod . ': ' . getModuleStatusFromManifests($mod, 'demo', 'system') . "\n";
}

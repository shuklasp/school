<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require 'c:/projects/apache/school1/spp/sppinit.php';

$modules = [];
$files = \SPP\SPPFS::findFile('module.xml', SPP_MODULES_DIR);
foreach ($files as $file) {
    try {
        $modXml = simplexml_load_file($file);
        $name = (string)$modXml->name;
        $modules[] = [
            'name' => $name,
            'public_name' => (string)($modXml->public_name ?? $name),
            'version' => (string)($modXml->version ?? '1.0'),
            'author' => (string)($modXml->author ?? 'Unknown'),
            'active' => \SPP\Module::isEnabled($name),
            'path' => str_replace(dirname(SPP_BASE_DIR), '', $file)
        ];
    } catch (Exception $e) {}
}

$response = [
    'success' => true,
    'message' => '',
    'data' => ['modules' => $modules],
    'errors_html' => ''
];

$json = json_encode($response);
if ($json === false) {
    echo "json_encode failed: " . json_last_error_msg() . "\n";
} else {
    echo $json . "\n";
}

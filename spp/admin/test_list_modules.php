<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
define('SPP_BASE_DIR', dirname(__DIR__));

$coreDir = SPP_BASE_DIR . '/core';
foreach (['class.sppobject.php', 'class.sppsession.php', 'class.sppbase.php', 'class.sppexception.php'] as $f) {
    if (file_exists($coreDir . '/' . $f)) require_once $coreDir . '/' . $f;
}

require_once SPP_BASE_DIR . '/sppinit.php';

function sendResponse($success, $data = [], $message = '') {
    $phpOutput = ob_get_clean();
    $response = ['success' => $success, 'data' => $data, 'message' => $message];
    if (!empty($phpOutput)) $response['_debug_output'] = mb_convert_encoding($phpOutput, 'UTF-8', 'auto');
    echo json_encode($response);
    exit;
}

$modules = [];
$files = \SPP\SPPFS::findFile('module.xml', SPP_MODULES_DIR);
foreach ($files as $file) {
    try {
        $modXml = simplexml_load_file($file);
        $name = (string)$modXml->name;
        $modules[] = [
            'name' => $name,
            'active' => \SPP\Module::isEnabled($name)
        ];
    } catch (Exception $e) {}
}

sendResponse(true, ['modules' => $modules]);

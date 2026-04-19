<?php
$_SERVER['DOCUMENT_ROOT'] = 'c:/projects/apache/school1';
require 'spp/sppinit.php';

$xmlPath = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . 'modules.xml';

$dom = new DOMDocument('1.0','UTF-8');
$dom->preserveWhiteSpace = true;
$dom->formatOutput = false;
if (!$dom->load($xmlPath)) { echo "load fail\n"; exit; }
$modules = $dom->getElementsByTagName('module');
foreach ($modules as $moduleNode) {
    $nameNode = $moduleNode->getElementsByTagName('modname')->item(0);
    if (!$nameNode) { $nameNode = $moduleNode->getElementsByTagName('name')->item(0); }
    if (!$nameNode) continue;
    if ($nameNode->textContent === 'spplogger') {
        $statusNode = $moduleNode->getElementsByTagName('status')->item(0);
        if ($statusNode) {
            $statusNode->textContent = 'active';
        } else {
            $newStatus = $dom->createElement('status','active');
            $moduleNode->appendChild($newStatus);
        }
        $dom->save($xmlPath);
        echo "modified\n";
        break;
    }
}
?>

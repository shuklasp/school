<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require 'c:/projects/apache/school1/spp/sppinit.php';
$modules = [];
$files = \SPP\SPPFS::findFile('module.xml', SPP_MODULES_DIR);
foreach ($files as $file) {
    echo "Processing $file\n";
    $modXml = simplexml_load_file($file);
    $name = (string)$modXml->name;
    echo "Module: $name\n";
    $active = \SPP\Module::isEnabled($name);
    echo "Active: $active\n";
}
echo "Done\n";

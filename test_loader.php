<?php
require_once 'spp/sppinit.php';
require_once 'spp/modules/spp/sppgroup/class.sppgrouploader.php';
require_once 'spp/modules/spp/sppgroup/class.sppgroup.php';

$appname = 'default';
echo "Scanning for groups in context: $appname\n";
echo "App Group Dir: " . \SPPMod\SPPGroup\SPPGroupLoader::getAppGroupDir($appname) . "\n";
echo "Global Group Dir: " . \SPPMod\SPPGroup\SPPGroupLoader::getGlobalGroupDir() . "\n";

$groups = \SPPMod\SPPGroup\SPPGroupLoader::listAllGroups($appname);
echo "Discovered Groups: " . count($groups) . "\n";
print_r($groups);

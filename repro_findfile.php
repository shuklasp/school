<?php
require_once 'spp/sppinit.php';
require_once 'spp/core/class.sppfs.php';

$res1 = \SPP\SPPFS::findFile('module.yml', 'spp/modules');
echo "Result for module.yml: " . (is_array($res1) ? count($res1) : var_export($res1, true)) . "\n";

$res2 = \SPP\SPPFS::findFile('module.xml', 'spp/modules');
echo "Result for module.xml: " . (is_array($res2) ? count($res2) : var_export($res2, true)) . "\n";

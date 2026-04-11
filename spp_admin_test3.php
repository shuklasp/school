<?php
error_reporting(E_ALL); 
ini_set('display_errors', '1'); 
define('SPP_BASE_DIR', 'c:/projects/apache/school1/spp'); 
$coreDir = SPP_BASE_DIR . '/core'; 
foreach (['class.sppobject.php', 'class.sppsession.php', 'class.sppbase.php', 'class.sppexception.php'] as $f) { 
    if (file_exists($coreDir . '/' . $f)) require_once $coreDir . '/' . $f; 
} 
$authDir = SPP_BASE_DIR . '/modules/spp/sppauth'; 
foreach (['class.sppuser.php', 'class.sppusersession.php'] as $f) { 
    if (file_exists($authDir . '/' . $f)) require_once $authDir . '/' . $f; 
} 
echo class_exists('SPPMod\SPPAuth\SPPUserSession') ? 'LOADED' : 'NOT LOADED';

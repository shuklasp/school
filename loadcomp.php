<?php
require_once('spp/sppinit.php');
require_once('global.php');
$obj=new SPP_Settings();
//require_once('vendor/autoload.php');
//global $services, $pages;
//require_once('src/server/'.$services[$_REQUEST['service']]);
SPP_Ajax::callService();
SPP_Ajax::callRoutine();
?>
<?php
require_once('spp/sppinit.php');
require_once('global.php');
//$obj=new SPP\Settings();
//require_once('vendor/autoload.php');
//global $services, $pages;
//require_once('src/server/'.$services[$_REQUEST['service']]);
Ajax::callService();
Ajax::callRoutine();
?>
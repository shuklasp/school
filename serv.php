<?php
require_once('spp/sppinit.php');
require_once('global.php');
//$obj=new SPP\Settings();
//require_once('vendor/autoload.php');
//global $services, $pages;
//require_once('src/server/'.$services[$_REQUEST['service']]);
if(Ajax::isServiceRequest()){
Ajax::callService();
Ajax::callRoutine();
}
else if(Ajax::isComponentRequest()){
    Ajax::loadPageComponent();
}
else{
    throw new \SPP\SPPException('Unknown request!');
}
?>
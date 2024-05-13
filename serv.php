<?php
require_once('spp/sppinit.php');
require_once('global.php');
//$obj=new SPP\Settings();
//require_once('vendor/autoload.php');
//global $services, $pages;
//require_once('src/server/'.$services[$_REQUEST['service']]);
if(SPP_Ajax::isServiceRequest()){
SPP_Ajax::callService();
SPP_Ajax::callRoutine();
}
else if(SPP_Ajax::isComponentRequest()){
    SPP_Ajax::loadPageComponent();
}
else{
    throw new \SPP\SPPException('Unknown request!');
}
?>
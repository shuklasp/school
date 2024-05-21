<?php
require_once('spp/sppinit.php');
require_once('global.php');
\SPP\SPPEvent::fireEvent('test_event');
\SPP\SPPEvent::fireEvent('another_test_event');
\SPP\SPPEvent::startEvent('test_simple');
\SPP\SPPEvent::startEvent('test_simple');
//$obj=new \SPP\Settings();
//require_once('vendor/autoload.php');
include('src/home.php');
/* try{
    $k=1;
    if($k==1)
    {
        throw new FakeException('This is a fake exception');
    }
    if($k==2)
    {
        throw new AotherException('This is another exception');
    }
//throw new FakeException('This is a fake exception');
}
catch(FakeException $e){
    echo get_class($e);
    //echo $e->getMessage();
    echo $e;
}
catch(AotherException $e){
    //echo $e->getMessage();
    echo $e;
}
 *///var_dump($_SESSION);
/*if($set=$obj->getSetting('src'))
//print_r($obj);
{
    print($set[0]->dir);
}*/
//var_dump($set);
?>
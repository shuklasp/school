<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//print_r($_GET);
use SPP\SPPGlobal;
use \SPPMod\SPPView\Pages;
use function Symfony\Component\String\u;

require_once('vendor/autoload.php');
require_once('spp/sppinit.php');
require_once('global.php');
\SPPMod\SPPView\ViewPage::addCssIncludeFile('res/spp/css/spp.css');
\SPPMod\SPPView\ViewPage::addJsIncludeFile('res/spp/js/spp.js');
\SPPMod\SPPView\ViewPage::addJsIncludeFile('res/spp/js/spp-router.js');

if (\SPP\Module::isEnabled('sppapi') && class_exists('\SPPMod\SPPAPI\SPPAPI') && \SPPMod\SPPAPI\SPPAPI::isApiRequest()) {
    \SPPMod\SPPAPI\SPPAPI::handle();
    exit;
}

// SPA dispatch: handle AJAX fragment/service requests before full HTML render
if (\SPP\Module::isEnabled('sppajax') && \SPPMod\SPPAjax\SPPAjax::isAjaxRequest()) {
    \SPPMod\SPPAjax\SPPAjax::handle();
    exit;
}

\SPPMod\SPPView\ViewPage::processForms();
\SPPMod\SPPView\ViewPage::showPage();
// $q = $_GET['q'];
// $page=array();
// if ($q == null) {
//     $def = Pages::getDefault('home');
//     $page=Pages::getPage($def);
//     // SPPGlobal::set('page', null);
//     // SPPGlobal::set('url', $page['src/home.php']);
//     // SPPGlobal::set('params', $_GET);
//     // SPPGlobal::set('q', $q);
//     // SPPGlobal::set('numparams', count($_GET));
//     // include($page['url']);
//     // die();
// }
// else{
//     $page = Pages::getPage();
// }
// // \SPP\SPPEvent::fireEvent('test_event');
// // \SPP\SPPEvent::fireEvent('another_test_event');
// // \SPP\SPPEvent::startEvent('test_simple');
// // \SPP\SPPEvent::startEvent('test_simple');
// //$obj=new \SPP\Settings();
// //require_once('vendor/autoload.php');
// //print($q);
// //echo $page;
// SPPGlobal::set('page', $page);
// SPPGlobal::set('url', $page['url']);
// SPPGlobal::set('params', $page['params']);
// SPPGlobal::set('q', $q);
// SPPGlobal::set('numparams', count($page['params']));
// if(file_exists($page['url']))
//     include($page['url']);
// foreach($page['params'] as $param=>&$value)
// {
//     echo $param.'=>'.$value.',';
// }
// echo count($page['params']);
//SPPGlobal::set('page',$page);
//print_r($page);
//if($q=='home')
//include('src/home.php');
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

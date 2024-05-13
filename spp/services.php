<?php
use SPP\SPPGlobal as SPPGlobal;
global $services, $pages, $servdir;

$servdir='src/server/';
$pagedir='src/comp/';

$services=array(
    'auth'=>'auth.php',
    'student'=>'student.php',
);

$pages=array(
    'login'=>array('page'=>'login-form.php','rights'=>array()),
    'navbar'=>array('page'=>'navbar.php', 'rights'=>array()),
    'welcome'=>array('page'=>'home-welcome.php','rights'=>array())
);

SPPGlobal::set('servdir',$servdir);
SPPGlobal::set('services',$services);
SPPGlobal::set('pages',$pages);
SPPGlobal::set('pagedir',$pagedir);

?>
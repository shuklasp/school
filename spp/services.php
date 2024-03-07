<?php
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

SPP_Global::set('servdir',$servdir);
SPP_Global::set('services',$services);
SPP_Global::set('pages',$pages);
SPP_Global::set('pagedir',$pagedir);

?>
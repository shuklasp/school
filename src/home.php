<?php
clearstatcache() ;
$obj=new \SPP\SPP_Settings();
$src=($obj->getSetting('src'));
//print_r($_SESSION);
\SPP\SPP_Error::destroyErrors();
            require_once('server/class.person.php');
            $p = new \School\Person();
            //var_dump($p);
//var_dump($src);
//echo '<img src="'.$src[0]->dir.'/img/name-plate.jpeg" />';
?>
<html>
    <head>
        <title>Virtual Shiksha Community</title>
        <link href="lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="lib/MDB5/css/mdb.min.css" rel="stylesheet">
    </head>
    <body>
        <div id="menu-area">
            <?php require('comp/navbar.php'); ?>
        </div>
            <div class="container-fluid">
                <div class="row">
                <div class="col-3" id="smenu-area"></div>
                    <div class="col" id=main-region>
                        <div class="container">
                    <div class="row" id="msg-area">
                        <div class="alert alert-warning alert-dismissible" role="alert" id="disp-msg">This is an alert
                            <span style="size: 50px;" align="right"><a name="" id="" data-bs-dismiss="alert" class="btn btn-primary" href="#" role="button">Dismiss</a></span>
                        </div>
                        <div class="row" id="working-area">
            <?php //require('comp/login-form.php'); ?>
</div>
                    </div>
                        </div>
                </div>
            </div>
        </div>
<script src="lib/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="lib/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="lib/MDB5/js/mdb.min.js"></script>
<script src="js/home.js"></script>
<script>//loadmain('src/comp/home-welcome.php');</script>
</body>
</html>
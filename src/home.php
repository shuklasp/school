<?php

use SPP\SPPGlobal;
use SPPMod\SPPView\ViewPage;

//ViewPage::render();
//echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js" type="text/javascript"></script>';
//print "<h1>Hello World</h1>";

//print_r(SPPGlobal::get('page'));
?>
<html>

<head>
    <title>Home | Vidyalaya</title>
    <link rel="stylesheet" href="res/css/home.css">
</head>

<body>
    <div class="wrapper">
        <div class="header">
            <div class="logo"><img src="res/img/vslogo.png" alt="Logo"></div>
            <div class="user-info">
                <div class="user-name">Hello User Name!</div>
                <div class="button">Login</div>
                <div class="button">Register</div>
                <div class="button">Logout</div>
            </div>
            <div class="menu">
                <ul>
                    <li class="selected"><a href="#">Home</a></li>
                    <li><a href="#">Student</a></li>
                    <li><a href="#">Staff</a></li>
                    <li><a href="#">Finance</a></li>
                    <li><a href="#">Management</a></li>
                </ul>
            </div>
        </div>
        <div class="left-panel">
            <div class="submenu1">
                <div class="menu-title">Topic</div>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Student</a></li>
                </ul>
            </div>
        </div>
        <div class="main-area">
            <div class="wrapper">
                <div class="title">
                    Vidyalaya
                </div>
                <h1>About page</h1>
            </div>
        </div>
    </div>
</body>

</html>
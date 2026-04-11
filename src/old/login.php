<?php

use SPP\SPPGlobal;
use SPPMod\SPPView\ViewPage;

//ViewPage::render();
//echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js" type="text/javascript"></script>';
//print "<h1>Hello World</h1>";

//print_r(SPPGlobal::get('page'));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Shiksha Vidyala - Sign In</title>
    <link rel="stylesheet" href="res/css/styles.css">
</head>

<body class="signin-body">
    <div class="signin-container">
        <div class="signin-box">
            <img src="logo.png" alt="Virtual Shiksha Vidyala Logo" class="logo">
            <h1>Sign In</h1>
            <form>
                <div class="form-group">
                    <label for="username">Username or Email:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="submit-btn">Sign In</button>
                <p>Don't have an account? <a href="register.html">Register here</a></p>
            </form>
        </div>
    </div>
</body>

</html>
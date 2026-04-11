<?php
// login.php - Login page for Virtual Shiksha Vidyala
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Shiksha Vidyala - Login</title>
    <link rel="stylesheet" href="res/css/styles.css">
</head>

<body>
    <?php 
    //$db=new SPPMod\SPPDB\SPP_DB();
    ?>
    <div class="loading" style="display: none;">Loading...</div>
    <header>
        <div class="header-content">
            <img src="res/img/logo.png" alt="Virtual Shiksha Vidyala Logo" class="logo">
            <div class="user-actions">
                <button onclick="window.location.href='index.php'">Go to Home</button>
            </div>
        </div>
    </header>

    <div class="container login-container">
        <main>
            <h1>Login</h1>
            <p>Please enter your credentials to log in.</p>
            <form class="submission-form" data-action="login">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <div class="form-response"></div>
        </main>
    </div>

    <script src="res/js/script.js"></script>
</body>

</html>
<?php
require_once __DIR__ . '/../../spp/sppinit.php';

// Initialize App Context
\SPP\App::getApp('PremiumBlade');

// 1. Handle Logout
if (isset($_GET['logout'])) {
    \SPP\App::killSession();
    header('Location: PremiumBlade_index.php');
    exit;
}

// 2. Define Form Submission Handler
function login_form_submitted() {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // In a real app, use \SPPMod\SPPAuth\SPPAuth::login($username, $password)
    // For this demo, we'll just mock it if credentials are 'admin'/'password'
    if ($username === 'admin' && $password === 'password') {
        // Mock successful login by setting session manually or using Auth
        // \SPPMod\SPPAuth\SPPAuth::forceLogin(1, 'admin'); 
        echo "<script>alert('Login Successful! (Demo Mock)');</script>";
    } else {
        echo "<script>alert('Invalid Credentials. Use admin/password');</script>";
    }
}

// 3. Process SPP Forms
\SPPMod\SPPView\ViewPage::processForms();

// 4. Render View
$blade = \SPP\App::getApp()->make('blade');
echo $blade->render('index', ['appName' => 'PremiumBlade', 'title' => 'Integrated Blade App']);
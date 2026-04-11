<?php
if (\SPPMod\SPPAuth\SPPAuth::authSessionExists()) {
    // User is logged in, proceed with the page
} else {
    // User is not logged in, redirect to login page
    //header("Location: /vidyalaya/login");
    include_once (\SPPMod\SPPView\Pages::getPage('login')['url']);
    exit;
}
// index.php - Main page for Virtual Shiksha Vidyala
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Shiksha Vidyala - Home</title>
    <link rel="stylesheet" href="res/css/styles.css">
    <link rel="stylesheet" href="res/css/home-styles.css">
</head>

<body>
    <header>
        <div class="header-content">
            <img src="res/img/logo.png" alt="Virtual Shiksha Vidyala Logo" class="logo">
            <div class="user-actions">
                <span>Hello User Name!</span>
                <div class="profile-dropdown">
                    <img src="res/img/profile-icon.png" alt="Profile" class="profile-icon" id="profileIcon">
                    <div class="dropdown-content" id="dropdownContent">
                        <a href="#" data-href="profile.php"><img src="res/img/profile-page-icon.png" alt="Profile Icon" class="menu-icon"> Profile</a>
                        <a href="#" data-href="preferences.php"><img src="res/img/preferences-icon.png" alt="Preferences Icon" class="menu-icon"> Preferences</a>
                        <a href="#" data-href="logout.php"><img src="res/img/logout-icon.png" alt="Logout Icon" class="menu-icon"> Logout</a>
                    </div>
                </div>
                <button>Login</button>
                <button>Register</button>
                <button>Logout</button>
            </div>
        </div>
    </header>

    <nav>
        <ul>
            <li><a href="#" class="nav-link active" data-href="home.php"><img src="res/img/home-icon.png" alt="Home Icon" class="nav-icon"> Home</a></li>
            <li><a href="#" class="nav-link" data-href="student.php" data-sidebar="student-sidebar.php"><img src="res/img/student-icon.png" alt="Student Icon" class="nav-icon"> Student</a></li>
            <li><a href="#" class="nav-link" data-href="staff.php" data-sidebar="staff-sidebar.php"><img src="res/img/staff-icon.png" alt="Staff Icon" class="nav-icon"> Staff</a></li>
            <li><a href="#" class="nav-link" data-href="finance.php" data-sidebar="finance-sidebar.php"><img src="res/img/finance-icon.png" alt="Finance Icon" class="nav-icon"> Finance</a></li>
            <li><a href="#" class="nav-link" data-href="management.php" data-sidebar="management-sidebar.php"><img src="res/img/management-icon.png" alt="Management Icon" class="nav-icon"> Management</a></li>
        </ul>
        <button class="toggle-sidebar-btn" style="display: none;">No Menu</button>
    </nav>

    <div class="container">
        <aside class="sidebar hidden">
            <h2>Sidebar Placeholder</h2>
            <ul class="sidebar-menu">
                <!-- Sidebar content will be loaded here -->
            </ul>
        </aside>

        <main>
            <div class="loading" style="display: none;">Loading...</div>
        </main>
    </div>

    <script src="res/js/script.js"></script>
</body>

</html>
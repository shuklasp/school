<?php

use SPP\SPPGlobal;
use SPPMod\SPPView\ViewPage;

//ViewPage::render();
//echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js" type="text/javascript"></script>';
//print "<h1>Hello World</h1>";

//print_r(SPPGlobal::get('page'));
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Shiksha Vidyala - Home</title>
    <link rel="stylesheet" href="res/css/home-styles.css">
</head>

<body>
    <header>
        <div class="header-content">
            <img src="res/img/logo.png" alt="Virtual Shiksha Vidyala Logo" class="logo">
            <div class="user-actions">
                <span>Hello User Name!</span>
                <button>Login</button>
                <button>Register</button>
                <button>Logout</button>
            </div>
        </div>
    </header>

    <nav>
        <ul>
            <li><a href="#" class="active">Home</a></li>
            <li><a href="about">Student</a></li>
            <li><a href="#">Staff</a></li>
            <li><a href="#">Finance</a></li>
            <li><a href="#">Management</a></li>
        </ul>
    </nav>

    <div class="container">
        <main>
            <h1>Welcome to Virtual Shiksha Vidyalaya</h1>
            <div class="icon-grid">
                <!-- Row 1 -->
                <div class="icon-box">
                    <img src="res/img/student-icon.png" alt="Student" class="icon">
                    <span>Student</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/staff-icon.png" alt="Staff" class="icon">
                    <span>Staff</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/finance-icon.png" alt="Finance" class="icon">
                    <span>Finance</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/management-icon.png" alt="Management" class="icon">
                    <span>Management</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/admission-icon.png" alt="Admission" class="icon">
                    <span>Admission</span>
                </div>

                <!-- Row 2 -->
                <div class="icon-box">
                    <img src="res/img/record-icon.png" alt="Record Entry" class="icon">
                    <span>Record Entry</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/report-icon.png" alt="Reports" class="icon">
                    <span>Reports</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/attendance-icon.png" alt="Attendance" class="icon">
                    <span>Attendance</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/exam-icon.png" alt="Exams" class="icon">
                    <span>Exams</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/library-icon.png" alt="Library" class="icon">
                    <span>Library</span>
                </div>

                <!-- Row 3 -->
                <div class="icon-box">
                    <img src="res/img/timetable-icon.png" alt="Timetable" class="icon">
                    <span>Timetable</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/fee-icon.png" alt="Fees" class="icon">
                    <span>Fees</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/hostel-icon.png" alt="Hostel" class="icon">
                    <span>Hostel</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/transport-icon.png" alt="Transport" class="icon">
                    <span>Transport</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/events-icon.png" alt="Events" class="icon">
                    <span>Events</span>
                </div>

                <!-- Row 4 -->
                <div class="icon-box">
                    <img src="res/img/notification-icon.png" alt="Notifications" class="icon">
                    <span>Notifications</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/profile-icon.png" alt="Profile" class="icon">
                    <span>Profile</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/settings-icon.png" alt="Settings" class="icon">
                    <span>Settings</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/help-icon.png" alt="Help" class="icon">
                    <span>Help</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/contact-icon.png" alt="Contact" class="icon">
                    <span>Contact</span>
                </div>

                <!-- Row 5 -->
                <div class="icon-box">
                    <img src="res/img/calendar-icon.png" alt="Calendar" class="icon">
                    <span>Calendar</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/grades-icon.png" alt="Grades" class="icon">
                    <span>Grades</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/syllabus-icon.png" alt="Syllabus" class="icon">
                    <span>Syllabus</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/resources-icon.png" alt="Resources" class="icon">
                    <span>Resources</span>
                </div>
                <div class="icon-box">
                    <img src="res/img/dashboard-icon.png" alt="Dashboard" class="icon">
                    <span>Dashboard</span>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
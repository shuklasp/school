<?php
// staff-sidebar.php - Sidebar menu for Staff
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Sidebar</title>
</head>

<body>
    <aside>
        <h2>Staff</h2>
        <ul class="sidebar-menu">
            <li>
                <a href="#" class="sidebar-link" data-href="staff-records.php"><img src="res/img/staff-icon.png" alt="Staff Records Icon" class="menu-icon"> Staff Records</a>
                <div class="submenu">
                    <a href="#" class="submenu-link" data-href="add-staff.php"><img src="res/img/add-record-icon.png" alt="Add Staff Icon" class="submenu-icon"> Add Staff</a>
                    <a href="#" class="submenu-link" data-href="view-staff.php"><img src="res/img/view-record-icon.png" alt="View Staff Icon" class="submenu-icon"> View Staff</a>
                </div>
            </li>
            <li>
                <a href="#" class="sidebar-link" data-href="attendance.php"><img src="res/img/attendance-icon.png" alt="Attendance Icon" class="menu-icon"> Attendance</a>
                <div class="submenu">
                    <a href="#" class="submenu-link" data-href="mark-attendance.php"><img src="res/img/mark-attendance-icon.png" alt="Mark Attendance Icon" class="submenu-icon"> Mark Attendance</a>
                    <a href="#" class="submenu-link" data-href="view-attendance.php"><img src="res/img/view-attendance-icon.png" alt="View Attendance Icon" class="submenu-icon"> View Attendance</a>
                </div>
            </li>
        </ul>
    </aside>
</body>

</html>
<?php
// student-sidebar.php - Sidebar menu for Student
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sidebar</title>
</head>

<body>
    <aside>
        <h2>Student</h2>
        <ul class="sidebar-menu">
            <li>
                <a href="#" class="sidebar-link" data-href="admission.php"><img src="res/img/admission-icon.png" alt="Admission Icon" class="menu-icon"> Admission</a>
                <div class="submenu">
                    <a href="#" class="submenu-link" data-href="new-admission.php"><img src="res/img/new-admission-icon.png" alt="New Admission Icon" class="submenu-icon"> New Admission</a>
                    <a href="#" class="submenu-link" data-href="admission-status.php"><img src="res/img/admission-status-icon.png" alt="Admission Status Icon" class="submenu-icon"> Admission Status</a>
                </div>
            </li>
            <li>
                <a href="#" class="sidebar-link" data-href="record-entry.php"><img src="res/img/record-entry-icon.png" alt="Record Entry Icon" class="menu-icon"> Record Entry</a>
                <div class="submenu">
                    <a href="#" class="submenu-link" data-href="add-record.php"><img src="res/img/add-record-icon.png" alt="Add Record Icon" class="submenu-icon"> Add Record</a>
                    <a href="#" class="submenu-link" data-href="view-record.php"><img src="res/img/view-record-icon.png" alt="View Record Icon" class="submenu-icon"> View Record</a>
                </div>
            </li>
            <li>
                <a href="#" class="sidebar-link" data-href="reports.php"><img src="res/img/reports-icon.png" alt="Reports Icon" class="menu-icon"> Reports</a>
                <div class="submenu">
                    <a href="#" class="submenu-link" data-href="generate-report.php"><img src="res/img/generate-report-icon.png" alt="Generate Report Icon" class="submenu-icon"> Generate Report</a>
                    <a href="#" class="submenu-link" data-href="download-report.php"><img src="res/img/download-report-icon.png" alt="Download Report Icon" class="submenu-icon"> Download Report</a>
                </div>
            </li>
        </ul>
    </aside>
</body>

</html>
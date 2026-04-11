<?php
// management-sidebar.php - Sidebar menu for Management
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Sidebar</title>
</head>

<body>
    <aside>
        <h2>Management</h2>
        <ul class="sidebar-menu">
            <li>
                <a href="#" class="sidebar-link" data-href="events.php"><img src="res/img/events-icon.png" alt="Events Icon" class="menu-icon"> Events</a>
                <div class="submenu">
                    <a href="#" class="submenu-link" data-href="add-event.php"><img src="res/img/add-event-icon.png" alt="Add Event Icon" class="submenu-icon"> Add Event</a>
                    <a href="#" class="submenu-link" data-href="view-events.php"><img src="res/img/view-events-icon.png" alt="View Events Icon" class="submenu-icon"> View Events</a>
                </div>
            </li>
            <li>
                <a href="#" class="sidebar-link" data-href="announcements.php"><img src="res/img/announcements-icon.png" alt="Announcements Icon" class="menu-icon"> Announcements</a>
                <div class="submenu">
                    <a href="#" class="submenu-link" data-href="add-announcement.php"><img src="res/img/add-announcement-icon.png" alt="Add Announcement Icon" class="submenu-icon"> Add Announcement</a>
                    <a href="#" class="submenu-link" data-href="view-announcements.php"><img src="res/img/view-announcements-icon.png" alt="View Announcements Icon" class="submenu-icon"> View Announcements</a>
                </div>
            </li>
        </ul>
    </aside>
</body>

</html>
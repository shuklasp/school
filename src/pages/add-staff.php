<?php
// add-staff.php - Main content for Add Staff
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Shiksha Vidyala - Add Staff</title>
</head>

<body>
    <main>
        <h1>Add Staff</h1>
        <p>Add a new staff member here.</p>
        <form class="submission-form" data-action="add-staff">
            <div class="form-group">
                <label for="staff-name">Staff Name:</label>
                <input type="text" id="staff-name" name="staff_name" required>
            </div>
            <div class="form-group">
                <label for="staff-role">Role:</label>
                <input type="text" id="staff-role" name="staff_role" required>
            </div>
            <button type="submit">Add Staff</button>
        </form>
        <div class="form-response"></div>
    </main>
</body>

</html>
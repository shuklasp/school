<?php
// add-record.php - Main content for Add Record
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Shiksha Vidyala - Add Record</title>
</head>

<body>
    <main>
        <h1>Add Record</h1>
        <p>Add a new student record here.</p>
        <form class="submission-form" data-action="add-record">
            <div class="form-group">
                <label for="record-id">Record ID:</label>
                <input type="text" id="record-id" name="record_id" required>
            </div>
            <div class="form-group">
                <label for="record-student-name">Student Name:</label>
                <input type="text" id="record-student-name" name="student_name" required>
            </div>
            <div class="form-group">
                <label for="record-description">Description:</label>
                <textarea id="record-description" name="description" required></textarea>
            </div>
            <button type="submit">Add Record</button>
        </form>
        <div class="form-response"></div>
    </main>
</body>

</html>
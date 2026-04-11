<?php
// new-admission.php - Main content for New Admission
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Shiksha Vidyala - New Admission</title>
</head>

<body>
    <main>
        <h1>New Admission</h1>
        <p>Add a new student admission here.</p>
        <form class="submission-form" data-action="new-admission">
            <div class="form-group">
                <label for="student-name">Student Name:</label>
                <input type="text" id="student-name" name="student_name" required>
            </div>
            <div class="form-group">
                <label for="student-age">Age:</label>
                <input type="number" id="student-age" name="student_age" required>
            </div>
            <div class="form-group">
                <label for="student-grade">Grade:</label>
                <input type="text" id="student-grade" name="student_grade" required>
            </div>
            <button type="submit">Submit Admission</button>
        </form>
        <div class="form-response"></div>
    </main>
</body>

</html>
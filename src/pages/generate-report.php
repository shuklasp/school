<?php
// generate-report.php - Main content for Generate Report
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Shiksha Vidyala - Generate Report</title>
</head>

<body>
    <main>
        <h1>Generate Report</h1>
        <p>Generate a new report here.</p>
        <form class="submission-form" data-action="generate-report">
            <div class="form-group">
                <label for="report-type">Report Type:</label>
                <select id="report-type" name="report_type" required>
                    <option value="student">Student Report</option>
                    <option value="finance">Finance Report</option>
                    <option value="attendance">Attendance Report</option>
                </select>
            </div>
            <div class="form-group">
                <label for="start-date">Start Date:</label>
                <input type="date" id="start-date" name="start_date" required>
            </div>
            <div class="form-group">
                <label for="end-date">End Date:</label>
                <input type="date" id="end-date" name="end_date" required>
            </div>
            <button type="submit">Generate Report</button>
        </form>
        <div class="form-response"></div>
    </main>
</body>

</html>
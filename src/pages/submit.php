<?php
// submit.php - Backend handler for form submissions

// Set the content type to JSON
header('Content-Type: application/json');

// Simulate a database or storage (in a real app, you'd use a database like MySQL)
$response = ['success' => false, 'message' => '', 'data' => []];

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the action from the form
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    switch ($action) {
        case 'login':
            // Process login form
            $username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
            $password = isset($_POST['password']) ? htmlspecialchars($_POST['password']) : '';

            // Simulate authentication (in a real app, check against a database)
            if ($username === 'admin' && $password === 'password123') {
                // In a real app, you'd start a session and set session variables
                $response['success'] = true;
                $response['message'] = 'Login successful! Redirecting to dashboard...';
                $response['data'] = [
                    'redirect_url' => 'index.php' // Redirect to the main dashboard
                ];
            } else {
                $response['message'] = 'Invalid username or password.';
            }
            break;

        case 'new-admission':
            // Process new admission form
            $student_name = isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : '';
            $student_age = isset($_POST['student_age']) ? (int)$_POST['student_age'] : 0;
            $student_grade = isset($_POST['student_grade']) ? htmlspecialchars($_POST['student_grade']) : '';

            if ($student_name && $student_age && $student_grade) {
                // Simulate saving to a database
                $response['success'] = true;
                $response['message'] = "New admission added successfully for $student_name (Age: $student_age, Grade: $student_grade).";
                $response['data'] = [
                    'student_name' => $student_name,
                    'student_age' => $student_age,
                    'student_grade' => $student_grade
                ];
            } else {
                $response['message'] = 'Please fill in all required fields for new admission.';
            }
            break;

        case 'add-record':
            // Process add record form
            $record_id = isset($_POST['record_id']) ? htmlspecialchars($_POST['record_id']) : '';
            $student_name = isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : '';
            $description = isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '';

            if ($record_id && $student_name && $description) {
                // Simulate saving to a database
                $response['success'] = true;
                $response['message'] = "Record added successfully (ID: $record_id, Student: $student_name).";
                $response['data'] = [
                    'record_id' => $record_id,
                    'student_name' => $student_name,
                    'description' => $description
                ];
            } else {
                $response['message'] = 'Please fill in all required fields for adding a record.';
            }
            break;

        case 'generate-report':
            // Process generate report form
            $report_type = isset($_POST['report_type']) ? htmlspecialchars($_POST['report_type']) : '';
            $start_date = isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : '';
            $end_date = isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : '';

            if ($report_type && $start_date && $end_date) {
                // Simulate generating a report
                $response['success'] = true;
                $response['message'] = "Report generated successfully (Type: $report_type, From: $start_date, To: $end_date).";
                $response['data'] = [
                    'report_type' => $report_type,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'report_url' => 'download-report.php?type=' . urlencode($report_type) // Simulate a link to download the report
                ];
            } else {
                $response['message'] = 'Please fill in all required fields for generating a report.';
            }
            break;

        default:
            $response['message'] = 'Invalid action specified.';
            break;
    }
} else {
    $response['message'] = 'Invalid request method. Please use POST.';
}

// Return the response as JSON
echo json_encode($response);
exit;

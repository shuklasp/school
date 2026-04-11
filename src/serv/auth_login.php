<?php
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true) ?? [];

$username = $data['username'] ?? $_POST['username'] ?? '';
$password = $data['password'] ?? $_POST['password'] ?? '';
$tenant   = $data['tenant'] ?? $_POST['tenant'] ?? '';

if ($username === 'admin' && $password === 'admin123') {
    echo json_encode([
        'status' => 'redirect',
        'message' => 'Login successful',
        'redirect_url' => '/school1/src/pages/dashboard.html'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid username or password.'
    ]);
}

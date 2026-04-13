<?php
require_once('sppinit.php');
require_once('admin/api.php'); // Note: This might exit or print depending on structure. 
// Actually api.php does not wrap in a function. 

function test_save_user($postData) {
    $_POST = $postData;
    $_GET = ['action' => 'save_user'];
    // Captured output
    ob_start();
    try {
        include('admin/api.php');
    } catch (\Exception $e) { echo $e->getMessage(); }
    return ob_get_clean();
}

echo "--- Test Save User (New) ---\n";
echo test_save_user(['username' => 'testuser', 'password' => 'pass123']);

echo "\n--- Test Save User (Missing Name) ---\n";
echo test_save_user(['email' => 'test@test.com']);

echo "\n--- Done ---\n";

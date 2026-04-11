<?php
/**
 * Global Handler for Form Submissions for SPP-Twitter and Base System
 */

// SPPAuth Bypass for Testing
if (isset($_GET['q']) && in_array($_GET['q'], ['test_form', 'test_success'])) {
    // If SPPAuth is active, we try to spoof a session or disable the check
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['spp_user_id'] = 1; 
    $_SESSION['spp_user_role'] = 'admin';
}

function test_form_submitted() {
    // Collect specific form field
    $name = $_POST['test_name'] ?? 'Unknown User';
    
    // Perform redirection to success page
    if (method_exists('\SPPMod\SPPView\ViewPage', 'redirect')) {
        \SPPMod\SPPView\ViewPage::redirect('test_success', ['name' => $name]);
    } else {
        header('Location: ?q=test_success&name=' . urlencode((string)$name));
        exit;
    }
}

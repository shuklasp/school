<?php
$_GET['action'] = 'list_group_members';
$_GET['group_id'] = 'studentclass';

// Mock session
session_start();
$_SESSION['spp_admin_user'] = 'admin';

define('BYPASS_AUTH', true); // Use this to skip auth check if I modify api.php temporarily

require_once __DIR__ . '/spp/admin/api.php';

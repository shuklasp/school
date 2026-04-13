<?php
namespace {
    require_once __DIR__ . '/spp/sppinit.php';
    require_once __DIR__ . '/spp/modules/spp/sppgroup/class.sppgrouploader.php';
    require_once __DIR__ . '/spp/modules/spp/sppgroup/class.sppgroup.php';

    $_GET['action'] = 'list_group_members';
    $_GET['group_id'] = 'studentclass';

    // Mock session manually
    session_start();
    $_SESSION['spp_admin_user'] = 'admin';

    // Capture Output
    ob_start();
    require_once __DIR__ . '/spp/admin/api.php';
    $output = ob_get_clean();
    echo $output;
}

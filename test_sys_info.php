<?php
$_GET['action'] = 'get_system_info';
ob_start();
require_once 'spp/admin/api.php';
$output = ob_get_clean();
$data = json_decode($output, true);

echo "API Verification: get_system_info\n";
echo "===============================\n";

if ($data && $data['success']) {
    echo "SUCCESS: API returned data.\n";
    echo "SPP Version: " . $data['data']['spp_version'] . "\n";
    echo "Apps Count: " . $data['data']['stats']['apps'] . "\n";
    echo "Modules Count: " . $data['data']['stats']['modules'] . "\n";
    echo "Entities Count: " . $data['data']['stats']['entities'] . "\n";
    echo "Forms Count: " . $data['data']['stats']['forms'] . "\n";
} else {
    echo "FAILURE: API returned error or invalid JSON.\n";
    echo "Output: " . $output . "\n";
}
echo "\nFinished.\n";

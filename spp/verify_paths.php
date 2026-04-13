<?php
require_once('sppinit.php');
echo "SPP_BASE_DIR: " . SPP_BASE_DIR . "\n";
echo "SPP_APP_DIR: " . SPP_APP_DIR . "\n";
echo "APP_ETC_DIR: " . APP_ETC_DIR . "\n";
echo "Searching for user_edit.yml...\n";
$path = APP_ETC_DIR . SPP_DS . 'admin' . SPP_DS . 'forms' . SPP_DS . 'user_edit.yml';
echo "Expected Path: " . $path . "\n";
if (file_exists($path)) {
    echo "EXISTS\n";
} else {
    echo "MISSING\n";
    // Search alternative
    $alt = SPP_BASE_DIR . SPP_DS . 'etc' . SPP_DS . 'apps' . SPP_DS . 'admin' . SPP_DS . 'forms' . SPP_DS . 'user_edit.yml';
    echo "Alt Path: " . $alt . "\n";
    if (file_exists($alt)) echo "ALT EXISTS\n";
}

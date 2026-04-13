<?php
/**
 * debug_api_v2.php
 * Mocks a logged-in session and calls api.php to find the 500 error.
 */

// We need to define namespace and classes for deserialization
namespace SPPMod\SPPAuth {
    class SPPUserSession {
        public $username = 'admin';
        public function isValid($t = false) { return true; }
    }
}

namespace {
    define('SPP_CLI_DEBUG', true);
    session_start();
    $_SESSION['__sppauth__'] = serialize(new \SPPMod\SPPAuth\SPPUserSession());

    $_REQUEST = ['action' => 'list_modules', 'appname' => 'demo'];
    $_GET = $_REQUEST;
    $_POST = [];

    // Capture output
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once 'spp/admin/api.php';
}

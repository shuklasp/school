<?php
require_once 'spp/sppinit.php';
$appname = 'demo';
$appDir = SPP_APP_DIR . SPP_DS . 'etc' . SPP_DS . 'apps' . SPP_DS . $appname;
if (is_dir($appDir)) {
    try {
        echo "Creating App instance for $appname...\n";
        new \SPP\App($appname, false, 1);
        echo "Registering context...\n";
        \SPP\Scheduler::setContext($appname);
        echo "Context set to " . \SPP\Scheduler::getContext() . "\n";
    } catch (\Throwable $e) {
        echo "Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo $e->getTraceAsString();
    }
} else {
    echo "Directory $appDir not found\n";
}

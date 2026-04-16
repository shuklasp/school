<?php
require_once __DIR__ . '/spp/sppinit.php';

echo "Testing Polyglot Bridge execution...\n";

// Ensure we are in the right directory for Python path discovery
$pythonLibDir = SPP_BASE_DIR . '/../var/shared/bridge';
// Add to PYTHONPATH so dispatch.py can find test_lib
putenv("PYTHONPATH=" . $pythonLibDir . PATH_SEPARATOR . getenv("PYTHONPATH"));

echo "Calling test_lib.add(5, 7) in Python...\n";
$res = \SPP\PolyglotBridge::call('python', 'test_lib', 'add', [5, 7]);

if ($res['success']) {
    echo "Result: " . $res['data'] . " (Expected: 12)\n";
} else {
    echo "Error: " . $res['error'] . "\n";
}

echo "\nCalling test_lib.greet('Satya') in Python...\n";
$res = \SPP\PolyglotBridge::call('python', 'test_lib', 'greet', ['Satya']);

if ($res['success']) {
    echo "Result: " . $res['data'] . "\n";
} else {
    echo "Error: " . $res['error'] . "\n";
}

<?php
/**
 * Registry Benchmarking Script
 */
require_once dirname(__DIR__) . '/sppinit.php';

use SPP\Registry;

// 1. Setup sample data
echo "Registering 1000 entities...\n";
for ($i = 0; $i < 1000; $i++) {
    Registry::register("category=>sub=>item_$i", "value_$i");
}

$iterations = 50000;
$searchKey = "category=>sub=>item_500";

echo "Running $iterations iterations for key: $searchKey\n";

// Warm up
Registry::get($searchKey);

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $val = Registry::get($searchKey);
}
$end = microtime(true);

$time = $end - $start;
echo "Total Time: " . number_format($time, 4) . "s\n";
echo "Avg Time per call: " . number_format(($time / $iterations) * 1000000, 4) . "μs\n";

// 2. Verify integrity
if (Registry::get($searchKey) === "value_500") {
    echo "Integrity Check: PASSED\n";
} else {
    echo "Integrity Check: FAILED (Value mismatch)\n";
}

// 3. Verify context isolation
// Register a dummy app to allow context switching
if (!\SPP\Registry::isRegistered('__apps=>demo=>status')) {
    new \SPP\App('demo', false, 1); // init_level 1 registers with scheduler
}

\SPP\Scheduler::setContext('demo');
Registry::register("ctx_item", "ctx_val");
if (Registry::get("ctx_item") === "ctx_val") {
    echo "Context Registration: PASSED\n";
}

\SPP\Scheduler::setContext('default');
if (Registry::get("ctx_item") === false) {
    echo "Context Isolation: PASSED\n";
} else {
    echo "Context Isolation: FAILED (Leakage detected)\n";
}

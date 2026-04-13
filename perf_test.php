<?php
$start = microtime(true);

require_once 'spp/sppinit.php';
$init_done = microtime(true);

echo "Framework Init: " . round(($init_done - $start) * 1000, 2) . "ms\n";

$start_mod = microtime(true);
\SPP\Module::loadAllModules();
$mod_done = microtime(true);
echo "Module Load (Second Call): " . round(($mod_done - $start_mod) * 1000, 2) . "ms\n";

$start_events = microtime(true);
\SPP\SPPEvent::scanHandlers();
$events_done = microtime(true);
echo "Event Scan: " . round(($events_done - $start_events) * 1000, 2) . "ms\n";

echo "Total Time: " . round((microtime(true) - $start) * 1000, 2) . "ms\n";

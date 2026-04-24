<?php
$dir = dirname(__DIR__) . '/spp/commands';
foreach (glob("$dir/*.php") as $file) {
    $content = file_get_contents($file);
    $newContent = str_replace('\$', '$', $content);
    if ($content !== $newContent) {
        file_put_contents($file, $newContent);
        echo "Fixed " . basename($file) . "\n";
    }
}

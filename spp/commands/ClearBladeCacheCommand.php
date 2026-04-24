<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class ClearBladeCacheCommand
 * Clears the compiled Blade view cache.
 */
class ClearBladeCacheCommand extends Command
{
    protected string $name = 'blade:clear';
    protected string $description = 'Clear the compiled Blade view cache';

    public function execute(array $args): void
    {
        $cacheDir = SPP_APP_DIR . '/var/cache';
        
        if (!is_dir($cacheDir)) {
            echo "Cache directory not found: {$cacheDir}\n";
            return;
        }

        echo "Clearing Blade view cache...\n";
        
        $files = $this->recursiveScan($cacheDir);
        $count = 0;

        foreach ($files as $file) {
            if (is_file($file) && (str_ends_with($file, '.php') || basename($file) !== '.gitignore')) {
                unlink($file);
                $count++;
            }
        }

        echo "Success: Removed {$count} compiled view files.\n";
    }

    private function recursiveScan(string $dir): array
    {
        $files = [];
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $files = array_merge($files, $this->recursiveScan($path));
            } else {
                $files[] = $path;
            }
        }
        return $files;
    }
}

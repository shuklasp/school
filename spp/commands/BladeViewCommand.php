<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class BladeViewCommand
 * Manages Blade views within an SPP application.
 */
class BladeViewCommand extends Command
{
    protected string $name = 'blade:view';
    protected string $description = 'Manage Blade views (list, create, delete)';

    public function execute(array $args): void
    {
        $action = $args[2] ?? 'list';
        $appName = \SPP\Scheduler::getContext();

        if ($appName === 'default' || !$appName) {
            echo "Error: Please set an application context first (or use -a <app>).\n";
            return;
        }

        $viewsDir = SPP_APP_DIR . "/resources/{$appName}/views";

        switch ($action) {
            case 'list':
                $this->listViews($viewsDir);
                break;
            case 'create':
                $viewName = $args[3] ?? null;
                if (!$viewName) die("Usage: php spp.php blade:view create <name>\n");
                $this->createView($viewsDir, $viewName);
                break;
            case 'delete':
                $viewName = $args[3] ?? null;
                if (!$viewName) die("Usage: php spp.php blade:view delete <name>\n");
                $this->deleteView($viewsDir, $viewName);
                break;
            default:
                echo "Unknown action: {$action}. Available: list, create, delete\n";
        }
    }

    protected function listViews(string $dir): void
    {
        if (!is_dir($dir)) {
            echo "Views directory not found: {$dir}\n";
            return;
        }
        echo "Blade Views for current context:\n";
        $files = glob($dir . '/*.blade.php');
        foreach ($files as $f) {
            echo "  - " . basename($f, '.blade.php') . "\n";
        }
    }

    protected function createView(string $dir, string $name): void
    {
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $file = $dir . '/' . str_replace('.', '/', $name) . '.blade.php';
        if (file_exists($file)) {
            echo "Error: View '{$name}' already exists.\n";
            return;
        }
        $parent = dirname($file);
        if (!is_dir($parent)) mkdir($parent, 0777, true);
        
        file_put_contents($file, "<!-- View: {$name} -->\n<div class='view-content'>\n    <h1>{$name}</h1>\n    <p>New blade view created via SPP CLI.</p>\n</div>");
        echo "Success: View '{$name}' created at {$file}\n";
    }

    protected function deleteView(string $dir, string $name): void
    {
        $file = $dir . '/' . str_replace('.', '/', $name) . '.blade.php';
        if (!file_exists($file)) {
            echo "Error: View '{$name}' not found.\n";
            return;
        }
        unlink($file);
        echo "Success: View '{$name}' deleted.\n";
    }
}

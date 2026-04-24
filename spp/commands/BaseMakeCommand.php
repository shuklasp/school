<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Abstract Class BaseMakeCommand
 * Provides common functionality for scaffolding commands.
 */
abstract class BaseMakeCommand extends Command
{
    /**
     * Resolve the target directory based on context.
     */
    protected function getTargetDir(string $subDir, string $app = 'default'): string
    {
        if ($app === 'default') {
            return SPP_APP_DIR . "/spp/{$subDir}";
        }
        
        // Ensure components are always in the src/ directory, not resources/
        if ($subDir === 'comp') {
            return SPP_APP_DIR . "/src/{$app}/comp";
        }

        return SPP_APP_DIR . "/src/{$app}/{$subDir}";
    }

    /**
     * Resolve the namespace based on context.
     */
    protected function getNamespace(string $subNamespace, string $app = 'default'): string
    {
        if ($app === 'default') {
            return "SPP\\" . ucfirst($subNamespace);
        }
        return "App\\" . ucfirst($app) . "\\" . ucfirst($subNamespace);
    }

    /**
     * Build the file from a stub.
     */
    protected function buildFromStub(string $stubName, string $targetPath, array $replacements): bool
    {
        $stubPath = __DIR__ . "/stubs/{$stubName}.stub";
        if (!file_exists($stubPath)) {
            echo "Error: Stub '{$stubName}' not found.\n";
            return false;
        }

        $content = file_get_contents($stubPath);
        foreach ($replacements as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (file_exists($targetPath)) {
            echo "Error: File already exists at {$targetPath}\n";
            return false;
        }

        file_put_contents($targetPath, $content);
        return true;
    }

    /**
     * Helper to get application context from arguments.
     */
    protected function getContext(array $args): string
    {
        foreach ($args as $arg) {
            if (strpos($arg, '--app=') === 0) {
                return substr($arg, 6);
            }
        }
        return \SPP\Scheduler::getContext() ?: 'default';
    }
}

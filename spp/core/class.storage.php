<?php
namespace SPP\Core;

/**
 * Class Storage
 * Facade for filesystem operations in the SPP framework.
 */
class Storage
{
    protected static array $disks = [];

    /**
     * Get a disk instance.
     */
    public static function disk(string $name = 'local'): DiskInterface
    {
        if (isset(self::$disks[$name])) {
            return self::$disks[$name];
        }

        // For now, only local disk is supported
        $disk = new LocalDisk();
        self::$disks[$name] = $disk;
        
        return $disk;
    }

    /**
     * Proxy calls to the default disk.
     */
    public static function __callStatic($method, $args)
    {
        return self::disk()->$method(...$args);
    }
}

/**
 * Interface DiskInterface
 */
interface DiskInterface
{
    public function get(string $path): ?string;
    public function put(string $path, string $contents): bool;
    public function exists(string $path): bool;
    public function delete(string $path): bool;
}

/**
 * Class LocalDisk
 */
class LocalDisk implements DiskInterface
{
    protected string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = $basePath ?: (defined('SPP_APP_DIR') ? SPP_APP_DIR . '/var/storage' : '');
        if ($this->basePath && !is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }

    public function get(string $path): ?string
    {
        $fullPath = $this->getFullPath($path);
        return file_exists($fullPath) ? file_get_contents($fullPath) : null;
    }

    public function put(string $path, string $contents): bool
    {
        $fullPath = $this->getFullPath($path);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return file_put_contents($fullPath, $contents) !== false;
    }

    public function exists(string $path): bool
    {
        return file_exists($this->getFullPath($path));
    }

    public function delete(string $path): bool
    {
        return @unlink($this->getFullPath($path));
    }

    protected function getFullPath(string $path): string
    {
        return $this->basePath . SPP_DS . ltrim($path, SPP_DS);
    }
}

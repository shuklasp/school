<?php
namespace SPP\Core;

/**
 * Class FileCache
 * High-performance filesystem-based cache driver.
 */
class FileCache implements CacheInterface
{
    protected string $path;

    public function __construct(string $path = null)
    {
        $this->path = $path ?: (defined('SPP_APP_DIR') ? SPP_APP_DIR . '/var/cache' : 'var/cache');
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    public function get(string $key)
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) return null;

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data['expires'] !== 0 && time() > $data['expires']) {
            $this->delete($key);
            return null;
        }

        return $data['value'];
    }

    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $file = $this->getFilePath($key);
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $data = [
            'expires' => ($ttl === 0) ? 0 : time() + $ttl,
            'value'   => $value
        ];

        // Atomic write via temporary file
        $tempFile = $file . '.' . uniqid('', true) . '.tmp';
        if (file_put_contents($tempFile, serialize($data), LOCK_EX) === false) {
            return false;
        }

        return rename($tempFile, $file);
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        return file_exists($file) ? @unlink($file) : true;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function clear(): bool
    {
        return $this->recursiveRemoveDir($this->path);
    }

    protected function getFilePath(string $key): string
    {
        $hash = md5($key);
        // 2-level directory structure for performance
        return $this->path . SPP_DS . substr($hash, 0, 2) . SPP_DS . substr($hash, 2, 2) . SPP_DS . $hash . '.cache';
    }

    private function recursiveRemoveDir($dir): bool
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveRemoveDir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}

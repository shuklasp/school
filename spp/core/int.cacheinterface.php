<?php
namespace SPP;

/**
 * Interface CacheInterface
 * Standard contract for framework cache drivers.
 */
interface CacheInterface
{
    /**
     * Retrieve a value from the cache.
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key);

    /**
     * Store a value in the cache.
     * @param string $key
     * @param mixed $value
     * @param int $ttl Time-to-live in seconds.
     * @return bool
     */
    public function set(string $key, $value, int $ttl = 3600): bool;

    /**
     * Remove a value from the cache.
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Clear all values from the cache.
     * @return bool
     */
    public function clear(): bool;

    /**
     * Check if a key exists in the cache.
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;
}

<?php
namespace SPP;

use SPP\Core\CacheInterface;
use SPP\Core\FileCache;
use SPP\Core\RedisCache;

/**
 * Class Cache
 * The primary facade for framework caching.
 * Automatically chooses between Redis and File drivers based on configuration and availability.
 */
class Cache extends \SPP\SPPObject
{
    /** @var CacheInterface|null */
    private static $driver = null;

    /**
     * Get the active cache driver.
     * 
     * @return CacheInterface
     */
    public static function driver(): CacheInterface
    {
        if (self::$driver !== null) {
            return self::$driver;
        }

        $redisEnabled = \SPP\Module::getConfig('enabled', 'redis') === true;

        if ($redisEnabled && RedisCache::isAvailable()) {
            self::$driver = new RedisCache();
        } else {
            // Fallback to optimized FileCache
            self::$driver = new FileCache();
        }

        return self::$driver;
    }

    /**
     * Facade methods
     */
    public static function get(string $key)
    {
        return self::driver()->get($key);
    }

    public static function set(string $key, $value, int $ttl = 3600): bool
    {
        return self::driver()->set($key, $value, $ttl);
    }

    public static function delete(string $key): bool
    {
        return self::driver()->delete($key);
    }

    public static function has(string $key): bool
    {
        return self::driver()->has($key);
    }

    public static function clear(): bool
    {
        return self::driver()->clear();
    }
}

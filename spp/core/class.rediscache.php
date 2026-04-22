<?php
namespace SPP;

/**
 * Class RedisCache
 * Enterprise-grade Redis wrapper for SPP.
 * Handles distributed caching and session state.
 */
class RedisCache extends \SPP\SPPObject implements CacheInterface
{
    /** @var \Redis */
    private static $instance = null;

    /**
     * Check if Redis is usable in the current environment.
     */
    public static function isAvailable(): bool
    {
        if (!class_exists('\Redis')) return false;
        
        try {
            $redis = self::getConnection();
            return $redis->ping() === '+PONG' || $redis->ping() === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get a connected Redis instance.
     * 
     * @param string $type The usage type (e.g., 'cache', 'audit') to determine DB index.
     * @return \Redis
     */
    public static function getConnection(string $type = 'cache'): \Redis
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = \SPP\Module::getConfig('host', 'redis') ?: '127.0.0.1';
        $port = \SPP\Module::getConfig('port', 'redis') ?: 6379;
        $password = \SPP\Module::getConfig('password', 'redis');
        
        // Logic: "store redis data according to config file setting in same db by default, 
        // but in different db if set in the config file."
        $defaultDb = (int)(\SPP\Module::getConfig('db', 'redis') ?: 0);
        $specificDb = \SPP\Module::getConfig($type . '_db', 'redis');
        $dbIndex = ($specificDb !== false) ? (int)$specificDb : $defaultDb;

        $redis = new \Redis();
        if (!$redis->connect($host, $port)) {
            throw new \Exception("Could not connect to Redis at {$host}:{$port}");
        }

        if ($password) {
            $redis->auth($password);
        }

        if ($dbIndex > 0) {
            $redis->select($dbIndex);
        }

        self::$instance = $redis;
        return $redis;
    }

    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $redis = self::getConnection();
        return $redis->set($key, serialize($value), $ttl);
    }

    public function get(string $key)
    {
        $redis = self::getConnection();
        $val = $redis->get($key);
        return ($val === false) ? null : unserialize($val);
    }

    public function delete(string $key): bool
    {
        $redis = self::getConnection();
        return (bool)$redis->del($key);
    }

    public function has(string $key): bool
    {
        $redis = self::getConnection();
        return $redis->exists($key);
    }

    public function clear(): bool
    {
        $redis = self::getConnection();
        return $redis->flushDB();
    }
}

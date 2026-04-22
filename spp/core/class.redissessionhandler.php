<?php
namespace SPP\Core;

use SPP\RedisCache;

/**
 * Class RedisSessionHandler
 * Custom session handler for storing sessions in Redis.
 */
class RedisSessionHandler implements \SessionHandlerInterface
{
    /** @var \Redis */
    private $redis;
    private $ttl;
    private $prefix = 'spp_sess:';

    public function __construct()
    {
        $this->redis = RedisCache::getConnection();
        $this->ttl = ini_get('session.gc_maxlifetime') ?: 3600;
        $this->prefix = \SPP\Module::getConfig('prefix', 'redis') ?: 'spp_sess:';
    }

    public function open($path, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string
    {
        $data = $this->redis->get($this->prefix . $id);
        return $data ?: '';
    }

    public function write($id, $data): bool
    {
        return $this->redis->setex($this->prefix . $id, $this->ttl, $data);
    }

    public function destroy($id): bool
    {
        return (bool)$this->redis->del($this->prefix . $id);
    }

    public function gc($max_lifetime): int|false
    {
        // Redis handles expiration automatically via setex/TTL
        return 0;
    }
}

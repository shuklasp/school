<?php
namespace SPP\Core;

/**
 * Class Debug
 * Collects diagnostic data for the SPP Debug Bar.
 */
class Debug
{
    protected static array $logs = [];
    protected static array $queries = [];
    protected static float $startTime;

    /**
     * Start the timer.
     */
    public static function start()
    {
        self::$startTime = microtime(true);
    }

    /**
     * Log a message for debugging.
     */
    public static function log(string $message, string $type = 'info')
    {
        self::$logs[] = [
            'time' => microtime(true) - self::$startTime,
            'message' => $message,
            'type' => $type
        ];
    }

    /**
     * Record a database query.
     */
    public static function query(string $sql, float $duration)
    {
        self::$queries[] = [
            'sql' => $sql,
            'duration' => $duration
        ];
    }

    /**
     * Get all collected data.
     */
    public static function getData(): array
    {
        return [
            'execution_time' => microtime(true) - self::$startTime,
            'memory_usage' => memory_get_usage(true),
            'logs' => self::$logs,
            'queries' => self::$queries,
            'context' => \SPP\Scheduler::getContext()
        ];
    }
}

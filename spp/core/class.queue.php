<?php
namespace SPP\Core;

/**
 * Abstract Class Job
 * Base class for all background jobs in the SPP framework.
 */
abstract class Job
{
    /** @var array Data passed to the job */
    protected array $data = [];

    /**
     * Job constructor.
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    abstract public function handle(): void;

    /**
     * Get job data.
     */
    public function getData(): array
    {
        return $this->data;
    }
}

/**
 * Class Queue
 * Manages the background job queue.
 */
class Queue
{
    protected static string $table = 'spp_jobs';

    /**
     * Dispatch a job to the queue.
     */
    public static function push(Job $job, int $delay = 0): bool
    {
        $payload = serialize($job);
        
        // Use standard DB connection through SPPEntity or direct SQL
        // MOCK: For this implementation, we'll use a simple file-based queue if DB table isn't ready
        // But for "General Purpose", we'll assume a DB is available.
        
        try {
            // Logic to insert into 'spp_jobs' table
            // INSERT INTO spp_jobs (payload, available_at, created_at) VALUES (...)
            return true; 
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Peek at the next job in the queue.
     */
    public static function pop(): ?Job
    {
        // SELECT * FROM spp_jobs WHERE available_at <= NOW() LIMIT 1
        return null;
    }
}

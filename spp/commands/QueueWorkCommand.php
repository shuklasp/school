<?php
namespace SPP\CLI\Commands;

use SPP\CLI\Command;
use SPP\Core\Queue;

/**
 * Class QueueWorkCommand
 * CLI Worker for processing background jobs.
 */
class QueueWorkCommand extends Command
{
    protected string $name = 'queue:work';
    protected string $description = 'Starts a worker loop to process background jobs from the queue.';

    public function execute(array $args): void
    {
        echo "SPP Queue Worker started...\n";
        echo "Press Ctrl+C to stop.\n\n";

        while (true) {
            $job = Queue::pop();

            if ($job) {
                echo "[ " . date('Y-m-d H:i:s') . " ] Processing: " . get_class($job) . "\n";
                try {
                    $job->handle();
                    echo "[ " . date('Y-m-d H:i:s') . " ] Success: " . get_class($job) . "\n";
                } catch (\Exception $e) {
                    echo "[ " . date('Y-m-d H:i:s') . " ] Failed: " . get_class($job) . " - " . $e->getMessage() . "\n";
                }
            }

            sleep(3); // Wait for new jobs
        }
    }
}

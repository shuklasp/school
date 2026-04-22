<?php
namespace SPP\Cron;

/**
 * Class Scheduler
 * Manages scheduled tasks (Cron) in the SPP framework.
 */
class Scheduler
{
    protected static array $tasks = [];

    /**
     * Schedule a Closure or Command to run at a specific interval.
     */
    public static function call(\Closure $task, string $cronExpression = '* * * * *'): void
    {
        self::$tasks[] = [
            'task' => $task,
            'expression' => $cronExpression
        ];
    }

    /**
     * Run the scheduled tasks.
     * Triggered via: php spp.php schedule:run
     */
    public static function run(): void
    {
        echo "Running scheduled tasks...\n";
        foreach (self::$tasks as $t) {
            // Basic check: In a real system, we'd use a Cron expression evaluator
            // For now, we'll just run them all for demonstration
            $t['task']();
        }
    }
}

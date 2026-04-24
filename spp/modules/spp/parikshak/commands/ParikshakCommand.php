<?php
namespace SPPMod\Parikshak\Commands;

use SPP\CLI\Command;
use SPPMod\Parikshak\Parikshak;
use SPP\Scheduler;
use SPP\Module;

/**
 * Class ParikshakCommand
 * CLI command to trigger the Parikshak (Evaluation) suite.
 */
class ParikshakCommand extends Command
{
    public function getName(): string
    {
        return 'sys:test:auto';
    }

    public function getDescription(): string
    {
        return 'Runs Automated Evolutionary Testing (Parikshak) for the current application.';
    }

    public function execute(array $args): void
    {
        // Guard check for module activity
        if (!\SPP\Module::getConfig('active', 'parikshak')) {
            $this->error("Parikshak (Evaluation) module is currently inactive. Set 'active: true' in config.yml to enable.");
            return;
        }

        // Argument 0 is script, 1 is command, 2 is the actual first parameter
        $appname = $args[2] ?? Scheduler::getContext() ?: 'default';
        if ($appname === 'sys:test:auto') $appname = 'default'; // Safety fallback
        
        $this->info("Initializing Parikshak Evaluator for context: [{$appname}]");
        
        try {
            $tester = new Parikshak();
            $results = $tester->runSuite($appname);
            
            $this->renderReport($results);
            
        } catch (\Exception $e) {
            $this->error("Evaluation failed: " . $e->getMessage());
        }
    }

    private function renderReport(array $results): void
    {
        $this->info("==================================================");
        $this->info("PARIKSHAK EVALUATION REPORT: " . $results['app']);
        $this->info("Timestamp: " . $results['timestamp']);
        $this->info("--------------------------------------------------");
        
        foreach ($results['entities'] as $e) {
            $status = strtoupper($e['status']);
            $color = $e['status'] === 'passed' ? "\033[32m" : "\033[31m";
            $this->line("{$color}[{$status}]\033[0m Entity: " . $e['class']);
            
            if (!empty($e['errors'])) {
                foreach ($e['errors'] as $err) {
                    $this->line("   - Error: {$err}");
                }
            }
        }
        
        $this->info("--------------------------------------------------");
        $passed = $results['summary']['passed'];
        $total = $results['summary']['total'];
        $this->info("SUMMARY: {$passed}/{$total} Entities Passed Invariants.");
        $this->info("==================================================");
    }
}

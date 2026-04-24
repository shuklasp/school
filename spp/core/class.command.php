<?php
namespace SPP\CLI;

/**
 * Abstract Class Command
 * Base class for all CLI commands in the SPP framework.
 */
abstract class Command
{
    /** @var string Command name (e.g. 'ui:serv') */
    protected string $name = '';
    
    /** @var string Command description */
    protected string $description = '';

    /**
     * Executes the command.
     *
     * @param array $args Command line arguments
     */
    abstract public function execute(array $args): void;

    /**
     * Gets the command name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the command description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Helpers for CLI output
     */
    protected function line(string $text): void { echo $text . "\n"; }
    protected function info(string $text): void { echo "\033[32mINFO: \033[0m" . $text . "\n"; }
    protected function warn(string $text): void { echo "\033[33mWARN: \033[0m" . $text . "\n"; }
    protected function error(string $text): void { echo "\033[31mERROR: \033[0m" . $text . "\n"; }
}

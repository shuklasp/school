<?php

namespace SPPMod\SPPView;

/**
 * class PHPComponent
 * 
 * Base class for server-side UI components that can be "hydrated" and controlled
 * via the SPP-UX SPA engine.
 * 
 * @author Satya Prakash Shukla
 */
abstract class PHPComponent extends \SPP\SPPObject
{
    /** @var array Shared state between PHP and generated JS */
    protected array $state = [];

    /**
     * Initialization logic (called before render).
     */
    public function onInit(): void {}

    /**
     * Renders the component HTML.
     */
    abstract public function render(): string;

    /**
     * Sets a state variable.
     */
    protected function setState(string $key, $value): void
    {
        $this->state[$key] = $value;
    }

    /**
     * Returns the current state.
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * Helper to wrap a method call as an AJAX action URL.
     */
    protected function action(string $method, array $params = []): string
    {
        return 'javascript:SPP.componentAction("' . get_class($this) . '", "' . $method . '", ' . json_encode($params) . ')';
    }
}

<?php

namespace SPP;

/**
 * class \SPP\Stack
 *
 * Simple stack implementation for internal use.
 * Compatible with old SPP builds, but fully PHP 8+ compliant.
 *
 * @author
 *     Satya Prakash Shukla
 * @version
 *     2.1 compatible with legacy SPP 1.x
 */
class Stack extends \SPP\SPPObject
{
    /** @var array<mixed> */
    private array $stack = [];

    /** @var int */
    private int $top = -1;

    /**
     * Push an element to the stack.
     *
     * @param mixed $value
     * @return void
     */
    public function push(mixed $value): void
    {
        $this->stack[++$this->top] = $value;
    }

    /**
     * Pop and return the top element from the stack.
     *
     * @return mixed|false Returns false if stack is empty.
     */
    public function pop(): mixed
    {
        if ($this->top < 0) {
            return false;
        }

        $value = $this->stack[$this->top];
        unset($this->stack[$this->top--]);
        return $value;
    }

    /**
     * Peek at the top element without removing it.
     *
     * @return mixed|false
     */
    public function peek(): mixed
    {
        return $this->top >= 0 ? $this->stack[$this->top] : false;
    }

    /**
     * Check if the stack is empty.
     */
    public function isEmpty(): bool
    {
        return $this->top < 0;
    }

    /**
     * Get number of items in the stack.
     */
    public function size(): int
    {
        return $this->top + 1;
    }

    /**
     * Reset stack contents.
     */
    public function clear(): void
    {
        $this->stack = [];
        $this->top = -1;
    }
}

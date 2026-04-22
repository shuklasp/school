<?php

namespace App\Autodemo\Components;

use SPPMod\SPPView\PHPComponent;

class Counter extends PHPComponent {
    protected array $state = [
        'title' => 'Interactive PHP Counter',
        'count' => 0
    ];

    public function render(): string {
        return "<div>
            <h1>{title}</h1>
            <p>Current Count: <strong>{count}</strong></p>
            <button onclick=\"{this.tick()}\">Increment</button>
        </div>";
    }

    public function tick($data): void {
        $this->state['count']++;
    }
}

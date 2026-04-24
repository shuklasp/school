<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class MakeVueComponentCommand
 * Development pillar: Scaffolds a Vue 3 component (ESM version).
 */
class MakeVueComponentCommand extends BaseMakeCommand
{
    protected string $name = 'make:vue-component';
    protected string $description = 'Scaffold a new Vue 3 component (ESM/No-build)';

    public function execute(array $args): void
    {
        $name = $args[2] ?? null;

        // Robust argument resolution
        foreach ($args as $arg) {
            if (strpos($arg, '--Name=') === 0) {
                $name = substr($arg, 7);
                break;
            }
        }

        if (!$name || strpos($name, '--') === 0) {
            echo "Usage: php spp.php make:vue-component <ComponentName>\n";
            return;
        }

        $context = $this->getContext($args);
        $targetDir = $this->getTargetDir('comp', $context);
        
        $className = ucfirst($name);
        $filePath = $targetDir . "/" . $className . ".js";

        $js = <<<'JS'
/**
 * Vue Component: {{CLASS_NAME}}
 * Generated via SPP CLI
 */
import { ref } from 'https://esm.sh/vue';

export default {
    setup() {
        const count = ref(0);
        return { count };
    },
    template: `
        <div class="vue-card">
            <h2>Vue 3 in SPP</h2>
            <p>Counter: <strong>{{ count }}</strong></p>
            <button @click="count++" class="btn-vue">
                Increment
            </button>
        </div>
    `
};
JS;
        $js = str_replace('{{CLASS_NAME}}', $className, $js);
        file_put_contents($filePath, $js);
        echo "Success: Vue component {$className} created at {$filePath}\n";
    }
}

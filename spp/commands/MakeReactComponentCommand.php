<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;

/**
 * Class MakeReactComponentCommand
 * Development pillar: Scaffolds a React component (No-build ESM version).
 */
class MakeReactComponentCommand extends BaseMakeCommand
{
    protected string $name = 'make:react-component';
    protected string $description = 'Scaffold a new React component (ESM/No-build)';

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
            echo "Usage: php spp.php make:react-component <ComponentName>\n";
            return;
        }

        $context = $this->getContext($args);
        $targetDir = $this->getTargetDir('comp', $context);
        
        $className = ucfirst($name);
        $filePath = $targetDir . "/" . $className . ".js";

        $js = <<<'JS'
/**
 * React Component: {{CLASS_NAME}}
 * Generated via SPP CLI
 */
import React from 'https://esm.sh/react';

export default function {{CLASS_NAME}}() {
    const [count, setCount] = React.useState(0);

    return React.createElement('div', { className: 'react-card' }, [
        React.createElement('h2', null, 'React in SPP'),
        React.createElement('p', null, `Counter: ${count}`),
        React.createElement('button', { 
            onClick: () => setCount(count + 1),
            className: 'btn-react'
        }, 'Increment')
    ]);
}
JS;
        $js = str_replace('{{CLASS_NAME}}', $className, $js);
        file_put_contents($filePath, $js);
        echo "Success: React component {$className} created at {$filePath}\n";
    }
}

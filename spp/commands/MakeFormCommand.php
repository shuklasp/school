<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * Class MakeFormCommand
 * Creates a new SPP form definition (YAML or XML).
 */
class MakeFormCommand extends BaseMakeCommand
{
    protected string $name = 'make:form';
    protected string $description = 'Create a new SPP form definition';

    public function execute(array $args): void
    {
        $formName = $args[2] ?? null;
        if (!$formName) {
            echo "Usage: php spp.php make:form <name> [--type=yml|xml]\n";
            return;
        }

        $type = 'yml';
        foreach ($args as $arg) {
            if (strpos($arg, '--type=') === 0) {
                $type = substr($arg, 7);
            }
        }

        $appName = \SPP\Scheduler::getContext() ?: 'default';
        $formsDir = SPP_APP_DIR . '/etc/apps/' . $appName . '/forms';

        if (!is_dir($formsDir)) {
            mkdir($formsDir, 0777, true);
        }

        $filePath = $formsDir . '/' . $formName . '.' . $type;
        if (file_exists($filePath)) {
            echo "Error: Form '{$formName}' already exists at {$filePath}\n";
            return;
        }

        if ($type === 'yml' || $type === 'yaml') {
            $data = [
                'form' => [
                    'name' => $formName . '_form',
                    'action' => '',
                    'method' => 'post',
                    'id' => $formName . '_form',
                    'controls' => [
                        'control' => [
                            ['name' => 'example_field', 'type' => 'SPPText', 'label' => 'Example Field', 'id' => 'example_field'],
                            ['name' => 'submit', 'type' => 'SPPSubmit', 'value' => 'Submit', 'id' => 'submit']
                        ]
                    ],
                    'validations' => [
                        'validation' => [
                            ['control' => 'example_field', 'type' => 'SPPRequiredValidator', 'message' => 'This field is required']
                        ]
                    ]
                ]
            ];
            file_put_contents($filePath, Yaml::dump($data, 8, 2));
        } else {
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            $xml .= "<form name=\"{$formName}_form\" action=\"\" method=\"post\" id=\"{$formName}_form\">\n";
            $xml .= "    <controls>\n";
            $xml .= "        <control name=\"example_field\" type=\"SPPText\" label=\"Example Field\" id=\"example_field\" />\n";
            $xml .= "        <control name=\"submit\" type=\"SPPSubmit\" value=\"Submit\" id=\"submit\" />\n";
            $xml .= "    </controls>\n";
            $xml .= "    <validations>\n";
            $xml .= "        <validation control=\"example_field\" type=\"SPPRequiredValidator\" message=\"This field is required\" />\n";
            $xml .= "    </validations>\n";
            $xml .= "</form>";
            file_put_contents($filePath, $xml);
        }

        echo "Success: Form '{$formName}' created in {$type} format at {$filePath}\n";
    }
}

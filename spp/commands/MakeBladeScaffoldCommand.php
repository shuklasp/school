<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * Class MakeBladeScaffoldCommand
 * Full stack scaffolding using Blade, YAML Forms, and Entities.
 */
class MakeBladeScaffoldCommand extends BaseMakeCommand
{
    protected string $name = 'make:blade-scaffold';
    protected string $description = 'Create a full stack Blade scaffold (Entity, YAML Form, Controller, Blade Views)';

    public function execute(array $args): void
    {
        $entityName = $args[2] ?? null;
        if (!$entityName) {
            echo "Entity Name (e.g. Student): ";
            $entityName = trim(fgets(STDIN));
        }
        if (!$entityName) return;

        $appName = \SPP\Scheduler::getContext() ?: 'default';
        if ($appName === 'default') {
            echo "App Name (Context): ";
            $appName = trim(fgets(STDIN)) ?: 'default';
        }

        $tableName = strtolower($entityName) . "s";
        echo "Table Name [{$tableName}]: ";
        $tableNameInput = trim(fgets(STDIN));
        if ($tableNameInput) $tableName = $tableNameInput;

        // 1. Define Entity
        echo "Defining Entity {$entityName}... ";
        $config = [
            'table' => $tableName,
            'id_field' => 'id',
            'sequence' => $tableName . '_seq',
            'attributes' => [
                'name' => 'varchar(255)',
                'description' => 'text'
            ]
        ];
        \SPPMod\SPPEntity\SPPEntity::saveEntityDefinition($entityName, $appName, $config);
        echo "DONE\n";

        // 2. Create YAML Form
        echo "Creating YAML Form... ";
        $formPath = SPP_APP_DIR . "/etc/apps/{$appName}/forms/" . strtolower($entityName) . ".yml";
        $formDir = dirname($formPath);
        if (!is_dir($formDir)) mkdir($formDir, 0777, true);

        $formYaml = [
            'form' => [
                'name' => strtolower($entityName) . '_form',
                'id' => strtolower($entityName) . '_form',
                'method' => 'post',
                'controls' => [
                    'control' => [
                        ['name' => 'name', 'type' => 'SPPText', 'label' => 'Name', 'id' => 'name'],
                        ['name' => 'description', 'type' => 'SPPTextArea', 'label' => 'Description', 'id' => 'description'],
                        ['name' => 'submit', 'type' => 'SPPSubmit', 'value' => 'Save ' . $entityName, 'id' => 'submit']
                    ]
                ],
                'validations' => [
                    'validation' => [
                        ['control' => 'name', 'type' => 'SPPRequiredValidator', 'message' => 'Name is required']
                    ]
                ]
            ]
        ];
        file_put_contents($formPath, Yaml::dump($formYaml, 8, 2));
        echo "DONE\n";

        // 3. Create Blade Views
        echo "Creating Blade Views... ";
        $viewsDir = SPP_APP_DIR . "/resources/{$appName}/views/" . strtolower($entityName);
        if (!is_dir($viewsDir)) mkdir($viewsDir, 0777, true);

        // List View
        $listView = "
@extends('layouts.app')
@section('content')
    <div class='card'>
        <h1>{{ $title }}</h1>
        <table class='table'>
            <thead><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->name }}</td>
                    <td>
                        <a href='?action=edit&id={{ $item->id }}'>Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <a href='?action=create' class='btn'>Add New</a>
    </div>
@endsection
";
        file_put_contents($viewsDir . "/index.blade.php", trim($listView));

        // Form View
        $formView = "
@extends('layouts.app')
@section('content')
    <div class='card'>
        <h1>{{ $item->id ? 'Edit' : 'Create' }} {{ $entityName }}</h1>
        
        @sppform('" . strtolower($entityName) . "')
        @sppbind($item)
        @sppform_start('" . strtolower($entityName) . "_form')
            <div>
                <label>Name</label>
                @sppelement('name')
            </div>
            <div>
                <label>Description</label>
                @sppelement('description')
            </div>
            <div>
                @sppelement('submit')
            </div>
        @sppform_end
        
        <a href='?'>Back to List</a>
    </div>
@endsection
";
        file_put_contents($viewsDir . "/form.blade.php", trim($formView));
        echo "DONE\n";

        // 4. Create Layout
        $layoutDir = SPP_APP_DIR . "/resources/{$appName}/views/layouts";
        if (!is_dir($layoutDir)) mkdir($layoutDir, 0777, true);
        $layout = "
<!DOCTYPE html>
<html>
<head>
    <title>{{ $title ?? 'SPP Blade' }}</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; padding: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 800px; margin: auto; }
        .table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        .table th, .table td { border-bottom: 1px solid #eee; padding: 0.75rem; text-align: left; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; }
        input, textarea { width: 100%; padding: 0.5rem; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
    @yield('content')
    <script src='/spp/admin/js/spp-loader.js' type='module'></script>
</body>
</html>
";
        file_put_contents($layoutDir . "/app.blade.php", trim($layout));

        // 5. Create Entry Point (Logic)
        echo "Creating Entry Point... ";
        $entryFile = SPP_APP_DIR . "/{$appName}_" . strtolower($entityName) . ".php";
        $logic = <<<'PHP'
<?php
require_once __DIR__ . '/spp/sppinit.php';
\SPP\Scheduler::setContext('{{APP_NAME}}');

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$entityClass = '\\SPPMod\\SPPEntity\\{{ENTITY_NAME}}';
$item = $id ? $entityClass::find($id) : new $entityClass();

// Handle Form Submission
function {{ENTITY_NAME_LOWER}}_form_submitted() {
    global $item;
    $item->loadFromArray($_POST);
    if ($item->save()) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
PHP;
        $logic = str_replace(
            ['{{APP_NAME}}', '{{ENTITY_NAME}}', '{{ENTITY_NAME_LOWER}}'],
            [$appName, $entityName, strtolower($entityName)],
            $logic
        );

        $renderCode = <<<'PHP'
\SPPMod\SPPView\ViewPage::processForms();

$blade = \SPP\App::getApp()->make('blade');
if ($action === 'edit' || $action === 'create') {
    echo $blade->render('edit_' . strtolower('{{ENTITY_NAME}}'), [
        'item' => $item,
        'title' => ($id ? 'Edit' : 'Create') . ' {{ENTITY_NAME}}'
    ]);
} else {
    $items = $entityClass::findAll();
    echo $blade->render('list_' . strtolower('{{ENTITY_NAME}}'), [
        'items' => $items,
        'title' => '{{ENTITY_NAME}} List'
    ]);
}
PHP;
        $logic .= "\n\n" . str_replace('{{ENTITY_NAME}}', $entityName, $renderCode);
        file_put_contents($entryFile, $logic);
        echo "DONE\n";

        echo "\nSuccess: Full Blade Scaffold for {$entityName} created!\n";
        echo "1. Run 'php spp.php db:sync' to create the table.\n";
        echo "2. Open: http://localhost/" . basename($entryFile) . "\n";
    }
}

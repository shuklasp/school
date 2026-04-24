<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * Class MakeBladeProjectCommand
 * Scaffolds a new Blade-enabled project in SPP.
 */
class MakeBladeProjectCommand extends BaseMakeCommand
{
    protected string $name = 'make:blade-project';
    protected string $description = 'Scaffold a new Blade-enabled SPP application';

    public function execute(array $args): void
    {
        $appName = $args[2] ?? null;
        if (!$appName) {
            echo "Usage: php spp.php make:blade-project <app_name>\n";
            return;
        }

        // 1. Create App Context
        $appDir = SPP_APP_DIR . '/etc/apps/' . $appName;
        if (is_dir($appDir) && !empty(glob($appDir . '/*')) && !isset($args['--force'])) {
            // Only error if it's not empty and not forced
            // (make:app creates the dir first, so we allow it if it's just been created)
        }
        
        if (!is_dir($appDir)) {
            mkdir($appDir, 0777, true);
        }

        echo "Scaffolding Blade project: {$appName}...\n";

        // Create standard app structure
        mkdir($appDir . '/modsconf/sppblade', 0777, true);
        mkdir($appDir . '/data', 0777, true);
        mkdir($appDir . '/logs', 0777, true);
        mkdir($appDir . '/forms', 0777, true);
        
        // 2. Configure SPPBlade for this app
        $bladeConfig = [
            'variables' => [
                'views_path' => "resources/{$appName}/views",
                'cache_path' => "var/cache/{$appName}/blade",
                'mode' => 0 // BladeOne::MODE_AUTO
            ]
        ];
        file_put_contents($appDir . '/modsconf/sppblade/config.yml', Yaml::dump($bladeConfig, 4, 2));

        // 3. Create Views directory
        $viewsDir = SPP_APP_DIR . "/resources/{$appName}/views";
        if (!is_dir($viewsDir)) {
            mkdir($viewsDir, 0777, true);
        }

        // 4. Create an example Form (login.yml)
        $formYaml = [
            'form' => [
                'name' => 'login_form',
                'action' => '',
                'method' => 'post',
                'id' => 'login_form',
                'controls' => [
                    'control' => [
                        ['name' => 'username', 'type' => 'SPPText', 'label' => 'Username', 'id' => 'username'],
                        ['name' => 'password', 'type' => 'SPPPassword', 'label' => 'Password', 'id' => 'password'],
                        ['name' => 'submit', 'type' => 'SPPSubmit', 'value' => 'Login', 'id' => 'submit']
                    ]
                ],
                'validations' => [
                    'validation' => [
                        ['control' => 'username', 'type' => 'SPPRequiredValidator', 'message' => 'Username is required'],
                        ['control' => 'password', 'type' => 'SPPRequiredValidator', 'message' => 'Password is required']
                    ]
                ]
            ]
        ];
        file_put_contents($appDir . '/forms/login.yml', Yaml::dump($formYaml, 8, 2));

        // 5. Create a Base Layout
        $layoutDir = $viewsDir . "/layouts";
        if (!is_dir($layoutDir)) mkdir($layoutDir, 0777, true);
        
        $layout = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>@yield('title', 'SPP Blade Project')</title>
    <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap' rel='stylesheet'>
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --accent: #a78bfa;
            --bg: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.7);
            --text: #0f172a;
            --text-muted: #64748b;
            --glass-border: rgba(255, 255, 255, 0.5);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        [data-theme='dark'] {
            --bg: #0f172a;
            --card-bg: rgba(255, 255, 255, 0.03);
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body { 
            font-family: 'Outfit', sans-serif; 
            background: var(--bg); 
            color: var(--text); 
            margin: 0; 
            transition: all 0.3s ease;
            background-image: 
                radial-gradient(at 0% 0%, rgba(49, 130, 206, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(237, 100, 166, 0.1) 0px, transparent 50%);
            min-height: 100vh;
        }

        .container { max-width: 1100px; margin: 0 auto; padding: 2rem; }
        
        .nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4rem; }
        .logo { font-weight: 700; font-size: 1.5rem; letter-spacing: -1px; background: linear-gradient(to right, var(--primary), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .hero { text-align: center; margin-bottom: 5rem; }
        .hero h1 { font-size: 3.5rem; font-weight: 700; margin-bottom: 1rem; letter-spacing: -2px; }
        .hero p { font-size: 1.25rem; color: var(--text-muted); max-width: 600px; margin: 0 auto; }
        
        .glass-panel {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 2.5rem;
        }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 3rem; }
        
        .feature-card { transition: transform 0.3s; }
        .feature-card:hover { transform: translateY(-5px); }
        .feature-card .icon { font-size: 2rem; margin-bottom: 1rem; display: block; }
        .feature-card h3 { margin-bottom: 0.5rem; }
        .feature-card p { font-size: 0.95rem; color: var(--text-muted); line-height: 1.6; }

        .workflow { margin-top: 8rem; text-align: center; }
        .workflow-steps { display: flex; justify-content: center; gap: 2rem; margin-top: 3rem; flex-wrap: wrap; }
        .step { flex: 1; min-width: 250px; position: relative; }
        .step-num { width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; margin: 0 auto 1rem; }
        
        .code-block { 
            background: #2d3748; color: #e2e8f0; padding: 1.5rem; border-radius: 12px; 
            font-family: 'Fira Code', monospace; font-size: 0.85rem; text-align: left; 
            margin-top: 2rem; overflow-x: auto; border: 1px solid rgba(255,255,255,0.1);
        }
        
        .btn { 
            display: inline-block; padding: 0.75rem 1.5rem; border-radius: 12px; 
            font-weight: 600; text-decoration: none; transition: all 0.2s;
            cursor: pointer; border: none; font-size: 0.95rem;
        }
        .primary-btn { background: var(--primary); color: white; }
        .primary-btn:hover { background: var(--primary-light); box-shadow: 0 4px 15px rgba(49, 130, 206, 0.3); }
        
        .footer { margin-top: 5rem; padding: 2rem 0; border-top: 1px solid var(--glass-border); text-align: center; color: var(--text-muted); font-size: 0.9rem; }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .container { padding: 1rem; }
        }
    </style>
</head>
<body data-theme='light'>
    <div class='container'>
        <nav class='nav'>
            <div class='nav-brand'>
                <img src='/school1/res/spp/images/logo.jpg' alt='SPP Logo' style='width: 32px; height: 32px; border-radius: 8px;'>
                <div class='logo'>SPP<span>Blade</span></div>
            </div>
            <div>
                <button class='btn' style='background:transparent; border:1px solid var(--glass-border);' onclick='document.body.dataset.theme = document.body.dataset.theme === \"dark\" ? \"light\" : \"dark\"'>🌓 Theme</button>
            </div>
        </nav>

        @yield('content')

        <footer class='footer'>
            &copy; {{ date('Y') }} SPP Framework • Powering Enterprise PHP with Blade
        </footer>
    </div>
</body>
</html>
";
        file_put_contents($layoutDir . "/app.blade.php", trim($layout));

        // Create the Index page
        $index = "
@extends('layouts.app')

@section('title', 'Welcome to ' . \$appName)

@section('content')
    <div class='hero'>
        <div class='glass-panel' style='display:inline-block; padding: 8px 20px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; margin-bottom: 2rem;'>
            🚀 NOW POWERED BY BLADE ENGINE
        </div>
        <h1>Expressive. Fast. <br> Seamlessly Integrated.</h1>
        <p>Your new SPP application <strong>{{ \$appName }}</strong> is ready. Experience the perfect harmony of Blade templates and SPP core services.</p>
        
        <div style='margin-top: 3rem;'>
            <a href='#workflow' class='btn primary-btn'>Explore Workflow</a>
            <a href='#demo' class='btn' style='margin-left: 1rem; border: 1px solid var(--glass-border);'>Try Interactive Demo</a>
        </div>
    </div>

    <div class='grid'>
        <div class='glass-panel feature-card'>
            <span class='icon'>🏗️</span>
            <h3>Native Directives</h3>
            <p>Use <code>@@sppform</code>, <code>@@sppauth</code>, and <code>@@sppbind</code> to bridge your templates with SPP modules effortlessly.</p>
        </div>
        <div class='glass-panel feature-card'>
            <span class='icon'>⚡</span>
            <h3>Zero Latency</h3>
            <p>Blade templates are compiled to plain PHP and cached, ensuring your enterprise apps run at peak performance.</p>
        </div>
        <div class='glass-panel feature-card'>
            <span class='icon'>📂</span>
            <h3>Structured Data</h3>
            <p>Built-in support for YAML forms and Entities means you spend less time on boilerplate and more on features.</p>
        </div>
    </div>

    <div class='workflow' id='workflow'>
        <h2>The SPP Workflow Advantage</h2>
        <p>Go from idea to implementation in three simple steps.</p>
        
        <div class='workflow-steps'>
            <div class='step'>
                <div class='step-num'>1</div>
                <h4>Define</h4>
                <p>Create your entity or form in simple YAML.</p>
                <div class='code-block'># login.yml\nform:\n  name: login_form\n  controls:\n    - username\n    - password</div>
            </div>
            <div class='step'>
                <div class='step-num'>2</div>
                <h4>Scaffold</h4>
                <p>Generate full-stack logic with a single CLI command.</p>
                <div class='code-block' style='background: #1a202c;'>php spp.php make:blade-scaffold Login</div>
            </div>
            <div class='step'>
                <div class='step-num'>3</div>
                <h4>Render</h4>
                <p>Drop the component into your Blade view.</p>
                <div class='code-block'>@@sppform('login')</div>
            </div>
        </div>
    </div>

    <div id='demo' style='margin-top: 8rem; text-align: center;'>
        <div class='glass-panel' style='max-width: 500px; margin: 0 auto; text-align: left;'>
            <h2 style='text-align: center;'>Interactive Login Demo</h2>
            <p style='text-align: center; font-size: 0.9rem; margin-bottom: 2rem;'>This form is controlled via <code>etc/apps/{{ \$appName }}/forms/login.yml</code></p>
            
            @sppguest
                @sppform('login')
                @sppform_start('login_form')
                    <div style='margin-bottom: 1.5rem;'>
                        <label>Username</label>
                        @sppelement('username', ['class' => 'form-input', 'style' => 'width:100%; padding: 0.8rem; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.05); color: inherit;'])
                    </div>
                    <div style='margin-bottom: 1.5rem;'>
                        <label>Password</label>
                        @sppelement('password', ['class' => 'form-input', 'style' => 'width:100%; padding: 0.8rem; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.05); color: inherit;'])
                    </div>
                    <div style='margin-top: 2rem;'>
                        @sppelement('submit', ['class' => 'btn primary-btn', 'style' => 'width: 100%;'])
                    </div>
                @sppform_end
            @endsppguest

            @sppauth
                <div style='text-align: center; padding: 2rem;'>
                    <div style='font-size: 3rem; margin-bottom: 1rem;'>👋</div>
                    <h3>Welcome back, {{ \SPPMod\SPPAuth\SPPAuth::getUser()->username }}!</h3>
                    <p>You have accessed this restricted area via <code>@@sppauth</code>.</p>
                    <a href='?logout=1' class='btn' style='margin-top: 1rem; color: var(--accent); font-weight: 600;'>Logout</a>
                </div>
            @endsppauth
        </div>
    </div>
@endsection
";
        file_put_contents($viewsDir . '/index.blade.php', trim($index));

        // 6. Create a default entry point with Form Processing and Auth logic
        $entryPoint = <<<'PHP'
<?php
require_once __DIR__ . '/spp/sppinit.php';

// Initialize App Context
\SPP\App::getApp('{{APP_NAME}}');

// Standardizing path to src/
$appEntryFile = SPP_APP_DIR . "/src/{{APP_NAME}}/index.php";

// 1. Handle Logout
if (isset($_GET['logout'])) {
    \SPP\App::killSession();
    header('Location: {{APP_NAME}}_index.php');
    exit;
}

// 2. Define Form Submission Handler
function login_form_submitted() {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // In a real app, use \SPPMod\SPPAuth\SPPAuth::login($username, $password)
    // For this demo, we'll just mock it if credentials are 'admin'/'password'
    if ($username === 'admin' && $password === 'password') {
        // Mock successful login by setting session manually or using Auth
        // \SPPMod\SPPAuth\SPPAuth::forceLogin(1, 'admin'); 
        echo "<script>alert('Login Successful! (Demo Mock)');</script>";
    } else {
        echo "<script>alert('Invalid Credentials. Use admin/password');</script>";
    }
}

// 3. Process SPP Forms
\SPPMod\SPPView\ViewPage::processForms();

// 4. Render View
$blade = \SPP\App::getApp()->make('blade');
echo $blade->render('index', ['appName' => '{{APP_NAME}}', 'title' => 'Integrated Blade App']);
PHP;
        $entryPoint = str_replace('{{APP_NAME}}', $appName, $entryPoint);
        $entryPoint = str_replace("require_once __DIR__ . '/spp/sppinit.php';", "require_once __DIR__ . '/../../spp/sppinit.php';", $entryPoint);
        if (!is_dir(dirname($appEntryFile))) mkdir(dirname($appEntryFile), 0777, true);
        file_put_contents($appEntryFile, $entryPoint);

        // 7. Automatically register in global-settings.yml
        $globalSettingsFile = SPP_APP_DIR . '/spp/etc/global-settings.yml';
        if (file_exists($globalSettingsFile)) {
            $globalSettings = Yaml::parseFile($globalSettingsFile);
            if (!isset($globalSettings['apps'][$appName])) {
                $globalSettings['apps'][$appName] = [
                    'base_url' => "/{$appName}",
                    'table_prefix' => strtolower(substr($appName, 0, 3)) . '_',
                    'shared_group' => 'core',
                    'etc_path' => "etc/apps/{$appName}",
                    'src_path' => "src/{$appName}"
                ];
                file_put_contents($globalSettingsFile, Yaml::dump($globalSettings, 8, 2));
                echo "Registered application '{$appName}' in global-settings.yml\n";
            }
        }

        // 8. Create pages.yml for the new app to handle routing
        if (!file_exists($appDir . '/pages.yml')) {
            $pagesYaml = [
                'home' => 'index',
                'pages' => [
                    'index' => [
                        'url' => "index.php",
                        'title' => $appName,
                        'special' => 1
                    ]
                ]
            ];
            file_put_contents($appDir . '/pages.yml', Yaml::dump($pagesYaml, 4, 2));
        }

        echo "\nSuccess! Integrated Blade project '{$appName}' created.\n";
        echo "Run it at: http://localhost/{$appName}_index.php or http://localhost/{$appName}\n";
    }
}

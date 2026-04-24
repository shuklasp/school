<?php

namespace SPP\CLI\Commands;

use SPP\CLI\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * Class MakeAppCommand
 * Creates a new SPP application context.
 */
class MakeAppCommand extends BaseMakeCommand
{
    protected string $name = 'make:app';
    protected string $description = 'Create a new SPP application context';

    public function execute(array $args): void
    {
        $appName = $args[2] ?? null;
        $appType = $args[3] ?? 'native'; // native, blade, react, vue, drupal
        
        if (!$appName) {
            echo "Usage: php spp.php make:app <AppName> <Type>\n";
            return;
        }

        $baseUrl = $args[4] ?? "/" . $appName;
        $tablePrefix = $args[5] ?? $appName . "_";

        // 1. Create Directories
        $dirs = [
            SPP_APP_DIR . "/etc/apps/{$appName}",
            SPP_APP_DIR . "/etc/apps/{$appName}/forms",
            SPP_APP_DIR . "/src/{$appName}",
            SPP_APP_DIR . "/src/{$appName}/controllers",
            SPP_APP_DIR . "/src/{$appName}/services",
            SPP_APP_DIR . "/resources/{$appName}/views",
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
                echo "Created directory: " . basename($dir) . "\n";
            }
        }

        // 2. Update global-settings.yml
        $settingsPath = SPP_APP_DIR . "/spp/etc/global-settings.yml";
        if (file_exists($settingsPath)) {
            $settings = Yaml::parseFile($settingsPath);
            if (!isset($settings['apps'][$appName])) {
                $settings['apps'][$appName] = [
                    'base_url' => $baseUrl,
                    'table_prefix' => $tablePrefix,
                    'type' => $appType,
                    'shared_group' => 'core',
                    'etc_path' => "etc/apps/{$appName}",
                    'src_path' => "src/{$appName}"
                ];
                file_put_contents($settingsPath, Yaml::dump($settings, 10, 2));
                echo "Updated global-settings.yml with app '{$appName}' (Type: {$appType}).\n";

                $pagesFile = SPP_APP_DIR . "/etc/apps/{$appName}/pages.yml";
                $defaultPages = [
                    'pages' => [
                        'index' => [
                            'url' => 'index.php',
                            'title' => $appName,
                            'special' => 1
                        ]
                    ],
                    'defaults' => [
                        'home' => 'index',
                        'pagedir' => "/src/{$appName}"
                    ]
                ];
                file_put_contents($pagesFile, Yaml::dump($defaultPages, 10, 2));
                echo "Created default pages.yml for '{$appName}'.\n";
            } else {
                echo "Warning: App '{$appName}' already exists in global-settings.yml.\n";
            }
        }

        // 3. Chain to Scaffolding based on type
        if ($appType === 'blade') {
            echo "Chaining to Blade scaffolding...\n";
            \SPP\CLI\CommandManager::execute('make:blade-project', [$appName]);
        } elseif ($appType === 'sppux') {
            echo "Provisioning SPP-UX structure...\n";
            $uxDir = SPP_APP_DIR . "/src/{$appName}/comp";
            if (!is_dir($uxDir)) mkdir($uxDir, 0777, true);
            
            $mainComp = <<<'JS'
export default class Main extends BaseComponent {
    async onInit() {
        this.setState({ 
            activeTab: 'roadmap',
            appName: '{{APP_NAME}}',
            stats: [
                { label: 'Latency', value: '0.4ms', icon: '⚡' },
                { label: 'Security', value: 'Shielded', icon: '🛡️' },
                { label: 'Uptime', value: '99.99%', icon: '🌐' }
            ]
        });
    }

    render() {
        return html`
            <div class="premium-container">
                <nav class="premium-nav">
                    <div class="nav-brand">
                        <img src="/school1/res/spp/images/logo.jpg" alt="Logo">
                        <span>SPP<span>UX</span></span>
                    </div>
                    <div class="nav-links">
                        <button class="${this.state.activeTab === 'roadmap' ? 'active' : ''}" 
                                @click="${() => this.setState({ activeTab: 'roadmap' })}">🗺️ Roadmap</button>
                        <button class="${this.state.activeTab === 'capabilities' ? 'active' : ''}" 
                                @click="${() => this.setState({ activeTab: 'capabilities' })}">🚀 Capabilities</button>
                    </div>
                </nav>

                <main class="premium-hero">
                    <div class="hero-content">
                        <div class="badge">Evolving Infrastructure</div>
                        <h1>${this.state.appName}</h1>
                        <p>Powered by SPP-UX Reactive Framework</p>
                        
                        <div class="stats-grid">
                            ${this.state.stats.map(s => html`
                                <div class="stat-card">
                                    <span class="stat-icon">${s.icon}</span>
                                    <div class="stat-info">
                                        <div class="stat-label">${s.label}</div>
                                        <div class="stat-value">${s.value}</div>
                                    </div>
                                </div>
                            `)}
                        </div>
                    </div>

                    <div class="view-container glass-panel">
                        ${this.state.activeTab === 'roadmap' ? this.renderRoadmap() : this.renderCapabilities()}
                    </div>
                </main>

                <footer class="premium-footer">
                    &copy; ${new Date().getFullYear()} SPP Framework • Reactive Web Experience
                </footer>
            </div>
        `;
    }

    renderRoadmap() {
        return html`
            <div class="roadmap-view">
                <h3>Development Lifecycle</h3>
                <div class="timeline">
                    <div class="timeline-item done">
                        <div class="point"></div>
                        <div class="content">
                            <h4>Phase 1: Scaffolding</h4>
                            <p>Application structure generated via CLI.</p>
                        </div>
                    </div>
                    <div class="timeline-item active">
                        <div class="point"></div>
                        <div class="content">
                            <h4>Phase 2: Logic Integration</h4>
                            <p>Injecting services and database entities.</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="point"></div>
                        <div class="content">
                            <h4>Phase 3: Visual Polish</h4>
                            <p>Implementing premium UX transitions.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderCapabilities() {
        return html`
            <div class="capabilities-view">
                <h3>Framework Capabilities</h3>
                <div class="cap-grid">
                    <div class="cap-card">
                        <div class="cap-icon">⚡</div>
                        <h4>Reactive State</h4>
                        <p>Real-time UI updates via this.setState()</p>
                    </div>
                    <div class="cap-card">
                        <div class="cap-icon">🔒</div>
                        <h4>Secured API</h4>
                        <p>Native integration with SPPAuth services.</p>
                    </div>
                </div>
            </div>
        `;
    }
}
JS;
            file_put_contents($uxDir . "/main.js", str_replace('{{APP_NAME}}', $appName, $mainComp));
            echo "Created main.js in {$appName}/comp\n";

            // Create Entry Point (PHP wrapper for SPA)
            $entryFile = SPP_APP_DIR . "/src/{$appName}/index.php";
            $entryCode = <<<'PHP'
<?php
require_once __DIR__ . '/../../spp/sppinit.php';
\SPP\App::getApp('{{APP_NAME}}');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{APP_NAME}} - SPP-UX</title>
    <!-- SPP-UX Runtime -->
    <script src="/school1/spp/admin/js/sppux.js"></script>
    <script src="/school1/spp/admin/js/spp-loader.js"></script>
    <script>
        // Minimal admin bridge for standalone apps
        window.spp_admin = {
            api: async (action, data) => { console.log('API call:', action, data); return { success: true, data: {} }; },
            apiPost: async (data) => { console.log('API Post:', data); return { success: true, data: {} }; },
            callAppService: async (name, params) => { console.log('App Service call:', name, params); return { success: true, data: {} }; }
        };
    </script>
    <style>
        :root {
            --bg-dark: #0f172a;
            --primary: #6366f1;
            --accent: #a78bfa;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
        }

        body { 
            margin: 0; 
            font-family: 'Outfit', -apple-system, sans-serif; 
            background: var(--bg-dark); 
            color: var(--text-main);
            overflow-x: hidden;
            background-image: radial-gradient(circle at 0% 0%, rgba(99, 102, 241, 0.1) 0%, transparent 50%);
        }

        .premium-container { min-height: 100vh; display: flex; flex-direction: column; }
        
        .premium-nav { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 1.5rem 5%; border-bottom: 1px solid var(--glass-border);
            backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 100;
        }

        .nav-brand { display: flex; align-items: center; gap: 1rem; font-weight: 700; font-size: 1.25rem; }
        .nav-brand img { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--glass-border); }
        .nav-brand span span { color: var(--primary); }

        .nav-links { display: flex; gap: 1rem; }
        .nav-links button { 
            background: transparent; border: none; color: var(--text-dim); 
            cursor: pointer; padding: 0.5rem 1rem; border-radius: 8px;
            transition: all 0.3s; font-weight: 500;
        }
        .nav-links button.active { background: var(--glass-bg); color: white; border: 1px solid var(--glass-border); }
        .nav-links button:hover { color: white; }

        .premium-hero { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 4rem 5%; text-align: center; }
        .badge { 
            display: inline-block; padding: 0.4rem 1.2rem; background: var(--glass-bg); 
            border: 1px solid var(--glass-border); border-radius: 50px; 
            font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--accent); margin-bottom: 2rem;
        }
        h1 { font-size: 4rem; margin: 0; font-weight: 800; letter-spacing: -0.04em; }
        .premium-hero > p { font-size: 1.25rem; color: var(--text-dim); margin-top: 1rem; }

        .stats-grid { display: flex; gap: 2rem; margin-top: 3rem; }
        .stat-card { display: flex; align-items: center; gap: 1rem; background: var(--glass-bg); padding: 1rem 1.5rem; border-radius: 12px; border: 1px solid var(--glass-border); }
        .stat-icon { font-size: 1.5rem; }
        .stat-label { font-size: 0.7rem; text-transform: uppercase; color: var(--text-dim); font-weight: 700; }
        .stat-value { font-size: 1.1rem; font-weight: 700; color: white; }

        .view-container { margin-top: 4rem; width: 100%; max-width: 800px; padding: 3rem; text-align: left; }
        .glass-panel { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 24px; backdrop-filter: blur(20px); }

        .roadmap-view h3, .capabilities-view h3 { margin-top: 0; margin-bottom: 2rem; font-size: 1.5rem; }
        
        .timeline { position: relative; padding-left: 2rem; border-left: 1px solid var(--glass-border); }
        .timeline-item { position: relative; margin-bottom: 2.5rem; }
        .timeline-item .point { 
            position: absolute; left: calc(-2rem - 6px); top: 5px; width: 11px; height: 11px; 
            background: #475569; border: 2px solid var(--bg-dark); border-radius: 50%; 
        }
        .timeline-item.done .point { background: var(--primary); box-shadow: 0 0 10px var(--primary); }
        .timeline-item.active .point { background: var(--accent); box-shadow: 0 0 10px var(--accent); }
        .timeline-item h4 { margin: 0; font-size: 1.1rem; }
        .timeline-item p { margin: 0.25rem 0 0; color: var(--text-dim); font-size: 0.9rem; }

        .cap-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .cap-card { padding: 1.5rem; background: rgba(255,255,255,0.02); border: 1px solid var(--glass-border); border-radius: 16px; transition: transform 0.3s; }
        .cap-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.05); }
        .cap-card h4 { margin: 1rem 0 0.5rem; }
        .cap-card p { margin: 0; font-size: 0.85rem; color: var(--text-dim); }

        .premium-footer { padding: 3rem; text-align: center; border-top: 1px solid var(--glass-border); color: var(--text-dim); font-size: 0.85rem; }

        #app-root { min-height: 100vh; display: flex; flex-direction: column; }
        .loader { height: 100vh; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #6366f1; }
    </style>
</head>
<body>
    <div id="app-root" data-spp-component="1" data-spp-type="ux" data-spp-path="/school1/src/{{APP_NAME}}/comp/main.js">
        <div class="loader">Initiating SPP-UX Environment...</div>
    </div>
</body>
</html>
PHP;
            file_put_contents($entryFile, str_replace('{{APP_NAME}}', $appName, $entryCode));
            echo "Created SPA entry point: {$appName}.php\n";

            // Create Internal Route file
            $srcIndex = SPP_APP_DIR . "/src/{$appName}/index.php";
            file_put_contents($srcIndex, $entryCode);
            echo "Created entry point: src/{$appName}/index.php\n";
        } elseif ($appType === 'dropin') {
            echo "Provisioning Drop-in HTML/PHP structure...\n";
            $viewsDir = SPP_APP_DIR . "/resources/{$appName}/views";
            if (!is_dir($viewsDir)) mkdir($viewsDir, 0777, true);
            
            // Create a universal entry point for the app
            $entryFile = SPP_APP_DIR . "/src/{$appName}/index.php";
            $entryCode = <<<'PHP'
<?php
require_once __DIR__ . '/../../spp/sppinit.php';
\SPP\Scheduler::setContext('{{APP_NAME}}');

// Low-Code Form Auto-Detection & Processing
if (class_exists('\SPPMod\SPPView\ViewPage')) {
    \SPPMod\SPPView\ViewPage::processForms();
}

// Simple Router for Drop-in files
$page = (isset($_GET['q']) && $_GET['q'] !== '') ? $_GET['q'] : 'index';
$file = __DIR__ . '/../../resources/{{APP_NAME}}/views/' . $page;

if (file_exists($file . '.php')) {
    include $file . '.php';
} elseif (file_exists($file . '.html')) {
    echo file_get_contents($file . '.html');
} else {
    echo "<h1>404 - Page Not Found</h1><p>File '{$page}' not found in resources/{{APP_NAME}}/views/</p>";
}
PHP;
            file_put_contents($entryFile, str_replace('{{APP_NAME}}', $appName, $entryCode));
            
            // Create a sample file with a form
            $sampleHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to {{APP_NAME}}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc;
            --primary: #6366f1;
            --text: #0f172a;
            --text-muted: #64748b;
        }
        body { 
            margin: 0; padding: 0; font-family: 'Outfit', sans-serif; 
            background-color: var(--bg); color: var(--text);
            background-image: radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.05) 0%, transparent 50%);
        }
        .container { max-width: 800px; margin: 4rem auto; padding: 0 2rem; }
        .card { 
            background: white; border-radius: 24px; padding: 3rem; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);
        }
        .header { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; }
        .header img { width: 40px; height: 40px; border-radius: 10px; }
        h1 { font-size: 2.5rem; margin: 0; font-weight: 800; }
        p { font-size: 1.1rem; color: var(--text-muted); line-height: 1.6; }
        
        .form-section { margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #f1f5f9; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; color: var(--text-muted); }
        input, textarea { 
            width: 100%; padding: 0.8rem 1rem; border-radius: 12px; 
            border: 1px solid #e2e8f0; font-family: inherit; font-size: 1rem;
            transition: all 0.2s; background: #fbfcfe;
        }
        input:focus, textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); background: white; }
        
        .btn { 
            background: var(--primary); color: white; border: none; 
            padding: 1rem 2rem; border-radius: 12px; font-weight: 600; 
            font-size: 1rem; cursor: pointer; transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3); }
        
        footer { margin-top: 4rem; text-align: center; font-size: 0.9rem; color: var(--text-muted); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <img src="/school1/res/spp/images/logo.jpg" alt="SPP Logo">
                <div>
                    <h1>{{APP_NAME}}</h1>
                    <p>Low-Code PHP Environment</p>
                </div>
            </div>
            
            <p>Welcome to your new SPP Drop-in application. This page is served directly from <code>resources/{{APP_NAME}}/views/index.html</code>.</p>
            
            <div class="form-section">
                <h3>Interactive Form Control</h3>
                <p style="font-size: 0.9rem;">Managed via <code>etc/apps/{{APP_NAME}}/forms/contact.yml</code></p>
                
                <form method="POST" action="">
                    <input type="hidden" name="spp_form_id" value="contact">
                    
                    <div class="form-group">
                        <label>Your Name</label>
                        <input type="text" name="guest_name" placeholder="John Doe" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" placeholder="How can we help you?" rows="4"></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Process Securely</button>
                </form>
            </div>
        </div>
        <footer>
            &copy; 2026 SPP Framework • The Future of Rapid PHP Development
        </footer>
    </div>
</body>
</html>
HTML;
            file_put_contents($viewsDir . "/index.html", str_replace('{{APP_NAME}}', $appName, $sampleHtml));

            // Create matching YAML config
            $formYml = <<<'YML'
id: contact
public_name: "Contact Form"
success_message: "Thank you! Your message has been processed via YAML control."
redirect_to: "index"
fields:
  guest_name:
    label: "Name"
    type: text
    required: true
    validation: "min:3"
  message:
    label: "Message"
    type: textarea
YML;
            file_put_contents(SPP_APP_DIR . "/etc/apps/{$appName}/forms/contact.yml", $formYml);
            
            echo "Created universal entry point and sample YAML form control.\n";
        }

        echo "Success: Application '{$appName}' created.\n";
    }
}

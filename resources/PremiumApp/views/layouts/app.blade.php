<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>@yield('title', 'SPP Blade Project')</title>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Fira+Code:wght@400;500&display=swap' rel='stylesheet'>
    <style>
        :root {
            --primary: #3182ce;
            --primary-light: #63b3ed;
            --accent: #ed64a6;
            --bg: #f7fafc;
            --card-bg: rgba(255, 255, 255, 0.8);
            --text: #2d3748;
            --text-muted: #718096;
            --glass-border: rgba(255, 255, 255, 0.3);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        [data-theme='dark'] {
            --bg: #1a202c;
            --card-bg: rgba(45, 55, 72, 0.8);
            --text: #f7fafc;
            --text-muted: #a0aec0;
            --glass-border: rgba(255, 255, 255, 0.05);
        }

        body { 
            font-family: 'Inter', sans-serif; 
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
            <div class='logo'>SPP<span>Blade</span></div>
            <div>
                <button class='btn' style='background:transparent; border:1px solid var(--glass-border);' onclick='document.body.dataset.theme = document.body.dataset.theme === "dark" ? "light" : "dark"'>🌓 Theme</button>
            </div>
        </nav>

        @yield('content')

        <footer class='footer'>
            &copy; {{ date('Y') }} SPP Framework • Powering Enterprise PHP with Blade
        </footer>
    </div>

    <!-- SPP-UX Runtime -->
    <script src='/school1/spp/admin/js/sppux.js'></script>
    <script src='/school1/spp/admin/js/spp-loader.js'></script>
    <script>
        // Minimal admin mock for standalone apps
        window.spp_admin = {
            api: async (action, data) => {
                console.log('API call:', action, data);
                return { success: true, data: {} };
            },
            apiPost: async (data) => {
                console.log('API Post:', data);
                return { success: true, data: {} };
            },
            callAppService: async (name, params) => {
                console.log('App Service call:', name, params);
                return { success: true, data: {} };
            }
        };
    </script>
</body>
</html>
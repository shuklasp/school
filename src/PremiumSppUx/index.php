<?php
require_once __DIR__ . '/../../spp/sppinit.php';
\SPP\App::getApp('PremiumSppUx');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PremiumSppUx - SPP-UX</title>
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
    <div id="app-root" data-spp-component="1" data-spp-type="ux" data-spp-path="/school1/src/PremiumSppUx/comp/main.js">
        <div class="loader">Initiating SPP-UX Environment...</div>
    </div>
</body>
</html>
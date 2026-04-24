<?php
require_once __DIR__ . '/spp/sppinit.php';
\SPP\App::getApp('SppUxApp');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SppUxApp - SPP-UX</title>
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
        body { margin: 0; font-family: 'Inter', system-ui, sans-serif; background: #f7fafc; color: #2d3748; }
        #app-root { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .loader { font-weight: 600; color: #667eea; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body>
    <div id="app-root" data-spp-component="1" data-spp-type="ux" data-spp-path="/school1/src/SppUxApp/comp/main.js">
        <div class="loader">Initiating SPP-UX Environment...</div>
    </div>
</body>
</html>
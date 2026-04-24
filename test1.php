<?php
define('SPP_DEBUG', true);
require_once __DIR__ . '/spp/sppinit.php';
new \SPP\App('test1');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>test1 - SPP-UX</title>
    <script src="/spp/admin/js/spp-loader.js" type="module"></script>
    <style>
        body { margin: 0; font-family: system-ui; background: #f4f7f6; }
        #app-root { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <div id="app-root" data-component="main">
        <div class="loader">Loading SPA Environment...</div>
    </div>
</body>
</html>
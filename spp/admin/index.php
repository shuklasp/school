<?php
/**
 * SPP Admin SPA Entry Point
 * 
 * This file serves the main Single Page Application for framework management.
 * Access is strictly restricted to development environments.
 * 
 * Route: /sppadmin/ -> spp/admin/index.php
 */

if (!defined('SPP_BASE_DIR')) {
    define('SPP_BASE_DIR', dirname(__DIR__));
}

require_once dirname(SPP_BASE_DIR) . '/vendor/autoload.php';
require_once SPP_BASE_DIR . '/sppinit.php';
require_once dirname(SPP_BASE_DIR) . '/global.php';

/**
 * checkDevMode function
 * Redirects or blocks access if the system profile is not 'dev'.
 */
function checkDevMode()
{
    $settingsPath = SPP_BASE_DIR . '/etc/settings.xml';
    if (!file_exists($settingsPath))
        return false;
    $xml = simplexml_load_file($settingsPath);
    return strtolower((string) $xml->profile) === 'dev';
}

if (!checkDevMode()) {
    http_response_code(403);
    die("Access Forbidden: SPP Administration Workbench is disabled in the current profile.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPP Admin | Developer Workbench</title>
    <meta name="description"
        content="SPP Framework Administration Portal — Manage modules, entities, forms and groups.">
    <meta name="robots" content="noindex, nofollow">
    <!-- Modern Typography -->
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <!-- Premium Stylesheet -->
    <link rel="stylesheet" href="css/admin.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/spp-logo.jpg">
</head>

<body class="dark-mode">

    <!-- Framework Message Center (SPPError Display) -->
    <div id="toast-container"></div>

    <!-- UI Logic Screens -->

    <!-- 1. Authentication Layer -->
    <div id="login-layer" class="active glass-overlay">
        <div class="glass-panel login-card">
            <header>
                <img src="images/spp-logo.jpg" alt="SPP Logo" class="brand-logo" onerror="this.style.display='none'">
                <h1>Workbench Login</h1>
                <p>System Administration Access</p>
            </header>
            <form id="login-form">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" placeholder="Enter username..." required autocomplete="username">
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" placeholder="Enter password..." required
                        autocomplete="current-password">
                </div>
                <div class="input-group">
                    <button type="submit" class="btn primary-btn pulse"
                        style="width:100%;justify-content:center;">Authenticate</button>
                </div>
            </form>
            <footer>
                <p>Powered by Satya Portal Pack &copy;
                    <?php echo date('Y'); ?>
                </p>
            </footer>
        </div>
    </div>

    <!-- 2. Management Workspace Layer -->
    <div id="workspace-layer">

        <!-- Navigation Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="logo-text">SPP <span>Admin</span></span>
                <span class="mode-badge">Dev Mode</span>
            </div>
            <div id="app-selector-container" style="margin-bottom: 2rem;"></div>
            <nav>
                <ul>
                    <li><a href="#system" class="nav-item active" data-view="system">
                            <span class="icon">🖥️</span> System Info
                        </a></li>
                    <li><a href="#modules" class="nav-item" data-view="modules">
                            <span class="icon">📦</span> Modules
                        </a></li>
                    <li><a href="#entities" class="nav-item" data-view="entities">
                            <span class="icon">🏗️</span> Entities
                        </a></li>
                    <li><a href="#forms" class="nav-item" data-view="forms">
                            <span class="icon">📝</span> Forms
                        </a></li>
                    <li><a href="#groups" class="nav-item" data-view="groups">
                            <span class="icon">👥</span> Groups
                        </a></li>
                    <li><a href="#access" class="nav-item" data-view="access">
                            <span class="icon">🛡️</span> Access Control
                        </a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar" id="user-avatar">A</div>
                    <div>
                        <div class="user-name" id="user-display-name">Admin</div>
                        <div class="user-role">Developer</div>
                    </div>
                </div>
                <button id="logout-btn" class="btn ghost-btn" style="width:100%;justify-content:center;">Logout</button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="content-header">
                <h2 id="view-title"><span class="view-icon">📦</span> System Modules</h2>
                <div class="header-actions" id="header-actions">
                    <!-- Contextual buttons injected here -->
                </div>
            </header>

            <section class="content-body">
                <!-- Data injection point -->
                <div id="view-container"></div>
            </section>
        </main>

    </div>

    <!-- 3. Global Modal System (Glassmorphism) -->
    <div id="modal-container" class="glass-overlay">
        <div class="glass-panel modal-box">
            <h3 id="modal-title">Editor</h3>
            <div id="modal-body"></div>
            <div class="modal-footer">
                <button class="btn secondary-btn" id="modal-close">Cancel</button>
                <button class="btn primary-btn" id="modal-save">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Application Script (Aggressive cache busting) -->
    <script src="js/admin.js?v=<?php echo time(); ?>"></script>
    <!-- Inline Override for stale caches -->
    <script>
        setTimeout(() => {
            if (window.admin) {
                window.admin.escapeHtml = function (str) {
                    if (str === null || str === undefined) return '';
                    const div = document.createElement('div');
                    div.textContent = String(str);
                    return div.innerHTML;
                };
                window.admin.escapeAttr = function (str) {
                    if (str === null || str === undefined) return '';
                    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                };
                console.log("SPP Admin Cache Override Applied.");
            }
        }, 1000);
    </script>
</body>

</html>
<?php

namespace SPPMod\SPPAjax;

use Symfony\Component\Yaml\Yaml;

/**
 * class SPPAjax
 *
 * SPA (Single Page Application) dispatch engine for the SPP framework.
 *
 * Handles two request tracks:
 *  - Page fragments: resolves a page via Pages::getPage(), captures its HTML,
 *    and returns a JSON envelope { status, html, title }.
 *  - Services: resolves a named service from the registry, includes the
 *    service script from /src/serv/, and returns the $response array as JSON.
 *
 * All SPA requests are identified by the presence of ?__spa=1 or the
 * X-SPP-Ajax: 1 HTTP header.
 *
 * Entry point: SPPAjax::handle() — call this in index.php before showPage().
 *
 * @author Satya Prakash Shukla
 */
class SPPAjax extends \SPP\SPPObject
{
    /** @var array<string,mixed>|null Parsed services.yml cache */
    private static ?array $serviceRegistry = null;

    // -------------------------------------------------------------------------
    // Public entry points
    // -------------------------------------------------------------------------

    /**
     * Main entry point. Called from index.php when isAjaxRequest() is true.
     * Routes to page dispatch or service dispatch based on GET parameters.
     */
    public static function handle(): void
    {
        if (!self::isSpaEnabled()) {
            self::respond('error', ['message' => 'SPA mode is disabled.'], 503);
        }

        // Component Action: ?__svc=component_action
        if (isset($_GET['__svc']) && $_GET['__svc'] === 'component_action') {
            self::dispatchComponentAction();
            return;
        }

        // Service call: ?__svc=service_name
        if (isset($_GET['__svc'])) {
            self::dispatchService(trim($_GET['__svc']));
            return;
        }

        // Component JS: ?__js_comp=ComponentName
        if (isset($_GET['__js_comp'])) {
            self::dispatchComponentJS(trim($_GET['__js_comp']));
            return;
        }

        // Page fragment request: ?q=page_name&__spa=1
        self::dispatchPage();
    }

    /**
     * Handles an AJAX action from a generated JS component by routing it
     * back to the corresponding PHPComponent class and method.
     */
    public static function dispatchComponentAction(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $compName = $input['component'] ?? null;
        $method = $input['method'] ?? null;
        $data = $input['data'] ?? [];

        if (!$compName || !$method) {
            self::respond('error', ['message' => 'Invalid component action request.']);
        }

        try {
            $app = \SPP\Scheduler::getContext();
            $className = "App\\" . ucfirst($app) . "\\Components\\" . $compName;
            
            if (!class_exists($className)) {
                self::respond('error', ['message' => "Component '{$compName}' not found."]);
            }

            $component = new $className();
            if (!method_exists($component, $method)) {
                self::respond('error', ['message' => "Method '{$method}' not found in component '{$compName}'."]);
            }

            // Execute the action
            $result = $component->$method($data);
            
            self::respond('ok', [
                'result' => $result,
                'state' => $component->getState()
            ]);
        } catch (\Throwable $e) {
            self::respond('error', ['message' => 'Component Action Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Dynamically generates and serves the JS for a PHP component.
     */
    public static function dispatchComponentJS(string $name): void
    {
        header('Content-Type: application/javascript; charset=utf-8');
        try {
            $app = \SPP\Scheduler::getContext();
            $className = "App\\" . ucfirst($app) . "\\Components\\" . $name;
            echo \SPPMod\SPPView\JSGenerator::generate($className);
        } catch (\Exception $e) {
            echo "// Error generating component JS: " . $e->getMessage();
        }
        exit;
    }

    /**
     * Returns true if this is an SPA request.
     */
    public static function isAjaxRequest(): bool
    {
        return (isset($_GET['__spa']) && $_GET['__spa'] === '1')
            || (isset($_SERVER['HTTP_X_SPP_AJAX']) && $_SERVER['HTTP_X_SPP_AJAX'] === '1')
            || (isset($_SERVER['X-SPP-Ajax']) && $_SERVER['X-SPP-Ajax'] === '1');
    }

    /**
     * Returns true when spa_enabled is set to true in module config.
     */
    public static function isSpaEnabled(): bool
    {
        $val = \SPP\Module::getConfig('spa_enabled', 'sppajax');
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    // -------------------------------------------------------------------------
    // Page fragment dispatcher
    // -------------------------------------------------------------------------

    /**
     * Resolves the requested page via Pages::getPage(), captures its output,
     * and returns JSON { status, html, title }.
     */
    public static function dispatchPage(): void
    {
        $q = isset($_GET['q']) ? trim($_GET['q']) : null;

        try {
            $page = \SPPMod\SPPView\Pages::getPage($q);
        } catch (\SPP\SPPException $e) {
            self::respond('error', ['message' => $e->getMessage()], 404);
        }

        if (empty($page['url'])) {
            self::respond('error', ['message' => 'Page not found.'], 404);
        }

        $pageDir = \SPP\Module::getConfig('spa_page_dir', 'sppajax') ?: '/src/pages';
        $filename = SPP_APP_DIR . $pageDir . '/' . ltrim($page['url'], '/');

        // Resolve symlinks and prevent path traversal
        $realBase = realpath(SPP_APP_DIR . $pageDir);
        $realFile = realpath($filename);

        if ($realFile === false || !str_starts_with($realFile, $realBase)) {
            self::respond('error', ['message' => 'Forbidden.'], 403);
        }

        if (!file_exists($realFile) || !is_file($realFile)) {
            self::respond('error', ['message' => 'Page file not found.'], 404);
        }

        // Capture the page output
        ob_start();
        try {
            include $realFile;
        } catch (\Throwable $e) {
            ob_end_clean();
            \SPPMod\SPPLogger\SPP_Logger::error("SPPAjax Page Exception ($filename): " . $e->getMessage());
            self::respond('error', ['message' => 'Page render error: ' . $e->getMessage()], 500);
        }
        $html = ob_get_clean();

        self::respond('ok', [
            'html' => $html,
            'title' => \SPPMod\SPPView\ViewPage::getPageTitle() ?? $page['name'] ?? '',
            'page' => $page['name'] ?? '',
            'params' => $page['params'] ?? [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Service dispatcher
    // -------------------------------------------------------------------------

    /**
     * Dispatches to a registered service script and returns its $response as JSON.
     * Only services declared in services.yml can be called.
     */
    public static function dispatchService(string $name): void
    {
        // Sanitize
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);

        $service = self::findService($name);
        if ($service === null) {
            self::respond('error', ['message' => 'Unknown service: ' . $name], 403);
        }

        // SPA Native Auth interceptor protecting endpoint dynamically
        if (!empty($service['requires_auth']) && filter_var($service['requires_auth'], FILTER_VALIDATE_BOOLEAN)) {
            if (!\SPPMod\SPPAuth\SPPAuth::authSessionExists()) {
                self::respond('error', ['message' => 'Unauthorized component execution.'], 401);
            }
        }

        // Enforce HTTP method constraint
        $allowedMethod = strtoupper($service['method'] ?? 'GET');
        if ($_SERVER['REQUEST_METHOD'] !== $allowedMethod) {
            self::respond('error', [
                'message' => "Service '{$name}' requires {$allowedMethod}.",
            ], 405);
        }

        // Resolve script path securely
        $servDir = \SPP\Module::getConfig('spa_service_dir', 'sppajax') ?: '/src/serv';
        $script = basename($service['script']); // strip any directory component
        $fullPath = SPP_APP_DIR . $servDir . '/' . $script;

        $realBase = realpath(SPP_APP_DIR . $servDir);
        $realFile = realpath($fullPath);

        if ($realFile === false || !str_starts_with($realFile, $realBase)) {
            self::respond('error', ['message' => 'Forbidden.'], 403);
        }

        if (!file_exists($realFile) || !is_file($realFile)) {
            self::respond('error', ['message' => "Service script '{$script}' not found."], 404);
        }

        // Execute the service script — it must set $response array
        $response = [];
        try {
            include $realFile;
        } catch (\Throwable $e) {
            \SPPMod\SPPLogger\SPP_Logger::error("SPPAjax Service Crash ($name): " . $e->getMessage());
            self::respond('error', ['message' => 'Service error: ' . $e->getMessage()], 500);
        }

        // Validate $response shape
        if (!is_array($response) || !isset($response['status'])) {
            self::respond('error', ['message' => 'Service did not return a valid $response array.'], 500);
        }

        // Normalize redirect: convert page name → URL if needed
        if ($response['status'] === 'redirect' && isset($response['redirect'])) {
            try {
                $dest = \SPPMod\SPPView\Pages::getPage($response['redirect']);
                if (!empty($dest['url'])) {
                    $response['redirect_url'] = '?q=' . urlencode($response['redirect']);
                }
            } catch (\Throwable) {
                // redirect value may already be a full URL — leave as-is
            }
        }

        self::respond($response['status'], $response);
    }

    // -------------------------------------------------------------------------
    // Service registry
    // -------------------------------------------------------------------------

    /**
     * Returns a flattened list of all registered services from both YAML and DB.
     */
    public static function listServices(): array
    {
        return self::loadServiceRegistry();
    }

    /**
     * Looks up a service by name from the services registry YAML.
     * @return array<string,string>|null
     */
    private static function findService(string $name): ?array
    {
        $registry = self::loadServiceRegistry();
        foreach ($registry as $svc) {
            if (isset($svc['name']) && $svc['name'] === $name) {
                return $svc;
            }
        }
        return null;
    }

    private static function loadServiceRegistry(): array
    {
        if (self::$serviceRegistry !== null) {
            return self::$serviceRegistry;
        }

        $registry = [];
        
        // 1. Load from YAML
        $file = self::getServiceRegistryFile();
        if (file_exists($file)) {
            try {
                $parsed = Yaml::parseFile($file);
                $ymlServices = $parsed['services'] ?? [];
                foreach ($ymlServices as &$svc) {
                    $svc['source'] = 'yaml';
                }
                $registry = array_merge($registry, $ymlServices);
            } catch (\Exception $e) {}
        }

        // 2. Load from Database
        if (\SPP\Module::isEnabled('sppdb')) {
            self::ensureDbSchema();
            try {
                $db = new \SPPMod\SPPDB\SPPDB();
                $dbServices = $db->execute_query('SELECT name, script, method FROM ' . \SPPMod\SPPDB\SPPDB::sppTable('sppajax_services'));
                foreach ($dbServices as &$svc) {
                    $svc['source'] = 'db';
                }
                $registry = array_merge($registry, $dbServices);
            } catch (\Exception $e) {}
        }

        self::$serviceRegistry = $registry;
        return self::$serviceRegistry;
    }

    // -------------------------------------------------------------------------
    // Response builder
    // -------------------------------------------------------------------------

    /**
     * Sends a JSON response and terminates execution.
     *
     * @param string              $status HTTP semantic status string: ok|redirect|error|reload
     * @param array<string,mixed> $data   Payload merged into the response envelope
     * @param int                 $code   HTTP status code
     */
    public static function respond(string $status, array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('X-SPP-Ajax-Response: 1');

        $envelope = array_merge(['status' => $status], $data);
        echo json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // -------------------------------------------------------------------------
    // Registry management helpers (PHP-side, for setup/admin use)
    // -------------------------------------------------------------------------

    /**
     * Registers a new service programmatically into either services.yml or the database.
     */
    public static function registerService(string $name, string $script, string $method = 'POST', string $source = 'yaml'): bool
    {
        if ($source === 'yaml') {
            $file = self::getServiceRegistryFile();
            $parsed = [];
            if (file_exists($file)) {
                $parsed = Yaml::parseFile($file) ?? [];
            }
            $services = $parsed['services'] ?? [];
            $updated = false;
            foreach ($services as &$svc) {
                if ($svc['name'] === $name) {
                    $svc['script'] = basename($script);
                    $svc['method'] = strtoupper($method);
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $services[] = [
                    'name' => preg_replace('/[^a-zA-Z0-9_\-]/', '', $name),
                    'script' => basename($script),
                    'method' => strtoupper($method),
                ];
            }
            $parsed['services'] = $services;
            file_put_contents($file, Yaml::dump($parsed, 3, 4), LOCK_EX);
        } else if ($source === 'db') {
            self::ensureDbSchema();
            $db = new \SPPMod\SPPDB\SPPDB();
            $db->execute_query(
                'REPLACE INTO ' . \SPPMod\SPPDB\SPPDB::sppTable('sppajax_services') . ' (name, script, method) VALUES (?, ?, ?)',
                [$name, basename($script), strtoupper($method)]
            );
        }

        // Bust cache
        self::$serviceRegistry = null;
        return true;
    }

    /**
     * Removes a service from either services.yml or the database.
     */
    public static function unregisterService(string $name, string $source = 'yaml'): bool
    {
        if ($source === 'yaml') {
            $file = self::getServiceRegistryFile();
            if (!file_exists($file)) return false;
            $parsed = Yaml::parseFile($file) ?? [];
            $services = $parsed['services'] ?? [];
            $filtered = array_values(array_filter($services, fn($s) => $s['name'] !== $name));
            if (count($filtered) === count($services)) return false;
            $parsed['services'] = $filtered;
            file_put_contents($file, Yaml::dump($parsed, 3, 4), LOCK_EX);
        } else if ($source === 'db') {
            if (\SPP\Module::isEnabled('sppdb')) {
                $db = new \SPPMod\SPPDB\SPPDB();
                $db->execute_query('DELETE FROM ' . \SPPMod\SPPDB\SPPDB::sppTable('sppajax_services') . ' WHERE name=?', [$name]);
            }
        }

        self::$serviceRegistry = null;
        return true;
    }

    /**
     * Ensures the database schema for AJAX services exists.
     */
    public static function ensureDbSchema(): void
    {
        if (!\SPP\Module::isEnabled('sppdb')) return;
        $db = new \SPPMod\SPPDB\SPPDB();
        $db->execute_query('CREATE TABLE IF NOT EXISTS ' . \SPPMod\SPPDB\SPPDB::sppTable('sppajax_services') . ' (
            id      INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name    VARCHAR(255) NOT NULL UNIQUE,
            script  VARCHAR(255) NOT NULL,
            method  VARCHAR(10)  NOT NULL DEFAULT "POST"
        )');
    }

    /**
     * Internal helper to resolve the service registry file path correctly.
     */
    private static function getServiceRegistryFile(): string
    {
        $appname = \SPP\Scheduler::getContext();
        $file = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'services.yml';
        
        if (!file_exists($file)) {
            $registryPath = \SPP\Module::getConfig('spa_services_registry', 'sppajax');
            if ($registryPath) {
                $file = SPP_APP_DIR . $registryPath;
            } else {
                $file = SPP_APP_DIR . '/etc/services.yml';
            }
        }
        return $file;
    }
}

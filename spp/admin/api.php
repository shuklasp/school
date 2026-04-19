<?php
/**
 * SPP Admin SPA API Controller
 * 
 * Handles all AJAX requests from the Administration SPA. Implements mode gating,
 * user authentication, and resource management for the framework.
 * 
 * Access: Restricted to 'dev' mode (set in spp/etc/settings.xml) and authenticated users.
 */

// Capture any PHP warnings/notices so they don't corrupt JSON output
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');


// Define framework paths
if (!defined('SPP_BASE_DIR')) {
    define('SPP_BASE_DIR', dirname(__DIR__));
}
/* 
// Pre-load classes required for session deserialization BEFORE session_start()
// sppinit.php calls session_start() which unserializes SPPUserSession objects.
// If these classes aren't loaded beforehand, PHP creates __PHP_Incomplete_Class.
$coreDir = SPP_BASE_DIR . '/core';
$authDir = SPP_BASE_DIR . '/modules/spp/sppauth';
$dbDir = SPP_BASE_DIR . '/modules/spp/sppdb';
$cfgDir = SPP_BASE_DIR . '/modules/spp/sppconfig';

// Core classes needed by the session chain
foreach (['class.sppobject.php', 'class.sppsession.php', 'class.sppbase.php', 'class.sppexception.php'] as $f) {
    if (file_exists($coreDir . '/' . $f))
        require_once $coreDir . '/' . $f;
}
// Auth module classes that get serialized into the session
foreach (['class.sppuser.php', 'class.sppusersession.php'] as $f) {
    if (file_exists($authDir . '/' . $f))
        require_once $authDir . '/' . $f;
}
// Database class (used by SPPUser and SPPUserSession)
if (file_exists($dbDir . '/class.sppdb.php'))
    require_once $dbDir . '/class.sppdb.php';
if (file_exists($cfgDir . '/class.sppconfig.php'))
    require_once $cfgDir . '/class.sppconfig.php';
 */
// Load symfony autoloader if available
$autoloaderPath = dirname(SPP_BASE_DIR) . '/vendor/autoload.php';
if (file_exists($autoloaderPath)) {
    require_once $autoloaderPath;
}

require_once SPP_BASE_DIR . '/sppinit.php';


// Load global handlers if available
$globalPath = dirname(SPP_BASE_DIR) . '/global.php';
if (file_exists($globalPath)) {
    require_once $globalPath;
}

use SPPMod\SPPAuth\SPPAuth;
use SPP\SPPError;


/**
 * sendResponse function
 * 
 * Helper to transmit JSON results back to the SPA. Automatically attaches
 * any pending SPPError messages for UI notification.
 *
 * @param bool $success Whether the operation completed successfully.
 * @param array $data Payload to return on success.
 * @param string $message User-facing message or context.
 */
function sendResponse($success, $data = [], $message = '')
{
    // Discard any PHP warnings/notices that leaked into the output buffer
    $phpOutput = ob_get_clean();

    $errorsHtml = '';
    if (class_exists('SPP\\SPPError')) {
        $errorsHtml = SPPError::getUlErrors();
        SPPError::destroyErrors();
    }

    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'errors_html' => $errorsHtml
    ];

    // Attach PHP errors as debug info (only in dev — useful for diagnostics)
    if (!empty($phpOutput)) {
        // Convert to UTF-8 to prevent json_encode from failing
        $response['_debug_output'] = mb_convert_encoding($phpOutput, 'UTF-8', 'auto');
    }

    $json = json_encode($response);
    if ($json === false) {
        // Strip data entirely in case it contains recursive/invalid structs causing encode failure
        $response['data'] = [];
        $response['_debug_output'] = "JSON Encode Failed: " . json_last_error_msg();
        echo json_encode($response);
    } else {
        echo $json;
    }
    exit;
}

/**
 * getModuleStatusFromManifests
 * 
 * Reads the status of a module directly from modules.xml files,
 * checking system-level and per-app manifests.
 *
 * @param string $modname Module internal name
 * @return string 'active', 'inactive', or 'unknown'
 */
function getModuleStatusFromManifests(string $modname, string $appname, string $type = 'any'): string
{
    $candidates = [];

    // 1. Primary App Preference: SPP_ETC_DIR/apps/<app>/modsconf/
    if (defined('SPP_ETC_DIR') && $appname !== '') {
        $modsconfDir = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modsconf';
        if (file_exists($modsconfDir . DIRECTORY_SEPARATOR . 'modules.yml')) {
            $candidates[] = $modsconfDir . DIRECTORY_SEPARATOR . 'modules.yml';
        } elseif (file_exists($modsconfDir . DIRECTORY_SEPARATOR . 'modules.xml')) {
            $candidates[] = $modsconfDir . DIRECTORY_SEPARATOR . 'modules.xml';
        }
    }

    // 2. User Overrides (Highest Priority - Root /etc/apps/)
    if (defined('APP_ETC_DIR') && $appname !== '') {
        $userModsconf = APP_ETC_DIR . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modsconf';
        if (file_exists($userModsconf . DIRECTORY_SEPARATOR . 'modules.yml')) {
            $candidates[] = $userModsconf . DIRECTORY_SEPARATOR . 'modules.yml';
        } elseif (file_exists($userModsconf . DIRECTORY_SEPARATOR . 'modules.xml')) {
            $candidates[] = $userModsconf . DIRECTORY_SEPARATOR . 'modules.xml';
        }
    }

    // 3. Framework-level App Defaults (spp/etc/apps/)
    if (defined('SPP_ETC_DIR')) {
        if ($appname !== '') {
            $candidates[] = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modules.yml';
            $candidates[] = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modules.xml';
        }
        
        // 4. Global Framework Defaults (spp/etc/)
        $candidates[] = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'modules.yml';
        $candidates[] = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'modules.xml';
    }

    foreach ($candidates as $file) {
        if (!file_exists($file))
            continue;

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === 'yml' || $ext === 'yaml') {
            try {
                $yml = \Symfony\Component\Yaml\Yaml::parseFile($file);
                $mods = $yml['modules'] ?? [];
                foreach ($mods as $m) {
                    $mArr = (array) $m;
                    if (($mArr['name'] ?? $mArr['modname'] ?? '') === $modname) {
                        return (string) ($mArr['status'] ?? 'active');
                    }
                }
            } catch (\Exception $e) {
            }
        } else {
            $xml = @simplexml_load_file($file);
            if ($xml === false)
                continue;
            foreach ($xml->module as $mod) {
                $name = (string) ($mod->modname ?? $mod->name ?? '');
                if ($name === $modname) {
                    return (string) ($mod->status ?? 'active');
                }
            }
        }
    }
    
    // Fallback: Module not explicitly listed in any manifest.
    // If not configured, treat as inactive.
    return 'inactive';
}

/**
 * repairNamespace
 * 
 * Re-inserts backslashes into framework class names if they were stripped 
 * during transit (e.g. SPPModSPPAuthSPPUser -> \SPPMod\SPPAuth\SPPUser).
 *
 * @param string $class The class name to repair.
 * @return string The repaired namespace.
 */
function repairNamespace($class)
{
    if (empty($class) || $class === 'null')
        return '';
    if (strpos($class, '\\') !== false)
        return $class; // Already has backslashes

    // Pattern for framework namespaces: SPPMod, SPP, etc.
    if (strpos($class, 'SPPMod') === 0) {
        // Break into known segments
        $repaired = str_replace(
            ['SPPMod', 'SPPAuth', 'SPPGroup', 'SPPDB', 'SPPConfig', 'SPPEntity'],
            ['\\SPPMod', '\\SPPAuth', '\\SPPGroup', '\\SPPDB', '\\SPPConfig', '\\SPPEntity'],
            $class
        );

        // Handle common entities
        $repaired = str_replace(
            ['SPPUser', 'SPPGroupMember'],
            ['\\SPPUser', '\\SPPGroupMember'],
            $repaired
        );

        // Clean up any double backslashes
        return str_replace('\\\\', '\\', $repaired);
    }

    return $class;
}

/**
 * checkDevMode function
 * 
 * Validates that the system is currently running in 'dev' profile.
 * Returns true if allowed, false otherwise.
 */
function checkDevMode()
{
    try {
        $settingsPath = SPP_BASE_DIR . '/etc/settings.xml';
        if (!file_exists($settingsPath))
            return false;

        $xml = simplexml_load_file($settingsPath);
        $profile = (string) $xml->profile;

        return strtolower($profile) === 'dev';
    } catch (Exception $e) {
        return false;
    }
}

// 1. Initial Security: Check if path is even accessible
if (!checkDevMode()) {
    http_response_code(403);
    sendResponse(false, [], "Access Denied: Administration portal is only accessible in 'dev' mode.");
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

if (!$action) {
    sendResponse(false, [], "No action specified.");
}

try {
    // 2. Authentication Handling
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            sendResponse(false, [], "Username and password are required.");
        }

        try {
            $session = SPPAuth::login($username, $password);
            sendResponse(true, ['user' => $username], "Login successful.");
        } catch (\SPP\Exceptions\UserNotFoundException $e) {
            sendResponse(false, [], "Invalid username or password.");
        } catch (\SPP\Exceptions\UserAuthenticationException $e) {
            sendResponse(false, [], "Invalid username or password.");
        } catch (\SPP\Exceptions\UserBannedException $e) {
            sendResponse(false, [], "This account has been disabled.");
        } catch (\Exception $e) {
            sendResponse(false, [], "Authentication error: " . $e->getMessage());
        }
    }

    // Check session for auth-check endpoint
    if ($action === 'check_auth') {
        try {
            if (SPPAuth::authSessionExists()) {
                sendResponse(true, ['username' => SPPAuth::get('UserName')], "User is active.");
            } else {
                sendResponse(false, [], "Not authenticated.");
            }
        } catch (\Exception $e) {
            sendResponse(false, [], "Not authenticated.");
        }
    }

    // All remaining actions require authentication
    $isAuthenticated = false;
    try {
        $isAuthenticated = SPPAuth::authSessionExists();
    } catch (\Exception $e) {
        sendResponse(false, [], "Session exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    }

    if (!$isAuthenticated) {
        sendResponse(false, [], "Session expired. Please login.");
    }

    if ($action === 'logout') {
        SPPAuth::logout();
        sendResponse(true, [], "You have been logged out.");
    }

    /**
     * get_profile: Retrieves metadata for the currently authenticated user.
     */
    if ($action === 'get_profile') {
        try {
            $username = SPPAuth::get('UserName');
            if (!$username) {
                sendResponse(false, [], "Session data missing.");
            }
            $user = new \SPPMod\SPPAuth\SPPUser($username);
            
            // Get role for display
            $role = "Developer"; // Default for workbench
            
            sendResponse(true, [
                'id' => $user->getId(),
                'username' => $user->username,
                'email' => $user->email,
                'role' => $role
            ]);
        } catch (\Exception $e) {
            sendResponse(false, [], "Profile fetch failed: " . $e->getMessage());
        }
    }

    // 3. Resolve App Context
    $appname = $_REQUEST['appname'] ?? $_GET['appname'] ?? $_REQUEST['context'] ?? \SPP\Scheduler::getContext();
    if (empty($appname) || $appname === 'undefined')
        $appname = 'default';

    // Explicitly set context for the duration of the request
    try {
        // Check if context is already registered, if not, instantiate it
        try {
            \SPP\Scheduler::getProcObj($appname);
        } catch (\Exception $e) {
            // Register it on the fly if folder exists
            $appDir = SPP_APP_DIR . SPP_DS . 'etc' . SPP_DS . 'apps' . SPP_DS . $appname;
            if (is_dir($appDir) || $appname === 'default') {
                new \SPP\App($appname, false, 1); // Minimized init to register it
            }
        }
        \SPP\Scheduler::setContext($appname);
    } catch (\Throwable $e) {
        // Fallback only if registration failed completely
        $appname = 'default';
        \SPP\Scheduler::setContext($appname);
    }

    // 4. Resource Management Logic
    switch ($action) {
        /**
         * list_apps: Returns a list of all registered applications in etc/apps.
         */
        case 'list_apps':
            $apps = [];
            $appsDir = APP_ETC_DIR;
            if (is_dir($appsDir)) {
                $dirs = scandir($appsDir);
                foreach ($dirs as $d) {
                    if ($d !== '.' && $d !== '..' && is_dir($appsDir . DIRECTORY_SEPARATOR . $d)) {
                        $apps[] = [
                            'name' => $d,
                            'path' => $appsDir . DIRECTORY_SEPARATOR . $d
                        ];
                    }
                }
            }
            sendResponse(true, ['apps' => $apps]);
            break;

        case 'list_modules':
            $modules = [];
            if (class_exists('\\SPP\\SPPFS')) {

                // 1. System modules (SPP core)
                $sys_yml = \SPP\SPPFS::findFile('module.yml', SPP_MODULES_DIR) ?: [];
                $sys_xml = \SPP\SPPFS::findFile('module.xml', SPP_MODULES_DIR) ?: [];

                // 2. User/App modules
                $user_mod_dir = SPP_APP_DIR . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $appname;
                $user_yml = is_dir($user_mod_dir) ? (\SPP\SPPFS::findFile('module.yml', $user_mod_dir) ?: []) : [];
                $user_xml = is_dir($user_mod_dir) ? (\SPP\SPPFS::findFile('module.xml', $user_mod_dir) ?: []) : [];

                $manifests = [];
                // Collect and prioritize (type: system) - System modules are available in all contexts
                foreach (array_merge($sys_yml, $sys_xml) as $f) {
                    $name = basename(dirname($f));
                    if (!isset($manifests[$name])) {
                        $manifests[$name] = ['file' => $f, 'type' => 'system'];
                    }
                }

                // Collect and prioritize (type: user - allows overrides if names match)
                foreach (array_merge($user_yml, $user_xml) as $f) {
                    $name = basename(dirname($f));
                    $manifests[$name] = ['file' => $f, 'type' => 'user'];
                }

                foreach ($manifests as $name => $mInfo) {
                    try {
                        $file = $mInfo['file'];
                        $type = $mInfo['type'];
                        $mod = new \SPP\Module($file);

                        // Check if module has config variables
                        $hasConfig = !empty($mod->ConfigVariables);
                        if (!$hasConfig) {
                            // Check filesystem for config file
                            $confDir = ($type === 'system')
                                ? SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modsconf' . DIRECTORY_SEPARATOR . $name
                                : APP_ETC_DIR . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modsconf' . DIRECTORY_SEPARATOR . $name;
                            if (is_dir($confDir))
                                $hasConfig = true;
                        }

                        $status = getModuleStatusFromManifests($name, $appname, $type);

                        $modules[] = [
                            'name' => $mod->InternalName ?: $name,
                            'public_name' => $mod->PublicName ?: ($mod->InternalName ?: $name),
                            'version' => $mod->Version ?: '1.0',
                            'description' => $mod->PublicDesc ?: '',
                            'author' => $mod->Author ?? 'Unknown',
                            'active' => $status === 'active',
                            'type' => $type,
                            'path' => $mod->ModPath,
                            'dependencies' => (array) ($mod->Dependencies ?? []),
                            'module_group' => $mod->ModuleGroup ?: 'General',
                            'has_config' => $hasConfig
                        ];
                    } catch (\Throwable $e) {
                        // Log technical error in debug details but continue listing others
                        if (!isset($response['_debug_errors']))
                            $response['_debug_errors'] = [];
                        $response['_debug_errors'][] = "Error loading module '{$name}': " . $e->getMessage();
                    }
                }
            }
            sendResponse(true, ['modules' => $modules]);
            break;

        case 'scan_module':
            $modname = $_POST['modname'] ?? '';
            $appname = $appname; // from earlier resolution
            if (!$modname) sendResponse(false, [], "Module name required.");
            
            try {
                // Find module manifest using centralized resolver
                $manifest = \SPP\Module::findManifestPath($modname);
                
                if (!$manifest) sendResponse(false, [], "Module manifest not found for '{$modname}'.");
                
                $mod = new \SPP\Module($manifest);
                $deltas = $mod->getInstallationDeltas();
                
                sendResponse(true, ['deltas' => $deltas]);
            } catch (\Exception $e) {
                sendResponse(false, [], "Scan failed: " . $e->getMessage());
            }
            break;

        case 'install_module':
            $modname = $_POST['modname'] ?? '';
            if (!$modname) sendResponse(false, [], "Module name required.");
            
            try {
                $manifest = \SPP\Module::findManifestPath($modname);
                
                if (!$manifest) sendResponse(false, [], "Module manifest not found for '{$modname}'.");
                
                $mod = new \SPP\Module($manifest);
                $log = $mod->runInstallation();
                
                sendResponse(true, ['log' => $log], "Installation completed successfully.");
            } catch (\Exception $e) {
                sendResponse(false, [], "Installation failed: " . $e->getMessage());
            }
            break;

        /**
         * list_entities: Returns metadata for all entity definitions.
         */
        case 'list_entities':
            $entities = \SPPMod\SPPEntity\SPPEntity::listAvailableEntities();
            sendResponse(true, ['entities' => array_values($entities)]);
            break;

        /**
         * parse_entity_yaml: Converts YAML string to JSON config.
         */
        case 'parse_entity_yaml':
            $yaml = $_POST['yaml'] ?? '';
            try {
                $config = \Symfony\Component\Yaml\Yaml::parse($yaml);
                sendResponse(true, ['config' => $config]);
            } catch (\Exception $e) {
                sendResponse(false, [], "YAML Parse Error: " . $e->getMessage());
            }
            break;

        /**
         * dump_entity_yaml: Converts JSON config to YAML string.
         */
        case 'dump_entity_yaml':
            $config = json_decode($_POST['config'] ?? '{}', true);
            try {
                $yaml = \Symfony\Component\Yaml\Yaml::dump($config, 4, 2);
                sendResponse(true, ['yaml' => $yaml]);
            } catch (\Exception $e) {
                sendResponse(false, [], "YAML Dump Error: " . $e->getMessage());
            }
            break;

        /**
         * save_entity_config: Saves YAML and generates PHP skeleton.
         */
        case 'save_entity_config':
            $name = trim($_POST['name'] ?? '');
            $configRaw = $_POST['config'] ?? '';
            $config = json_decode($configRaw, true);
            
            if (empty($name) || empty($config)) {
                sendResponse(false, [], "Entity name and configuration are required.");
            }

            try {
                \SPPMod\SPPEntity\SPPEntity::saveEntityDefinition($name, $appname, $config);
                sendResponse(true, [], "Entity '$name' and skeleton class saved successfully.");
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to save entity: " . $e->getMessage());
            }
            break;

        /**
         * delete_entity: Removes a YAML entity configuration file.
         */
        case 'delete_entity':
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                sendResponse(false, [], "Entity name is required.");
            }

            $filePath = APP_ETC_DIR . '/' . $appname . '/entities/' . strtolower($name) . '.yml';
            if (file_exists($filePath)) {
                unlink($filePath);
                sendResponse(true, [], "Entity '{$name}' deleted successfully.");
            } else {
                sendResponse(false, [], "Entity '{$name}' not found.");
            }
            break;

        /**
         * list_forms: Scans the application's etc/forms directory for YAML form definitions.
         */
        case 'list_forms':
            $formsDir = APP_ETC_DIR . '/' . $appname . '/forms';
            $formMap = []; // Dedup map: [name => file_data]
            if (is_dir($formsDir)) {
                $ymlFiles = glob($formsDir . '/*.yml');
                $xmlFiles = glob($formsDir . '/*.xml');
                $allFiles = array_merge($ymlFiles, $xmlFiles);
                foreach ($allFiles as $file) {
                    $name = pathinfo($file, PATHINFO_FILENAME);
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    
                    // YAML priority: Only add if not seen, or if this is .yml and we previously saw .xml
                    if (!isset($formMap[$name]) || $ext === 'yml') {
                        $formMap[$name] = [
                            'name' => $name,
                            'type' => strtoupper($ext),
                            'content' => file_get_contents($file),
                            'size' => filesize($file),
                            'modified' => date('Y-m-d H:i', filemtime($file))
                        ];
                    }
                }
            }
            sendResponse(true, ['forms' => array_values($formMap)]);
            break;

        /**
         * save_form: Creates or updates a YAML form definition.
         */
        case 'save_form':
            $name = trim($_POST['name'] ?? '');
            $content = $_POST['content'] ?? '';
            $type = strtolower(trim($_POST['type'] ?? 'yml'));
            $checkDup = ($_POST['check_duplicate'] ?? 'false') === 'true';

            if (empty($name) || empty($content)) {
                sendResponse(false, [], "Form name and content are required.");
            }

            $formsDir = APP_ETC_DIR . '/' . $appname . '/forms';
            if (!is_dir($formsDir))
                mkdir($formsDir, 0777, true);

            // Check for duplicates if requested (new forms)
            if ($checkDup) {
                if (file_exists($formsDir . '/' . strtolower($name) . '.yml') || 
                    file_exists($formsDir . '/' . strtolower($name) . '.xml')) {
                    sendResponse(false, [], "A form with the name '{$name}' already exists. Please choose a different name.");
                }
            }

            // Clean extension
            $ext = in_array($type, ['xml', 'yml', 'yaml']) ? $type : 'yml';
            $filePath = $formsDir . '/' . strtolower($name) . '.' . $ext;
            file_put_contents($filePath, $content);
            sendResponse(true, [], "Form '{$name}' saved successfully.");
            break;

        /**
         * delete_form: Removes a form definition file.
         */
        case 'delete_form':
            $name = trim($_POST['name'] ?? '');
            $type = strtolower(trim($_POST['type'] ?? 'yml'));
            if (empty($name)) {
                sendResponse(false, [], "Form name is required.");
            }

            $formsDir = APP_ETC_DIR . '/' . $appname . '/forms';
            // Try both yml and xml
            $candidates = [
                $formsDir . '/' . strtolower($name) . '.yml',
                $formsDir . '/' . strtolower($name) . '.yaml',
                $formsDir . '/' . strtolower($name) . '.xml',
            ];

            $deleted = false;
            foreach ($candidates as $path) {
                if (file_exists($path)) {
                    unlink($path);
                    $deleted = true;
                }
            }

            if ($deleted) {
                sendResponse(true, [], "Form '{$name}' deleted successfully.");
            } else {
                sendResponse(false, [], "Form '{$name}' not found.");
            }
            break;

        /**
         * get_form_config: Returns structured JSON for a form YAML.
         */
        case 'get_form_config':
            $name = $_GET['name'] ?? $_POST['name'] ?? '';
            $path = APP_ETC_DIR . '/' . $appname . '/forms/' . strtolower($name) . '.yml';
            try {
                if (file_exists($path)) {
                    $config = \Symfony\Component\Yaml\Yaml::parseFile($path);
                    sendResponse(true, ['config' => $config]);
                } else {
                    sendResponse(false, [], "Form '{$name}' not found.");
                }
            } catch (\Exception $e) {
                sendResponse(false, [], "Parse error: " . $e->getMessage());
            }
            break;

        /**
         * parse_form_yaml: Converts raw YAML string to JSON config.
         */
        case 'parse_form_yaml':
            $yaml = $_POST['yaml'] ?? '';
            try {
                $config = \Symfony\Component\Yaml\Yaml::parse($yaml);
                sendResponse(true, ['config' => $config]);
            } catch (\Exception $e) {
                sendResponse(false, [], "Invalid YAML: " . $e->getMessage());
            }
            break;

        /**
         * dump_form_yaml: Converts JSON config config to raw YAML.
         */
        case 'dump_form_yaml':
            $rawConfig = $_POST['config'] ?? '';
            $config = is_string($rawConfig) ? json_decode($rawConfig, true) : $rawConfig;
            try {
                $yaml = \Symfony\Component\Yaml\Yaml::dump($config, 10, 2);
                sendResponse(true, ['yaml' => $yaml]);
            } catch (\Exception $e) {
                sendResponse(false, [], "Dump failure: " . $e->getMessage());
            }
            break;

        /**
         * save_form_config: Serializes JSON config to YAML and saves it.
         */
        case 'save_form_config':
            $name = trim($_POST['name'] ?? '');
            $rawConfig = $_POST['config'] ?? '';
            $checkDup = ($_POST['check_duplicate'] ?? 'false') === 'true';
            $config = is_string($rawConfig) ? json_decode($rawConfig, true) : $rawConfig;

            if (empty($name) || empty($config)) {
                sendResponse(false, [], "Form name and valid configuration are required.");
            }

            $formsDir = APP_ETC_DIR . '/' . $appname . '/forms';
            
            // Check for duplicates if requested (new forms)
            if ($checkDup) {
                if (file_exists($formsDir . '/' . strtolower($name) . '.yml') || 
                    file_exists($formsDir . '/' . strtolower($name) . '.xml')) {
                    sendResponse(false, [], "A form with the name '{$name}' already exists. Please choose a different name.");
                }
            }

            try {
                $yaml = \Symfony\Component\Yaml\Yaml::dump($config, 10, 2);
                $path = $formsDir . '/' . strtolower($name) . '.yml';
                file_put_contents($path, $yaml);
                sendResponse(true, [], "Form '{$name}' saved successfully.");
            } catch (\Exception $e) {
                sendResponse(false, [], "Dump failure: " . $e->getMessage());
            }
            break;

        /**
         * list_groups: Reads group entity data from discovery (App, Global, DB).
         */
        case 'list_groups':
            $context = $appname;
            try {
                // Ensure context is registered for SPPBase::sppTable/config to work
                try {
                    \SPP\Scheduler::getProcObj($context);
                } catch (\Exception $e) {
                    if (is_dir(SPP_APP_DIR . '/etc/apps/' . $context) || $context === 'default') {
                        new \SPP\App($context, false, 1);
                    }
                }

                require_once(SPP_BASE_DIR . '/modules/spp/sppgroup/class.sppgrouploader.php');
                require_once(SPP_BASE_DIR . '/modules/spp/sppgroup/class.sppgroup.php');

                $discovered = \SPPMod\SPPGroup\SPPGroupLoader::listAllGroups($context);
                $groups = [];

                foreach ($discovered as $g) {
                    try {
                        $group = new \SPPMod\SPPGroup\SPPGroup();
                        $group->load($g['name']);

                        $groups[] = [
                            'id' => $group->getId(),
                            'name' => $group->get('name') ?: $g['name'],
                            'description' => $group->get('description'),
                            'member_count' => count($group->getMembers(true)),
                            'source' => $g['source']
                        ];
                    } catch (\Exception $e) {
                        // Skip individual corrupted groups
                    }
                }
                sendResponse(true, ['groups' => $groups]);
            } catch (\Exception $e) {
                // Return success anyway to avoid UI "Failed to Load" crash
                sendResponse(true, ['groups' => []], "Group discovery limited for context: " . $e->getMessage());
            }
            break;

        /**
         * save_group: Creates or updates a group entity including custom metadata.
         */
        case 'save_group':
            $id = $_POST['id'] ?? null;
            if ($id === '')
                $id = null;

            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $metadata = $_POST['metadata'] ?? '{}'; // Expect JSON string

            if (empty($name)) {
                sendResponse(false, [], "Group name is required.");
            }

            try {
                require_once(SPP_BASE_DIR . '/modules/spp/sppgroup/class.sppgrouploader.php');
                require_once(SPP_BASE_DIR . '/modules/spp/sppgroup/class.sppgroup.php');

                $group = new \SPPMod\SPPGroup\SPPGroup($id);

                // For new groups, default to app-specific file storage
                if ($id === null) {
                    $group->setValues(['name' => $name]); // Set name before setSource for slugify
                    $group->setSource('app', 'default');
                }

                $group->setValues([
                    'name' => $name,
                    'description' => $description
                ]);

                // Map metadata attributes
                $metaArr = json_decode($metadata, true);
                if (is_array($metaArr)) {
                    foreach ($metaArr as $k => $v) {
                        $group->set($k, $v);
                    }
                }

                $savedId = $group->save();
                sendResponse(true, ['id' => $savedId], "Group '" . htmlspecialchars($name) . "' saved successfully.");
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to save group: " . $e->getMessage());
            }
            break;

        /**
         * delete_group: Permanently removes a group and its membership associations.
         */
        case 'delete_group':
            $id = $_POST['id'] ?? $_POST['name'] ?? null; // handle both id or name if used as identifier
            if (!$id)
                sendResponse(false, [], "Group ID is required.");

            try {
                require_once(SPP_BASE_DIR . '/modules/spp/sppgroup/class.sppgroup.php');
                $group = new \SPPMod\SPPGroup\SPPGroup($id);
                $group->delete();
                sendResponse(true, [], "Group deleted successfully.");
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to delete group: " . $e->getMessage());
            }
            break;

        /**
         * list_group_members: Retrieves detailed member list for a group (Transitive).
         */
        case 'list_group_members':
            $groupId = $_GET['group_id'] ?? null;
            if (!$groupId)
                sendResponse(false, [], "Group ID is required.");

            try {
                require_once(SPP_BASE_DIR . '/modules/spp/sppgroup/class.sppgrouploader.php');
                require_once(SPP_BASE_DIR . '/modules/spp/sppgroup/class.sppgroup.php');

                $group = new \SPPMod\SPPGroup\SPPGroup();
                $group->load($groupId);

                $results = $group->getMembers(true);
                $members = [];

                foreach ($results as $m) {
                    $entity = $m['entity'];
                    // We need a human-readable identifier for the entity
                    $name = $entity->getId();
                    if ($entity instanceof \SPPMod\SPPAuth\SPPUser) {
                        $name = $entity->get('username') ?: $entity->get('uname');
                    } elseif ($entity instanceof \SPPMod\SPPGroup\SPPGroup) {
                        $name = $entity->get('name');
                    }

                    $members[] = [
                        'entity' => get_class($entity),
                        'id' => $entity->getId(),
                        'name' => $name,
                        'role' => $m['role'],
                        'direct' => $m['direct'],
                        'inherited_via' => $m['inherited_via'] ?? null
                    ];
                }
                sendResponse(true, ['members' => $members]);
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to load members: " . $e->getMessage());
            }
            break;

        /**
         * add_group_member: Adds an entity to a group.
         */
        case 'add_group_member':
            $groupId = $_POST['group_id'] ?? '';
            $entityClass = repairNamespace($_POST['member_entity'] ?? $_POST['member_class'] ?? '');
            $entityId = $_POST['member_id'] ?? '';
            $role = $_POST['role'] ?? 'member';

            if (!$groupId || !$entityClass || !$entityId) {
                sendResponse(false, [], "Missing required membership details.");
            }

            try {
                require_once(SPP_BASE_DIR . '/modules/spp/sppgroup/class.sppgroup.php');
                require_once(SPP_BASE_DIR . '/modules/spp/sppauth/class.sppuser.php');
                
                $group = new \SPPMod\SPPGroup\SPPGroup($groupId);
                
                if (!class_exists($entityClass)) {
                    sendResponse(false, [], "Entity class '$entityClass' not found.");
                }

                $member = new $entityClass($entityId);
                if ($group->addMember($member, $role)) {
                    sendResponse(true, [], "Member added to group.");
                } else {
                    sendResponse(false, [], "Entity is already a member of this group.");
                }
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to add member: " . $e->getMessage());
            }
            break;

        /**
         * remove_group_member: Removes an entity from a group.
         */
        case 'remove_group_member':
            $groupId = $_POST['group_id'] ?? null;
            $entityClass = repairNamespace($_POST['member_entity'] ?? '');
            $entityId = $_POST['member_id'] ?? null;

            if (!$groupId || !$entityClass || !$entityId) {
                sendResponse(false, [], "Missing membership identifiers.");
            }

            try {
                require_once(SPP_BASE_DIR . '/modules/spp/sppgroup/class.sppgroup.php');
                
                $group = new \SPPMod\SPPGroup\SPPGroup($groupId);
                
                if (!class_exists($entityClass)) {
                    sendResponse(false, [], "Entity class not found.");
                }

                $member = new $entityClass($entityId);
                if ($group->removeMember($member)) {
                    sendResponse(true, [], "Member removed from group.");
                } else {
                    sendResponse(false, [], "Member not found in this group.");
                }
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to remove member: " . $e->getMessage());
            }
            break;

        /**
         * search_entities: Dynamic search for entities by name/type for group assignment.
         */        case 'search_entities':
            $query = trim($_REQUEST['q'] ?? '');
            $requestedType = $_REQUEST['type'] ?? 'all';

            if (strlen($query) < 1) {
                sendResponse(true, ['results' => []]);
            }

            try {
                $db = new \SPPMod\SPPDB\SPPDB();
                $results = [];

                // 1. Search Users
                if ($requestedType === 'all' || $requestedType === 'user' || $requestedType === 'SPPMod\\SPPAuth\\SPPUser') {
                    $table = \SPPMod\SPPDB\SPPDB::sppTable('users');
                    $sql = "SELECT id, username as name FROM {$table} WHERE username LIKE ? OR email LIKE ? LIMIT 10";
                    $data = $db->execute_query($sql, ["%{$query}%", "%{$query}%"]);
                    foreach ($data as $r) {
                        $results[] = [
                            'id' => $r['id'],
                            'name' => $r['name'],
                            'label' => $r['name'],
                            'type' => 'user',
                            'is_custom' => false,
                            'class' => '\\SPPMod\\SPPAuth\\SPPUser'
                        ];
                    }
                }

                // 2. Search Groups
                if ($requestedType === 'all' || $requestedType === 'group' || $requestedType === 'SPPMod\\SPPEntity\\SPPGroup') {
                    $table = \SPPMod\SPPDB\SPPDB::sppTable('sppgroups');
                    $sql = "SELECT id, name FROM {$table} WHERE name LIKE ? LIMIT 10";
                    $data = $db->execute_query($sql, ["%{$query}%"]);
                    foreach ($data as $r) {
                        $results[] = [
                            'id' => $r['id'],
                            'name' => $r['name'],
                            'label' => $r['name'],
                            'type' => 'group',
                            'is_custom' => false,
                            'class' => '\\SPPMod\\SPPEntity\\SPPGroup'
                        ];
                    }
                }

                // 3. Search Login-Enabled Custom Entities
                if ($requestedType === 'all' || (!in_array($requestedType, ['user', 'group']) && !strpos($requestedType, 'SPPMod'))) {
                    $entitiesDir = APP_ETC_DIR . '/' . $appname . '/entities';
                    if (is_dir($entitiesDir)) {
                        $files = glob($entitiesDir . '/*.yml');
                        foreach ($files as $file) {
                            $name = basename($file, '.yml');
                            $config = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
                            
                            // Only include login-enabled entities
                            if (empty($config['login_enabled'])) continue;

                            $table = $config['table'] ?? '';
                            if (empty($table)) continue;

                            // Dynamic check for name-like columns
                            $searchCol = 'name';
                            $columns = $db->execute_query("SHOW COLUMNS FROM {$table}");
                            $found = false;
                            foreach (['name', 'title', 'label', 'username', 'id'] as $candidate) {
                                foreach ($columns as $col) {
                                    if ($col['Field'] === $candidate) {
                                        $searchCol = $candidate;
                                        $found = true;
                                        break 2;
                                    }
                                }
                            }

                            $sql = "SELECT id, {$searchCol} as display_name FROM {$table} WHERE {$searchCol} LIKE ? LIMIT 5";
                            $data = $db->execute_query($sql, ["%{$query}%"]);
                            
                            $namespace = "App\\" . ucfirst($appname) . "\\Entities";
                            $className = $namespace . "\\" . ucfirst($name);

                            foreach ($data as $r) {
                                $results[] = [
                                    'id' => $r['id'],
                                    'name' => $r['display_name'],
                                    'label' => $r['display_name'] . " (" . ucfirst($name) . ")",
                                    'type' => ucfirst($name),
                                    'is_custom' => true,
                                    'entity_name' => ucfirst($name),
                                    'class' => $className
                                ];
                            }
                        }
                    }
                }

                sendResponse(true, ['results' => $results]);
            } catch (\Exception $e) {
                sendResponse(false, [], "Search failed: " . $e->getMessage());
            }
            break;
            
            break;

        /**
         * system_info: Returns framework metadata for the dashboard header.
         */
        case 'system_info':
            $info = [
                'spp_version' => defined('SPP_VER') ? SPP_VER : 'Unknown',
                'php_version' => phpversion(),
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'app_dir' => basename(SPP_APP_DIR),
                'entity_count' => count(glob(SPP_APP_DIR . '/etc/entities/*.yml')),
                'form_count' => count(glob(SPP_APP_DIR . '/etc/forms/*.yml')) + count(glob(SPP_APP_DIR . '/etc/forms/*.xml')),
                'module_count' => 0,
            ];

            // Count modules
            if (defined('SPP_MODULES_DIR') && class_exists('\\SPP\\SPPFS')) {
                $info['module_count'] = count(\SPP\SPPFS::findFile('module.xml', SPP_MODULES_DIR));
            }

            sendResponse(true, $info);
            break;

        /**
         * get_system_info: Returns diagnostic and telemetry data about the SPP environment.
         */
        case 'get_system_info':
            $db_info = "Unknown";
            try {
                if (class_exists('\\SPP\\SPPDB') && \SPP\Registry::get('db')) {
                    $db = \SPP\Registry::get('db');
                    $db_info = "Connected (MySQL/MariaDB)";
                }
            } catch (\Throwable $e) {
            }

            $info = [
                'spp_version' => defined('SPP_VER') ? SPP_VER : 'Unknown',
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'db_status' => $db_info,
                'spp_base' => SPP_BASE_DIR,
                'app_root' => SPP_APP_DIR,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'stats' => [
                    'apps' => 0,
                    'modules' => 0,
                    'entities' => 0,
                    'forms' => 0
                ]
            ];

            // Calculate stats
            if (defined('APP_ETC_DIR') && is_dir(APP_ETC_DIR)) {
                $apps = array_filter(scandir(APP_ETC_DIR), function ($d) {
                    return $d !== '.' && $d !== '..' && is_dir(APP_ETC_DIR . DIRECTORY_SEPARATOR . $d);
                });
                $info['stats']['apps'] = count($apps);

                // Count entities and forms in default app
                $entDir = APP_ETC_DIR . '/default/entities';
                if (is_dir($entDir)) {
                    $ents = glob($entDir . '/*.yml');
                    $info['stats']['entities'] = is_array($ents) ? count($ents) : 0;
                }

                $formDir = APP_ETC_DIR . '/default/forms';
                if (is_dir($formDir)) {
                    $forms = glob($formDir . '/*.{yml,xml}', GLOB_BRACE);
                    $info['stats']['forms'] = is_array($forms) ? count($forms) : 0;
                }
            }

            if (class_exists('\\SPP\\Module')) {
                // This might be expensive, but fine for a dashboard load
                \SPP\Module::loadAllModules();
                $mods = \SPP\Registry::get('__mods');
                $info['stats']['modules'] = is_array($mods) ? count($mods) : 0;
            }

            sendResponse(true, $info);
            break;

        /**
         * system_update_list: Scans all modules and entities for installation deltas (Dry Run).
         */
        case 'system_update_list':
            $summary = \SPP\Module::getSystemUpdateDeltas();
            sendResponse(true, ['deltas' => $summary]);
            break;

         /**
          * system_update_run: Applies all pending system-wide updates.
          */
        case 'system_update_run':
            $log = \SPP\Module::runSystemUpdate();
            sendResponse(true, ['log' => $log]);
            break;

        /**
         * list_apps: Returns list of registered applications from the apps config directory.
         */
        case 'list_apps':
            $apps = [];
            if (defined('APP_ETC_DIR')) {
                $appsDir = APP_ETC_DIR;
                if (is_dir($appsDir)) {
                    foreach (scandir($appsDir) as $entry) {
                        if ($entry === '.' || $entry === '..' || !is_dir($appsDir . DIRECTORY_SEPARATOR . $entry))
                            continue;
                        if ($entry === 'rc.d')
                            continue; // Skip rc.d if at root etc

                        $appInfo = ['name' => $entry];
                        $appPath = $appsDir . DIRECTORY_SEPARATOR . $entry;
                        $modsConfPath = $appPath . DIRECTORY_SEPARATOR . 'modsconf';

                        $hasModules = file_exists($modsConfPath . DIRECTORY_SEPARATOR . 'modules.yml')
                            || file_exists($modsConfPath . DIRECTORY_SEPARATOR . 'modules.xml');
                        $hasSettings = file_exists($appPath . DIRECTORY_SEPARATOR . 'settings.yml')
                            || file_exists($appPath . DIRECTORY_SEPARATOR . 'settings.xml');

                        $configCount = 0;
                        if (is_dir($modsConfPath)) {
                            $configCount = count(array_filter(scandir($modsConfPath), function ($d) use ($modsConfPath) {
                                return $d !== '.' && $d !== '..' && is_dir($modsConfPath . DIRECTORY_SEPARATOR . $d);
                            }));
                        }

                        $appInfo['has_modules'] = $hasModules;
                        $appInfo['has_settings'] = $hasSettings;
                        $appInfo['config_count'] = $configCount;
                        $apps[] = $appInfo;
                    }
                }
            }

            // Still check legacy location for backward compatibility list
            if (defined('SPP_ETC_DIR')) {
                $legacyAppsDir = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'apps';
                if (is_dir($legacyAppsDir)) {
                    foreach (scandir($legacyAppsDir) as $entry) {
                        if ($entry === '.' || $entry === '..' || !is_dir($legacyAppsDir . DIRECTORY_SEPARATOR . $entry))
                            continue;
                        $alreadyListed = array_filter($apps, function ($a) use ($entry) {
                            return $a['name'] === $entry;
                        });
                        if (!$alreadyListed) {
                            $apps[] = ['name' => $entry, 'legacy' => true];
                        }
                    }
                }
            }
            sendResponse(true, ['apps' => $apps]);
            break;

        /**
         * toggle_module: Activates or deactivates a module by updating both
         * modules.xml and modules.yml at all levels.
         */
        case 'toggle_module':
            $modname = trim($_POST['modname'] ?? '');
            $newStatus = trim($_POST['status'] ?? '');

            if (empty($modname)) {
                sendResponse(false, [], "Module name is required.");
            }
            if (!in_array($newStatus, ['active', 'inactive'])) {
                sendResponse(false, [], "Status must be 'active' or 'inactive'.");
            }

            try {
                $updatedFiles = \SPP\Module::toggleModuleStatus($modname, $newStatus);
                if (count($updatedFiles) > 0) {
                    $fileList = array_map(function ($f) {
                        return basename(dirname($f)) . '/' . basename($f);
                    }, $updatedFiles);
                    sendResponse(true, [
                        'modname' => $modname,
                        'status' => $newStatus,
                        'updated_files' => $fileList
                    ], "Module '{$modname}' set to '{$newStatus}'. Updated: " . implode(', ', $fileList) . ". Changes take effect on next page load.");
                } else {
                    sendResponse(false, [], "Module '{$modname}' not found in any modules manifest file.");
                }
            } catch (\Throwable $e) {
                sendResponse(false, [], "Failed to toggle module: " . $e->getMessage());
            }
            break;

        /**
         * get_module_config: Returns config variables as key-value pairs.
         */
        case 'get_module_config':
            $modname = trim($_GET['modname'] ?? $_POST['modname'] ?? '');
            $appname = trim($_GET['appname'] ?? $_POST['appname'] ?? '');
            if (empty($modname)) {
                sendResponse(false, [], "Module name is required.");
            }
            if (empty($appname)) {
                sendResponse(false, [], "App name is required. Select an app context.");
            }

            try {
                $config = \SPP\Module::getAllConfigForApp($modname, $appname);
                sendResponse(true, $config);
            } catch (\Throwable $e) {
                sendResponse(false, [], "Failed to read config: " . $e->getMessage());
            }
            break;

        /**
         * save_module_config: Saves config variables from key-value pairs.
         */
        case 'save_module_config':
            $modname = trim($_POST['modname'] ?? '');
            $appname = trim($_POST['appname'] ?? '');
            $configJson = $_POST['config'] ?? '';

            if (empty($modname)) {
                sendResponse(false, [], "Module name is required.");
            }
            if (empty($appname)) {
                sendResponse(false, [], "App name is required.");
            }

            $configData = json_decode($configJson, true);
            if (!is_array($configData)) {
                sendResponse(false, [], "Invalid config data. Expected JSON object.");
            }

            try {
                foreach ($configData as $key => $value) {
                    \SPP\Module::setConfigForApp($key, $value, $modname, $appname);
                }
                sendResponse(true, [], "Configuration for '{$modname}' (app: {$appname}) saved successfully.");
            } catch (\Throwable $e) {
                sendResponse(false, [], "Failed to save config: " . $e->getMessage());
            }
            break;

        /**
         * get_module_config_raw: Returns raw config file content for direct editing.
         */
        case 'get_module_config_raw':
            $modname = trim($_GET['modname'] ?? $_POST['modname'] ?? '');
            $appname = trim($_GET['appname'] ?? $_POST['appname'] ?? '');
            if (empty($modname)) {
                sendResponse(false, [], "Module name is required.");
            }
            if (empty($appname)) {
                sendResponse(false, [], "App name is required.");
            }

            try {
                $raw = \SPP\Module::getRawConfigForApp($modname, $appname);
                sendResponse(true, $raw);
            } catch (\Throwable $e) {
                sendResponse(false, [], "Failed to read raw config: " . $e->getMessage());
            }
            break;

        /**
         * save_module_config_raw: Saves raw config file content directly.
         */
        case 'save_module_config_raw':
            $modname = trim($_POST['modname'] ?? '');
            $appname = trim($_POST['appname'] ?? '');
            $content = $_POST['content'] ?? '';
            $format = strtolower(trim($_POST['format'] ?? 'yml'));

            if (empty($modname)) {
                sendResponse(false, [], "Module name is required.");
            }
            if (empty($appname)) {
                sendResponse(false, [], "App name is required.");
            }
            if (empty($content)) {
                sendResponse(false, [], "Config content cannot be empty.");
            }

            $format = in_array($format, ['yml', 'yaml', 'xml']) ? $format : 'yml';

            try {
                // Determine where to save — use existing file path or create canonical
                $existing = \SPP\Module::getRawConfigForApp($modname, $appname);
                if (!empty($existing['path'])) {
                    $targetPath = $existing['path'];
                } else {
                    // Create in canonical per-app location
                    $modsConfDir = SPP_ETC_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . $appname . DIRECTORY_SEPARATOR . 'modsconf';
                    $dir = $modsConfDir . DIRECTORY_SEPARATOR . $modname;
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $targetPath = $dir . DIRECTORY_SEPARATOR . 'config.' . $format;
                }

                file_put_contents($targetPath, $content);

                sendResponse(true, ['path' => $targetPath], "Raw config for '{$modname}' (app: {$appname}) saved to " . basename($targetPath) . ".");
            } catch (\Throwable $e) {
                sendResponse(false, [], "Failed to save raw config: " . $e->getMessage());
            }
            break;

        /**
         * call_service: Executes application-specific PHP services from src/<appname>/serv/
         */
        case 'call_service':
            $app = $_REQUEST['appname'] ?? 'default';
            $service = $_REQUEST['service'] ?? '';
            // Security check: Only allow alphanumeric and underscore
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $service)) {
                sendResponse(false, [], "Invalid service name.");
            }
            
            $path = dirname(SPP_BASE_DIR) . "/src/$app/serv/$service.php";
            if (file_exists($path)) {
                $params = json_decode($_REQUEST['params'] ?? '{}', true);
                $db = new \SPPMod\SPPDB\SPPDB();
                
                // Expose context to the script
                $input = $params;
                
                require $path;
                
                if (isset($response)) {
                    sendResponse(true, $response);
                }
                exit; // Ensure no double output
            } else {
                sendResponse(false, [], "Service '$service' not found in app '$app'.");
            }
            break;

        /**
         * list_users: Returns a list of all users from the auth system.
         */
        case 'list_users':
            try {
                $users = \SPPMod\SPPAuth\SPPUser::find_all();
                sendResponse(true, ['users' => $users]);
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to list users: " . $e->getMessage());
            }
            break;

        /**
         * save_user: Creates or updates a user using SPPUser entity.
         */
        case 'save_user':
            try {
                $id = \SPPMod\SPPAuth\SPPUser::saveUserInfo($_POST);
                sendResponse(true, ['id' => $id], "User saved successfully.");
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to save user: " . $e->getMessage());
            }
            break;

        /**
         * list_roles: Returns all available system roles.
         */
        case 'list_roles':
            try {
                $roles = \SPPMod\SPPAuth\SPPRole::find_all();
                sendResponse(true, ['roles' => $roles]);
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to list roles: " . $e->getMessage());
            }
            break;

        /**
         * save_role: Creates or updates a role.
         */
        case 'save_role':
            try {
                $id = \SPPMod\SPPAuth\SPPRole::saveRoleInfo($_POST);
                sendResponse(true, ['id' => $id], "Role saved successfully.");
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to save role: " . $e->getMessage());
            }
            break;

        /**
         * list_rights: Returns all system permissions/rights.
         */
        case 'list_rights':
            try {
                $rights = \SPPMod\SPPAuth\SPPRight::find_all();
                sendResponse(true, ['rights' => $rights]);
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to list rights: " . $e->getMessage());
            }
            break;

        /**
         * save_right: Creates or updates a right.
         */
        case 'save_right':
            $id = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            if (empty($name) && !empty($id)) {
                try {
                    $existingRight = new \SPPMod\SPPAuth\SPPRight($id);
                    $name = $existingRight->name;
                } catch (\Exception $e) {}
            }

            if (empty($name)) sendResponse(false, [], "Right name is required.");


            try {
                $right = new \SPPMod\SPPAuth\SPPRight($id);
                $right->name = $name;
                $right->description = $desc;
                $right->save();
                sendResponse(true, ['id' => $right->id], "Right '{$name}' saved successfully.");
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to save right: " . $e->getMessage());
            }
            break;

        /**
         * assign_role_to_entity: Polymorphic role assignment.
         */
        case 'assign_role_to_entity':
            $targetClass = $_POST['target_class'] ?? '';
            $targetId = $_POST['target_id'] ?? '';
            $roleIds = $_POST['role_id'] ?? [];
            
            if (!is_array($roleIds)) {
                $roleIds = [$roleIds];
            }

            if (!$targetClass || !$targetId || empty($roleIds)) {
                sendResponse(false, [], "Target class, ID, and Role ID(s) are required.");
            }

            try {
                $db = new \SPPMod\SPPDB\SPPDB();
                foreach ($roleIds as $roleId) {
                    // 1. Update polymorphic entity_roles
                    $check = $db->execute_query("SELECT 1 FROM " . \SPPMod\SPPDB\SPPDB::sppTable('entity_roles') . " WHERE target_class=? AND target_id=? AND role_id=?", [$targetClass, $targetId, $roleId]);
                    if (empty($check)) {
                        $db->insertValues('entity_roles', [
                            'target_class' => $targetClass,
                            'target_id' => $targetId,
                            'role_id' => $roleId
                        ]);
                    }

                    // 2. Sync with userroles if target is a user
                    if (strpos($targetClass, 'SPPUser') !== false) {
                        $checkUser = $db->execute_query("SELECT 1 FROM " . \SPPMod\SPPDB\SPPDB::sppTable('userroles') . " WHERE userid=? AND roleid=?", [$targetId, $roleId]);
                        if (empty($checkUser)) {
                            $db->insertValues('userroles', ['userid' => $targetId, 'roleid' => $roleId]);
                        }
                    }
                }
                sendResponse(true, [], "Role(s) assigned successfully.");
            } catch (\Exception $e) {
                sendResponse(false, [], "Assignment failed: " . $e->getMessage());
            }
            break;

        case 'list_entity_assignments':
            try {
                $db = new \SPPMod\SPPDB\SPPDB();
                $sql = "SELECT er.target_class, er.target_id, er.role_id, r.role_name 
                        FROM " . \SPPMod\SPPDB\SPPDB::sppTable('entity_roles') . " er 
                        JOIN " . \SPPMod\SPPDB\SPPDB::sppTable('roles') . " r ON er.role_id = r.id 
                        ORDER BY er.target_class, er.target_id";
                $res = $db->execute_query($sql);
                
                $grouped = [];
                foreach ($res as $row) {
                    $key = $row['target_class'] . ':' . $row['target_id'];
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'target_class' => $row['target_class'],
                            'target_id' => $row['target_id'],
                            'roles' => []
                        ];
                    }
                    $grouped[$key]['roles'][] = [
                        'id' => $row['role_id'],
                        'name' => $row['role_name']
                    ];
                }
                sendResponse(true, array_values($grouped));
            } catch (\Exception $e) {
                sendResponse(false, [], "Failed to list assignments: " . $e->getMessage());
            }
            break;


        /**
         * remove_role_from_entity: Remove polymorphic role assignment.
         */
        case 'remove_role_from_entity':
            $targetClass = $_POST['target_class'] ?? '';
            $targetId = $_POST['target_id'] ?? '';
            $roleId = $_POST['role_id'] ?? '';

            try {
                $db = new \SPPMod\SPPDB\SPPDB();
                $db->execute_query("DELETE FROM " . \SPPMod\SPPDB\SPPDB::sppTable('entity_roles') . " WHERE target_class=? AND target_id=? AND role_id=?", 
                    [$targetClass, $targetId, $roleId]);
                
                // Sync with userroles if target is a user
                if (strpos($targetClass, 'SPPUser') !== false) {
                    $db->execute_query("DELETE FROM " . \SPPMod\SPPDB\SPPDB::sppTable('userroles') . " WHERE userid=? AND roleid=?", 
                        [$targetId, $roleId]);
                }

                sendResponse(true, [], "Role removed from entity.");
            } catch (\Exception $e) {
                sendResponse(false, [], "Removal failed: " . $e->getMessage());
            }
            break;

        /**
         * get_iam_details: Retrieves roles for a user or rights for a role.
         */
        case 'get_iam_details':
            $type = $_GET['type'] ?? $_POST['type'] ?? '';
            $id = $_GET['id'] ?? $_POST['id'] ?? '';
            if (!$type || !$id) sendResponse(false, [], "Type and ID required.");

            try {
                if ($type === 'users') {
                    $user = new \SPPMod\SPPAuth\SPPUser($id);
                    $roles = \SPPMod\SPPAuth\SPPRole::find_all();
                    sendResponse(true, [
                        'assigned_ids' => $user->getRoles(),
                        'available' => $roles
                    ]);
                } else if ($type === 'roles') {
                    $role = new \SPPMod\SPPAuth\SPPRole($id);
                    $rights = \SPPMod\SPPAuth\SPPRight::find_all();
                    sendResponse(true, [
                        'assigned_ids' => $role->getRights(),
                        'available' => $rights
                    ]);
                } else {
                    sendResponse(false, [], "Unsupported IAM type for details.");
                }
            } catch (\Exception $e) {
                sendResponse(false, [], "Fetch failed: " . $e->getMessage());
            }
            break;

        /**
         * assign_right_to_role: Link a permission to a role.
         */
        case 'assign_right_to_role':
            $roleId = $_POST['role_id'] ?? '';
            $rightId = $_POST['right_id'] ?? '';
            if (!$roleId || !$rightId) sendResponse(false, [], "Role ID and Right ID required.");
            try {
                \SPPMod\SPPAuth\SPPRole::assignRight($roleId, $rightId);
                sendResponse(true, [], "Right assigned to role.");
            } catch (\Exception $e) {
                sendResponse(false, [], "Assignment failed: " . $e->getMessage());
            }
            break;

        /**
         * remove_right_from_role: Unlink a permission from a role.
         */
        case 'remove_right_from_role':
            $roleId = $_POST['role_id'] ?? '';
            $rightId = $_POST['right_id'] ?? '';
            if (!$roleId || !$rightId) sendResponse(false, [], "Role ID and Right ID required.");
            try {
                \SPPMod\SPPAuth\SPPRole::unassignRight($roleId, $rightId);
                sendResponse(true, [], "Right removed from role.");
            } catch (\Exception $e) {
                sendResponse(false, [], "Removal failed: " . $e->getMessage());
            }
            break;

        /**
         * get_form_html: Renders an SPPForm for use in the SPA UI.
         */
        case 'get_iam_form':
        case 'get_form_html':
            $formName = $_GET['form'] ?? $_POST['form'] ?? $_GET['type'] ?? $_POST['type'] ?? '';
            $entityId = $_GET['id'] ?? $_POST['id'] ?? null;

            if (empty($formName)) sendResponse(false, [], "Form name required.");

            try {
                // Resolving Admin specific forms first
                $adminFormPath = SPP_BASE_DIR . SPP_DS . 'etc' . SPP_DS . 'apps' . SPP_DS . 'admin' . SPP_DS . 'forms' . SPP_DS . $formName . '.yml';
                $fullPath = file_exists($adminFormPath) ? $adminFormPath : $formName;

                // Support raw YAML for live preview
                if (strpos($formName, 'form:') !== false) {
                    $form = \SPPMod\SPPView\ViewFormBuilder::fromString($formName);
                } else {
                    $form = \SPPMod\SPPView\ViewFormBuilder::fromYaml($fullPath);
                }
                
                // If ID is provided, bind data
                if ($entityId !== null && $form->getEntityClass()) {
                    $class = $form->getEntityClass();
                    if (class_exists($class)) {
                        $entity = new $class($entityId);
                        $form->bind($entity);
                    }
                }

                sendResponse(true, [
                    'html' => $form->getHTML(), 
                    'title' => $form->getMatter() ?: "Edit " . $formName
                ]);
            } catch (\Exception $e) {
                sendResponse(false, [], "Form rendering failed: " . $e->getMessage());
            }
            break;

        /**
         * Routing Management: Pages
         */
        case 'list_pages':
            require_once SPP_BASE_DIR . '/modules/spp/sppview/class.pages.php';
            $pages = \SPPMod\SPPView\Pages::listPages();
            sendResponse(true, ['pages' => $pages]);
            break;

        case 'save_page':
            $name = trim($_POST['name'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $source = $_POST['source'] ?? 'yaml';
            if (!$name || !$url) sendResponse(false, [], "Name and URL required.");
            
            require_once SPP_BASE_DIR . '/modules/spp/sppview/class.pages.php';
            \SPPMod\SPPView\Pages::savePage($name, $url, $source);
            sendResponse(true, [], "Page route saved to {$source}.");
            break;

        case 'remove_page':
            $name = $_POST['name'] ?? '';
            $source = $_POST['source'] ?? 'yaml';
            if (!$name) sendResponse(false, [], "Name required.");
            
            require_once SPP_BASE_DIR . '/modules/spp/sppview/class.pages.php';
            \SPPMod\SPPView\Pages::removePage($name, $source);
            sendResponse(true, [], "Page route removed from {$source}.");
            break;

        /**
         * Routing Management: AJAX Services
         */
        case 'list_services':
            require_once SPP_BASE_DIR . '/modules/spp/sppajax/class.sppajax.php';
            $services = \SPPMod\SPPAjax\SPPAjax::listServices();
            sendResponse(true, ['services' => $services]);
            break;

        case 'save_service':
            $name = trim($_POST['name'] ?? '');
            $script = trim($_POST['script'] ?? '');
            $method = strtoupper($_POST['method'] ?? 'POST');
            $source = $_POST['source'] ?? 'yaml';
            if (!$name || !$script) sendResponse(false, [], "Name and Script required.");
            
            require_once SPP_BASE_DIR . '/modules/spp/sppajax/class.sppajax.php';
            \SPPMod\SPPAjax\SPPAjax::registerService($name, $script, $method, $source);
            sendResponse(true, [], "Service registered in {$source}.");
            break;

        case 'remove_service':
            $name = $_POST['name'] ?? '';
            $source = $_POST['source'] ?? 'yaml';
            if (!$name) sendResponse(false, [], "Name required.");

            require_once SPP_BASE_DIR . '/modules/spp/sppajax/class.sppajax.php';
            \SPPMod\SPPAjax\SPPAjax::unregisterService($name, $source);
            sendResponse(true, [], "Service removed from {$source}.");
            break;

        /**
         * Polyglot Bridge Management
         */
        case 'get_bridge_info':
            if (!class_exists('\SPP\PolyglotBridge')) sendResponse(false, [], "PolyglotBridge core not found.");
            $runtimes = \SPP\PolyglotBridge::discoverRuntimes();
            
            $sharedDir = \SPP\Module::getConfig('shared_dir', 'bridge') ?: 'var/shared';
            if (!str_starts_with($sharedDir, '/') && !str_contains($sharedDir, ':')) {
                $sharedDir = SPP_BASE_DIR . SPP_DS . '..' . SPP_DS . $sharedDir;
            }
            $bridgeFile = $sharedDir . SPP_DS . 'bridge_config.json';
            
            sendResponse(true, [
                'runtimes' => $runtimes,
                'shared_dir' => realpath($sharedDir),
                'config_exists' => file_exists($bridgeFile),
                'last_sync' => file_exists($bridgeFile) ? date("Y-m-d H:i:s", filemtime($bridgeFile)) : null
            ]);
            break;

        case 'setup_bridge':
            if (!class_exists('\SPP\PolyglotBridge')) sendResponse(false, [], "PolyglotBridge core not found.");
            $res = \SPP\PolyglotBridge::setup();
            sendResponse($res['success'], $res, $res['success'] ? "Bridge environment refreshed successfully." : "Bridge setup failed.");
            break;

        default:
            sendResponse(false, [], "Unsupported API action: " . htmlspecialchars($action));
            break;
    }

} catch (\Throwable $e) {
    sendResponse(false, [], "Server Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

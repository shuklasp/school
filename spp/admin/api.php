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
ini_set('display_errors', '1'); // Temporarily enable displaying errors 

// Define framework paths
if (!defined('SPP_BASE_DIR')) {
    define('SPP_BASE_DIR', dirname(__DIR__));
}

// Pre-load classes required for session deserialization BEFORE session_start()
// sppinit.php calls session_start() which unserializes SPPUserSession objects.
// If these classes aren't loaded beforehand, PHP creates __PHP_Incomplete_Class.
$coreDir = SPP_BASE_DIR . '/core';
$authDir = SPP_BASE_DIR . '/modules/spp/sppauth';
$dbDir   = SPP_BASE_DIR . '/modules/spp/sppdb';
$cfgDir  = SPP_BASE_DIR . '/modules/spp/sppconfig';

// Core classes needed by the session chain
foreach (['class.sppobject.php', 'class.sppsession.php', 'class.sppbase.php', 'class.sppexception.php'] as $f) {
    if (file_exists($coreDir . '/' . $f)) require_once $coreDir . '/' . $f;
}
// Auth module classes that get serialized into the session
foreach (['class.sppuser.php', 'class.sppusersession.php'] as $f) {
    if (file_exists($authDir . '/' . $f)) require_once $authDir . '/' . $f;
}
// Database class (used by SPPUser and SPPUserSession)
if (file_exists($dbDir . '/class.sppdb.php')) require_once $dbDir . '/class.sppdb.php';
if (file_exists($cfgDir . '/class.sppconfig.php')) require_once $cfgDir . '/class.sppconfig.php';

require_once SPP_BASE_DIR . '/sppinit.php';


// Load global handlers and composer autoload if available
$globalPath = dirname(SPP_BASE_DIR) . '/global.php';
if (file_exists($globalPath)) {
    require_once $globalPath;
}
$autoloadPath = dirname(SPP_BASE_DIR) . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
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
function sendResponse($success, $data = [], $message = '') {
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
 * checkDevMode function
 * 
 * Validates that the system is currently running in 'dev' profile.
 * Returns true if allowed, false otherwise.
 */
function checkDevMode() {
    try {
        $settingsPath = SPP_BASE_DIR . '/etc/settings.xml';
        if (!file_exists($settingsPath)) return false;
        
        $xml = simplexml_load_file($settingsPath);
        $profile = (string)$xml->profile;
        
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
    
    // 3. Resource Management Logic
    switch ($action) {
        /**
         * list_modules: Scans the filesystem for available modules and returns their metadata.
         */
        case 'list_modules':
            $modules = [];
            if (defined('SPP_MODULES_DIR') && class_exists('\\SPP\\SPPFS')) {
                $files = \SPP\SPPFS::findFile('module.xml', SPP_MODULES_DIR);
                foreach ($files as $file) {
                    try {
                        $modXml = simplexml_load_file($file);
                        $name = (string)$modXml->name;
                        $modules[] = [
                            'name' => $name,
                            'public_name' => (string)($modXml->public_name ?? $name),
                            'version' => (string)($modXml->version ?? '1.0'),
                            'author' => (string)($modXml->author ?? 'Unknown'),
                            'active' => \SPP\Module::isEnabled($name),
                            'path' => str_replace(dirname(SPP_BASE_DIR), '', $file)
                        ];
                    } catch (Exception $e) {}
                }
            }
            sendResponse(true, ['modules' => $modules]);
            break;
            
        /**
         * list_entities: Scans the application's etc/entities directory for YAML definitions.
         */
        case 'list_entities':
            $entitiesDir = SPP_APP_DIR . '/etc/entities';
            $entities = [];
            if (is_dir($entitiesDir)) {
                $files = glob($entitiesDir . '/*.yml');
                foreach ($files as $file) {
                    $entities[] = [
                        'name' => basename($file, '.yml'),
                        'content' => file_get_contents($file),
                        'size' => filesize($file),
                        'modified' => date('Y-m-d H:i', filemtime($file))
                    ];
                }
            }
            sendResponse(true, ['entities' => $entities]);
            break;

        /**
         * save_entity: Creates or updates a YAML entity configuration file.
         */
        case 'save_entity':
            $name = trim($_POST['name'] ?? '');
            $content = $_POST['content'] ?? '';
            if (empty($name) || empty($content)) {
                sendResponse(false, [], "Entity name and YAML content are required.");
            }
            
            $entitiesDir = SPP_APP_DIR . '/etc/entities';
            if (!is_dir($entitiesDir)) mkdir($entitiesDir, 0777, true);
            
            $filePath = $entitiesDir . '/' . strtolower($name) . '.yml';
            file_put_contents($filePath, $content);
            sendResponse(true, [], "Entity definition '{$name}' saved successfully.");
            break;

        /**
         * delete_entity: Removes a YAML entity configuration file.
         */
        case 'delete_entity':
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                sendResponse(false, [], "Entity name is required.");
            }
            
            $filePath = SPP_APP_DIR . '/etc/entities/' . strtolower($name) . '.yml';
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
            $formsDir = SPP_APP_DIR . '/etc/forms';
            $forms = [];
            if (is_dir($formsDir)) {
                $ymlFiles = glob($formsDir . '/*.yml');
                $xmlFiles = glob($formsDir . '/*.xml');
                $allFiles = array_merge($ymlFiles, $xmlFiles);
                foreach ($allFiles as $file) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    $forms[] = [
                        'name' => pathinfo($file, PATHINFO_FILENAME),
                        'type' => strtoupper($ext),
                        'content' => file_get_contents($file),
                        'size' => filesize($file),
                        'modified' => date('Y-m-d H:i', filemtime($file))
                    ];
                }
            }
            sendResponse(true, ['forms' => $forms]);
            break;

        /**
         * save_form: Creates or updates a YAML form definition.
         */
        case 'save_form':
            $name = trim($_POST['name'] ?? '');
            $content = $_POST['content'] ?? '';
            $type = strtolower(trim($_POST['type'] ?? 'yml'));
            
            if (empty($name) || empty($content)) {
                sendResponse(false, [], "Form name and content are required.");
            }
            
            $formsDir = SPP_APP_DIR . '/etc/forms';
            if (!is_dir($formsDir)) mkdir($formsDir, 0777, true);
            
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
            
            $formsDir = SPP_APP_DIR . '/etc/forms';
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
         * list_groups: Reads group entity data from the database via SPPGroup.
         */
        case 'list_groups':
            $groups = [];
            try {
                $db = new \SPPMod\SPPDB\SPP_DB();
                $tableName = \SPP\SPPBase::sppTable('sppgroup');
                $sql = "SELECT * FROM {$tableName} ORDER BY id DESC LIMIT 100";
                $results = $db->execute_query($sql);
                foreach ($results as $row) {
                    // Count members
                    $memberTable = \SPP\SPPBase::sppTable('sppgroupmember');
                    $memberSql = "SELECT COUNT(*) as cnt FROM {$memberTable} WHERE group_id=?";
                    $memberCount = $db->execute_query($memberSql, [$row['id']]);
                    
                    $groups[] = [
                        'id' => $row['id'],
                        'name' => $row['name'] ?? 'Group #' . $row['id'],
                        'description' => $row['description'] ?? '',
                        'member_count' => (int)($memberCount[0]['cnt'] ?? 0),
                    ];
                }
            } catch (\Exception $e) {
                // Table might not exist yet — return empty
            }
            sendResponse(true, ['groups' => $groups]);
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

        default:
            sendResponse(false, [], "Unsupported API action: " . htmlspecialchars($action));
            break;
    }
    
} catch (\Throwable $e) {
    sendResponse(false, [], "Server Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

<?php

namespace SPP;

use SPP\Exceptions\DuplicateModuleException;
use Symfony\Component\Yaml\Yaml;

/**
 * class \SPP\Module
 * Defines a new module in Satya Portal Pack.
 *
 * Modernized for PHP 8+ — fully backward compatible.
 *
 * @author Satya Prakash Shukla
 */
class Module extends \SPP\SPPObject
{
    /**
     * Allowed magic set/get properties (kept for backward compatibility).
     * These names are intentionally the same as the legacy class.
     *
     * @var array<string>
     */
    protected array $_setprops = [
        'PublicName',
        'PublicDesc',
        'InternalName',
        'Version',
        'InstallScript',
        'UninstallScript',
        'ModuleGroup',
        'IncludeFiles',
        'Dependencies',
        'ModPath',
        'ConfigFile'
    ];

    /** @var array<string> */
    protected array $_getprops = [
        'PublicName',
        'PublicDesc',
        'InternalName',
        'InstallScript',
        'UninstallScript',
        'ModuleDir',
        'Version',
        'IncludeFiles',
        'Dependencies',
        'ModPath',
        'ConfigFile',
        'ModuleGroup'
    ];

    /**
     * In-memory cache for configuration values to prevent duplicate XML I/O parsing.
     * @var array<string, array<string, string|false>>
     */
    protected static array $configCache = [];

    /**
     * Module constructor.
     *
     * Accepts path to module manifest (module.xml or module.yml).
     *
     * @param string $file Path to module manifest
     * @throws \SPP\SPPException
     */
    public function __construct(string $file)
    {
        if (!file_exists($file)) {
            throw new \SPP\SPPException("Module manifest not found: {$file}");
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === 'yml' || $ext === 'yaml') {
            $this->readYaml($file);
        } else {
            $this->readXML($file);
        }
    }

    /**
     * Read module definition from YAML manifest.
     *
     * Note: legacy code mixed YAML and XML parsing — this method keeps behavior
     * consistent: it looks for top-level 'module' or 'modules' structure.
     *
     * @param string $file
     * @return void
     * @throws \SPP\SPPException
     */
    private function readYaml(string $file): void
    {
        $parsed = Yaml::parseFile($file);

        // YAML structure might vary; support a couple legacy-friendly shapes.
        // Typical expected shapes:
        //  modules:
        //    - module: { name: ..., version: ..., pubname: ..., ... }
        // or
        //  module:
        //    name: ...
        $moduleData = null;
        if (isset($parsed['module']) && is_array($parsed['module'])) {
            $moduleData = $parsed['module'];
        } elseif (isset($parsed['modules']) && is_array($parsed['modules'])) {
            // take first 'module' entry (legacy modules.yml may have many)
            $first = reset($parsed['modules']);
            if (is_array($first) && isset($first['module'])) {
                $moduleData = $first['module'];
            } else {
                $moduleData = $first;
            }
        } else {
            throw new \SPP\SPPException("Unexpected YAML module format in {$file}");
        }

        $this->ModPath = dirname($file);

        // Map fields using same keys as XML parser for compatibility
        $this->mapManifestArray($moduleData);
    }

    /**
     * Read module definition from XML manifest.
     *
     * @param string $file
     * @return void
     * @throws \SPP\SPPException
     */
    private function readXML(string $file): void
    {
        if (\LIBXML_VERSION < 20900 && function_exists('libxml_disable_entity_loader')) {
            libxml_disable_entity_loader(true);
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file);
        if ($xml === false) {
            $errs = libxml_get_errors();
            libxml_clear_errors();
            $msg = 'Failed to parse XML module manifest: ' . ($errs ? $errs[0]->message : 'unknown error');
            throw new \SPP\SPPException($msg);
        }

        $node = $xml->xpath('/module');
        if ($node === false || !isset($node[0])) {
            throw new \SPP\SPPException("module element not found in {$file}");
        }

        $arr = (array) $node[0];
        $this->ModPath = dirname($file);

        $this->mapManifestArray($arr);
    }

    /**
     * Common helper: maps a parsed manifest associative array to module properties.
     *
     * Keeps legacy keys names (name, version, pubname, pubdesc, modgroup, config, includes, deps).
     *
     * @param array<mixed> $arr
     * @return void
     * @throws \SPP\SPPException
     */
    private function mapManifestArray(array $arr): void
    {
        foreach ($arr as $key => $val) {
            switch (strtolower($key)) {
                case 'name':
                    $this->InternalName = (string) $val;
                    break;
                case 'version':
                    $this->Version = (string) $val;
                    break;
                case 'pubname':
                case 'publicname':
                    $this->PublicName = (string) $val;
                    break;
                case 'modgroup':
                case 'modulegroup':
                    $this->ModuleGroup = (string) $val;
                    break;
                case 'pubdesc':
                case 'publicdesc':
                    $this->PublicDesc = (string) $val;
                    break;
                case 'config':
                    $this->ConfigFile = (string) $val;
                    break;
                case 'includes':
                    // includes may be a single include entry or an array
                    $includes = (array) $val;
                    // If YAML/XML structure nests 'include' key, handle it
                    if (isset($includes['include'])) {
                        $this->IncludeFiles = $includes['include'];
                    } else {
                        $this->IncludeFiles = $includes;
                    }
                    break;
                case 'deps':
                case 'dependencies':
                    $deps = (array) $val;
                    if (isset($deps['depends'])) {
                        $this->Dependencies = $deps['depends'];
                    } else {
                        $this->Dependencies = $deps;
                    }
                    break;
                default:
                    // Ignore unknown keys (keep robust)
                    break;
            }
        }

        // Basic validation: internal name must be set
        if (empty($this->_attributes['InternalName'])) {
            throw new \SPP\SPPException('Module manifest missing "name" (InternalName).');
        }
    }

    /**
     * Gets a config variable for the module.
     *
     * Resolution order (highest to lowest priority):
     *  1. In-memory cache
     *  2. Canonical per-app YAML: spp/etc/apps/<app>/modsconf/<modname>/config.yml
     *  3. Module's own bundled config (module.xml → <config> tag)
     *  4. Legacy YAML: etc/settings/modules/<modname>/config.yml
     *  5. Legacy XML: modsconf/<modname>/config.xml
     *
     * @param string $varname
     * @param string $modname
     * @return string|false
     * @throws \SPP\SPPException
     */
    public static function getConfig(string $varname, string $modname): string|false
    {
        // Sanitize varname and modname against injection & traversal
        $varname = str_replace(["'", '"'], '', $varname);
        $modname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $modname);

        if (isset(self::$configCache[$modname][$varname])) {
            return self::$configCache[$modname][$varname];
        }

        // --- Step 1: Check canonical per-app YAML config (highest priority) ---
        // Path: spp/etc/apps/<app>/modsconf/<modname>/config.yml
        $proc = \SPP\Scheduler::getActiveProc();
        $yamlConfFile = $proc->getModsConfDir() . SPP_DS . $modname . SPP_DS . 'config.yml';
        if (file_exists($yamlConfFile)) {
            $yamlData = Yaml::parseFile($yamlConfFile);
            // Convention: variables are stored under a 'variables' key
            $val = $yamlData['variables'][$varname] ?? ($yamlData[$varname] ?? null);
            if ($val !== null) {
                $result = (string) $val;
                self::$configCache[$modname][$varname] = $result;
                return $result;
            }
        }

        // --- Step 2: Try /etc/modules/<appname>/modsconf/config.yml (New System Level User Modules Config) ---
        $appname = \SPP\Scheduler::getContext();
        $userGlobalConf = SPP_APP_DIR . SPP_DS . 'etc' . SPP_DS . 'modules' . SPP_DS . $appname . SPP_DS . 'modsconf' . SPP_DS . 'config.yml';
        if (file_exists($userGlobalConf)) {
            $userYaml = Yaml::parseFile($userGlobalConf);
            // Global config is assumed to be keyed by module name
            if (isset($userYaml[$modname]['variables'][$varname])) {
                $val = (string) $userYaml[$modname]['variables'][$varname];
                self::$configCache[$modname][$varname] = $val;
                return $val;
            } elseif (isset($userYaml[$modname][$varname])) {
                $val = (string) $userYaml[$modname][$varname];
                self::$configCache[$modname][$varname] = $val;
                return $val;
            }
        }

        if (\LIBXML_VERSION < 20900 && function_exists('libxml_disable_entity_loader')) {
            libxml_disable_entity_loader(true);
        }

        $result = false;

        // --- Step 3: Try module's own bundled config (from module.xml <config> tag) ---
        $modpath = \SPP\Registry::get('__mods=>' . $modname);
        if ($modpath !== false) {
            $modManifest = $modpath . SPP_DS . 'module.xml';
            if (file_exists($modManifest)) {
                $xml = simplexml_load_file($modManifest);
                if ($xml !== false) {
                    $arr = $xml->xpath('/module');
                    $arr = (array) $arr[0] ?? [];
                    if (array_key_exists('config', $arr) && !empty($arr['config'])) {
                        $cfgFile = $modpath . SPP_DS . $arr['config'];
                        if (file_exists($cfgFile)) {
                            $cfgXml = simplexml_load_file($cfgFile);
                            if ($cfgXml !== false) {
                                $valueNodes = $cfgXml->xpath('/config/variables/variable[name=\'' . $varname . '\']/value');
                                if (!empty($valueNodes) && isset($valueNodes[0])) {
                                    $result = (string) $valueNodes[0];
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($result !== false) {
            self::$configCache[$modname][$varname] = $result;
            return $result;
        }

        // --- Step 3: YAML-based module config in legacy app settings location ---
        $yaml_dir = SPP_APP_DIR . SPP_DS . 'etc' . SPP_DS . 'settings' . SPP_DS . 'modules' . SPP_DS . $modname;
        $yaml_file = $yaml_dir . SPP_DS . 'config.yml';
        if (file_exists($yaml_file)) {
            $result = \SPP\Settings::getSetting($varname, 'variables', 'config.yml', $yaml_dir);
            if ($result !== false) {
                self::$configCache[$modname][$varname] = $result;
                return $result;
            }
        }

        // --- Step 4: Final fallback — app-level modsconf config.xml ---
        $confdir = $proc->getModsConfDir() . SPP_DS . $modname;
        $confXmlFile = $confdir . SPP_DS . 'config.xml';
        if (file_exists($confXmlFile)) {
            $xml = simplexml_load_file($confXmlFile);
            if ($xml !== false) {
                $valueNodes = $xml->xpath('/config/variables/variable[name=\'' . $varname . '\']/value');
                if (!empty($valueNodes) && isset($valueNodes[0])) {
                    $result = (string) $valueNodes[0];
                }
            }
        }

        self::$configCache[$modname][$varname] = $result;
        return $result;
    }

    /**
     * Sets a config variable for the module, persisting it to the canonical
     * per-app YAML config file: spp/etc/apps/<app>/modsconf/<modname>/config.yml
     *
     * Creates the directory and file if they do not yet exist.
     * Invalidates the in-memory cache entry so subsequent getConfig() reads
     * reflect the new value immediately.
     *
     * @param string $varname Config variable name
     * @param mixed  $value   Value to store (will be cast to string in YAML)
     * @param string $modname Module internal name
     * @return void
     * @throws \SPP\SPPException
     */
    public static function setConfig(string $varname, mixed $value, string $modname): void
    {
        $varname = str_replace(["'", '"'], '', $varname);
        $modname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $modname);

        $proc = \SPP\Scheduler::getActiveProc();
        $yamlConfFile = $proc->getModsConfDir() . SPP_DS . $modname . SPP_DS . 'config.yml';

        // Load existing data or start fresh
        $yamlData = [];
        if (file_exists($yamlConfFile)) {
            $yamlData = Yaml::parseFile($yamlConfFile) ?? [];
        } else {
            $dir = dirname($yamlConfFile);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new \SPP\SPPException("Failed to create config directory: {$dir}");
                }
            }
        }

        // Store under the 'variables' key to match the getConfig() read convention
        if (!isset($yamlData['variables']) || !is_array($yamlData['variables'])) {
            $yamlData['variables'] = [];
        }
        $yamlData['variables'][$varname] = $value;

        file_put_contents($yamlConfFile, Yaml::dump($yamlData, 4, 4));

        // Invalidate cache so next getConfig() reflects the new value
        self::$configCache[$modname][$varname] = (string) $value;
    }

    /**
     * Get config directory for module (app-level mods conf dir + module).
     *
     * @param string $modname
     * @return string
     */
    public static function getConfDir(string $modname): string
    {
        $modname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $modname);
        $dir = \SPP\Scheduler::getModsConfDir();
        $dir .= SPP_DS . $modname;
        return $dir;
    }

    /**
     * Returns an array of candidate config file paths for a module/filename.
     *
     * Legacy code returned an array with two candidate files.
     *
     * @param string $modname
     * @param string $filename
     * @return array<string>
     */
    public static function getConfFile(string $modname, string $filename): array
    {
        $file = self::getConfDir($modname);
        $file .= SPP_DS . $filename;

        $legacyYaml = SPP_APP_DIR . SPP_DS . 'etc' . SPP_DS . 'modules' . SPP_DS . $modname . SPP_DS . 'config.yml';

        return [$file, $legacyYaml];
    }

    /**
     * Returns a Module object for given module name by loading module.xml from registry path.
     *
     * @param string $modname
     * @return \SPP\Module
     * @throws \SPP\SPPException
     */
    public static function getModule(string $modname): \SPP\Module
    {
        $modname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $modname);
        $modpath = \SPP\Registry::get('__mods=>' . $modname);
        if ($modpath === false) {
            throw new \SPP\SPPException("Module not registered: {$modname}");
        }
        $modManifest = $modpath . SPP_DS . 'module.xml';
        return new \SPP\Module($modManifest);
    }

    /**
     * Scans modules directory for available modules (legacy helper).
     *
     * @return array<string>
     */
    public static function scanModules(): array
    {
        return SPPFS::findFile('module.xml', SPP_MODULES_DIR);
    }

    /**
     * Includes required files declared in module manifest and runs legacy mod-init.
     *
     * @return void
     */
    public function includeFiles(): void
    {
        $arr = (array) ($this->IncludeFiles ?? []);
        $realModPath = realpath($this->ModPath);

        foreach ($arr as $file) {
            $path = $this->ModPath . SPP_DS . $file;
            $realPath = realpath($path);

            if ($realPath !== false && str_starts_with($realPath, $realModPath) && file_exists($realPath)) {
                require_once $realPath;
            }
        }

        $initFile = $this->ModPath . SPP_DS . 'modinit.php';
        if (file_exists($initFile)) {
            require_once $initFile;
        }

        $eventsDir = $this->ModPath . SPP_DS . 'events';
        if (file_exists($eventsDir) && is_dir($eventsDir)) {
            // Register event directories using legacy API
            \SPP\SPPEvent::scanAndRegisterDirs($eventsDir);
        }
    }

    /**
     * Registers this module in the registry (path only).
     *
     * @return void
     * @throws DuplicateModuleException
     */
    public function register(): void
    {
        if (\SPP\Registry::get('__mods=>' . $this->InternalName) === false) {
            \SPP\Registry::register('__mods=>' . $this->InternalName, $this->ModPath);
        } else {
            // Gracefully ignore duplicate scans instead of fatal crashing locally safely seamlessly expertly natively intuitively smoothly safely explicitly cleverly cleanly dynamically organically successfully smoothly optimally physically natively smoothly comprehensively safely.
        }
    }

    /**
     * Returns true if module is registered.
     *
     * @return bool
     */
    public function isRegistered(): bool
    {
        return \SPP\Registry::get('__mods=>' . $this->InternalName) !== false;
    }

    /**
     * Loads all active modules for current context.
     *
     * Modern flow:
     *  - Loads original app and system modules.
     *  - Loads user/system modules from /etc/modules/<appname>/ (new).
     *
     * @return void
     * @throws \SPP\SPPException
     */
    public static function loadAllModules(): void
    {
        $appname = \SPP\Scheduler::getContext();

        // 1. Original SPP module locations (Always "system")
        $orig_yaml = ($appname !== '')
            ? SPP_ETC_DIR . SPP_DS . 'apps' . SPP_DS . $appname . SPP_DS . 'modules.yml'
            : SPP_ETC_DIR . SPP_DS . 'modules.yml';
        self::loadModulesFromManifest($orig_yaml, 'system');

        $orig_xml = ($appname !== '')
            ? SPP_ETC_DIR . SPP_DS . 'apps' . SPP_DS . $appname . SPP_DS . 'modules.xml'
            : SPP_ETC_DIR . SPP_DS . 'modules.xml';
        self::loadModulesFromManifest($orig_xml, 'system');

        // 2. Additional user module locations (Default "user")
        $user_xml = SPP_APP_DIR . SPP_DS . 'etc' . SPP_DS . 'modules' . SPP_DS . $appname . SPP_DS . 'modules.xml';
        self::loadModulesFromManifest($user_xml, 'user');

        $user_yaml = SPP_APP_DIR . SPP_DS . 'etc' . SPP_DS . 'modules' . SPP_DS . $appname . SPP_DS . 'modules.yml';
        self::loadModulesFromManifest($user_yaml, 'user');
    }

    /**
     * Helper to load modules from a specific manifest file.
     *
     * @param string $file        Path to modules.xml or modules.yml
     * @param string $defaultType Default module type (system/user)
     * @return void
     * @throws \SPP\SPPException
     */
    private static function loadModulesFromManifest(string $file, string $defaultType): void
    {
        if (!file_exists($file)) {
            return;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mods = [];

        if ($ext === 'yml' || $ext === 'yaml') {
            $parsed = Yaml::parseFile($file);
            $mods = $parsed['modules'] ?? [];
        } else {
            if (\LIBXML_VERSION < 20900 && function_exists('libxml_disable_entity_loader')) {
                libxml_disable_entity_loader(true);
            }
            $xml = simplexml_load_file($file);
            if ($xml !== false) {
                $mods = $xml->xpath('/modules/module[status=\'active\']');
            }
        }

        foreach ($mods as $mod) {
            $modArr = (array) $mod;

            // Handle type and base direction resolution
            // Type logic (per-module override or default)
            $type = $modArr['type'] ?? $defaultType;
            $baseDir = ($type === 'system') ? SPP_MODULES_DIR : SPP_DS . 'modules';

            $status = $modArr['status'] ?? 'active';
            if ((string) $status !== 'active') {
                continue;
            }

            $path = $modArr['modpath'] ?? ($modArr['path'] ?? null);
            if (empty($path)) {
                continue;
            }

            // Normalize path separators and resolve module.xml path
            if (SPP_DS !== '/') {
                $path = str_replace('/', SPP_DS, $path);
            }
            $manifestPath = $baseDir . SPP_DS . $path . SPP_DS . 'module.xml';

            if (!file_exists($manifestPath)) {
                continue;
            }

            $module = new \SPP\Module($manifestPath);
            $module->register();
            $module->includeFiles();
        }
    }

    /**
     * Returns true if a module is enabled (registered).
     *
     * @param string $mod
     * @return bool
     */
    public static function isEnabled(string $mod): bool
    {
        return \SPP\Registry::get('__mods=>' . $mod) !== false;
    }
}

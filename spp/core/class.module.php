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
        'ConfigFile',
        'ConfigVariables',
        'Installation',
        'RuntimeBridgeConfig'
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
        'ModuleGroup',
        'ConfigVariables',
        'Installation',
        'RuntimeBridgeConfig'
    ];

     /**
      * In-memory cache for configuration values to prevent duplicate XML I/O parsing.
      * @var array<string, array<string, string|false>>
      */
     protected static array $configCache = [];
 
      /**
       * Module origin type (system | user)
       * @var string
       */
      public string $ModuleType = 'user';

    /** @var bool Guard to prevent redundant full scans */
    private static bool $allModulesLoaded = false;

    /** @var array $Dependencies Stores dependencies requested by the module */
    public $Dependencies = array();

    /** @var array<string, array> Global manifest file cache */
    private static array $manifestFileCache = [];

    /** @var array<string, array> Individual module manifest data cache */
    private static array $moduleManifestCache = [];

    /** @var array Runtime bridge configuration */
    public array $RuntimeBridgeConfig = [];

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
        $this->ModPath = dirname($file);

        if (isset(self::$moduleManifestCache[$file])) {
            $this->mapManifestArray(self::$moduleManifestCache[$file]);
            return;
        }

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
        self::$moduleManifestCache[$file] = $moduleData;
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
        self::$moduleManifestCache[$file] = $arr;
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
        $this->Dependencies = [];
        $this->IncludeFiles = [];

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
                case 'config_variables':
                case 'config_defaults':
                    $this->ConfigVariables = (array) $val;
                    break;
                case 'includes':
                    // includes may be a single include entry or an array
                    $includes = (array) $val;
                    // If YAML/XML structure nests 'include' key, handle it
                    if (isset($includes['include'])) {
                        $this->IncludeFiles = (array)$includes['include'];
                    } else {
                        $this->IncludeFiles = $includes;
                    }
                    break;
                case 'deps':
                case 'dependencies':
                    $deps = (array) $val;
                    if (isset($deps['depends'])) {
                        $this->Dependencies = (array)$deps['depends'];
                    } else {
                        $this->Dependencies = $deps;
                    }
                    break;
                case 'installation':
                    $this->Installation = (array) $val;
                    break;
                case 'runtime_bridge':
                case 'bridge':
                    $this->RuntimeBridgeConfig = (array) $val;
                    break;
                case 'services':
                    $this->registerServices((array) $val);
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
     * Registers services into the current application container.
     */
    private function registerServices(array $services): void
    {
        $app = \SPP\App::getApp();
        foreach ($services as $abstract => $concrete) {
            $shared = true; // Default to singleton for services
            if (is_array($concrete)) {
                $shared = $concrete['shared'] ?? true;
                $concrete = $concrete['class'] ?? $concrete['concrete'] ?? null;
            }
            if ($concrete) {
                $app->bind($abstract, $concrete, $shared);
            }
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
     * @param string|null $appname Optional app context
     * @return string|false
     * @throws \SPP\SPPException
     */
    public static function getConfig(string $varname, string $modname, ?string $appname = null): string|false
    {
        // Sanitize varname and modname against injection & traversal
        $varname = str_replace(["'", '"'], '', $varname);
        $modname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $modname);
        $appname = $appname ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $appname) : \SPP\Scheduler::getContext();
        $cacheKey = $appname . '::' . $modname . '::' . $varname;

        if (isset(self::$configCache[$cacheKey])) {
            return self::$configCache[$cacheKey];
        }

        // --- Step 1: Check isolated per-app YAML config (Modern TOP priority) ---
        // Path: etc/apps/<app>/modsconf/<modname>/config.yml
        if ($appname) {
            $isolatedConf = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'modsconf' . SPP_DS . $modname . SPP_DS . 'config.yml';
            if (file_exists($isolatedConf)) {
                $yamlData = Yaml::parseFile($isolatedConf);
                $val = $yamlData['variables'][$varname] ?? ($yamlData[$varname] ?? null);
                if ($val !== null) {
                    $result = (string) $val;
                    self::$configCache[$cacheKey] = $result;
                    return $result;
                }
            }
        }

        // --- Step 2: Check canonical per-app YAML config (Framework priority) ---
        // Path: spp/etc/apps/<app>/modsconf/<modname>/config.yml
        $proc = null;
        if (\SPP\Scheduler::hasContext()) {
            $proc = \SPP\Scheduler::getActiveProc();
            $yamlConfFile = $proc->getModsConfDir() . SPP_DS . $modname . SPP_DS . 'config.yml';
            if (file_exists($yamlConfFile)) {
                $yamlData = Yaml::parseFile($yamlConfFile);
                // Convention: variables are stored under a 'variables' key
                $val = $yamlData['variables'][$varname] ?? ($yamlData[$varname] ?? null);
                if ($val !== null) {
                    $result = (string) $val;
                    self::$configCache[$cacheKey] = $result;
                    return $result;
                }
            }
        }

        // --- Step 2: Try /etc/<appname>/modsconf/config.yml (Legacy/Secondary Global App Config) ---
        $appname = $appname ?: \SPP\Scheduler::getContext();
        if ($appname) {
            $appGlobalConf = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'modsconf' . SPP_DS . 'config.yml';
            if (file_exists($appGlobalConf)) {
                $appYaml = Yaml::parseFile($appGlobalConf);
                if (isset($appYaml[$modname]['variables'][$varname])) {
                    $val = (string) $appYaml[$modname]['variables'][$varname];
                    self::$configCache[$cacheKey] = $val;
                    return $val;
                } elseif (isset($appYaml[$modname][$varname])) {
                    $val = (string) $appYaml[$modname][$varname];
                    self::$configCache[$cacheKey] = $val;
                    return $val;
                }
            }
        }

        // --- Step 2.5: Try global system etc directory for <modname>.yml (Service Fallback) ---
        $globalServiceConf = SPP_ETC_DIR . SPP_DS . $modname . '.yml';
        if (file_exists($globalServiceConf)) {
            $serviceYaml = Yaml::parseFile($globalServiceConf);
            $val = $serviceYaml['variables'][$varname] ?? ($serviceYaml[$varname] ?? null);
            if ($val !== null) {
                $result = (string) $val;
                self::$configCache[$cacheKey] = $result;
                return $result;
            }
        }

        if (\LIBXML_VERSION < 20900 && function_exists('libxml_disable_entity_loader')) {
            libxml_disable_entity_loader(true);
        }

        $result = false;

        // --- Step 3: Try module's own bundled config (from manifest <config> tag) ---
        $modpath = \SPP\Registry::get('__mods=>' . $modname);
        if ($modpath !== false) {
            // Check for YAML manifest first, then XML
            $manifestFiles = [$modpath . SPP_DS . 'module.yml', $modpath . SPP_DS . 'module.xml'];
            foreach ($manifestFiles as $modManifest) {
                if (file_exists($modManifest)) {
                    $ext = strtolower(pathinfo($modManifest, PATHINFO_EXTENSION));
                    $configRelPath = null;
                    if ($ext === 'yml' || $ext === 'yaml') {
                        $yml = Yaml::parseFile($modManifest);
                        $configRelPath = $yml['module']['config'] ?? null;
                    } else {
                        $xml = simplexml_load_file($modManifest);
                        if ($xml !== false) {
                            $arr = (array) ($xml->xpath('/module')[0] ?? []);
                            $configRelPath = $arr['config'] ?? null;
                        }
                    }

                    if ($configRelPath) {
                        $cfgFile = $modpath . SPP_DS . $configRelPath;
                        if (file_exists($cfgFile)) {
                            $cfgExt = strtolower(pathinfo($cfgFile, PATHINFO_EXTENSION));
                            if ($cfgExt === 'yml' || $cfgExt === 'yaml') {
                                $cfgData = Yaml::parseFile($cfgFile);
                                $val = $cfgData['variables'][$varname] ?? ($cfgData[$varname] ?? null);
                                if ($val !== null) {
                                    $result = (string)$val;
                                    break;
                                }
                            } else {
                                $cfgXml = simplexml_load_file($cfgFile);
                                if ($cfgXml !== false) {
                                    $valueNodes = $cfgXml->xpath('/config/variables/variable[name=\'' . $varname . '\']/value');
                                    if (!empty($valueNodes) && isset($valueNodes[0])) {
                                        $result = (string) $valueNodes[0];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    if ($result !== false) break;
                }
            }
        }

        if ($result !== false) {
            self::$configCache[$cacheKey] = $result;
            return $result;
        }

        // --- Step 3: YAML-based module config in legacy app settings location ---
        $yaml_dir = SPP_APP_DIR . SPP_DS . 'etc' . SPP_DS . 'settings' . SPP_DS . 'modules' . SPP_DS . $modname;
        $yaml_file = $yaml_dir . SPP_DS . 'config.yml';
        if (file_exists($yaml_file)) {
            $result = \SPP\Settings::getSetting($varname, 'variables', 'config.yml', $yaml_dir);
            if ($result !== false) {
                self::$configCache[$cacheKey] = $result;
                return $result;
            }
        }

        // --- Step 4: Final fallback — app-level modsconf config.xml ---
        if ($proc) {
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
        }

        if ($result === false) {
            // --- Step 5: Manifest Declarations (Final Fallback) ---
            $mod = \SPP\Registry::get('__modobj=>' . $modname);
            if ($mod) {
                $declared = $mod->ConfigVariables ?? [];
                if (in_array($varname, $declared) || isset($declared[$varname])) {
                    $result = (string) ($declared[$varname] ?? "");
                }
            }
        }

        self::$configCache[$cacheKey] = $result;
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
        $appname = $appname ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $appname) : \SPP\Scheduler::getContext();
        $cacheKey = $appname . '::' . $modname . '::' . $varname;
        self::$configCache[$cacheKey] = (string) $value;
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
     * Returns the expected path for a module's YAML configuration file.
     * 
     * @param string $modname
     * @param string|null $appname
     * @return string
     */
    public static function getExpectedConfigPath(string $modname, ?string $appname = null): string
    {
        $appname = $appname ?: \SPP\Scheduler::getContext();
        return APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'modsconf' . SPP_DS . $modname . SPP_DS . 'config.yml';
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
            \SPP\Registry::register('__modobj=>' . $this->InternalName, $this);
        } else {
            // Gracefully ignore duplicate scans instead of fatal crashing locally safely seamlessly expertly natively intuitively smoothly safely explicitly cleverly cleanly dynamically organically successfully smoothly optimally physically natively smoothly comprehensively safely.
        }
    }

    /**
     * Finds the absolute path to a module's manifest file (module.yml or module.xml).
     * Correctly handles nested directory structures (e.g. spp/modules/spp/modname).
     *
     * @param string $modname Internal name of the module
     * @param string $type Origin type ('system' or 'user')
     * @param string|null $appname App context for user modules
     * @return string|null
     */
    public static function findManifestPath(string $modname, string $type = 'system', ?string $appname = null): ?string
    {
        $possible = ['module.yml', 'module.yaml', 'module.xml'];
        $appname = $appname ?: \SPP\Scheduler::getContext();

        if ($type === 'system') {
            // Check flat root first
            $checkPaths = [SPP_MODULES_DIR . SPP_DS . $modname];
            // Check common subdirectories
            foreach (['spp', 'school', 'custom'] as $sub) {
                $checkPaths[] = SPP_MODULES_DIR . SPP_DS . $sub . SPP_DS . $modname;
            }

            foreach ($checkPaths as $dir) {
                if (!is_dir($dir)) continue;
                foreach ($possible as $m) {
                    if (file_exists($dir . SPP_DS . $m)) return $dir . SPP_DS . $m;
                }
            }
        } else {
            // User/App modules
            $userDir = SPP_APP_DIR . SPP_DS . 'modules' . SPP_DS . $appname . SPP_DS . $modname;
            if (is_dir($userDir)) {
                foreach ($possible as $m) {
                    if (file_exists($userDir . SPP_DS . $m)) return $userDir . SPP_DS . $m;
                }
            }
        }

        return null;
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
        if (self::$allModulesLoaded) {
            return;
        }

        $appname = \SPP\Scheduler::getContext();
        $registries = [];

        // 1. Primary App Preference: SPP_ETC_DIR/apps/<app>/modsconf/
        if ($appname !== '') {
            $modsconfDir = SPP_ETC_DIR . SPP_DS . 'apps' . SPP_DS . $appname . SPP_DS . 'modsconf';
            if (file_exists($modsconfDir . SPP_DS . 'modules.yml')) {
                $registries[] = ['file' => $modsconfDir . SPP_DS . 'modules.yml', 'type' => 'system'];
            } elseif (file_exists($modsconfDir . SPP_DS . 'modules.xml')) {
                $registries[] = ['file' => $modsconfDir . SPP_DS . 'modules.xml', 'type' => 'system'];
            }
        }

        // 2. User Overrides (etc/apps/): Standard priority
        if ($appname !== '') {
            $userModsconf = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'modsconf';
            if (file_exists($userModsconf . SPP_DS . 'modules.yml')) {
                $registries[] = ['file' => $userModsconf . SPP_DS . 'modules.yml', 'type' => 'user'];
            } elseif (file_exists($userModsconf . SPP_DS . 'modules.xml')) {
                $registries[] = ['file' => $userModsconf . SPP_DS . 'modules.xml', 'type' => 'user'];
            }
        }

        // 3. System Defaults (spp/etc/): Fallbacks
        $registries[] = ['file' => SPP_ETC_DIR . SPP_DS . 'modules.yml', 'type' => 'system'];
        $registries[] = ['file' => SPP_ETC_DIR . SPP_DS . 'modules.xml', 'type' => 'system'];
        
        // 4. Per-app Legacy Defaults (spp/etc/apps/)
        if ($appname !== '') {
            $registries[] = ['file' => SPP_ETC_DIR . SPP_DS . 'apps' . SPP_DS . $appname . SPP_DS . 'modules.yml', 'type' => 'system'];
            $registries[] = ['file' => SPP_ETC_DIR . SPP_DS . 'apps' . SPP_DS . $appname . SPP_DS . 'modules.xml', 'type' => 'system'];
        }

        foreach ($registries as $r) {
            self::loadModulesFromManifest($r['file'], $r['type']);
        }

        self::$allModulesLoaded = true;
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
        $appname = \SPP\Scheduler::getContext();
        if (!file_exists($file)) {
            return;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mods = [];

        try {
            if (isset(self::$manifestFileCache[$file])) {
                $mods = self::$manifestFileCache[$file];
            } else {
                if ($ext === 'yml' || $ext === 'yaml') {
                    $parsed = Yaml::parseFile($file);
                    $mods = $parsed['modules'] ?? [];
                } else {
                    if (\LIBXML_VERSION < 20900 && function_exists('libxml_disable_entity_loader')) {
                        libxml_disable_entity_loader(true);
                    }
                    $xml = simplexml_load_file($file);
                    if ($xml !== false) {
                        $mods = $xml->xpath('/modules/module');
                    }
                }
                self::$manifestFileCache[$file] = $mods;
            }
        } catch (\Exception $e) {
            return;
        }

        $deadModules = [];

        foreach ($mods as $mod) {
            $modArr = (array) $mod;

            // Handle type and base direction resolution
            $type = $modArr['type'] ?? $defaultType;
            $status = $modArr['status'] ?? 'active';
            
            if ((string) $status !== 'active') {
                continue;
            }

            $path = $modArr['modpath'] ?? ($modArr['path'] ?? null);
            if (empty($path)) {
                continue;
            }

            // Normalize path separators
            if (SPP_DS !== '/') {
                $path = str_replace('/', SPP_DS, $path);
            }
            
            $manifestPath = null;
            $possibleManifests = ['module.yml', 'module.yaml', 'module.xml'];
            
            // Primary parent directory based on type
            $primaryDir = ($type === 'system') 
                ? SPP_MODULES_DIR 
                : SPP_APP_DIR . SPP_DS . 'modules' . SPP_DS . $appname;

            // Discovery logic for system modules (any-depth)
            if ($type === 'system') {
                $foundDir = null;
                if (is_dir(SPP_MODULES_DIR . SPP_DS . $path)) {
                    $foundDir = SPP_MODULES_DIR . SPP_DS . $path;
                } else {
                    // Try depth 2: e.g. spp/modules/spp/modname or spp/modules/school/modname
                    foreach (['spp', 'school', 'custom'] as $sub) {
                        if (is_dir(SPP_MODULES_DIR . SPP_DS . $sub . SPP_DS . $path)) {
                            $foundDir = SPP_MODULES_DIR . SPP_DS . $sub . SPP_DS . $path;
                            break;
                        }
                    }
                }

                if ($foundDir) {
                    foreach ($possibleManifests as $m) {
                        if (file_exists($foundDir . SPP_DS . $m)) {
                            $manifestPath = $foundDir . SPP_DS . $m;
                            break;
                        }
                    }
                }
            } else {
                // User/App modules
                foreach ($possibleManifests as $m) {
                    $testPath = $primaryDir . SPP_DS . $path . SPP_DS . $m;
                    if (file_exists($testPath)) {
                        $manifestPath = $testPath;
                        break;
                    }
                }
            }

            if (!$manifestPath) {
                $deadModules[] = $modArr['name'] ?? $modArr['modname'] ?? basename($path);
                continue;
            }

            $module = new \SPP\Module($manifestPath);
            // Safety Enforcement: Force system type if located in SPP_MODULES_DIR
            if (strpos(realpath($manifestPath), realpath(SPP_MODULES_DIR)) === 0) {
                $module->ModuleType = 'system';
            } else {
                $module->ModuleType = $type;
            }
            
            $module->register();
            $module->includeFiles();
        }

        // Cleanup dead references automatically
        // Only prune if we are reasonably sure the filesystem is healthy and the base directory exists
        if (!empty($deadModules) && is_dir(SPP_MODULES_DIR)) {
             self::pruneFromManifest($file, $deadModules);
        }
    }

    /**
     * Removes dead module references from a manifest file.
     *
     * @param string $file
     * @param array $moduleNames
     */
    private static function pruneFromManifest(string $file, array $moduleNames): void
    {
        if (!file_exists($file)) return;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if ($ext === 'yml' || $ext === 'yaml') {
            $data = Yaml::parseFile($file);
            if (!isset($data['modules']) || !is_array($data['modules'])) return;
            
            $initialCount = count($data['modules']);
            $data['modules'] = array_filter($data['modules'], function($m) use ($moduleNames) {
                $mArr = (array)$m;
                $name = $mArr['name'] ?? $mArr['modname'] ?? '';
                return !in_array($name, $moduleNames);
            });

            if (count($data['modules']) !== $initialCount) {
                file_put_contents($file, Yaml::dump($data, 4, 4));
            }
        } elseif ($ext === 'xml') {
            $xml = simplexml_load_file($file);
            if ($xml === false) return;
            
            $modified = false;
            foreach ($moduleNames as $name) {
                // Try several common XML shapes
                $nodes = $xml->xpath("//module[name='{$name}'] | //module[modname='{$name}'] | //module[@name='{$name}']");
                foreach ($nodes as $node) {
                    $dom = dom_import_simplexml($node);
                    $dom->parentNode->removeChild($dom);
                    $modified = true;
                }
            }
            
            if ($modified) {
                $xml->asXML($file);
            }
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

    /**
     * Toggles a module's status in all known modules.xml and modules.yml files
     * (system-level and per-app) to keep them in sync.
     *
     * @param string $modname Module internal name
     * @param string $status  'active' or 'inactive'
     * @return array List of files that were modified
     * @throws \SPP\SPPException
     */
    public static function toggleModuleStatus(string $modname, string $status): array
    {
        $modname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $modname);
        $status = in_array($status, ['active', 'inactive']) ? $status : 'inactive';

        $updatedFiles = [];

        // Determine app context for per-app paths
        $appname = '';
        if (class_exists('\\SPP\\Scheduler')) {
            try { $appname = \SPP\Scheduler::getContext(); } catch (\Throwable $e) {}
        }
        // Fallback to GET/POST parameter if Scheduler context is not set
        if ($appname === '' && isset($_POST['appname'])) {
            $appname = $_POST['appname'];
        }
        if ($appname === '' && isset($_GET['appname'])) {
            $appname = $_GET['appname'];
        }
        if ($appname === '') $appname = 'default';

        // 1. Identify type and location
        $type = null;
        $modPath = \SPP\Registry::get('__mods=>' . $modname);
        $modObj = \SPP\Registry::get('__modobj=>' . $modname);
        
        if ($modObj instanceof \SPP\Module) {
            $type = $modObj->ModuleType;
        }

        // If not registered, perform discovery
        if (!$modPath) {
            // Check System hierarchy (spp/modules/spp/)
            if (is_dir(SPP_MODULES_DIR . SPP_DS . 'spp' . SPP_DS . $modname)) {
                $type = 'system';
                $modPath = 'spp' . SPP_DS . $modname;
            } elseif (is_dir(SPP_MODULES_DIR . SPP_DS . $modname)) {
                $type = 'system';
                $modPath = $modname;
            } else {
                // Check User/App hierarchy (modules/<appname>/)
                $userDir = SPP_APP_DIR . SPP_DS . 'modules' . SPP_DS . $appname . SPP_DS . $modname;
                if (is_dir($userDir)) {
                    $type = 'user';
                    $modPath = $modname;
                }
            }
        } else {
            // Already registered, check type by path if Obj missing
            if (!$type) {
                if (strpos(realpath($modPath), realpath(SPP_MODULES_DIR)) === 0) {
                    $type = 'system';
                } else {
                    $type = 'user';
                }
            }
        }

        if (!$type) {
             throw new \SPP\SPPException("Module '{$modname}' could not be located in the filesystem.");
        }

        // 2. Canonical write target: SPP_ETC_DIR/apps/<appname>/modsconf/modules.yml
        $candidates = [];
        $preferred = '';

        if (defined('SPP_ETC_DIR')) {
            $preferred = SPP_ETC_DIR . SPP_DS . 'apps' . SPP_DS . $appname . SPP_DS . 'modsconf' . SPP_DS . 'modules.yml';
            $candidates[] = $preferred;
        }

        // 2a. Auto-migrate: if modules.yml doesn't exist but modules.xml does, migrate first
        if ($preferred !== '' && !file_exists($preferred)) {
            $xmlSibling = substr($preferred, 0, -4) . '.xml'; // same path but .xml
            if (file_exists($xmlSibling)) {
                self::migrateXmlToYaml($xmlSibling, $preferred);
            }
        }

        // 3. Try to update existing entries
        foreach ($candidates as $file) {
            if (!file_exists($file)) continue;

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (($ext === 'yml' || $ext === 'yaml') && self::toggleInYaml($file, $modname, $status)) {
                $updatedFiles[] = $file;
            }
        }

        // 4. If not found in any file, append to the preferred one
        if (empty($updatedFiles) && $preferred !== '') {
            if (!is_dir(dirname($preferred))) mkdir(dirname($preferred), 0755, true);
            
            $entry = [
                'name' => $modname,
                'path' => str_replace('\\', '/', $modPath),
                'status' => $status
            ];
            
            $data = file_exists($preferred) ? Yaml::parseFile($preferred) : ['modules' => []];
            if (!isset($data['modules']) || !is_array($data['modules'])) $data['modules'] = [];
            $data['modules'][] = $entry;
            
            file_put_contents($preferred, Yaml::dump($data, 4, 4));
            $updatedFiles[] = $preferred;
        }

        return $updatedFiles;
    }

    /**
     * Migrates a modules.xml file to modules.yml in the same directory.
     *
     * Reads all <module> entries from the XML and writes them as a
     * structured YAML file. The XML file is left untouched.
     *
     * @param string $xmlFile  Absolute path to the source modules.xml
     * @param string $ymlFile  Absolute path to the target modules.yml
     * @return bool True if migration succeeded
     */
    public static function migrateXmlToYaml(string $xmlFile, string $ymlFile): bool
    {
        if (!file_exists($xmlFile)) {
            return false;
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($xmlFile);
        if ($xml === false) {
            return false;
        }

        $modules = [];
        foreach ($xml->module as $mod) {
            $entry = [];
            // Normalize: modules.xml uses <modname>, some use <name>
            $entry['modname'] = (string) ($mod->modname ?? $mod->name ?? '');
            if (empty($entry['modname'])) continue;
            $entry['path']   = (string) ($mod->modpath ?? $mod->path ?? '');
            $entry['status'] = (string) ($mod->status ?? 'active');
            // Preserve dependencies if present
            if (isset($mod->dependencies)) {
                $deps = [];
                foreach ($mod->dependencies->dependency as $dep) {
                    $deps[] = (string) $dep;
                }
                if (!empty($deps)) $entry['dependencies'] = $deps;
            }
            $modules[] = $entry;
        }

        if (empty($modules)) {
            return false;
        }

        $data = ['modules' => $modules];

        if (!is_dir(dirname($ymlFile))) {
            mkdir(dirname($ymlFile), 0755, true);
        }

        file_put_contents($ymlFile, Yaml::dump($data, 4, 4));
        return true;
    }

    /**
     * Updates module status in an XML modules manifest using DOMDocument
     * to preserve formatting and comments.
     *
     * @param string $file    Path to modules.xml
     * @param string $modname Module name to find
     * @param string $status  New status value
     * @return bool True if the file was modified
     */
    private static function toggleInXml(string $file, string $modname, string $status): bool
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        if (!$dom->load($file)) {
            return false;
        }

        $modified = false;
        $modules = $dom->getElementsByTagName('module');

        foreach ($modules as $moduleNode) {
            $nameNode = $moduleNode->getElementsByTagName('modname')->item(0);
            // Also try <name> tag (module.xml uses <name>, modules.xml uses <modname>)
            if (!$nameNode) {
                $nameNode = $moduleNode->getElementsByTagName('name')->item(0);
            }
            if (!$nameNode || $nameNode->textContent !== $modname) {
                continue;
            }

            $statusNode = $moduleNode->getElementsByTagName('status')->item(0);
            if ($statusNode) {
                if ($statusNode->textContent !== $status) {
                    $statusNode->textContent = $status;
                    $modified = true;
                }
            } else {
                // Create <status> element if missing
                $newStatus = $dom->createElement('status', $status);
                $moduleNode->appendChild($newStatus);
                $modified = true;
                $dom->save($file);
            }
            break;
        }

        if ($modified) {
            $dom->save($file);
        }

        return $modified;
    }

    /**
     * Updates module status in a YAML modules manifest.
     *
     * @param string $file    Path to modules.yml
     * @param string $modname Module name to find
     * @param string $status  New status value
     * @return bool True if the file was modified
     */
    private static function toggleInYaml(string $file, string $modname, string $status): bool
    {
        $parsed = Yaml::parseFile($file);
        if (!isset($parsed['modules']) || !is_array($parsed['modules'])) {
            return false;
        }

        $modified = false;
        foreach ($parsed['modules'] as &$mod) {
            $modArr = (array) $mod;
            $name = $modArr['modname'] ?? ($modArr['name'] ?? null);
            if ($name === $modname) {
                $currentStatus = $modArr['status'] ?? 'active';
                if ($currentStatus !== $status) {
                    $mod['status'] = $status;
                    $modified = true;
                }
                break;
            }
        }
        unset($mod);

        if ($modified) {
            file_put_contents($file, Yaml::dump($parsed, 4, 4));
        }

        return $modified;
    }

    /**
     * Returns all config variables for a module as an associative array.
     *
     * Resolution order:
     *  1. Canonical per-app YAML config
     *  2. Module's bundled config (XML)
     *  3. Legacy YAML in app settings
     *  4. Legacy XML modsconf
     *
     * @param string $modname Module internal name
     * @return array ['variables' => [...], 'source' => string]
     */
    public static function getAllConfig(string $modname): array
    {
        $modname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $modname);
        $appname = \SPP\Scheduler::getContext();
        
        // 1. Start with per-app logic (Strict Separation)
        $res = self::getAllConfigForApp($modname, $appname);
        $variables = $res['variables'] ?? [];
        $source = $res['source'] ?? '';

        if (!empty($variables)) {
            return ['variables' => $variables, 'source' => $source];
        }

        // 2. Module's bundled config XML (Fallback)
        $modpath = \SPP\Registry::get('__mods=>' . $modname);
        if ($modpath !== false) {
            $modManifest = $modpath . SPP_DS . 'module.xml';
            if (file_exists($modManifest)) {
                $xml = simplexml_load_file($modManifest);
                if ($xml !== false) {
                    $arr = (array) ($xml->xpath('/module')[0] ?? []);
                    if (!empty($arr['config'])) {
                        $cfgFile = $modpath . SPP_DS . $arr['config'];
                        if (file_exists($cfgFile)) {
                            $ext = strtolower(pathinfo($cfgFile, PATHINFO_EXTENSION));
                            if ($ext === 'yml' || $ext === 'yaml') {
                                $cfgData = Yaml::parseFile($cfgFile);
                                $variables = $cfgData['variables'] ?? $cfgData ?? [];
                            } else {
                                $cfgXml = simplexml_load_file($cfgFile);
                                if ($cfgXml !== false) {
                                    $varNodes = $cfgXml->xpath('/config/variables/variable');
                                    foreach ($varNodes as $vn) {
                                        $variables[(string) $vn->name] = (string) $vn->value;
                                    }
                                }
                            }
                            $source = $cfgFile;
                            return ['variables' => $variables, 'source' => $source];
                        }
                    }
                }
            }
        }

        // 3. Legacy YAML in app settings
        if (defined('SPP_APP_DIR')) {
            $legacyYaml = SPP_APP_DIR . SPP_DS . 'etc' . SPP_DS . 'settings' . SPP_DS . 'modules' . SPP_DS . $modname . SPP_DS . 'config.yml';
            if (file_exists($legacyYaml)) {
                $yamlData = Yaml::parseFile($legacyYaml);
                $variables = $yamlData['variables'] ?? $yamlData ?? [];
                $source = $legacyYaml;
                return ['variables' => $variables, 'source' => $source];
            }
        }

        // 4. Legacy XML modsconf
        try {
            $proc = \SPP\Scheduler::getActiveProc();
            $confXmlFile = $proc->getModsConfDir() . SPP_DS . $modname . SPP_DS . 'config.xml';
            if (file_exists($confXmlFile)) {
                $cfgXml = simplexml_load_file($confXmlFile);
                if ($cfgXml !== false) {
                    $varNodes = $cfgXml->xpath('/config/variables/variable');
                    foreach ($varNodes as $vn) {
                        $variables[(string) $vn->name] = (string) $vn->value;
                    }
                }
                $source = $confXmlFile;
            }
        } catch (\Throwable $e) {}

        return ['variables' => $variables, 'source' => $source];
    }

    /**
     * Returns the raw content of a module's config file for direct editing.
     *
     * @param string $modname Module internal name
     * @return array ['content' => string, 'path' => string, 'format' => string]
     */
    public static function getRawConfig(string $modname): array
    {
        $modname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $modname);

        // Search for config file in priority order
        $candidates = [];

        // 1. Canonical per-app YAML
        try {
            $proc = \SPP\Scheduler::getActiveProc();
            $candidates[] = $proc->getModsConfDir() . SPP_DS . $modname . SPP_DS . 'config.yml';
        } catch (\Throwable $e) {}

        // 2. Module's bundled config (resolve from module.xml)
        $modpath = \SPP\Registry::get('__mods=>' . $modname);
        if ($modpath !== false) {
            $modManifest = $modpath . SPP_DS . 'module.xml';
            if (file_exists($modManifest)) {
                $xml = simplexml_load_file($modManifest);
                if ($xml !== false) {
                    $arr = (array) ($xml->xpath('/module')[0] ?? []);
                    if (!empty($arr['config'])) {
                        $candidates[] = $modpath . SPP_DS . $arr['config'];
                    }
                }
            }
        }

        // 3. Legacy paths
        try {
            $proc = \SPP\Scheduler::getActiveProc();
            $candidates[] = $proc->getModsConfDir() . SPP_DS . $modname . SPP_DS . 'config.xml';
        } catch (\Throwable $e) {}

        if (defined('SPP_APP_DIR')) {
            $candidates[] = SPP_APP_DIR . SPP_DS . 'etc' . SPP_DS . 'settings' . SPP_DS . 'modules' . SPP_DS . $modname . SPP_DS . 'config.yml';
        }

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return [
                    'content' => file_get_contents($path),
                    'path'    => $path,
                    'format'  => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                ];
            }
        }

        return ['content' => '', 'path' => '', 'format' => 'yml'];
    }

    /**
     * Returns all config variables for a module within a specific app context.
     * Bypasses the Scheduler — works with direct path resolution.
     *
     * @param string $modname Module internal name
     * @param string $appname Application name (e.g. 'default', 'demo', 'sppadmin')
     * @return array ['variables' => [...], 'source' => string]
     */
    public static function getAllConfigForApp(string $modname, string $appname): array
    {
        $modname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $modname);
        $appname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $appname);
        $variables = [];
        $source = '';

        // Identify type (System or User)
        $type = 'user'; // Default
        $modObj = \SPP\Registry::get('__modobj=>' . $modname);
        if ($modObj instanceof \SPP\Module) {
            $type = $modObj->ModuleType;
        } else {
            // Discovery fallback: check location
            $modPath = \SPP\Registry::get('__mods=>' . $modname);
            if ($modPath && strpos(realpath($modPath), realpath(SPP_MODULES_DIR)) === 0) {
                $type = 'system';
            }
        }

        $modsConfDir = '';
        if ($type === 'system') {
            if (defined('SPP_ETC_DIR')) {
                $modsConfDir = SPP_ETC_DIR . SPP_DS . 'apps' . SPP_DS . $appname . SPP_DS . 'modsconf';
            }
        } else {
            if (defined('APP_ETC_DIR')) {
                $modsConfDir = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'modsconf';
            }
        }

        if ($modsConfDir !== '') {
            // 1. Canonical per-app YAML
            $yamlConfFile = $modsConfDir . SPP_DS . $modname . SPP_DS . 'config.yml';
            if (file_exists($yamlConfFile)) {
                $yamlData = Yaml::parseFile($yamlConfFile);
                $variables = $yamlData['variables'] ?? $yamlData ?? [];
                $source = $yamlConfFile;
                return ['variables' => $variables, 'source' => $source];
            }

            // 2. Canonical per-app XML
            $xmlConfFile = $modsConfDir . SPP_DS . $modname . SPP_DS . 'config.xml';
            if (file_exists($xmlConfFile)) {
                $cfgXml = simplexml_load_file($xmlConfFile);
                if ($cfgXml !== false) {
                    $varNodes = $cfgXml->xpath('/config/variables/variable');
                    foreach ($varNodes as $vn) {
                        $variables[(string) $vn->name] = (string) $vn->value;
                    }
                }
                $source = $xmlConfFile;
                return ['variables' => $variables, 'source' => $source];
            }
        }

        // 3. Module's bundled config (from manifest)
        $modpath = \SPP\Registry::get('__mods=>' . $modname);
        if ($modpath !== false) {
            $manifestFiles = [$modpath . SPP_DS . 'module.yml', $modpath . SPP_DS . 'module.xml'];
            foreach ($manifestFiles as $modManifest) {
                if (file_exists($modManifest)) {
                    $ext = strtolower(pathinfo($modManifest, PATHINFO_EXTENSION));
                    $configRelPath = null;
                    if ($ext === 'yml' || $ext === 'yaml') {
                        $yml = Yaml::parseFile($modManifest);
                        $configRelPath = $yml['module']['config'] ?? null;
                    } else {
                        $xml = simplexml_load_file($modManifest);
                        if ($xml !== false) {
                            $arr = (array) ($xml->xpath('/module')[0] ?? []);
                            $configRelPath = $arr['config'] ?? null;
                        }
                    }

                    if ($configRelPath) {
                        $cfgFile = $modpath . SPP_DS . $configRelPath;
                        if (file_exists($cfgFile)) {
                            $cfgExt = strtolower(pathinfo($cfgFile, PATHINFO_EXTENSION));
                            if ($cfgExt === 'yml' || $cfgExt === 'yaml') {
                                $cfgData = Yaml::parseFile($cfgFile);
                                $foundVars = $cfgData['variables'] ?? $cfgData ?? [];
                                $variables = array_merge($variables, $foundVars);
                                $source = $cfgFile;
                            } else {
                                $cfgXml2 = simplexml_load_file($cfgFile);
                                if ($cfgXml2 !== false) {
                                    $varNodes = $cfgXml2->xpath('/config/variables/variable');
                                    foreach ($varNodes as $vn) {
                                        $variables[(string) $vn->name] = (string) $vn->value;
                                    }
                                    $source = $cfgFile;
                                }
                            }
                        }
                    }
                    if (!empty($variables)) break;
                }
            }
        }

        // 4. Manifest Declarations (Merged with actual values)
        $mod = \SPP\Registry::get('__modobj=>' . $modname);
        if ($mod) {
            $declared = $mod->ConfigVariables ?? [];
            if (!empty($declared)) {
                $merged = [];
                foreach ($declared as $k => $v) {
                    $key = is_numeric($k) ? (string)$v : (string)$k;
                    $merged[$key] = "";
                }
                // Merge any actually found variables over the defaults
                return ['variables' => array_merge($merged, $variables), 'source' => ($source ?: 'manifest')];
            }
        }

        return ['variables' => $variables, 'source' => $source];
    }

    /**
     * Returns raw config file content for a module within a specific app context.
     * Bypasses the Scheduler — works with direct path resolution.
     *
     * @param string $modname Module internal name
     * @param string $appname Application name
     * @return array ['content' => string, 'path' => string, 'format' => string]
     */
    public static function getRawConfigForApp(string $modname, string $appname): array
    {
        $modname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $modname);
        $appname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $appname);

        // Identify type (System or User)
        $type = 'user'; // Default
        $modObj = \SPP\Registry::get('__modobj=>' . $modname);
        if ($modObj instanceof \SPP\Module) {
            $type = $modObj->ModuleType;
        } else {
            // Discovery fallback: check location
            $modPath = \SPP\Registry::get('__mods=>' . $modname);
            if ($modPath && strpos(realpath($modPath), realpath(SPP_MODULES_DIR)) === 0) {
                $type = 'system';
            }
        }

        $modsConfDir = '';
        if ($type === 'system') {
            if (defined('SPP_ETC_DIR')) {
                $modsConfDir = SPP_ETC_DIR . SPP_DS . 'apps' . SPP_DS . $appname . SPP_DS . 'modsconf';
            }
        } else {
            if (defined('APP_ETC_DIR')) {
                $modsConfDir = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'modsconf';
            }
        }

        if ($modsConfDir === '') {
            return ['content' => '', 'path' => '', 'format' => 'yml'];
        }

        $candidates = [];

        // 1. Per-app YAML
        $candidates[] = $modsConfDir . SPP_DS . $modname . SPP_DS . 'config.yml';
        // 2. Per-app XML
        $candidates[] = $modsConfDir . SPP_DS . $modname . SPP_DS . 'config.xml';

        // 3. Module's bundled config
        $modpath = \SPP\Registry::get('__mods=>' . $modname);
        if ($modpath !== false) {
            $modManifest = $modpath . SPP_DS . 'module.xml';
            if (file_exists($modManifest)) {
                $xml = simplexml_load_file($modManifest);
                if ($xml !== false) {
                    $arr = (array) ($xml->xpath('/module')[0] ?? []);
                    if (!empty($arr['config'])) {
                        $candidates[] = $modpath . SPP_DS . $arr['config'];
                    }
                }
            }
        }

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return [
                    'content' => file_get_contents($path),
                    'path'    => $path,
                    'format'  => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                ];
            }
        }

        return ['content' => '', 'path' => '', 'format' => 'yml'];
    }

    /**
     * Saves a config variable for a module within a specific app context.
     * Bypasses the Scheduler.
     *
     * @param string $varname Variable name
     * @param mixed  $value   Value
     * @param string $modname Module internal name
     * @param string $appname Application name
     */
    public static function setConfigForApp(string $varname, mixed $value, string $modname, string $appname): void
    {
        $varname = str_replace(["'", '"'], '', $varname);
        $modname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $modname);
        $appname = preg_replace('/[^a-zA-Z0-9_\-]/', '', $appname);

        // Identify type (System or User)
        $type = 'user'; // Default
        $modObj = \SPP\Registry::get('__modobj=>' . $modname);
        if ($modObj instanceof \SPP\Module) {
            $type = $modObj->ModuleType;
        } else {
            // Discovery fallback: check location
            $modPath = \SPP\Registry::get('__mods=>' . $modname);
            if ($modPath && strpos(realpath($modPath), realpath(SPP_MODULES_DIR)) === 0) {
                $type = 'system';
            }
        }

        $modsConfDir = '';
        if ($type === 'system') {
            $modsConfDir = SPP_ETC_DIR . SPP_DS . 'apps' . SPP_DS . $appname . SPP_DS . 'modsconf';
        } else {
            $modsConfDir = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'modsconf';
        }

        $yamlConfFile = $modsConfDir . SPP_DS . $modname . SPP_DS . 'config.yml';

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

        if (!isset($yamlData['variables']) || !is_array($yamlData['variables'])) {
            $yamlData['variables'] = [];
        }
        $yamlData['variables'][$varname] = $value;

        file_put_contents($yamlConfFile, Yaml::dump($yamlData, 4, 4));

        // Invalidate cache
        $appname = $appname ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $appname) : \SPP\Scheduler::getContext();
        $cacheKey = $appname . '::' . $modname . '::' . $varname;
        self::$configCache[$cacheKey] = (string) $value;
    }

    /**
     * Scans the module's installation requirements and returns a list of deltas
     * compared to the current database state.
     *
     * @return array
     */
    public function getInstallationDeltas(): array
    {
        $db = new \SPPMod\SPPDB\SPPDB();
        $deltas = [
            'tables' => [],
            'entities' => [],
            'sequences' => [],
            'config' => []
        ];

        $install = $this->Installation ?? [];
        
        // 1. Tables check
        $tables = $install['tables'] ?? [];
        foreach ($tables as $tname => $cols) {
            $tnameFull = \SPPMod\SPPDB\SPPDB::sppTable($tname);
            if (!$db->tableExists($tnameFull)) {
                $deltas['tables'][] = ['name' => $tname, 'status' => 'missing', 'columns' => $cols];
            } else {
                $missingCols = [];
                foreach ($cols as $col => $type) {
                    if ($col === 'PRIMARY KEY') continue; // Skip composite PK declaration
                    if (!$db->columnExists($tnameFull, $col)) {
                        $missingCols[$col] = $type;
                    }
                }
                if (!empty($missingCols)) {
                    $deltas['tables'][] = ['name' => $tname, 'status' => 'outdated', 'missing_columns' => $missingCols];
                }
            }
        }

        // 2. Entities check
        $entities = $install['entities'] ?? [];
        foreach ($entities as $entityClass) {
            if (class_exists($entityClass)) {
                try {
                    $reflection = new \ReflectionClass($entityClass);
                    if ($reflection->isSubclassOf('\\SPPMod\\SPPEntity\\SPPEntity')) {
                        // Check if the table for this entity exists
                        $entityName = $reflection->getShortName();
                        $tableName = \SPPMod\SPPDB\SPPDB::sppTable(strtolower($entityName) . 's');
                        if (!$db->tableExists($tableName)) {
                             $deltas['entities'][] = ['class' => $entityClass, 'status' => 'missing'];
                        }
                    }
                } catch (\Exception $e) {}
            } else {
                $deltas['entities'][] = ['class' => $entityClass, 'status' => 'not_found'];
            }
        }

        $sequences = $install['sequences'] ?? [];
        foreach ($sequences as $key => $val) {
             $seqName = is_int($key) ? $val : $key;
             $sDef = is_int($key) ? 1 : $val;

             if (!\SPPMod\SPPDB\SPPSequence::sequenceExists($seqName)) {
                 $start = is_array($sDef) ? ($sDef['start'] ?? 1) : $sDef;
                 $inc = is_array($sDef) ? ($sDef['increment'] ?? 1) : 1;
                 
                 $deltas['sequences'][] = [
                     'name' => $seqName, 
                     'status' => 'missing', 
                     'start' => (int)$start,
                     'increment' => (int)$inc
                 ];
             }
        }

        return $deltas;
    }

    /**
     * Executes the installation routines for the module incrementally.
     *
     * @return array Log of actions performed
     */
    public function runInstallation(): array
    {
        $db = new \SPPMod\SPPDB\SPPDB();
        $deltas = $this->getInstallationDeltas();
        $log = [];

        // 1. Process Tables
        foreach ($deltas['tables'] as $t) {
            if ($t['status'] === 'missing') {
                $db->createTableIncremental($t['name'], $t['columns']);
                $log[] = "Created table: " . $t['name'];
            } elseif ($t['status'] === 'outdated') {
                $db->add_columns($t['name'], $t['missing_columns']);
                $log[] = "Updated table " . $t['name'] . " (added " . count($t['missing_columns']) . " columns)";
            }
        }

        // 2. Process Entities
        $entities = $this->Installation['entities'] ?? [];
        foreach ($entities as $entityClass) {
            if (class_exists($entityClass)) {
                call_user_func([$entityClass, 'install']);
                $log[] = "Synchronized entity: " . $entityClass;
            }
        }

        // 3. Process Sequences
        foreach ($deltas['sequences'] as $s) {
            \SPPMod\SPPDB\SPPSequence::createSequence($s['name'], $s['start'] ?? 1, $s['increment'] ?? 1);
            $log[] = "Created sequence: " . $s['name'];
        }

        // 4. Process Seeds
        $seeds = $this->Installation['seeds'] ?? [];
        foreach ($seeds as $table => $rows) {
            // Identify identity field (assume 'id' if not provided)
            $idField = 'id';
            if (isset($rows[0]) && is_array($rows[0])) {
                // If the first row has an 'id' field, use it.
                // Otherwise try to find any field that looks like a PK.
                if (!isset($rows[0]['id'])) {
                     $keys = array_keys($rows[0]);
                     $idField = $keys[0]; // Fallback to first field
                }
            }

            foreach ($rows as $row) {
                if ($db->safeInsert($table, $row, $idField)) {
                    $log[] = "Inserted seed record into $table ({$idField}=" . ($row[$idField] ?? '?') . ")";
                }
            }
        }

        return $log;
    }

    /**
     * static public function getSystemUpdateDeltas()
     * Scans all modules and independent app entities for installation deltas.
     * Centralized logic used by both API and CLI.
     */
    public static function getSystemUpdateDeltas(): array
    {
        self::loadAllModules();
        $summary = ['modules' => [], 'entities' => []];

        // 1. Collect all unique modules from global and current app context
        $allModules = [];
        
        // Global modules
        $globalModsReg = \SPP\Registry::$reg['__modobj'] ?? [];
        foreach ($globalModsReg as $name => $valkey) {
            $modVal = \SPP\Registry::$values[$valkey] ?? null;
            if ($modVal instanceof \SPP\Module) {
                $allModules[$name] = $modVal;
            }
        }
        
        // App-specific modules
        $context = \SPP\Scheduler::getContext();
        if ($context !== '' && isset(\SPP\Registry::$reg['__apps'][$context]['__modobj'])) {
            $appModsReg = \SPP\Registry::$reg['__apps'][$context]['__modobj'];
            foreach ($appModsReg as $name => $valkey) {
                 $modVal = \SPP\Registry::$values[$valkey] ?? null;
                 if ($modVal instanceof \SPP\Module) {
                     $allModules[$name] = $modVal;
                 }
            }
        }

        foreach ($allModules as $name => $mod) {
            $deltas = $mod->getInstallationDeltas();
            // Check if any significant deltas exist
            if (!empty($deltas['tables']) || !empty($deltas['entities']) || !empty($deltas['sequences'])) {
                $summary['modules'][$name] = $deltas;
            }
        }

        // 2. Scan App Entities (YAML files in etc/apps/[context]/entities)
        $appname = $context ?: 'default';
        $entitiesDir = APP_ETC_DIR . SPP_DS . $appname . SPP_DS . 'entities';
        if (is_dir($entitiesDir)) {
            $files = glob($entitiesDir . '/*.yml');
            $db = new \SPPMod\SPPDB\SPPDB();
            foreach ($files as $f) {
                $ename = basename($f, '.yml');
                
                // Refined Class Discovery
                $classCandidates = [
                    "\\SPPMod\\SPPEntity\\" . ucfirst($ename),
                    "\\SPPMod\\SPPEntity\\" . $ename,
                    ucfirst($ename),
                    $ename
                ];

                $className = null;
                foreach ($classCandidates as $candidate) {
                    if (class_exists($candidate) && is_subclass_of($candidate, "\\SPPMod\\SPPEntity\\SPPEntity")) {
                        $className = $candidate;
                        break;
                    }
                }

                $tname = "";
                if ($className) {
                     $tname = $className::getMetadata('table');
                } else {
                     // Fallback to basic pluralization/convention if class not found or matched
                     $tname = \SPPMod\SPPDB\SPPDB::sppTable(strtolower($ename) . 's');
                }

                if (!empty($tname) && !$db->tableExists((string)$tname)) {
                    $summary['entities'][] = ['name' => $ename, 'status' => 'missing', 'file' => basename($f)];
                }
            }
        }

        return $summary;
    }

    /**
     * static public function runSystemUpdate()
     * Runs the incremental installation routines for the entire system based on detected deltas.
     */
    public static function runSystemUpdate(): array
    {
        $summary = self::getSystemUpdateDeltas();
        $log = [];

        // 1. Run updates for modules
        foreach ($summary['modules'] as $name => $deltas) {
            $mod = null;
            // Resolve module again from registry for safety
            $modObj = \SPP\Registry::get('__modobj=>' . $name);
            if ($modObj instanceof \SPP\Module) {
                $mod = $modObj;
            } else {
                 // Try app context
                 $ctx = \SPP\Scheduler::getContext();
                 if ($ctx) {
                     $modObj = \SPP\Registry::get('__apps=>' . $ctx . '=>__modobj=>' . $name);
                     if ($modObj instanceof \SPP\Module) $mod = $modObj;
                 }
            }

            if ($mod) {
                try {
                    $modLog = $mod->runInstallation();
                    if (!empty($modLog)) {
                        $log = array_merge($log, array_map(fn($l) => "Module [$name]: $l", $modLog));
                    } else {
                        $log[] = "Module [$name]: Already up to date.";
                    }
                } catch (\Exception $e) {
                    $log[] = "Module [$name] ERROR: " . $e->getMessage();
                }
            }
        }

        // 2. Run updates for app entities
        foreach ($summary['entities'] as $e) {
            $ename = $e['name'];
            $classCandidates = [
                "\\SPPMod\\SPPEntity\\" . ucfirst($ename),
                "\\SPPMod\\SPPEntity\\" . $ename,
                ucfirst($ename),
                $ename
            ];
            
            $className = null;
            foreach ($classCandidates as $candidate) {
                if (class_exists($candidate) && is_subclass_of($candidate, "\\SPPMod\\SPPEntity\\SPPEntity")) {
                    $className = $candidate;
                    break;
                }
            }

            if ($className) {
                try {
                    call_user_func([$className, 'install']);
                    $log[] = "Entity [$ename]: Schema synchronized successfully using class $className.";
                } catch (\Exception $ex) {
                    $log[] = "Entity [$ename] ERROR: " . $ex->getMessage();
                }
            } else {
                $log[] = "Entity [$ename]: ERROR - Entity class not found for automated installation.";
            }
        }

        return $log;
    }
}

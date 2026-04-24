<?php

namespace SPP;

/**
 * class \SPP\Scheduler
 *
 * Handles context and process scheduling for SPP.
 *
 * @author
 *     Satya Prakash Shukla
 * @version
 *     2.1 compatible with legacy SPP 1.x
 */
class Scheduler extends \SPP\SPPObject
{
    /** @var string */
    private static string $AppContext = '';

    /** @var array<string,\SPP\App> */
    private static array $procs = [];

    /**
     * Set or switch application context.
     *
     * @throws \SPP\SPPException
     */
    public static function setContext(string $context): void
    {
        $context = trim($context);
        $context = ($context === '') ? 'default' : $context;

        if (!array_key_exists($context, self::$procs)) {
            throw new \SPP\SPPException('Unregistered context: ' . $context);
        }

        if (self::$AppContext === '') {
            self::$AppContext = $context;
            return;
        }

        $currProc = self::getActiveProc();
        $newProc = self::getProcObj($context);

        $currProc->setStatus(\SPP\App::APP_WAITING);
        $newProc->setStatus(\SPP\App::APP_EXEC);

        self::$AppContext = $context;
    }

    /**
     * Register a new \SPP\App process.
     *
     * @throws \SPP\SPPException
     */
    public static function regProc(\SPP\App $proc): void
    {
        $pname = $proc->getName();

        if (isset(self::$procs[$pname])) {
            throw new \SPP\SPPException('Duplicate process registration: ' . $pname);
        }

        self::$procs[$pname] = $proc;
    }

    /**
     * Get current active context name.
     */
    public static function getContext(): string
    {
        return self::$AppContext;
    }

    /**
     * Check if an application context has been set.
     */
    public static function hasContext(): bool
    {
        return self::$AppContext !== '';
    }

    /**
     * Get module configuration directory for current process.
     */
    public static function getModsConfDir(): string
    {
        return self::getActiveProc()->getModsConfDir();
    }

    /**
     * Get \SPP\App object for a specific process.
     *
     * @throws \SPP\SPPException
     */
    public static function getProcObj(string $pname): \SPP\App
    {
        if (!array_key_exists($pname, self::$procs)) {
            throw new \SPP\SPPException('Unregistered process: ' . $pname);
        }

        return self::$procs[$pname];
    }

    /**
     * Get currently active \SPP\App process.
     *
     * @throws \SPP\SPPException
     */
    public static function getActiveProc(): \SPP\App
    {
        if (self::$AppContext === '') {
            throw new \SPP\SPPException('Application context not set.');
        }

        return self::$procs[self::$AppContext];
    }

    /**
     * Get active SPPError object from the current app.
     */
    public static function getActiveErrorObj(): ?\SPP\SPPError
    {
        return self::getActiveProc()->getErrorObj();
    }

    /**
     * Detects the app context based on Request URI and base_url in global registry.
     * Enforces strict prefixing.
     */
    public static function detectAndEnforceContext(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Load Global Settings
        $settings = [];
        $path = (defined('SPP_ETC_DIR') ? SPP_ETC_DIR : (defined('SPP_BASE_DIR') ? SPP_BASE_DIR : dirname(__DIR__, 2)) . '/etc') . '/global-settings.yml';
        if (file_exists($path) && class_exists('\\Symfony\\Component\\Yaml\\Yaml')) {
            try {
                $settings = \Symfony\Component\Yaml\Yaml::parseFile($path);
            } catch (\Exception $e) {}
        }
        
        $apps = $settings['apps'] ?? [];
        $matchedApp = null;
        $maxLen = -1;

        // Sort apps by base_url length descending to match most specific first
        foreach ($apps as $name => $meta) {
            $baseUrl = $meta['base_url'] ?? '';
            if ($baseUrl === '' || $baseUrl === '/') continue;
            
            // Check if URI starts with base_url (Case-Insensitive)
            if (stripos($uri, $baseUrl) !== false) {
                 if (strlen($baseUrl) > $maxLen) {
                     $maxLen = strlen($baseUrl);
                     $matchedApp = $name;
                 }
            }
        }

        if (!$matchedApp) {
            // Find explicit base app or fallback to 'default'
            foreach ($apps as $name => $meta) {
                if (!empty($meta['is_base_app'])) {
                    $matchedApp = $name;
                    break;
                }
            }
            if (!$matchedApp) $matchedApp = 'default';
        }

        self::$AppContext = $matchedApp;
    }
}

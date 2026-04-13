<?php

namespace SPP;

/*
 * file: class.sppapp.php
 * Defines \SPP\App class.
 */

/**
 * class \SPP\App
 * Defines application in SPP.
 *
 * Backward compatible version — modernized for PHP 8.2+.
 *
 * @author Satya Prakash Shukla
 */
class App extends \SPP\SPPObject
{
    private string $appname = '';
    private bool $modsloaded = false;
    private ?SPPError $errobj = null;
    private int $app_status = self::APP_EXEC;

    public const APP_EXEC    = 1;
    public const APP_WAITING = 2;
    public const APP_STOPPED = 3;
    public const APP_ERROR   = 4;

    // Directories
    private string $data_dir = '';
    private string $log_dir = '';
    private string $cache_dir = '';
    private string $tmp_dir = '';
    private string $conf_dir = '';
    private string $mod_dir = '';

    /** @var array<string, \SPP\App> */
    private static array $instances = [];

    /**
     * Constructor.
     *
     * @param string  $appname      Application name
     * @param bool    $handleerror  Whether to enable error handling
     * @param integer $init_level   Init Level (1–4)
     * @throws \SPP\SPPException
     */
    public function __construct(string $appname = '', bool $handleerror = true, int $init_level = 4)
    {
        if ($appname === '') {
            $appname = 'default';
        }

        // Mitigate path traversal and enforce valid structure
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $appname)) {
            throw new \SPP\SPPException("Invalid application name. Only alphanumeric characters are allowed.");
        }

        $this->appname = $appname;
        $this->initializeDirs($appname);

        if (\SPP\Registry::isRegistered('__apps=>' . $appname . '=>status')) {
            throw new \SPP\SPPException("Application '{$appname}' already exists.");
        }

        self::$instances[$appname] = $this;
        \SPP\Registry::register('__apps=>' . $appname . '=>status', self::APP_EXEC);

        // Controlled staged initialization
        if ($init_level >= 1) {
            \SPP\Scheduler::regProc($this);
            \SPP\Scheduler::setContext($appname);
        }
        if ($init_level >= 2) {
            $this->loadModules();
        }
        if ($init_level >= 3) {
            if (!SPPSession::sessionExists()) {
                $ssn = new SPPSession();
                $_SESSION['__' . $appname . '_sppsession'] = serialize($ssn);
            }
        }
        if ($init_level >= 4) {
            $this->errobj = new SPPError($handleerror);
        }

        \SPP\SPPEvent::registerDirs();
        \SPP\SPPEvent::scanHandlers();
    }

    /**
     * Initialize standard application directories.
     */
    private function initializeDirs(string $appname): void
    {
        $this->data_dir  = $this->makeAppPath($appname, 'data');
        $this->log_dir   = $this->makeAppPath($appname, 'logs');
        $this->cache_dir = $this->makeAppPath($appname, 'cache');
        $this->tmp_dir   = $this->makeAppPath($appname, 'tmp');
        $this->conf_dir  = $this->makeAppPath($appname, 'conf');
        $this->mod_dir   = $this->makeAppPath($appname, 'mods');
    }

    /**
     * Helper to build application directory paths.
     */
    private function makeAppPath(string $appname, string $subdir): string
    {
        return APP_ETC_DIR . SPP_DS . $appname . SPP_DS . $subdir . SPP_DS;
    }

    /**
     * Returns a new App instance for the current context.
     */
    public static function getApp(): \SPP\App
    {
        $context = \SPP\Scheduler::getContext();
        if (isset(self::$instances[$context])) {
            return self::$instances[$context];
        }
        return new \SPP\App($context);
    }

    /**
     * Returns the currently active application name.
     */
    public static function getActiveApp(): string
    {
        return \SPP\Scheduler::getContext();
    }

    public function isModsLoaded(): bool
    {
        return $this->modsloaded;
    }

    /**
     * Sets the application status.
     *
     * @throws \SPP\SPPException
     */
    public function setStatus(int $status): void
    {
        if (in_array($status, [self::APP_EXEC, self::APP_STOPPED, self::APP_WAITING, self::APP_ERROR], true)) {
            $this->app_status = $status;
        } else {
            throw new \SPP\SPPException('Invalid application status.');
        }
    }

    public function getStatus(): int
    {
        return $this->app_status;
    }

    // === Directory getters (backward compatible) ===
    public function getLogDir(): string
    {
        return $this->log_dir;
    }
    public function getCacheDir(): string
    {
        return $this->cache_dir;
    }
    public function getTmpDir(): string
    {
        return $this->tmp_dir;
    }
    public function getConfDir(): string
    {
        return $this->conf_dir;
    }
    public function getModDir(): string
    {
        return $this->mod_dir;
    }
    public function getDataDir(): string
    {
        return $this->data_dir;
    }

    /**
     * Gets the error object for this application.
     */
    public function getErrorObj(): ?SPPError
    {
        return $this->errobj;
    }

    /**
     * Returns the name of this application.
     */
    public function getName(): string
    {
        return $this->appname;
    }

    public function getModsConfDir(): string
    {
        return APP_ETC_DIR . SPP_DS . $this->appname . SPP_DS . 'modsconf';
    }

    public function getAppConfDir(): string
    {
        return APP_ETC_DIR . SPP_DS . $this->appname;
    }

    // === Session management ===

    public static function initSession(): void
    {
        $ssname = self::getSessionName();
        if (!SPPSession::sessionExists()) {
            $ssn = new SPPSession();
            $_SESSION[$ssname] = serialize($ssn);
        }
    }

    public static function killSession(): void
    {
        \SPP\SPPEvent::startEvent('ev_kill_session');
        $ssname = self::getSessionName();
        if (SPPSession::sessionExists()) {
            unset($_SESSION[$ssname]);
        }
        \SPP\SPPEvent::endEvent('ev_kill_session');
    }

    public static function getSessionName(): string
    {
        $context = \SPP\Scheduler::getContext();
        return '__' . $context . '_sppsession';
    }

    /**
     * Loads all active modules for the current application.
     */
    public function loadModules(): void
    {
        if (!$this->modsloaded) {
            $oldcontext = \SPP\Scheduler::getContext();
            \SPP\Scheduler::setContext($this->appname);
            \SPP\Module::loadAllModules();
            \SPP\Scheduler::setContext($oldcontext);
            $this->modsloaded = true;
        }
    }
}

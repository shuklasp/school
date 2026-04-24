<?php
namespace SPP;

use Symfony\Component\Yaml\Yaml;

class App extends \SPP\SPPObject
{
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
    protected array $_getprops = ['type', 'appname'];
    protected \SPP\Core\Container $container;

    /** @var array<string, \SPP\App> */
    private static array $instances = [];

    public function __construct(string $appname = '', bool $handleerror = true, int $init_level = 4)
    {
        if ($appname === '') {
            $appname = 'default';
        }

        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $appname)) {
            throw new \SPP\SPPException("Invalid application name.");
        }

        $this->_attributes['appname'] = $appname;
        $this->container = new \SPP\Core\Container();

        $settings = self::getGlobalSettings();
        $this->_attributes['type'] = $settings['apps'][$appname]['type'] ?? 'native';
        
        if ($init_level >= 4) {
            $this->errobj = new SPPError($handleerror);
        }

        $this->initializeDirs($appname);

        if (\SPP\Registry::isRegistered('__apps=>' . $appname . '=>status')) {
            throw new \SPP\SPPException("Application '{$appname}' already exists.");
        }

        self::$instances[$appname] = $this;
        \SPP\Registry::register('__apps=>' . $appname . '=>status', self::APP_EXEC);

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

        \SPP\SPPEvent::registerDirs();
        \SPP\SPPEvent::scanHandlers();
    }

    private function initializeDirs(string $appname): void
    {
        $this->data_dir  = $this->makeAppPath($appname, 'data');
        $this->log_dir   = $this->makeAppPath($appname, 'logs');
        $this->cache_dir = $this->makeAppPath($appname, 'cache');
        $this->tmp_dir   = $this->makeAppPath($appname, 'tmp');
        $this->conf_dir  = $this->makeAppPath($appname, 'conf');
        $this->mod_dir   = $this->makeAppPath($appname, 'mods');
    }

    private function makeAppPath(string $appname, string $subdir): string
    {
        if (str_starts_with($appname, '__')) return '';
        return APP_ETC_DIR . SPP_DS . $appname . SPP_DS . $subdir . SPP_DS;
    }

    public static function getApp(string $appname = ''): \SPP\App
    {
        $context = ($appname === '') ? \SPP\Scheduler::getContext() : $appname;
        if (isset(self::$instances[$context])) {
            return self::$instances[$context];
        }
        return new \SPP\App($context);
    }

    public static function getGlobalSettings(): array
    {
        $path = SPP_ETC_DIR . '/global-settings.yml';
        if (file_exists($path)) {
            return Yaml::parseFile($path);
        }
        return [];
    }

    public static function getActiveApp(): string
    {
        return \SPP\Scheduler::getContext();
    }

    public function isModsLoaded(): bool
    {
        return $this->modsloaded;
    }

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

    public function getLogDir(): string { return $this->log_dir; }
    public function getCacheDir(): string { return $this->cache_dir; }
    public function getTmpDir(): string { return $this->tmp_dir; }
    public function getConfDir(): string { return $this->conf_dir; }
    public function getModDir(): string { return $this->mod_dir; }
    public function getDataDir(): string { return $this->data_dir; }

    public function getErrorObj(): ?SPPError
    {
        return $this->errobj;
    }

    public function getName(): string
    {
        return $this->_attributes['appname'];
    }

    public function getModsConfDir(): string
    {
        return APP_ETC_DIR . SPP_DS . $this->_attributes['appname'] . SPP_DS . 'modsconf';
    }

    public function getAppConfDir(): string
    {
        return APP_ETC_DIR . SPP_DS . $this->_attributes['appname'];
    }

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
        $ssname = self::getSessionName();
        if (SPPSession::sessionExists()) unset($_SESSION[$ssname]);
    }

    public static function getSessionName(): string
    {
        $context = \SPP\Scheduler::getContext();
        return '__' . $context . '_sppsession';
    }

    public function loadModules(): void
    {
        if (!$this->modsloaded) {
            $oldcontext = \SPP\Scheduler::getContext();
            \SPP\Scheduler::setContext($this->_attributes['appname']);
            \SPP\Module::loadAllModules();
            \SPP\Scheduler::setContext($oldcontext);
            $this->modsloaded = true;
        }
    }

    public function make(string $abstract, array $parameters = [])
    {
        return $this->container->make($abstract, $parameters);
    }

    public function bind(string $abstract, $concrete = null, bool $shared = false)
    {
        $this->container->bind($abstract, $concrete, $shared);
    }

    public function singleton(string $abstract, $concrete = null)
    {
        $this->container->singleton($abstract, $concrete);
    }
}

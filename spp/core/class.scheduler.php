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
}

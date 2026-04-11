<?php

namespace SPP;

/*
 * file: class.sppscheduler.php
 * Defines the class \SPP\Scheduler
 */

/**
 * class \SPP\Scheduler
 * Handles process context switching and registration for SPP applications.
 *
 * Fully backward-compatible modernization.
 *
 * @author Satya Prakash Shukla
 */
class Scheduler extends \SPP\SPPObject
{
    private static string $AppContext = '';
    /** @var array<string, \SPP\App> */
    private static array $procs = [];

    public const APP_CREATED = 1;
    public const APP_EXEC    = 2;
    public const APP_WAITING = 3;

    /**
     * Sets the current application context.
     *
     * @param string $context Name of application context.
     * @throws \SPP\SPPException If the context is unregistered.
     */
    public static function setContext(string $context): void
    {
        if ($context === '') {
            $context = 'default';
        }

        if (!array_key_exists($context, self::$procs)) {
            throw new \SPP\SPPException('Unregistered context: ' . $context);
        }

        // If no current context is set — assign directly
        if (self::$AppContext === '') {
            self::$AppContext = $context;
            return;
        }

        // Handle context switching
        $curr_proc = self::getActiveProc();
        $new_proc  = self::getProcObj($context);

        $curr_proc->setStatus(\SPP\App::APP_WAITING);
        $new_proc->setStatus(\SPP\App::APP_EXEC);

        self::$AppContext = $context;
    }

    /**
     * Registers a new application process.
     *
     * @param \SPP\App $proc The SPP\App object to register.
     * @throws \SPP\SPPException On duplicate or invalid registration.
     */
    public static function regProc(\SPP\App $proc): void
    {
        $pname = $proc->getName();

        if (array_key_exists($pname, self::$procs)) {
            throw new \SPP\SPPException('Duplicate process registration: ' . $pname);
        }

        if (!is_a($proc, '\SPP\App')) {
            throw new \SPP\SPPException('Invalid process registration: ' . $pname);
        }

        self::$procs[$pname] = $proc;
    }

    /**
     * Returns the currently active application context name.
     *
     * @return string Context name.
     */
    public static function getContext(): string
    {
        return self::$AppContext;
    }

    /**
     * Returns the active application's mods configuration directory.
     *
     * @return string
     */
    public static function getModsConfDir(): string
    {
        return self::getActiveProc()->getModsConfDir();
    }

    /**
     * Returns the \SPP\App object for a given process name.
     *
     * @param string $pname Process name.
     * @return \SPP\App
     * @throws \SPP\SPPException If process not registered.
     */
    public static function getProcObj(string $pname): \SPP\App
    {
        if (!array_key_exists($pname, self::$procs)) {
            throw new \SPP\SPPException('Unregistered process: ' . $pname);
        }

        return self::$procs[$pname];
    }

    /**
     * Returns the \SPP\App object for the active context.
     *
     * @return \SPP\App
     * @throws \SPP\SPPException If context is not set.
     */
    public static function getActiveProc(): \SPP\App
    {
        if (self::$AppContext === '') {
            throw new \SPP\SPPException('Application context not set.');
        }

        return self::$procs[self::$AppContext];
    }

    /**
     * Returns the SPPError object for the active application.
     *
     * @return \SPP\SPPError
     * @throws \SPP\SPPException If context is not set.
     */
    public static function getActiveErrorObj(): \SPP\SPPError
    {
        return self::getActiveProc()->getErrorObj();
    }
}

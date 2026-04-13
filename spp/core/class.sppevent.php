<?php
namespace SPP;

/**
 * class \SPP\SPPEvent
 * Implements event system in Satya Portal Pack.
 *
 * @author Satya Prakash Shukla
 * 
 */
class SPPEvent extends \SPP\SPPObject
{
    private static array $events = [];
    private static array $activeHandlers = [];
    private static array $scannedDirs = [];
    private static bool $dirsRegistered = false;

    /**
     * Constructor
     * Declared private to prevent creation of an object.
     */
    private function __construct()
    {
        // parent::__construct();
    }

    /**
     * function registerEvent()
     * Registers an event.
     * 
     * @param string $event_name Name of event.
     */
    public static function registerEvent(string $event_name, ?string $default_handler = null)
    {
        if (!array_key_exists($event_name, self::$events)) {

            self::$events[$event_name] = array(
                'defaulthandler' => $default_handler,
                'handlers' => array(),
                'overriders' => false
            );
        } else {
            throw new \SPP\SPPException('Event "' . $event_name . '" already registered!');
        }
    }

    /**
     * function getEvents()
     * Returns all registered events.
     * 
     * @return array All registered events.
     */
    public static function getEvents()
    {
        return self::$events;
    }

    public static function markOverrider($event_name)
    {
        if (isset(self::$events[$event_name])) {
            self::$events[$event_name]['overriders'] = true;
        }
    }

    public static function getDefaultHandler($event_name)
    {
        $events = self::getEvents();

        if (array_key_exists($event_name, $events)) {
            return $events[$event_name]['defaulthandler'];
        } else {
            throw new \SPP\SPPException('Event "' . $event_name . '" not registered!');
        }
    }

    /**
     * function hasDefaultHandler()
     * Checks if a default handler is registered for an event.
     * 
     * @param string $event_name Name of event.
     */
    public static function hasDefaultHandler($event_name)
    {
        return (self::getDefaultHandler($event_name) !== null);
    }

    /**
     * function registerEvents()
     * Registers multiple events.
     * 
     * @param array $events Array of event names.
     */
    public static function registerEvents(array $events)
    {
        foreach ($events as $event) {
            self::registerEvent($event);
        }
    }

    /**
     * function registerHandler()
     * Registers a handler for an event.
     * 
     * @param string $event_name Name of event.
     * @param string $handler_name Name of handler function.
     * @param bool $default Default handler.
     */
    public static function registerHandler(string $event_name, string $handler_name, bool $default = false)
    {
        if (!array_key_exists($event_name, self::$events)) {
            if ($default) {
                self::registerEvent($event_name, $handler_name);
                return true;
            } else {
                self::registerEvent($event_name);
            }
        }

        if (
            !is_subclass_of('EventHandlers\\' . $handler_name, '\\SPP\\EventHandler') &&
            !is_subclass_of('EventHandlers\\Defaults\\' . $handler_name, '\\SPP\\EventHandler')
        ) {
            throw new \SPP\SPPException(
                'Invalid handler "' . $handler_name . '" for event "' . $event_name . '"!'
            );
        }

        if ($default) {
            self::$events[$event_name]['defaulthandler'] = $handler_name;
        } else {
            if (!in_array($handler_name, self::$events[$event_name]['handlers'], true)) {
                self::$events[$event_name]['handlers'][] = $handler_name;
            }
        }

        return true;
    }

    /**
     * function registerHandlers()
     * Registers multiple handlers for an event.
     * 
     * @param array $handlers Array of handler names.
     * @param bool $default Default handler.
     */
    public static function registerHandlers(string $event_name, array $handlers, bool $default = false)
    {
        foreach ($handlers as $handler) {
            self::registerHandler($event_name, $handler, $default);
        }
    }

    /****
     * function startEvent()
     * Starts an event.
     * 
     * @param string $event_name Name of event.
     */
    public static function startEvent(string $event_name, array &$params = array())
    {
        if (!array_key_exists($event_name, self::$events)) {
            return;
        }

        foreach (self::$events[$event_name]['handlers'] as $handler) {
            self::callHandler($handler, 'before', $params);
        }
    }

    /**
     * function endEvent()
     * Ends an event.
     * 
     * @param string $event_name Name of event.
     */
    public static function endEvent($event_name, &$params = array())
    {
        if (!array_key_exists($event_name, self::$events)) {
            return;
        }

        foreach (self::$events[$event_name]['handlers'] as $handler) {
            self::callHandler($handler, 'after', $params);
        }
    }

    /**
     * function overrideEvent()
     * Overrides a fireable event.
     * 
     * @param mixed $event_name Name of event.
     * @param array $params Array of parameters.
     * @return $name
     */
    public static function overrideEvent($event_name, &$params = array())
    {
        if (!array_key_exists($event_name, self::$events)) {
            return;
        }

        foreach (self::$events[$event_name]['handlers'] as $handler) {
            if (method_exists('EventHandlers\\' . $handler, 'overrideHandler')) {
                self::callHandler($handler, 'override', $params);
                self::$events[$event_name]['overriders'] = true;
            }
        }
    }

    /**
     * function hasOverrider()
     * Checks if an event has an overrider.
     * 
     * @param string $handler_name Name of handler.
     * @return bool
     */
    public static function hasOverrider($handler_name)
    {
        return method_exists('EventHandlers\\' . $handler_name, 'overrideHandler');
    }

    /**
     * function fireEvent()
     * Fires an overridable event.
     * 
     * @param string $event_name Name of event.
     * @param array $params Array of parameters.
     * @return void
     * @throws \SPP\SPPException
     */
    public static function fireEvent($event_name, array &$params = array(), mixed $inline_handler = null)
    {
        if (!array_key_exists($event_name, self::$events)) {
            return;
        }

        $overridden = false;

        foreach (self::$events[$event_name]['handlers'] as $handler) {
            self::callHandler($handler, 'before', $params);
        }

        foreach (self::$events[$event_name]['handlers'] as $handler) {
            if (self::hasOverrider($handler)) {
                self::callHandler($handler, 'override', $params);
                $overridden = true;
            }
        }

        if (!$overridden) {
            if ($inline_handler !== null && (is_object($inline_handler) || is_array($inline_handler)) && is_callable($inline_handler)) {
                $inline_handler($params);
            } else {
                $default = self::$events[$event_name]['defaulthandler'];

                if ($default !== null) {
                    self::callHandler($default, 'default', $params);
                } else {
                    throw new \SPP\SPPException(
                        'Event "' . $event_name . '" is not overridable!'
                    );
                }
            }
        }

        foreach (self::$events[$event_name]['handlers'] as $handler) {
            self::callHandler($handler, 'after', $params);
        }
    }

    /**
     * function callHandler()
     * Calls a handler.
     * 
     * @param string $handler_name Name of handler.
     * @param string $occurence Occurence of handler.
     * @param array &$params Parameter payload dynamically bound by reference.
     * @return void
     */
    private static function callHandler($handler_name, $occurence, array &$params = array())
    {
        if ($occurence === 'inline' && (is_object($handler_name) || is_array($handler_name)) && is_callable($handler_name)) {
            $handler_name($params);
            return;
        }

        switch ($occurence) {
            case 'default':
                $class = '\\EventHandlers\\Defaults\\' . $handler_name;
                break;
            default:
                $class = '\\EventHandlers\\' . $handler_name;
        }

        if (!class_exists($class)) {
            return;
        }

        // Singleton Flyweight cache array instantiation
        if (!isset(self::$activeHandlers[$class])) {
            $instanceStore = new $class();
            if (!$instanceStore instanceof \SPP\EventHandler) {
                return;
            }
            self::$activeHandlers[$class] = $instanceStore;
        }
        $instance = self::$activeHandlers[$class];

        if ($occurence === 'before' && method_exists($instance, 'beforeHandler')) {
            $instance->beforeHandler($params);
        } elseif ($occurence === 'override' && method_exists($instance, 'overrideHandler')) {
            $instance->overrideHandler($params);
        } elseif ($occurence === 'after' && method_exists($instance, 'afterHandler')) {
            $instance->afterHandler($params);
        } elseif ($occurence === 'default' && method_exists($instance, 'overrideHandler')) {
            $instance->overrideHandler($params);
        }
    }

    /**
     * function scanHandlers()
     * Scans the event handlers.
     * 
     * @return void
     */
    public static function scanHandlers()
    {
        $dirs = \SPP\Registry::getDirs('events');

        if (!is_array($dirs)) {
            return;
        }

        foreach ($dirs as $dir) {
            if (isset(self::$scannedDirs[$dir])) {
                continue;
            }

            if (!is_dir($dir)) {
                continue;
            }
            
            self::$scannedDirs[$dir] = true;

            $files = scandir($dir);

            foreach ($files as $file) {

                if ($file === '.' || $file === '..') {
                    continue;
                }

                $path = $dir . SPP_DS . $file;

                if (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {

                    require_once $path;

                    $class = pathinfo($file, PATHINFO_FILENAME);

                    if (class_exists('EventHandlers\\' . $class)) {
                        self::registerHandler($class, $class);
                    }

                    if (class_exists('EventHandlers\\Defaults\\' . $class)) {
                        self::registerHandler($class, $class, true);
                    }
                }
            }
        }
    }

    /**
     * function registerDirs()
     * Registers directories for events.
     * 
     * @return void
     */
    public static function registerDirs()
    {
        if (self::$dirsRegistered) {
            return;
        }
        self::scanAndRegisterDirs(SPP_BASE_DIR . SPP_DS . 'events');
        self::scanAndRegisterDirs(SPP_APP_DIR . SPP_DS . 'events');
        self::$dirsRegistered = true;
    }

    /**
     * function scanAndRegisterDirs()
     * Recursively scans and registers directories for events.
     * 
     * @param string $dir Directory to scan.
     * @param bool $top_dir Top level directory.
     * @return void
     */
    public static function scanAndRegisterDirs($dir, $top_dir = true)
    {
        if (!is_dir($dir)) {
            return;
        }

        if ($top_dir) {
            \SPP\Registry::registerDir('events', $dir);
        }

        foreach (scandir($dir) as $file) {

            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . SPP_DS . $file;

            if (is_dir($path)) {
                if (is_link($path)) {
                    continue;
                }
                \SPP\Registry::registerDir('events', $path);
                self::scanAndRegisterDirs($path, false);
            }
        }
    }
}

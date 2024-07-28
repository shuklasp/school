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
     public static function registerEvent(string $event_name, string $default_handler=null)
     {
        $events=\SPP\Registry::get('__events');
        if($events===false)
        {
            $events=array();
        }
        if(!array_key_exists($event_name, $events) || !is_array($events[$event_name]))
        {
            //echo 'Registering event '.$event_name;

            $events[$event_name]=array('defaulthandler'=>$default_handler,'handlers'=>array(), 'overriders'=>false, 'params'=>array());
            //var_dump($events[$event_name]);
        }
        else
        {
            throw new \SPP\SPPException('Event "'.$event_name.'" already registered!');
        }
        \SPP\Registry::register('__events',$events);
     }

     /**
      * function getEvents()
      * Returns all registered events.
      * 
      * @return array All registered events.
      */
     public static function getEvents()
     {
        return \SPP\Registry::get('__events');
     }

     public static function getDefaultHandler($event_name)
     {
        $events= self::getEvents();
        if(array_key_exists($event_name, $events))
        {
            return $events[$event_name]['defaulthandler'];
        }
        else
        {
            throw new \SPP\SPPException('Event "'.$event_name.'" not registered!');
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
        $default_handler=self::getDefaultHandler($event_name);
        if($default_handler!==null)
        {
            return true;
        }
        else
        {
            return false;
        }
     }



     /**
      * function registerEvents()
      * Registers multiple events.
      * 
      * @param array $events Array of event names.
      */
     public static function registerEvents(array $events)
     {
        foreach($events as $event)
        {
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
     public static function registerHandler(string $event_name, string $handler_name, bool $default=false)
     {
        //echo 'Registering event '.$event_name.' handler '.$handler_name.' '.$default;
        $events=\SPP\Registry::get('__events');
        if($events===false || !array_key_exists($event_name, $events))
        {
            if($default)
            {
                self::registerEvent($event_name, $handler_name);
                return true;
            }
            else
            {
                self::registerEvent($event_name);
                $events = \SPP\Registry::get('__events');
            }
        }
        if (!is_subclass_of('EventHandlers\\' . $handler_name, '\\SPP\\EventHandler') && !is_subclass_of('EventHandlers\\Defaults\\' . $handler_name, '\\SPP\\EventHandler'))
        {
            throw new \SPP\SPPException('Invalid handler "'.$handler_name.'" for event "'.$event_name.'"!');
        }
        if ($default) {
                $events[$event_name]['defaulthandler'] = $handler_name;
            } else {
            if(array_key_exists($event_name, $events))
            {
                $handlers = $events[$event_name]['handlers'];
                if(!in_array($handler_name, $handlers))
                {
                    $handlers[] = $handler_name;
                }
                $events[$event_name]['handlers'] = $handlers;
            }
            else
            {
                $handlers = array($handler_name);
                $events[$event_name]['handlers'] = $handlers;
            }

        }
        \SPP\Registry::register('__events',$events);
        return true;
     }

     /**
      * function registerHandlers()
      * Registers multiple handlers for an event.
      * 
      * @param array $handlers Array of handler names.
      * @param bool $default Default handler.
      */
     public static function registerHandlers(array $handlers, bool $default=false)
     {
        foreach($handlers as $handler)
        {
            self::registerHandler($handler, $default);
        }
     }

     /**
      * function getParams()
      * Returns parameters for an event.
      * 
      * @param string $event_name Name of event.
      */
     public static function getParams(string $event_name)
     {
        $events=\SPP\Registry::get('__events');
        $event=$events[$event_name];
        return $event['params'];
     }

     /**
      * function setParams()
      * Sets parameters for an event.
      *
      * @param mixed $event_name Name of event.
      * @param mixed $params Array of parameters.
      */
     public static function setParams(string $event_name, array $params)
     {
        $events = \SPP\Registry::get('__events');
        $event = $events[$event_name];
        $event['params']=$params;
        $events[$event_name] = $event;
        \SPP\Registry::register('__events', $events);
        return true;
     }


     /****
      * function startEvent()
      * Starts an event.
      * 
      * @param string $event_name Name of event.
      */
     public static function startEvent(string $event_name, array &$params=array())
     {
        $events=\SPP\Registry::get('__events');
        //var_dump($events);
        if(array_key_exists($event_name, $events))
        {
            self::setParams($event_name, $params);
            if (!empty($events[$event_name]['handlers'])) {
                $handlers=$events[$event_name]['handlers'];
                $events[$event_name]['params'] = $params;
                foreach($handlers as $hnd)
                {
                    self::callHandler($hnd,'before');
                }
                $params=self::getParams($event_name);
            }
        }
     }


     /**
      * function endEvent()
      * Ends an event.
      * 
      * @param string $event_name Name of event.
      */
     public static function endEvent($event_name, &$params=array())
     {
        $events=\SPP\Registry::get('__events');
        if (array_key_exists($event_name, $events)) {
            self::setParams($event_name, $params);
            if (!empty($events[$event_name]['handlers'])) {
                $handlers = $events[$event_name]['handlers'];
                $events[$event_name]['params'] = $params;
                foreach ($handlers as $hnd) {
                    self::callHandler($hnd, 'after');
                }
                $params = self::getParams($event_name);
                // foreach ($events[$event_name]['params'] as $key => $value) {
                //     $params[$key] = $value;
                // }
            }
        }
        // else
        // {
        //     throw new \SPP\SPPException('Cannot end event "'.$event_name.'". Event not registered!');
        // }
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
        $events = \SPP\Registry::get('__events');
        self::setParams($event_name, $params);
        if (array_key_exists($event_name, $events)) {
            if (!empty($events[$event_name]['handlers'])) {
                $handlers = $events[$event_name]['handlers'];
                $events[$event_name]['params'] = $params;
                foreach ($handlers as $hnd) {
                    self::callHandler($hnd, 'override');
                }
                $params = self::getParams($event_name);
                // foreach ($events[$event_name]['params'] as $key => $value) {
                //     $params[$key] = $value;
            }
        }
        // else {
        //     throw new \SPP\SPPException('Cannot start event "' . $event_name . '". Event not registered!');
        // }
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
        if(class_exists('\\EventHandlers\\'.$handler_name))
        {
            $event_name=$handler_name;
            $events=\SPP\Registry::get('__events');
            $has_override=false;
            $has_override=$events[$event_name]['overriders'];
            // var_dump($has_override);
            // echo ':'.$event_name.'<br/>';
            if($has_override)
            {
                return true;
            }
        }
        return false;
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
    public static function fireEvent($event_name, array &$params=array(), mixed $inline_handler=null)
    {
        $events = \SPP\Registry::get('__events');
        $overridden=false;
        if (array_key_exists($event_name, $events)) {
            self::setParams($event_name, $params);
            if (!empty($events[$event_name]['handlers'])) {
                $handlers = $events[$event_name]['handlers'];
                $events[$event_name]['params'] = $params;
                // var_dump($handlers);
                // var_dump($events[$event_name]['params']);
                foreach ($handlers as $hnd) {
                    //var_dump($hnd);
                    self::callHandler($hnd, 'before');
                }
                foreach ($handlers as $hnd) {
                    if (self::hasOverrider($hnd)) {
                        self::callHandler($hnd, 'override');

                        $overridden=true;
                    }
                }
                if(!$overridden)
                {
                    if(!is_null($inline_handler))
                    {
                        $inline_handler();
                    }
                    else{
                        $default_handler = self::getDefaultHandler($event_name);
                        if ($default_handler !== null) {
                        //if(self::hasDefaultHandler($event_name)){
                            self::callHandler($default_handler, 'default');
                        } else {
                            throw new \SPP\SPPException('Event "' . $event_name . '" is not overridable!');
                        }
                    }
                }
                foreach ($handlers as $hnd) {
                    self::callHandler($hnd, 'after');
                }
                $params = self::getParams($event_name);

                // foreach ($events[$event_name]['params'] as $key => $value) {
                //     $params[$key] = $value;
                // }
            }
        }
        //else {
        //     throw new \SPP\SPPException('Cannot fire event "' . $event_name . '". Event not registered!');
        // }
    }


    /**
     * function callHandler()
     * Calls a handler.
     * 
     * @param string $handler_name Name of handler.
     * @param string $occurence Occurence of handler.
     * @return void
     * @throws \SPP\SPPException
     */
    private static function callHandler($handler_name, $occurence)
    {
        //echo $handler_name. ' : '.$occurence.'<br/>';

        $hnd=null;
        $hnd_str=null;
        try{
            if($occurence=='inline')
            {
                if(is_callable($handler_name))
                {
                    $handler_name();
                }
                else
                {
                    throw new \SPP\SPPException('Invalid handler "'.$handler_name.' called!');

                }
            }
            if($occurence=='default')
            {
                $hnd_str = '\\EventHandlers\\Defaults\\'.$handler_name;
                $hnd = new $hnd_str();
                $hnd->overrideHandler();
            }
            else if ($occurence == 'override') {
                //var_dump($handler_name);
                //var_dump($occurence);
                $hnd_str = '\\EventHandlers\\' . $handler_name;
                $hnd = new $hnd_str();
                $hnd->overrideHandler();
            }
            else if($occurence=='before'){ 
                $hnd_str = '\\EventHandlers\\'.$handler_name;
                $hnd = new $hnd_str();
                $hnd->beforeHandler();
            } else if ($occurence == 'after') {
                $hnd_str = '\\EventHandlers\\' . $handler_name;
                $hnd = new $hnd_str();
                $hnd->afterHandler();
            }
        } catch (\SPP\SPPException $e) {
            throw new \SPP\SPPException('Invalid handler "'.$handler_name.' called!');
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
        $dirs=array(SPP_DS.'events', SPP_MODULES_DIR.'events', SPP_MODULES_DIR.'eventHandlers');
        $dirs = \SPP\Registry::getDirs('events');
        foreach ($dirs as $dir) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    if (!is_dir($file)) {
                        $fl = explode('.', $file);
                        $fle = array_pop($fl);
                        if ($fle == 'php') {
                            require_once($dir . SPP_DS . $file);
                            $fl = array_pop($fl);
                            //echo $fl;
                            if (class_exists('EventHandlers\\' . $fl)) {
                                \SPP\SPPEvent::registerHandler($fl, $fl);
                            }
                            if (class_exists('EventHandlers\\Defaults\\' . $fl)) {
                                \SPP\SPPEvent::registerHandler($fl, $fl, true);
                            }
                        }
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
        \SPP\SPPEvent::scanAndRegisterDirs(SPP_BASE_DIR . SPP_DS . 'events');
        \SPP\SPPEvent::scanAndRegisterDirs(SPP_APP_DIR . SPP_DS . 'events');
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
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $file = $dir . SPP_DS . $file;
                if (is_dir($file)) {
                    \SPP\Registry::registerDir('events', $file);
                    self::scanAndRegisterDirs($file, false);
                } else {
                    return;
                }
            }
        }
    }


}
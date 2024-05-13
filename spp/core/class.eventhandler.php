<?php
namespace SPP;

abstract class EventHandler
{
    public static $event_name;

    public static function beforeHandler($event, $handler){}
    public static function afterHandler($event, $handler){}
    public function __call($name, $arguments)
    {
        $event = $arguments[0];
        $handler = $arguments[1];
    }

    public static function getEventName()
    {
        if (static::$event_name == null)
            static::$event_name = get_called_class();

        return static::$event_name;
    }

    public static function getHandlerName()
    {
        return get_called_class();
    }
}
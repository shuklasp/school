<?php

namespace SPP;

abstract class ExternalHandler
{
    protected string $event_name;
    protected string $handler_name;
    protected $before_handlers = array();
    protected $after_handlers = array();

    /**
     * function __construct
     * Constructor
     * 
     * @param string $event_name
     * @param string $handler_name
     */
    public function __construct($event_name, $handler_name = null)
    {
        $this->handler_name = $this->getHandlerName();
        $this->event_name = $event_name;
        $this->initHandler();
    }

    public function __destruct()
    {
        //echo 'Destructor called for ' . get_called_class() . '<br/>';
    }

    /** 
     * function beforeHandler
     * Calls before handler for the event
     */
    public function beforeHandler()
    {
        //echo 'Before handler called for ' . get_called_class() . '<br/>';
        foreach ($this->before_handlers as $handler) {
            if (is_callable(array($this, $handler)))
                $this->$handler();
            else
                throw new \Exception("Before handler must be callable");
        }
    }

    /** 
     * function afterHandler()
     * Calls after handler for the event
     */
    public function afterHandler()
    {
        //echo 'After handler called for ' . get_called_class() . '<br/>';
        foreach ($this->after_handlers as $handler) {
            if (is_callable(array($this, $handler)))
                $this->$handler();
            else
                throw new \Exception("After handler must be callable");
        }
    }


    /** 
     * function initHandler
     * Initializes the handler
     * To be overridden in child classes
     */
    protected function initHandler()
    {
    }

    /** 
     * function addBeforeHandler
     * Adds before handler
     * 
     * @param string $handler
     */
    protected function addBeforeHandler($handler)
    {
        //echo 'Adding before handler '.$handler.'<br/>';
        if (!in_array($handler, $this->before_handlers))
            $this->before_handlers[] = $handler;
    }

    /**
     * function addAfterHandler
     * Adds after handler
     * 
     * @param string $handler
     */
    protected function addAfterHandler($handler)
    {
        //echo 'Adding after handler ' . $handler . '<br/>';
        if (!in_array($handler, $this->after_handlers))
            $this->after_handlers[] = $handler;
    }


    public function __call($name, $arguments)
    {
        // $event = $arguments[0];
        // $handler = $arguments[1];
    }

    /**
     * function getEventName
     * Gets event name of the handler
     * 
     * @return string
     */
    public function getEventName()
    {
        return $this->event_name;
    }

    /**
     * function getHandlerName
     * Gets handler name of the handler
     * 
     * @return string
     */
    public function getHandlerName()
    {
        if ($this->handler_name == '') {
            $this->handler_name = get_called_class();
            $ev = explode('\\', $this->handler_name);
            $this->handler_name = array_pop($ev);
        }

        return $this->event_name;
    }
}

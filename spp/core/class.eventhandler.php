<?php
namespace SPP;

abstract class EventHandler
{
    protected string $event_name;
    protected string $handler_name;
    protected $before_handlers = array();
    protected $after_handlers = array();
    protected $override_handlers = array();
    protected $external_handlers = array();


    /**
     * function __construct
     * Constructor
     * 
     * @param string $event_name
     * @param bool $is_default
     */
    public function __construct($event_name = null, $is_default = false)
    {
        $this->event_name = '';
        $this->event_name = $this->getEventName();
        $this->handler_name = $this->event_name;
        $this->event_name = ($event_name == null) ? $this->handler_name : $event_name;
        if ($is_default) {
            $this->overrideHandler();
        } else {
            $this->initHandler();
        }
    }






    public function __destruct()
    {
        //echo 'Destructor called for ' . get_called_class() . '<br/>';
    }

    /** 
     * function beforeHandler
     * Calls before handler for the event
     */
    public function beforeHandler(array &$params = [])
    {
        $this->externalBeforeHandler('execBefore', $params);
        foreach ($this->before_handlers as $handler) {
            if (is_callable(array($this, $handler)))
                $this->$handler($params);
            else
                throw new \Exception("Before handler must be callable");
        }
        $this->externalBeforeHandler('execAfter', $params);
    }

    /** 
     * function overrideHandler
     * Calls override handler for the event
     */
    public function overrideHandler(array &$params = [])
    {
        foreach ($this->override_handlers as $handler) {
            if (is_callable(array($this, $handler)))
                $this->$handler($params);
            else
                throw new \Exception("Override handler must be callable");
        }
    }

    /** 
     * function afterHandler()
     * Calls after handler for the event
     */
    public function afterHandler(array &$params = [])
    {
        $this->externalAfterHandler('execBefore', $params);
        foreach ($this->after_handlers as $handler) {
            if (is_callable(array($this, $handler)))
                $this->$handler($params);
            else
                throw new \Exception("After handler must be callable");
        }
        $this->externalAfterHandler('execAfter', $params);
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
     * function addExternalHandler
     * Adds external handler
     * 
     * @param string $handler
     * @param bool $exec_before
     */
    protected function addExternalHandler($handler, $exec_before = false)
    {
        if (class_exists('\\ExternalHandlers\\' . $handler)) {
            if ($exec_before)
                $this->external_handlers[] = array('handler' => $handler, 'occurence' => 'execBefore');
            else
                $this->external_handlers[] = array('handler' => $handler, 'occurence' => 'execAfter');
        } else {
            throw new \SPP\SPPException("External handler must lie in the namespace \\ExternalHandlers");
        }
    }

    /**
     * externalBeforeHandler
     * Calls external before handler
     * 
     * @param string $ocurence
     */
    protected function externalBeforeHandler($ocurence, array &$params = [])
    {
        foreach ($this->external_handlers as $handler) {
            if ($handler['occurence'] == $ocurence) {
                $className = '\\ExternalHandlers\\' . $handler['handler'];
                $instance = new $className();
                $instance->beforeHandler($params);
            }
        }
    }


    /** 
     * function externalAfterHandler
     * Calls external after handler
     * 
     * @param string $ocurence
     */
    protected function externalAfterHandler($ocurence, array &$params = [])
    {
        foreach ($this->external_handlers as $handler) {
            if ($handler['occurence'] == $ocurence) {
                $className = '\\ExternalHandlers\\' . $handler['handler'];
                $instance = new $className();
                $instance->afterHandler($params);
            }
        }
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

    /**
     * function addOverrideHandler
     * Adds override handler
     * 
     * @param string $handler
     */
    protected function addOverrideHandler($handler)
    {
        \SPP\SPPEvent::markOverrider($this->event_name);
        if (!in_array($handler, $this->override_handlers))
            $this->override_handlers[] = $handler;
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
        if ($this->event_name == '') {
            $this->event_name = get_called_class();
            $this->event_name = basename(str_replace('\\', '/', $this->event_name));
        }

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
        return $this->getEventName();
    }
}
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
    public function beforeHandler(){
        //echo 'Before handler called for ' . get_called_class() . '<br/>';
        $this->externalBeforeHandler('execBefore');
        foreach($this->before_handlers as $handler)
        {
             if(is_callable(array($this,$handler)))
                $this->$handler();
            else
                throw new \Exception("Before handler must be callable");
        }
        $this->externalBeforeHandler('execAfter');
    }
    
    /** 
     * function overrideHandler
     * Calls override handler for the event
     */
    public function overrideHandler(){
        //echo 'Override handler called for ' . get_called_class() . '<br/>';
        foreach($this->override_handlers as $handler)
        {
            if (is_callable(array($this, $handler)))
                $this->$handler();
            else
                throw new \Exception("Override handler must be callable");
        }
    }

    /** 
     * function afterHandler()
     * Calls after handler for the event
     */
    public function afterHandler(){
        //echo 'After handler called for ' . get_called_class() . '<br/>';
        $this->externalAfterHandler('execBefore');
        foreach($this->after_handlers as $handler)
        {
            if (is_callable(array($this, $handler)))
                $this->$handler();
            else
                throw new \Exception("After handler must be callable");
        }
        $this->externalAfterHandler('execAfter');
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
        if(class_exists('\\ExternalHandlers'.$handler))
        {
            if($exec_before)
                $this->external_handlers[] = array('handler'=>$handler, 'occurence'=>'execBefore');
            else
                $this->external_handlers[] = array($handler, 'execAfter');
        }
        else
        {
            throw new \SPP\SPPException("External handler must lie in the namespace \\ExternalHandlers");
        }
    }

    /**
     * externalBeforeHandler
     * Calls external before handler
     * 
     * @param string $ocurence
     */
    protected function externalBeforeHandler($ocurence)
    {
        foreach($this->external_handlers as $handler)
        {
            if($handler['occurence'] == $ocurence)
            {
                $handler['handler']->beforeHandler();
            }
        }
    }


    /** 
     * function externalAfterHandler
     * Calls external after handler
     * 
     * @param string $ocurence
     */
    protected function externalAfterHandler($ocurence)
    {
        foreach ($this->external_handlers as $handler) {
            if ($handler['occurence'] == $ocurence) {
                $handler['handler']->afterHandler();
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
        if(!in_array($handler,$this->before_handlers))
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
        if(!in_array($handler,$this->after_handlers))
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
        //echo 'Adding override handler ' . $handler . '<br/>';
        $events=\SPP\Registry::get('__events');
        //print_r($events);
        // $event_name=$this->getEventName();
        // echo $event_name.'<br/>';
        $events[ $this->event_name ]['overriders']=1;
        //var_dump($this->event_name);
        \SPP\Registry::register('__events',$events);
        if(!in_array($handler,$this->override_handlers))
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
        if ($this->event_name == '')
        {
            $this->event_name = get_called_class();
            //echo $this->event_name.' class called.<br/>';
            $ev=explode('\\',$this->event_name);
            //var_dump($ev);
            $this->event_name=array_pop($ev);
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
<?php
namespace SPP;

/**
 * class \SPP\SPP_Event
 * Implements event system in Satya Portal Pack.
 *
 * @author Satya Prakash Shukla
 * 
 */
class SPP_Event extends \SPP\SPP_Object
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
     * private static function callHandler()
     * Calls a handler callback.
     * 
     * @param string $hnd Name of handler function
     * @return mixed Return value of handler.
     */

     /**
      * function registerEvent()
      * Registers an event.
      * 
      * @param string $event_name Name of event.
      */
     public static function registerEvent(string $event_name)
     {
        $events=\SPP\Registry::get('__events');
        //$events=array();
        if($events===false)
        {
            $events=array();
        }
        //var_dump($events);
        if(!array_key_exists($event_name, $events) || !is_array($events[$event_name]))
        {
            $events[$event_name]=array('before'=>array(),'after'=>array());
        }
        else
        {
            throw new \SPP\SPP_Exception('Event "'.$event_name.'" already registered!');
        }
        //var_dump($events);
        \SPP\Registry::register('__events',$events);
     }

     /**
      * function registerHandler()
      * Registers a handler for an event.
      * 
      * @param string $event_name Name of event.
      * @param string $handler_name Name of handler function.
      * @param string $occurence Occurence of handler.
      */
     public static function registerHandler(string $event_name, string $handler_name, string $occurence, array $params=array())
     {
        $events=\SPP\Registry::get('__events');
        if($events===false)
        {
            $events=array();
            throw new \SPP\SPP_Exception('Event "'.$event_name.'" not registered!');
        }
        if(!array_key_exists($event_name, $events))
        {
            throw new \SPP\SPP_Exception('Event "'.$event_name.'" not registered!');
        }
        if(!in_array($occurence, array('before','after')))
        {
            throw new \SPP\SPP_Exception('Invalid occurence "'.$occurence.'" for event "'.$event_name.'"!');
        }
        if(!is_callable($handler_name))
        {
            throw new \SPP\SPP_Exception('Invalid handler "'.$handler_name.'" for event "'.$event_name.'"!');
        }
        $events[$event_name][$occurence][]=array('handler'=>$handler_name,'params'=>$params);
        \SPP\Registry::register('__events',$events);
     }

     public static function getHandlerParams($event_name, $handler_name, $occurence='before')
     {
        $events=\SPP\Registry::get('__events');
        $event=$events[$event_name];
        $handlers=$event[$occurence];
        foreach($handlers as $hnd)
        {
            if($hnd['handler']==$handler_name)
            {
                return $hnd['params'];
            }
        }
        throw new \SPP\SPP_Exception('Handler "'.$handler_name.'" not registered for event "'.$event_name.'" for occurence "'.$occurence.'"!');
     }

     public static function setHandlerParams($event_name, $handler_name, $params, $occurence='after')
     {
        $events=\SPP\Registry::get('__events');
        $event=$events[$event_name];
        $handlers=$event[$occurence];
        $param_set=false;
        $i=0;
        foreach ($handlers as $hnd) {
            if ($hnd['handler'] == $handler_name) {
                foreach(array_keys($params) as $key)
                {
                    if(array_key_exists($key, $hnd['params']))
                    {
                        $hnd['params'][$key]=$params[$key];
                    }
                    else
                    {
                        throw new \SPP\SPP_Exception('Invalid parameter "'.$key.'" for handler "'.$handler_name.'" for event "'.$event_name.'"! Parameter not registered!');
                    }
                }
                $param_set=true;
            }
            $handlers[$i]=$hnd;
            $i++;
        }
        if($param_set)
        {
            $event[$occurence]=$handlers;
            $events[$event_name]=$event;
            \SPP\Registry::register('__events',$events);
            return true;
        }
        throw new \SPP\SPP_Exception('Handler "' . $handler_name . '" not registered for event "' . $event_name . '" for occurence "'.$occurence.'"!');
     }


     /****
      * function startEvent()
      * Starts an event.
      * 
      * @param string $event_name Name of event.
      */
     public static function startEvent($event_name)
     {
        $events=\SPP\Registry::get('__events');
        if(array_key_exists($event_name, $events))
        {
            $handlers=$events[$event_name]['before'];
            foreach($handlers as $hnd)
            {
                self::callHandler($hnd['handler'],$hnd['params']);
            }
        }
        else
        {
            throw new \SPP\SPP_Exception('Cannot start event "'.$event_name.'". Event not registered!');
        }
     }


     /**
      * function endEvent()
      * Ends an event.
      * 
      * @param string $event_name Name of event.
      */
     public static function endEvent($event_name)
     {
        $events=\SPP\Registry::get('__events');
        if(array_key_exists($event_name, $events))
        {
            $handlers=$events[$event_name]['after'];
            foreach($handlers as $hnd)
            {
                self::callHandler($hnd['handler'], $hnd['params']);
            }
        }
        else
        {
            throw new \SPP\SPP_Exception('Cannot start event "'.$event_name.'". Event not registered!');
        }
    }

    private static function callHandler($hnd, $params)
    {
        //var_dump($hnd);
/*         if(is_array($hnd))
        {
            return call_user_func_array($hnd, $params);
        }
        elseif(is_string($hnd))
        {
            return call_user_func($hnd);
        }
 */        /*if(method_exists($this, $hnd))
        {
            return call_user_func(array($this,$hnd));
        }*/
        if(is_callable($hnd))
        {
            return call_user_func_array($hnd, $params);
        }
        else
        {
            throw new \SPP\SPP_Exception('Invalid handler '.$hnd.' called!');
        }
    }

    /**
     * function getVar()
     * Gets value of a passed variable
     * 
     * @param string $variable Variable name
     * @return mixed Value of variable.
     */
/*     public static function getVar($variable)
    {
        $appctxt=\SPP\Scheduler::getContext();
        if(SPP_Global::is_set('__'.$appctxt.'_event_variables')) {
            $stack=SPP_Global::get('__'.$appctxt.'_event_variables');
            $arr=$stack->getTop();
            if($arr===false)
            {
                throw new \SPP\SPP_Exception('Event variable used outside event function.');
            }
            if(array_key_exists($variable, $arr['vars']))
            {
                $vars=$arr['vars'];
                return $vars[$variable];
            }
            else
            {
                throw new \SPP\SPP_Exception('Unknown event variable "'.$variable.'" accesed!');
            }
        } else {
            throw new \SPP\SPP_Exception('Event variable used outside event function.');
        }
    }

 */    /**
     * function getVar()
     * Sets value of a passed variable
     *
     * @param string $variable Variable name
     * @return mixed Value of variable.
     */
/*     public static function setVar($variable, $value)
    {
        $appctxt=\SPP\Scheduler::getContext();
        if(SPP_Global::is_set('__'.$appctxt.'_event_variables')) {
            $stack=SPP_Global::get('__'.$appctxt.'_event_variables');
            $arr=$stack->pop();
            if($arr===false)
            {
                throw new \SPP\SPP_Exception('Event variable used outside event function.');
            }
            if(array_key_exists($variable, $arr['vars']))
            {
                $vars=$arr['vars'][$variable]=$value;
                //print_r($arr);
                //$vars[$variable]=$value;
                $stack->push($arr);
            }
            else
            {
                throw new \SPP\SPP_Exception('Unknown event variable "'.$variable.'" accesed!');
            }
        } else {
            throw new \SPP\SPP_Exception('Event variable used outside event function.');
        }
    }
 */
    /**
     * function getVars()
     * Gets an array of all passed variables.
     * 
     * @return array An array of all passed variables.
     */
/*     public static function getVars()
    {
        $appctxt=\SPP\Scheduler::getContext();
        if(SPP_Global::is_set('__'.$appctxt.'_event_variables')) {
            $stack=SPP_Global::get('__'.$appctxt.'_event_variables');
            $arr=$stack->getTop();
            if($arr===false)
            {
                throw new \SPP\SPP_Exception('Event variable used outside event function.');
            }
            else
            {
                $vars=$arr['vars'];
                return $vars;
            }
        } else {
            throw new \SPP\SPP_Exception('Event variable used outside event function.');
        }
    }
 */
    /**
     * function startEvent()
     * Starts a non-overridable event.
     * 
     * @param string $event Name of event
     * @param array $variables An array of variables to be available to callback function.
     */
/*     public static function startEvent($event, $variables=array())
    {
        $appctxt=\SPP\Scheduler::getContext();
        $hnd='';
        //$ev['vars']=$variables;
        $stack='';
        if(SPP_Global::is_set('__'.$appctxt.'_event_variables'))
        {
            $stack=SPP_Global::get('__'.$appctxt.'_event_variables');
        }
        else
        {
            $stack=new \SPP\Stack();
        }
        $arr['evname']=$event;
        $arr['vars']=$variables;
        //print_r($arr);
        $stack->push($arr);
        SPP_Global::set('__'.$appctxt.'_event_variables', $stack);
        $hnd=\SPP\Registry::get('__events=>'.$event.'=>handler');
        $mhnd=\SPP\Registry::get('__events=>'.$event.'=>handlers');
        if($hnd!==false||$mhnd!==false)
        {
//                var_dump($hnd);
            $occr='';
            if($hnd!==false)
            {
                $occr=\SPP\Registry::get('__events=>'.$event.'=>occurence');
                switch($occr)
                {
                    case 'before':
                        self::callHandler($hnd);
                        break;
                    case 'after':
                        break;
                    case 'instead':
                        throw new \SPP\SPP_Exception('"instead" occurence type used for a non overridable event : '.$event);
                        break;
                    default:
                        throw new \SPP\SPP_Exception('Wrong occurence type '.$occr.' is registered');
                        break;
                }
            }
            if($mhnd!==false)
            {
                //var_dump($mhnd);
                foreach($mhnd as $hand)
                {
                //var_dump($hand);
                    $occr=$hand['occurence'];
                    $hnd=$hand['handler'];
                    switch($occr)
                    {
                        case 'before':
                            self::callHandler($hnd);
                            break;
                        case 'after':
                            break;
                        default:
                            throw new \SPP\SPP_Exception('Wrong occurence type '.$occr.' is registered for handler '.$hnd);
                    }
                }
            }
        }
    }
 */
    /**
     * function endEvent()
     * Ends a non-overridable event.
     * 
     * @param string $event Name of event.
     */
/*     public static function endEvent($event)
    {
        $appctxt=\SPP\Scheduler::getContext();
        $hnd='';
        //$ev['vars']=$variables;
        $stack='';
        if(SPP_Global::is_set('__'.$appctxt.'_event_variables'))
        {
            $stack=SPP_Global::get('__'.$appctxt.'_event_variables');
        }
        else
        {
            throw new \SPP\SPP_Exception('"endEvent" used outside an event.');
        }
        $arr=$stack->getTop();
        //print_r($arr);
        if(array_key_exists('callback', $arr))
        {
            throw new \SPP\SPP_Exception('"endEvent" used inside an overridable event');
        }
        elseif($arr['evname']!=$event)
        {
            throw new \SPP\SPP_Exception('"endEvent" for event '.$event.' used inside the event "'.$arr['evname'].'".');
        }
        $hnd=\SPP\Registry::get('__events=>'.$event.'=>handler');
        $mhnd=\SPP\Registry::get('__events=>'.$event.'=>handlers');
//                var_dump($hnd);
        if($hnd!==false||$mhnd!==false)
        {
            $occr='';
            if($hnd!==false)
            {
                $occr=\SPP\Registry::get('__events=>'.$event.'=>occurence');
                switch($occr)
                {
                    case 'before':
                        break;
                    case 'after':
                        self::callHandler($hnd);
                        break;
                    case 'instead':
                        throw new \SPP\SPP_Exception('"instead" occurence type used for a non overridable event : '.$event);
                        break;
                    default:
                        throw new \SPP\SPP_Exception('Wrong occurence type '.$occr.' is registered');
                        break;
                }
            }
            if($mhnd!==false)
            {
                foreach($mhnd as $hand)
                {
                //var_dump($hand);
                    $occr=$hand['occurence'];
                    $hnd=$hand['handler'];
                    switch($occr)
                    {
                        case 'before':
                            break;
                        case 'after':
                            self::callHandler($hnd);
                            break;
                        default:
                            throw new \SPP\SPP_Exception('Wrong occurence type '.$occr.' is registered for handler '.$hnd);
                            break;
                    }
                }
            }
        }
        $stack->pop();
        SPP_Global::set('__'.$appctxt.'_event_variables',$stack);
    }
 */
    /**
     * function fireEvent()
     * Fires an event
     * 
     * @param string $event Name of event
     * @param string $evfunc Callback function
     * @param array $variables An array of passed variables.
     */
/*     public static function fireEvent($event, $evfunc, $variables=array())
    {
        $appctxt=\SPP\Scheduler::getContext();
        $hnd='';
        //$ev['vars']=$variables;
        $stack='';
        if(SPP_Global::is_set('__'.$appctxt.'_event_variables'))
        {
            $stack=SPP_Global::get('__'.$appctxt.'_event_variables');
        }
        else
        {
            $stack=new \SPP\Stack();
        }
        $arr['evname']=$event;
        $arr['callback']=$evfunc;
        $arr['vars']=$variables;
        $stack->push($arr);
        SPP_Global::set('__'.$appctxt.'_event_variables', $stack);
        $hnd=\SPP\Registry::get('__events=>'.$event.'=>handler');
        $mhnd=\SPP\Registry::get('__events=>'.$event.'=>handlers');
        if($hnd!==false||$mhnd!==false)
        {
            $occr='';
            if($hnd!==false)
            {
                $occr=\SPP\Registry::get('__events=>'.$event.'=>occurence');
                switch($occr)
                {
                    case 'before':
                        self::callHandler($hnd);
                        self::callHandler($evfunc);
                        break;
                    case 'after':
                        self::callHandler($evfunc);
                        self::callHandler($hnd);
                        break;
                    case 'instead':
                        self::callHandler($hnd);
                        break;
                    default:
                        throw new \SPP\SPP_Exception('Wrong occurence type '.$occr.' is registered');
                        break;
                }
            }
            if($mhnd!==false)
            {
                foreach($mhnd as $hand)
                {
                    $occr=$hand['occurence'];
                    $hnd=$hand['handler'];
                    switch($occr)
                    {
                        case 'before':
                            self::callHandler($hnd);
                            self::callHandler($evfunc);
                            break;
                        case 'after':
                            self::callHandler($evfunc);
                            self::callHandler($hnd);
                            break;
                        default:
                            throw new \SPP\SPP_Exception('Wrong occurence type '.$occr.' is registered');
                            break;
                    }
                }
            }
        }
        else
        {
            self::callHandler($evfunc);
        }
        $stack->pop();
        SPP_Global::set('__'.$appctxt.'_event_variables',$stack);
    }

 */    /**
     * function callOriginalHandler()
     * Calls the original handler function.
     */
/*     public static function callOriginalHandler()
    {
        $appctxt=\SPP\Scheduler::getContext();
        if(SPP_Global::is_set('__'.$appctxt.'_event_variables')) {
            $stack=SPP_Global::get('__'.$appctxt.'_event_variables');
            $arr=$stack->getTop();
            if($arr===false)
            {
                throw new \SPP\SPP_Exception('Event variable used outside event function.');
            }
            elseif(array_key_exists('callback', $arr))
            {
                $hnd=$arr['callback'];
                self::callHandler($hnd);
            }
            else
            {
                throw new \SPP\SPP_Exception(__FUNCTION__.' used in a non-overridable event.');
            }
        } else {
            throw new \SPP\SPP_Exception('Event variable used outside event function.');
        }
    }
 */    
    /**
     * static function setEventHandler()
     * Registers custom handler for an event.
     *
     * @param $event Name of event
     * @param mixed $handler Name of handler function
     * @param string $occurence before, after or instead
     */
/*     public static function setDefaultEventHandler($event, $handler, $occurence='instead') {
        if(in_array($occurence,array('before', 'after', 'instead')))
        {
            if(is_callable($handler))
            {
                \SPP\Registry::register('__events=>'.$event.'=>handler', $handler);
                \SPP\Registry::register('__events=>'.$event.'=>occurence', $occurence);
            }
            else
            {
                throw new \SPP\SPP_Exception('Invalid event handler '.$handler.' was registered');
            }
        }
        else
        {
            throw new \SPP\SPP_Exception('Unknown occurence type '.$occurence.' specified.');
        }
    }
 */
    /**
     * static function addEventHandler()
     * Adds a custom handler for an event.
     *
     * @param $event Name of event
     * @param mixed $handler Name of handler function
     * @param string $occurence before or after
     */
/*     public static function addEventHandler($event, $handler, $occurence='after') {
        if(in_array($occurence,array('before', 'after')))
        {
            if(is_callable($handler))
            {
                //echo '<br /><br />----- Registering handler -----<br /><br />';
                $hnd=\SPP\Registry::get('__events=>'.$event.'=>handlers');
                if(!is_array($hnd))
                {
                    $hnd=array();
                }
                $hnd[]=array('handler'=>$handler,'occurence'=>$occurence);
                $hnd=array_values($hnd);
                //var_dump($hnd);
                \SPP\Registry::register('__events=>'.$event.'=>handlers', $hnd);
                //var_dump(\SPP\Registry::get('__events=>'.$event.'=>handlers'));
                //echo '<br /><br />----- Registered handler -----<br /><br />';
            }
            else
            {
                throw new \SPP\SPP_Exception('Invalid event handler '.$handler.' was registered');
            }
        }
        else
        {
            throw new \SPP\SPP_Exception('Unknown occurence type '.$occurence.' specified.');
        }
    }
 */
}
?>
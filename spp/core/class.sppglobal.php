<?php
namespace SPP;
/**
 * class SPPGlobal
 * Manages global variables in SPP
 *
 * @author Satya Prakash Shukla
 */
final class SPPGlobal extends \SPP\SPPObject {
    private static $globals=array();

    
    /**
     * function __construct()
     * Private constructor.
     */
    private function __construct()
    {
        ;
    }

    /**
     * function set()
     * Set a global property.
     *
     * @param string $prop The property
     * @param mixed $val The value.
     */
    public static function set($prop,$val)
    {
        $context=\SPP\Scheduler::getContext();
        self::$globals[$context][$prop]=$val;
    }

    /**
     * function get()
     * Gets a global property.
     *
     * @param string $prop The property
     * @return mixed The value
     */
    public static function get($prop)
    {
        $context = \SPP\Scheduler::getContext();
        if(array_key_exists($prop, self::$globals[$context]))
        {
            return self::$globals[$context][$prop];
        }
        else
        {
            throw new \SPP\SPPException('Invalid SPPGlobal variable "'.$prop.'" was accessed!');
        }
    }

    /**
     * function is_set()
     * Returns true if property is set.
     *
     * @param string $prop Property name.
     * @return bool
     */
    public static function is_set($prop)
    {
        $context = \SPP\Scheduler::getContext();
        if(array_key_exists($prop, self::$globals[$context]))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * function do_unset()
     * Unset a property.
     *
     * @param string $prop Property name
     * @return bool Success or failure.
     */
    public static function do_unset($prop)
    {
       $context = \SPP\Scheduler::getContext();
       if(array_key_exists($prop, self::$globals[$context]))
        {
            unset(self::$globals[$context][$prop]);
            return true;
        }
        else
        {
            return false;
        }
    }
}
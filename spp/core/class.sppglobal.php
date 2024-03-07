<?php
/**
 * class SPP_Global
 * Manages global variables in SPP
 *
 * @author Satya Prakash Shukla
 */
final class SPP_Global extends SPP_Object {
    private static $globals=array();

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
        self::$globals[$prop]=$val;
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
        if(array_key_exists($prop, self::$globals))
        {
            return self::$globals[$prop];
        }
        else
        {
            throw new SPP_Exception('Invalid SPP_Global variable "'.$prop.'" was accessed!');
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
        if(array_key_exists($prop, self::$globals))
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
        if(array_key_exists($prop, self::$globals))
        {
            unset(self::$globals[$prop]);
            return true;
        }
        else
        {
            return false;
        }
    }
}
?>
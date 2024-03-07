<?php
//require_once 'class.sppobject.php';
/**
 * class SPP_Entity
 *
 * Defines a basic SPP entity.
 *
 * @author Satya Prakash Shukla
 */
abstract class SPP_Entity extends SPP_Object{
    protected $attributes=array();
    protected $getprops=array(),$setprops=array();

    /**
     * Constructor
     * Load attribute values here.
     */
    public function  __construct() {
        parent::__construct();
    }

    /**
     * function __unset()
     * Magic function function to unset an attribute.
     *
     * @param string $attr
     */
    public function __unset($attr)
    {
        if($this->__isset($attr))
        {
            unset($this->attributes[$attr]);
        }
    }

    /**
     * function __isset()
     * Magic function to check for existence of an attribute.
     *
     * @param string $attr
     * @return bool
     */
    public function __isset($attr)
    {
        if(array_key_exists($attr, $this->attributes))
        {
            return true;
        }
        {
            return false;
        }
    }

    
    /**
     * function get()
     *
     * Gets the value of property.
     *
     * @param mixed $propname
     * @return <type>
     */
    public function __get($propname)
    {
        if(in_array($propname, $this->getprops))
        {
            return $this->attributes[$propname];
        }
        else
        {
            throw new UnknownPropertyException('Unknown property '.$propname);
        }
    }

    /**
     * function set()
     * Sets a property value.
     *
     * @param string $propname
     * @param mixed $propval
     * @return mixed
     */
    public function __set($propname,$propval)
    {
        if(in_array($propname, $this->setprops))
        {
            return $this->attributes[$propname]=$propval;
        }
        else
        {
            throw new UnknownPropertyException('Unknown property '.$propname);
        }
    }
}
?>
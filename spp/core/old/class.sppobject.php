<?php
namespace SPP;
/**
 * class \SPP\SPPObject
 *
 * Top level class for all the spp classes.
 *
 * @author Satya Prakash Shukla
 */
use SPP\Exceptions\UnknownPropertyException;
abstract class SPPObject {
    protected $_attributes=array();
    protected $_getprops=array(),$_setprops=array();

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
            unset($this->_attributes[$attr]);
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
        if(array_key_exists($attr, $this->_attributes))
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
        if(in_array($propname, $this->_getprops))
        {
            return $this->_attributes[$propname];
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
        if(in_array($propname, $this->_setprops))
        {
            return $this->_attributes[$propname]=$propval;
        }
        else
        {
            throw new \UnknownPropertyException('Unknown property '.$propname);
        }
    }

    public function __toString()
    {
        return var_export($this->_attributes,true);
    }
}
?>
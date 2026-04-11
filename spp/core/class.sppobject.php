<?php

namespace SPP;

/**
 * class \SPP\SPPObject
 *
 * Top-level abstract class for all SPP classes.
 * Provides dynamic property access and safe attribute management.
 *
 * Fully backward-compatible modernization.
 *
 * @author
 *     Satya Prakash Shukla
 * @version
 *     2.1 compatible with legacy SPP 1.x
 */

use SPP\Exceptions\UnknownPropertyException;

abstract class SPPObject
{
    /** @var array<string,mixed> */
    protected array $_attributes = [];

    /** @var array<string> */
    protected array $_getprops = [];

    /** @var array<string> */
    protected array $_setprops = [];

    /**
     * Magic unsetter — removes an attribute if it exists.
     *
     * @param string $attr
     */
    public function __unset(string $attr)
    {
        if (in_array($attr, $this->_setprops, true) && $this->__isset($attr)) {
            unset($this->_attributes[$attr]);
        }
    }

    /**
     * Magic isset — checks if an attribute exists.
     *
     * @param string $attr
     * @return bool
     */
    public function __isset(string $attr)
    {
        return in_array($attr, $this->_getprops, true) && array_key_exists($attr, $this->_attributes);
    }

    /**
     * Magic getter — retrieves value of readable property.
     *
     * @param string $propname
     * @return mixed
     * @throws UnknownPropertyException
     */
    public function __get(string $propname)
    {
        if (in_array($propname, $this->_getprops, true)) {
            return $this->_attributes[$propname] ?? null;
        }

        throw new UnknownPropertyException('Unknown property: ' . $propname);
    }

    /**
     * Magic setter — sets value of writable property.
     *
     * @param string $propname
     * @param mixed $propval
     * @return void
     * @throws UnknownPropertyException
     */
    public function __set(string $propname, mixed $propval)
    {
        if (in_array($propname, $this->_setprops, true)) {
            $this->_attributes[$propname] = $propval;
            return;
        }

        throw new UnknownPropertyException('Unknown property: ' . $propname);
    }

    /**
     * Converts object attributes to a string (for debugging).
     *
     * @return string
     */
    public function __toString()
    {
        return var_export($this->_attributes, true);
    }
}

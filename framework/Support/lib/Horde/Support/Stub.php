<?php
/**
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Support
 */

/**
 * Class that can substitute for any object and safely do nothing.
 *
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Support
 */
class Horde_Support_Stub
{
    /**
     * Cooerce to an empty string.
     *
     * @return string
     */
    public function __toString()
    {
        return '';
    }

    /**
     * Ignore setting the requested property.
     *
     * @param string $key  The property.
     * @param mixed $val   The property's value.
     */
    public function __set($key, $val)
    {
    }

    /**
     * Return null for any requested property.
     *
     * @param string $key  The requested object property.
     *
     * @return null  Null.
     */
    public function __get($key)
    {
        return null;
    }

    /**
     * Property existence.
     *
     * @param string $key  The requested object property.
     *
     * @return boolean  False.
     */
    public function __isset($key)
    {
        return false;
    }

    /**
     * Ignore unsetting a property.
     *
     * @param string $key  The requested object property.
     */
    public function __unset($key)
    {
    }

    /**
     * Gracefully accept any method call and do nothing.
     *
     * @param string $method  The method that was called.
     * @param array $args     The method's arguments.
     */
    public function __call($method, $args)
    {
    }

    /**
     * Gracefully accept any static method call and do nothing.
     *
     * @param string $method  The method that was called.
     * @param array $args     The method's arguments.
     */
    public static function __callStatic($method, $args)
    {
    }

}

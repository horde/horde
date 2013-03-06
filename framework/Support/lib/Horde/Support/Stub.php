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

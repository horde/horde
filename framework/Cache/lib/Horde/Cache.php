<?php
/**
 * The Horde_Cache:: class provides a common abstracted interface into
 * the various caching backends.  It also provides functions for
 * checking in, retrieving, and flushing a cache.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Cache
 */
class Horde_Cache
{
    /**
     * Singleton instances.
     *
     * @var array
     */
    static protected $_instances = array();

    /**
     * Attempts to return a concrete Horde_Cache instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Horde_Cache subclass to
     *                       return. If $driver is an array, then we will look
     *                       in $driver[0]/lib/Cache/ for the subclass
     *                       implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration
     *                       or connection parameters a subclass might need.
     *
     * @return Horde_Cache  The newly created concrete Horde_Cache instance.
     * @throws Horde_Exception
     */
    static public function factory($driver, $params = array())
    {
        if (is_array($driver)) {
            list($app, $driv_name) = $driver;
            $driver = basename($driv_name);
        } else {
            $driver = basename($driver);
        }

        if (empty($driver) || $driver == 'none') {
            return new Horde_Cache_Null($params);
        }

        $class = (empty($app) ? 'Horde' : $app) . '_Cache_' . ucfirst($driver);

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception('Class definition of ' . $class . ' not found.');
    }

    /**
     * Attempts to return a reference to a concrete Horde_Cache instance
     * based on $driver. It will only create a new instance if no
     * Horde_Cache instance with the same parameters currently exists.
     *
     * This should be used if multiple cache backends (and, thus,
     * multiple Horde_Cache instances) are required.
     *
     * This method must be invoked as:
     *   $var = Horde_Cache::singleton()
     *
     * @param mixed $driver  The type of concrete Horde_Cache subclass to
     *                       return. If $driver is an array, then we will look
     *                       in $driver[0]/lib/Cache/ for the subclass
     *                       implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Cache  The concrete Horde_Cache reference.
     * @throws Horde_Exception
     */
    static public function singleton($driver, $params = array())
    {
        ksort($params);
        $signature = hash('md5', serialize(array($driver, $params)));

        if (!isset(self::$_instances[$signature])) {
            self::$_instances[$signature] = self::factory($driver, $params);
        }

        return self::$_instances[$signature];
    }

}

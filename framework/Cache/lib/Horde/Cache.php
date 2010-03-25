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
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Cache
 */
class Horde_Cache
{
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

}

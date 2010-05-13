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
     * Attempts to return a concrete instance based on $driver.
     *
     * @param string $driver  The type of concrete subclass to
     *                        return. The class name is based on the storage
     *                        driver ($driver). The code is dynamically
     *                        included.
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Horde_Cache_Base  The newly created concrete instance.
     * @throws Horde_Cache_Exception
     */
    static public function factory($driver, array $params = array())
    {
        $driver = ucfirst(basename($driver));
        $class = __CLASS__ . '_' . $driver;

        if (!class_exists($class)) {
            $class = __CLASS__ . '_Null';
        }

        return new $class($params);
    }

}

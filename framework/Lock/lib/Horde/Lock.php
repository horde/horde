<?php
/**
 * The Horde_Lock class provides an API to create, store, check and expire locks
 * based on a given resource URI.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @author   Ben Klang <ben@alkaloid.net>
 * @category Horde
 * @package  Lock
 */
class Horde_Lock
{
    /* Class constants. */
    const TYPE_EXCLUSIVE = 1;
    const TYPE_SHARED = 2;

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param mixed $driver  The type of concrete subclass to return.
     *                       This is based on the storage driver ($driver).
     *                       The code is dynamically included.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Lock_Base  The newly created concrete instance.
     * @throws Horde_Lock_Exception
     */
    static public function factory($driver, $params = array())
    {
        $driver = Horde_String::ucfirst(basename($driver));
        $class = __CLASS__ . '_' . $driver;

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Lock_Exception('Horde_Lock driver (' . $class . ') not found');
    }

}

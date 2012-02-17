<?php
/**
 * VFS API for abstracted file storage and access.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Vfs
 */
class Horde_Vfs
{
    /* Quota constants. */
    const QUOTA_METRIC_BYTE = 1;
    const QUOTA_METRIC_KB = 2;
    const QUOTA_METRIC_MB = 3;
    const QUOTA_METRIC_GB = 4;

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @deprecated
     *
     * @param mixed $driver  The type of concrete subclass to return. This
     *                       is based on the storage driver ($driver). The
     *                       code is dynamically included.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return VFS  The newly created concrete VFS instance.
     * @throws Horde_Vfs_Exception
     */
    static public function factory($driver, $params = array())
    {
        $class = 'Horde_Vfs_' . basename(Horde_String::ucfirst($driver));

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Vfs_Exception('Class definition of ' . $class . ' not found.');
    }
}

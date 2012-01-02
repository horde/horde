<?php
/**
 * This class provides an API for various compression techniques that can be
 * used by Horde applications.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */
class Horde_Compress
{
    /**
     * Attempts to return a concrete Horde_Compress_Base instance based on
     * $driver.
     *
     * @param string $driver  Either a driver name, or the full class name to
     *                        use (class must extend Horde_Compress_Base).
     * @param array $params   Hash containing any additional configuration
     *                        or parameters a subclass needs.
     *
     * @return Horde_Compress_Base  The newly created concrete instance.
     * @throws Horde_Compress_Exception
     */
    static public function factory($driver, $params = null)
    {
        /* Base drivers (in Compress/ directory). */
        $class = __CLASS__ . '_' . ucfirst($driver);
        if (@class_exists($class)) {
            return new $class($params);
        }

        /* Explicit class name. */
        if (@class_exists($driver)) {
            return new $driver($params);
        }

        throw new Horde_Compress_Exception(__CLASS__ . ': Class definition of ' . $driver . ' not found.');
    }

}

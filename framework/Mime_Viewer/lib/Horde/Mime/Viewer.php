<?php
/**
 * The Horde_Mime_Viewer:: class provides an abstracted interface to render
 * MIME data into various formats.  It depends on both a set of
 * Horde_Mime_Viewer_* drivers which handle the actual rendering, and a
 * configuration file to map MIME types to drivers.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer
{
    /**
     * Attempts to return a concrete Horde_Mime_Viewer_Base instance based on
     * $driver.
     *
     * @param string $driver         Either a driver name, or the full class
     *                               name to use (class must extend
     *                               Horde_Mime_Viewer_Base).
     * @param Horde_Mime_Part $part  The MIME part object to display.
     * @param array $params          A hash containing any additional
     *                               configuration or parameters a subclass
     *                               might need.
     *
     * @return Horde_Mime_Viewer_Base  The newly created concrete instance.
     * @throws Horde_Mime_Viewer_Exception
     */
    static public function factory($driver, $part, array $params = array())
    {
        $params['_driver'] = $driver;

        /* Base drivers (in Viewer/ directory). */
        $class = __CLASS__ . '_' . $driver;
        if (class_exists($class)) {
            return new $class($part, $params);
        }

        /* Explicit class name, */
        if (class_exists($driver)) {
            return new $driver($part, $params);
        }

        throw new Horde_Mime_Viewer_Exception(__CLASS__ . ': Class definition of ' . $class . ' not found.');
    }

}

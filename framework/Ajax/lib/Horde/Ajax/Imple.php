<?php
/**
 * Class to attach PHP actions to javascript elements.
 * (Originally named 'Dimple' as it was first implemented in DIMP - rennamed
 * to 'Imple' when DIMP was merged into IMP.)
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Ajax
 */
class Horde_Ajax_Imple
{
    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param mixed $driver  The type of concrete subclass to return. If
     *                       $driver is an array, then look in
     *                       $driver[0]/lib/Ajax/Imple for the subclass
     *                       implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration or
     *                       parameters a subclass might need.
     *
     * @return Horde_Ajax_Imple_Base  The newly created concrete instance.
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

        $class = (empty($app) ? 'Horde' : $app) . '_Ajax_Imple_' . ucfirst($driver);

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception('Class definition of ' . $class . ' not found.');
    }
}

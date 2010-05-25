<?php
/**
 * This class provides an interface to sign up or have new users sign
 * themselves up into the horde installation, depending on how the admin has
 * configured Horde.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */
class Horde_Core_Auth_Signup
{
    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param string $driver  The type of the concrete subclass to return.
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Horde_Core_Auth_Signup_Base  The newly created concrete
     *                                      instance.
     * @throws Horde_Exception
     */
    static public function factory($driver, $params = array())
    {
        $driver = Horde_String::ucfirst(basename($driver));
        $class = __CLASS__ . '_' . $driver;

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception(__CLASS__ . ' driver (' . $class . ') not found.');
    }

}

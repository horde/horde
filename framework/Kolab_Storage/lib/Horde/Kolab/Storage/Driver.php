<?php
/**
 * The driver for accessing Kolab storage.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The driver class for accessing Kolab storage.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
abstract class Horde_Kolab_Storage_Driver
{
    /**
     * Factory.
     *
     * @param string $driver The driver type used for the storage connection.
     * @param array  $params Additional connection parameters.
     *
     * @return Horde_Kolab_Storage_List A concrete list instance.
     */
    static public function &factory($driver, $params = array())
    {
        $class = 'Horde_Kolab_Storage_Driver_' . ucfirst(basename($driver));
        if (class_exists($class)) {
            $driver = new $class($params);
            return $driver;
        }
        throw new Horde_Kolab_Storage_Exception(
            'Driver type definition "' . $class . '" missing.');
    }
}
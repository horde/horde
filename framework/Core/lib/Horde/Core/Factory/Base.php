<?php
/**
 * The abstract Horde factory class.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * The abstract Horde factory class.
 *
 * This class is used for factories that are intended to have their create()
 * methods manually called by code.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Base
{
    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    protected $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return the classname of the driver to load.
     *
     * @param string $driver  Driver name.
     * @param string $base    The base classname.
     *
     * @return string  Classname.
     * @throws Horde_Exception
     */
    protected function _getDriverName($driver, $base)
    {
        $class = $base . '_' . Horde_String::ucfirst($driver);
        if (class_exists($class)) {
            return $class;
        }

        if (class_exists($driver)) {
            return $driver;
        }

        throw new Horde_Exception('"' . $driver . '" driver (for ' . $base . ' not found).');
    }

}

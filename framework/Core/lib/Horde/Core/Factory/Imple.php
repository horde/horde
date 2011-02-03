<?php
/**
 * A Horde_Injector:: based Horde_Core_Ajax_Imple:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Core_Ajax_Imple:: factory.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Imple extends Horde_Core_Factory_Base
{
    /**
     * Attempts to return a concrete Imple instance.
     *
     * @param mixed $driver  The type of concrete subclass to return. If
     *                       $driver is an array, then look in
     *                       $driver[0]/lib/Ajax/Imple for the subclass
     *                       implementation named $driver[1].php.
     * @param array $params  A hash containing any additional configuration or
     *                       parameters a subclass might need.
     * @param boolean $noattach  Don't attach on creation.
     *
     * @return Horde_Core_Ajax_Imple  The newly created instance.
     * @throws Horde_Exception
     */
    public function create($driver, array $params = array(),
                             $noattach = false)
    {
        if (is_array($driver)) {
            list($app, $driv_name) = $driver;
            $driver = ucfirst(basename($driv_name));
            $class = ucfirst($app) . '_Ajax_Imple_' . $driver;
        } else {
            $driver = basename($driver);
            $class = 'Horde_Core_Ajax_Imple_' . $driver;
        }

        if (class_exists($class)) {
            $ob = new $class($params);
            if (!$noattach) {
                $ob->attach();
            }
            return $ob;
        }

        throw new Horde_Exception('Imple driver ' . $driver . ' not found.');
    }

}

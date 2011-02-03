<?php
/**
 * A Horde_Injector:: based Horde_Core_Ajax_Application:: factory.
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
 * A Horde_Injector:: based Horde_Core_Ajax_Application:: factory.
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
class Horde_Core_Factory_Ajax extends Horde_Core_Factory_Base
{
    /**
     * Return a Horde_Core_Ajax_Application instance.
     *
     * @param string $app            The application name.
     * @param Horde_Variables $vars  Form/request data.
     * @param string $action         The AJAX action to perform.
     *
     * @return Horde_Core_Ajax_Application  The requested instance.
     * @throws Horde_Exception
     */
    public function create($app, $vars, $action = null)
    {
        $class = $app . '_Ajax_Application';

        if (class_exists($class)) {
            return new $class($app, $vars, $action);
        }

        throw new Horde_Exception('Ajax configuration for ' . $app . ' not found.');
    }

}

<?php
/**
 * Perform AJAX actions.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Horde_Ajax
 */
class Horde_Ajax
{
    /**
     * Get a Horde_Ajax_Application_Base instance.
     *
     * @param string $app     The application name.
     * @param string $action  The AJAX action to perform.
     *
     * @return Horde_Ajax_Application_Base  The requested instance.
     * @throws Horde_Exception
     */
    static public function getInstance($app, $action = null)
    {
        $class = $app . '_Ajax_Application';

        if (class_exists($class)) {
            return new $class($app, $action);
        }

        throw new Horde_Ajax_Exception('Ajax configuration for ' . $app . ' not found.');
    }

}

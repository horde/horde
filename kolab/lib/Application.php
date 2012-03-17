<?php
/**
 * Kolab application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Kolab through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Kolab
 */

/* Determine the base directories. */
if (!defined('KOLAB_BASE')) {
    define('KOLAB_BASE', __DIR__ . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(KOLAB_BASE . '/config/horde.local.php')) {
        include KOLAB_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', KOLAB_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Kolab_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H5 (0.1-git)';

    /**
     * Global variables defined:
     * - $variable: List all global variables here.
     */
    protected function _bootstrap()
    {
        $GLOBALS['injector']->bindFactory('Kolab_Driver', 'Kolab_Factory_Driver', 'create');
    }

    /**
     */
    public function menu($menu)
    {
        $menu->add(Horde::url('list.php'), _("List"), 'user.png');
    }
}

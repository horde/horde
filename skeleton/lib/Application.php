<?php
/**
 * Skeleton application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Skeleton through this API.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Skeleton
 */

/* Determine the base directories. */
if (!defined('SKELETON_BASE')) {
    define('SKELETON_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(SKELETON_BASE . '/config/horde.local.php')) {
        include SKELETON_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', SKELETON_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Skeleton_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (0.1-git)';

    /**
     * Initialization function.
     *
     * Global variables defined:
     * - $variable: List all global variables here.
     */
    protected function _init()
    {
    }

    /**
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
        $menu->add(Horde::url('list.php'), _("List"), 'user.png');
    }

}

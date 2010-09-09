<?php
/**
 * Jeta application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Jeta through this API.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Jeta
 */

/* Determine the base directories. */
if (!defined('JETA_BASE')) {
    define('JETA_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(JETA_BASE . '/config/horde.local.php')) {
        include JETA_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', JETA_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Jeta_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
        $menu->addArray(array(
            'class' => ((basename($_SERVER['PHP_SELF']) == 'index.php') ? 'current' : ''),
            'icon' => 'jeta.png',
            'text' => _("_Shell"),
            'url' => Horde::url('index.php')
        ));
    }

}

<?php
/**
 * Luxor application API.
 *
 * This file defines Luxor's core application definition.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Luxor
 */

/* Determine the base directories. */
if (!defined('LUXOR_BASE')) {
    define('LUXOR_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(LUXOR_BASE . '/config/horde.local.php')) {
        include LUXOR_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', LUXOR_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Luxor_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (1.0-git)';

    /**
     * Initialization function.
     *
     * Global variables defined:
     *   $luxor_shares - TODO
     */
    protected function _init()
    {
        // Luxor base libraries.
        Luxor::initialize();
    }

    /**
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu()
    {
        return Luxor::getMenu();
    }

}

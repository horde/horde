<?php
/**
 * Luxor application API.
 *
 * This file defines Luxor's core application definition.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Luxor
 */

/* Determine the base directories. */
if (!defined('LUXOR_BASE')) {
    define('LUXOR_BASE', __DIR__ . '/..');
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
     */
    public $version = 'H5 (1.0-git)';

    /**
     * Global variables defined:
     *   $luxor_shares - TODO
     */
    protected function _init()
    {
        Luxor::initialize();
    }

    /**
     */
    public function menu(Horde_Menu $menu)
    {
        $menu->add(Horde::url('source.php'), _("_Browse"), 'luxor.png');
    }
}

<?php
/**
 * Skeleton application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package IMP
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
     *   $notification - Notification object
     *
     * Global constants defined:
     *   SKELETON_TEMPLATES - (string) Location of template files.
     */
    protected function _init()
    {
        if (!defined('SKELETON_TEMPLATES')) {
            define('SKELETON_TEMPLATES', $GLOBALS['registry']->get('templates'));
        }

        // Notification system.
        $notification = Horde_Notification::singleton();
        $notification->attach('status');
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Skeleton::getMenu();
    }

}

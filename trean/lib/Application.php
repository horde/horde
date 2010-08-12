<?php
/**
 * Trean application API
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

/* Determine the base directories. */
if (!defined('TREAN_BASE')) {
    define('TREAN_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(TREAN_BASE . '/config/horde.local.php')) {
        include TREAN_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', TREAN_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Trean_Application extends Horde_Registry_Application
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
     *   $trean_db     - TODO
     *   $trean_shares - TODO
     */
    protected function _init()
    {
        // Set the timezone variable.
        $GLOBALS['registry']->setTimeZone();

        // Create db and share instances.
        $GLOBALS['trean_db'] = Trean::getDb();
        if ($GLOBALS['trean_db'] instanceof PEAR_Error) {
            throw new Horde_Exception($GLOBALS['trean_db']);
        }
        $GLOBALS['trean_shares'] = new Trean_Bookmarks();

        Trean::initialize();
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms = array();

        $perms['tree']['trean']['max_folders'] = false;
        $perms['title']['trean:max_folders'] = _("Maximum Number of Folders");
        $perms['type']['trean:max_folders'] = 'int';
        $perms['tree']['trean']['max_bookmarks'] = false;
        $perms['title']['trean:max_bookmarks'] = _("Maximum Number of Bookmarks");
        $perms['type']['trean:max_bookmarks'] = 'int';

        return $perms;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Trean::getMenu();
    }
}

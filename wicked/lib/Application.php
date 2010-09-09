<?php
/**
 * Wicked application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Wicked through this API.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Wicked
 */

/* Determine the base directories. */
if (!defined('WICKED_BASE')) {
    define('WICKED_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(WICKED_BASE . '/config/horde.local.php')) {
        include WICKED_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', WICKED_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Wicked_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Wicked initialization.
     *
     * Global variables defined:
     *   $wicked - The Wicked_Driver object.
     */
    protected function _init()
    {
        $GLOBALS['wicked'] = Wicked_Driver::factory();
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms['tree']['wicked']['pages'] = array();
        $perms['title']['wicked:pages'] = _("Pages");

        foreach (array('AllPages', 'LeastPopular', 'MostPopular', 'RecentChanges') as $val) {
            $perms['tree']['wicked']['pages'][$val] = false;
            $perms['title']['wicked:pages:' . $val] = $val;
        }

        $pages = $GLOBALS['wicked']->getPages();
        if (!($pages instanceof PEAR_Error)) {
            foreach ($pages as $pagename) {
                $pageId = $GLOBALS['wicked']->getPageId($pagename);
                $perms['tree']['wicked']['pages'][$pageId] = false;
                $perms['title']['wicked:pages:' . $pageId] = $pagename;
            }
            ksort($perms['tree']['wicked']['pages']);
        }

        return $perms;
    }

}

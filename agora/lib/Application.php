<?php
/**
 * Agora external API interface.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Agora through this API.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Duck <duck@obala.net>
 * @package Agora
 */

/* Determine the base directories. */
if (!defined('AGORA_BASE')) {
    define('AGORA_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(AGORA_BASE . '/config/horde.local.php')) {
        include AGORA_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', AGORA_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Agora_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (1.0-git)';

    /**
     * TODO
     */
    static protected $_perms = array();

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        if (!empty(self::$_perms)) {
            return self::$_perms;
        }

        self::$_perms['tree']['agora']['admin'] = true;
        self::$_perms['title']['agora:admin'] = _("Admin");
        self::$_perms['title']['agora:forums'] = _("Forums");

        foreach ($GLOBALS['registry']->listApps() as $scope) {
            self::$_perms['title']['agora:forums:' . $scope] = $GLOBALS['registry']->get('name', $scope);
            self::$_perms['tree']['agora']['forums'][$scope] = false;

            $forums = &Agora_Messages::singleton($scope);
            $forums_list = $forums->getBareForums();
            if (($forums_list instanceof PEAR_Error) || empty($forums_list)) {
                continue;
            }

            foreach ($forums_list as $id => $title) {
                self::$_perms['tree']['agora']['forums'][$scope][$id] = false;
                self::$_perms['title']['agora:forums:' . $scope . ':' . $id] = $title;
            }

        }

        return self::$_perms;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Agora::getMenu();
    }

}

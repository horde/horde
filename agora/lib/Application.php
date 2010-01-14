<?php
/**
 * Agora external API interface.
 *
 * This file defines Agora's external API interface. Other
 * applications can interact with Agora through this API.
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

class Agora_Application extends Horde_Registry_Application
{
    public $version = 'H4 (1.0-git)';

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        static $perms = array();
        if (!empty($perms)) {
            return $perms;
        }

        require_once dirname(__FILE__) . '/base.php';

        $perms['tree']['agora']['admin'] = true;
        $perms['title']['agora:admin'] = _("Admin");
        $perms['title']['agora:forums'] = _("Forums");

        foreach ($GLOBALS['registry']->listApps() as $scope) {
            $perms['title']['agora:forums:' . $scope] = $GLOBALS['registry']->get('name', $scope);
            $perms['tree']['agora']['forums'][$scope] = false;

            $forums = &Agora_Messages::singleton($scope);
            $forums_list = $forums->getBareForums();
            if (($forums_list instanceof PEAR_Error) || empty($forums_list)) {
                continue;
            }

            foreach ($forums_list as $id => $title) {
                $perms['tree']['agora']['forums'][$scope][$id] = false;
                $perms['title']['agora:forums:' . $scope . ':' . $id] = $title;
            }

        }

        return $perms;
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

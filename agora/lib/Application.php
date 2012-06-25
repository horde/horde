<?php
/**
 * Agora external API interface.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Agora through this API.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Duck <duck@obala.net>
 * @package Agora
 */

/* Determine the base directories. */
if (!defined('AGORA_BASE')) {
    define('AGORA_BASE', __DIR__ . '/..');
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
     */
    public $version = 'H5 (1.0-git)';

    /**
     */
    public function perms()
    {
        $perms = array(
            'admin' => array(
                'title' => _("Admin")
            ),
            'forums' => array(
                'title' => _("Forums")
            )
        );
        foreach ($GLOBALS['registry']->listApps() as $scope) {
            $perms['forums:' . $scope] = array(
                'title' => $GLOBALS['registry']->get('name', $scope)
            );

            $forums = $GLOBALS['injector']->getInstance('Agora_Factory_Driver')->create($scope);
            try {
                $forums_list = $forums->getBareForums();
                foreach ($forums_list as $id => $title) {
                    $perms['forums:' . $scope . ':' . $id] = array(
                        'title' => $title
                    );
                }
            } catch (Horde_Db_Exception $e) {
                throw new Agora_Exception($e->getMessage());
            }
        }

        return $perms;
    }

    /**
     */
    public function menu($menu)
    {
        $scope = Horde_Util::getGet('scope', 'agora');

        /* Agora Home. */
        $url = Horde::url('forums.php')->add('scope', $scope);
        $menu->add($url, _("_Forums"), 'forums.png', null, null, null,
                   dirname($_SERVER['PHP_SELF']) == $GLOBALS['registry']->get('webroot') && basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);

        /* Thread list, if applicable. */
        if (isset($GLOBALS['forum_id'])) {
            $menu->add(Agora::setAgoraId($GLOBALS['forum_id'], null, Horde::url('threads.php')), _("_Threads"), 'threads.png');
            if ($scope == 'agora' && $GLOBALS['registry']->getAuth()) {
                $menu->add(Agora::setAgoraId($GLOBALS['forum_id'], null, Horde::url('messages/edit.php')), _("New Thread"), 'newmessage.png');
            }
        }

        if ($scope == 'agora' &&
            Agora_Driver::hasPermission(Horde_Perms::DELETE, 0, $scope)) {
            $menu->add(Horde::url('editforum.php'), _("_New Forum"), 'newforum.png', null, null, null, Horde_Util::getFormData('agora') ? '__noselection' : null);
        }

        if (Agora_Driver::hasPermission(Horde_Perms::DELETE, 0, $scope)) {
            $url = Horde::url('moderate.php')->add('scope', $scope);
            $menu->add($url, _("_Moderate"), 'moderate.png');
        }

        if ($GLOBALS['registry']->isAdmin()) {
            $menu->add(Horde::url('moderators.php'), _("_Moderators"), 'hot.png');
        }

        $url = Horde::url('search.php')->add('scope', $scope);
        $menu->add($url, _("_Search"), 'search.png');
    }

}

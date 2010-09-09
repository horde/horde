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
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        self::$_perms['tree']['agora']['admin'] = true;
        self::$_perms['title']['agora:admin'] = _("Admin");
        self::$_perms['title']['agora:forums'] = _("Forums");

        foreach ($GLOBALS['registry']->listApps() as $scope) {
            self::$_perms['title']['agora:forums:' . $scope] = $GLOBALS['registry']->get('name', $scope);
            self::$_perms['tree']['agora']['forums'][$scope] = false;

            $forums = Agora_Messages::singleton($scope);
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
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
        $img_dir = Horde_Themes::img();
        $scope = Horde_Util::getGet('scope', 'agora');

        /* Agora Home. */
        $url = Horde::url('forums.php')->add('scope', $scope);
        $menu->add($url, _("_Forums"), 'forums.png', $img_dir, null, null,
                   dirname($_SERVER['PHP_SELF']) == $GLOBALS['registry']->get('webroot') && basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);

        /* Thread list, if applicable. */
        if (isset($GLOBALS['forum_id'])) {
            $menu->add(Agora::setAgoraId($GLOBALS['forum_id'], null, Horde::url('threads.php')), _("_Threads"), 'threads.png', Horde_Themes::img());
            if ($scope == 'agora' && $GLOBALS['registry']->getAuth()) {
                $menu->add(Agora::setAgoraId($GLOBALS['forum_id'], null, Horde::url('messages/edit.php')), _("New Thread"), 'newmessage.png', Horde_Themes::img());
            }
        }

        if ($scope == 'agora' &&
            Agora_Messages::hasPermission(Horde_Perms::DELETE, 0, $scope)) {
            $menu->add(Horde::url('editforum.php'), _("_New Forum"), 'newforum.png', $img_dir, null, null, Horde_Util::getFormData('agora') ? '__noselection' : null);
        }

        if (Agora_Messages::hasPermission(Horde_Perms::DELETE, 0, $scope)) {
            $url = Horde::url('moderate.php')->add('scope', $scope);
            $menu->add($url, _("_Moderate"), 'moderate.png', $img_dir);
        }

        if ($GLOBALS['registry']->isAdmin()) {
            $menu->add(Horde::url('moderators.php'), _("_Moderators"), 'hot.png', $img_dir);
        }

        $url = Horde::url('search.php')->add('scope', $scope);
        $menu->add($url, _("_Search"), 'search.png', Horde_Themes::img(null, 'horde'));
    }

    /**
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        switch ($ui->group) {
        case 'display_avatar':
            $vfs = Agora::getVFS();
            if (!($vfs instanceof PEAR_Error) &&
                $GLOBALS['conf']['avatar']['enable_gallery'] &&
                $vfs->isFolder(Agora::AVATAR_PATH, 'gallery')) {
                Horde::addScriptFile('popup.js', 'horde', true);
            } else {
                $suppress[] = 'avatar_link';
            }
            break;
        }

        /* Hide appropriate prefGroups. */
        if (!$GLOBALS['conf']['avatar']['allow_avatars']) {
            $ui->suppressGroups[] = 'display_avatar';
        }
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the prefs page.
     */
    public function prefsSpecial($ui, $item)
    {
        switch ($item) {
        case 'avatarselect':
            return $this->_accountsManagement($ui);
        }

        return '';
    }

}

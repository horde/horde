<?php
/**
 * Gollem application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Amith Varghese <amith@xalan.com>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Ben Klang <bklang@alkaloid.net>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Gollem
 */

/* Determine the base directories. */
if (!defined('GOLLEM_BASE')) {
    define('GOLLEM_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(GOLLEM_BASE . '/config/horde.local.php')) {
        include GOLLEM_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', GOLLEM_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 *  Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Gollem_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Gollem initialization.
     *
     * Global variables defined:
     *   $gollem_backends - A link to the current list of available backends
     *   $gollem_be - A link to the current backend parameters in the session
     *   $gollem_vfs - A link to the current VFS object for the active backend
     */
    protected function _init()
    {
        // Set the global $gollem_be variable to the current backend's
        // parameters.
        $backend_key = $GLOBALS['session']->get('gollem', 'backend_key');
        $GLOBALS['gollem_be'] = $backend_key
            ? $GLOBALS['session']->get('gollem', 'backends/' . $backend_key)
            : null;

        // Load the backend list.
        Gollem::loadBackendList();
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms = array(
            'backends' => array(
                'title' => _("Backends")
            )
        );

        // Run through every backend.
        require GOLLEM_BASE . '/config/backends.php';
        foreach ($backends as $key => $val) {
            $perms['backends:' . $key] = array(
                'title' => $val['name']
            );
        }

        return $perms;
    }

    /**
     * Determine active prefs when displaying a group.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsGroup($ui)
    {
        foreach ($ui->getChangeablePrefs() as $val) {
            switch ($val) {
            case 'columns':
                Horde_Core_Prefs_Ui_Widgets::sourceInit();
                break;
            }
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
        case 'columnselect':
            $cols = json_decode($GLOBALS['prefs']->getValue('columns'));
            $sources = array();

            foreach ($GLOBALS['gollem_backends'] as $source => $info) {
                $selected = $unselected = array();
                $selected_list = isset($cols[$source])
                    ? array_flip($cols[$source])
                    : array();

                foreach ($info['attributes'] as $column) {
                    if (isset($selected_list[$column])) {
                        $selected[] = array($column, $column);
                    } else {
                        $unselected[] = array($column, $column);
                    }
                }
                $sources[$source] = array(
                    'selected' => $selected,
                    'unselected' => $unselected,
                );
            }

            return Horde_Core_Prefs_Ui_Widgets::source(array(
                'mainlabel' => _("Choose which backends to display, and in what order:"),
                'selectlabel' => _("These backends will display in this order:"),
                'sourcelabel' => _("Select a backend:"),
                'sources' => $sources,
                'unselectlabel' => _("Backends that will not be displayed:")
            ));
        }

        return '';
    }

    /**
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
        $menu->add(Horde::url('manager.php')->add('dir', Gollem::getHome()), _("_My Home"), 'folder_home.png');

        if ($GLOBALS['registry']->isAdmin()) {
            $menu->add(Horde::url('permissions.php')->add('backend', $backend_key), _("_Permissions"), 'perms.png');
        }

        if ($GLOBALS['gollem_be']['quota_val'] != -1) {
            if ($GLOBALS['browser']->hasFeature('javascript')) {
                $quota_url = 'javascript:' . Horde::popupJs(Horde::url('quota.php'), array('params' => array('backend' => $backend_key), 'height' => 300, 'width' => 300, 'urlencode' => true));
            } else {
                $quota_url = Horde::url('quota.php')->add('backend', $backend_key);
            }
            $menu->add($quota_url, _("Check Quota"), 'info_icon.png');
        }
    }

    /* Sidebar method. */

    /**
     * Add node(s) to the sidebar tree.
     *
     * @param Horde_Tree_Base $tree  Tree object.
     * @param string $parent         The current parent element.
     * @param array $params          Additional parameters.
     *
     * @throws Horde_Exception
     */
    public function sidebarCreate(Horde_Tree_Base $tree, $parent = null,
                                  array $params = array())
    {
        // TODO
        return;

        $login_url = Horde::url('login.php');

        foreach ($GLOBALS['gollem_backends'] as $key => $val) {
            if (Gollem::checkPermissions('backend', Horde_Perms::SHOW, $key)) {
                $tree->addNode(
                    $parent . $key,
                    $parent,
                    $val['name'],
                    1,
                    false,
                    array(
                        'icon' => Horde_Themes::img('gollem.png'),
                        'url' => $login_url->copy()->add(array('backend_key' => $key, 'change_backend' => 1))
                    )
                );
            }
        }
    }

}

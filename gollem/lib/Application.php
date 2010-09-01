<?php
/**
 * Gollem application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Amith Varghese <amith@xalan.com>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Ben Klang <bklang@alkaloid.net>
 * @package Gollem
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
     * Permissions cache.
     *
     * @var array
     */
    protected $_permsCache = array();

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
        $GLOBALS['gollem_be'] = empty($_SESSION['gollem']['backend_key'])
            ? null
            : $_SESSION['gollem']['backends'][$_SESSION['gollem']['backend_key']];

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
        if (!empty($this->_permsCache)) {
            return $this->_permsCache;
        }

        $perms['tree']['gollem']['backends'] = false;
        $perms['title']['gollem:backends'] = _("Backends");

        // Run through every backend.
        require GOLLEM_BASE . '/config/backends.php';
        foreach ($backends as $key => $val) {
            $perms['tree']['gollem']['backends'][$key] = false;
            $perms['title']['gollem:backends:' . $key] = $val['name'];
        }

        $this->_permsCache = $perms;

        return $perms;
    }

    /**
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        global $prefs;

        switch ($ui->group) {
        case 'display':
            if (!$prefs->isLocked('columns')) {
                Horde_Core_Prefs_Ui_Widgets::sourceInit();
            }
            break;
        }
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the options page.
     */
    public function prefsSpecial($ui, $item)
    {
        switch ($item) {
        case 'columnselect':
            $cols = Gollem::displayColumns();
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
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        switch ($item) {
        case 'columnselect':
            if (isset($ui->vars->sources)) {
                $pref = array();
                foreach (Horde_Serialize::unserialize($ui->vars->sources, Horde_Serialize::JSON) as $val) {
                    $pref[] = implode("\t", array_merge($val[0], $val[1]));
                }
                $GLOBALS['prefs']->setValue('columns', implode("\n", $pref));
                return true;
            }
            break;
        }

        return false;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Gollem::getMenu();
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

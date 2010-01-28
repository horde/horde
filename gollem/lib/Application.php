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
 * @author  Amith Varghese (amith@xalan.com)
 * @author  Michael Slusarz (slusarz@curecanti.org)
 * @author  Ben Klang (bklang@alkaloid.net)
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
     * Perms cache.
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
     * Special preferences handling on update.
     *
     * @param string $item      The preference name.
     * @param boolean $updated  Set to true if preference was updated.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecial($item, $updated)
    {
        switch ($item) {
        case 'columnselect':
            $columns = Horde_Util::getFormData('columns');
            if (!empty($columns)) {
                $GLOBALS['prefs']->setValue('columns', $columns);
                return true;
            }
            break;
        }

        return $updated;
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

}

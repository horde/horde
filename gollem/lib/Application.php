<?php
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

/**
 * Gollem application API.
 *
 * This file defines Gollem's external API interface. Other
 * applications can interact with Gollem through this API.
 *
 * @author  Amith Varghese (amith@xalan.com)
 * @author  Michael Slusarz (slusarz@curecanti.org)
 * @author  Ben Klang (bklang@alkaloid.net)
 * @package Gollem
 */
class Gollem_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * The auth type to use.
     *
     * @var string
     */
    static public $authType = null;

    /**
     * Disable compression of pages?
     *
     * @var boolean
     */
    static public $noCompress = false;

    /**
     * Constructor.

     * @param array $args  The following entries:
     * <pre>
     * 'init' - (boolean|array) If true, perform application init. If an
     *          array, perform application init and pass the array to init().
     * </pre>
     */
    public function __construct($args = array())
    {
        if (!empty($args['init'])) {
            $this->init(is_array($args['init']) ? $args['init'] : array());
        }
    }

    /**
     * Gollem base initialization.
     *
     * Global variables defined:
     *   $gollem_backends - A link to the current list of available backends
     *   $gollem_be - A link to the current backend parameters in the session
     *   $gollem_vfs - A link to the current VFS object for the active backend
     *
     * @param array $args  Optional arguments:
     * <pre>
     * 'authentication' - (string) The type of authentication to use:
     *   'horde' - Only use horde authentication
     *   'none'  - Do not authenticate
     *   [DEFAULT] - Authenticate to backend; on no auth redirect to
     *               login screen
     * 'nocompress' - (boolean) Controls whether the page should be
     *                compressed.
     * 'session_control' - (string) Sets special session control limitations:
     *   'readonly' - Start session readonly
     *   [DEFAULT] - Start read/write session
     * </pre>
     */
    public function init($args = array())
    {
        $args = array_merge(array(
            'authentication' => null,
            'nocompress' => false,
            'session_control' => null
        ), $args);

        self::$authType = $args['authentication'];
        self::$noCompress = $args['nocompress'];

        // Registry.
        $s_ctrl = 0;
        switch ($args['session_control']) {
        case 'readonly':
            $s_ctrl = Horde_Registry::SESSION_READONLY;
            break;
        }

        $GLOBALS['registry'] = Horde_Registry::singleton($s_ctrl);

        try {
            $GLOBALS['registry']->pushApp('gollem', array('check_perms' => ($args['authentication'] != 'none'), 'logintasks' => true));
        } catch (Horde_Exception $e) {
            Horde_Auth::authenticateFailure('gollem', $e);
        }

        if (!defined('GOLLEM_TEMPLATES')) {
            define('GOLLEM_TEMPLATES', $GLOBALS['registry']->get('templates'));
        }

        // Notification system.
        $notification = Horde_Notification::singleton();
        $notification->attach('status');

        // Start compression.
        if (!self::$noCompress) {
            Horde::compressOutput();
        }

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
        static $perms = array();
        if (!empty($perms)) {
            return $perms;
        }

        require_once dirname(__FILE__) . '/base.load.php';
        require GOLLEM_BASE . '/config/backends.php';

        $perms['tree']['gollem']['backends'] = false;
        $perms['title']['gollem:backends'] = _("Backends");

        // Run through every backend.
        foreach ($backends as $backend => $curBackend) {
            $perms['tree']['gollem']['backends'][$backend] = false;
            $perms['title']['gollem:backends:' . $backend] = $curBackend['name'];
        }

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

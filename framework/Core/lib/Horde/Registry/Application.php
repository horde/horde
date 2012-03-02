<?php
/**
 * Default class for the Horde Application API.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Registry_Application
{
    /**
     * The list of available authentication capabilities handled by this
     * application.
     * The full capability list can be found in Horde_Core_Auth_Application.
     *
     * @var array
     */
    public $auth = array();

    /**
     * List of features supported by this application.
     *
     * @var array
     */
    public $features = array(
        // View Handlers
        'dynamicView' => false,
        'minimalView' => false,
        'smartmobileView' => false,
        // Notification Handler
        'notificationHandler' => false,
        // Alarm Handler
        'alarmHandler' => false
    );

    /**
     * The init params used.
     *
     * @var array
     */
    public $initParams = array();

    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'unknown';

    /**
     * Application identifier.
     *
     * @var string
     */
    protected $_app;

    /**
     * Constructor.
     *
     * Global constants defined:
     *   - [APPNAME]_TEMPLATES - (string) Location of template files.
     *
     * @param string $app  Application identifier.
     */
    final public function __construct($app)
    {
        $this->_app = $app;

        $appname = Horde_String::upper($app);
        if (!defined($appname . '_TEMPLATES')) {
            define($appname . '_TEMPLATES', $GLOBALS['registry']->get('templates', $app));
        }

        $this->_bootstrap();
    }

    /**
     * Application-specific code to run if application auth fails.
     * Called from Horde_Registry::appInit().
     *
     * @param Horde_Exception $e  The exception object.
     */
    public function appInitFailure($e)
    {
    }

    /* Initialization methods. */

    /**
     * Bootstrap code for an application. This is run when the application
     * object is being created. The full Horde environment is not available in
     * this method, and the user may not yet be authenticated. Only tasks
     * necessary to setup the base application environment should be done here.
     */
    protected function _bootstrap()
    {
    }

    /**
     * Code to run on successful authentication. This will be called once
     * per session, and the entire Horde framework will be available.
     *
     * @throws Horde_Exception
     */
    public function authenticated()
    {
    }

    /**
     * Code run when the application is pushed on the stack for the first
     * time in a page access. The entire Horde framework will be available,
     * but the user may not be authenticated.
     *
     * @throws Horde_Exception
     */
    public function init()
    {
    }

    /* Menu generation methods. */

    /**
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
    }


    // Functions called from Horde's API.

    /**
     * Tasks to perform at logout.
     */
    public function logout()
    {
    }

    /**
     * Removes user data.
     *
     * @param string $user  Name of user to remove data for.
     *
     * @throws Horde_Exception
     */
    public function removeUserData($user)
    {
    }

    // Horde permissions.

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        return array();
    }

    /**
     * Returns the specified permission for the given app permission.
     *
     * @param string $permission  The permission to check.
     * @param mixed $allowed      The allowed permissions.
     * @param array $opts         Additional options ('value').
     *
     * @return mixed  The value of the specified permission.
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        return true;
    }


    // Horde_Notification methods.

    /**
     * Modifies the global notification handler.
     *
     * @param Horde_Notification_Handler $handler  A notification handler.
     */
    public function setupNotification(Horde_Notification_Handler $handler)
    {
    }


    // Horde_Alarm methods.

    /**
     * Lists alarms for a given moment.
     *
     * @param integer $time  The time to retrieve alarms for.
     * @param string $user   The user to retreive alarms for. All users if
     *                       null.
     *
     * @return array  An array of UIDs.
     */
    public function listAlarms($time, $user = null)
    {
        return array();
    }


    // Horde_Core_Auth_Application methods.

    /**
     * Return login parameters used on the login page.
     *
     * @return array  See Horde_Core_Auth_Application#authLoginParams().
     */
    public function authLoginParams()
    {
        return array(
            'js_code' => array(),
            'js_files' => array(),
            'params' => array()
        );
    }

    /**
     * Tries to authenticate with the server and create a session.
     *
     * @param string $userId      The username of the user.
     * @param array $credentials  Credentials of the user.
     *
     * @throws Horde_Auth_Exception
     */
    public function authAuthenticate($userId, $credentials)
    {
        throw new Horde_Auth_Exception('Authentication failed.');
    }

    /**
     * Tries to transparently authenticate with the server and create a
     * session.
     *
     * @param Horde_Core_Auth_Application $auth_ob  The authentication object.
     *
     * @return boolean  Whether transparent login is supported.
     * @throws Horde_Auth_Exception
     */
    public function authTransparent($auth_ob)
    {
        return false;
    }

    /**
     * Validates an existing authentication.
     *
     * @since Horde_Core 1.4.0
     *
     * @return boolean  Whether the authentication is still valid.
     */
    public function authValidate()
    {
        return false;
    }

    /**
     * Adds a user defined by authentication credentials.
     *
     * @param string $userId      The user ID to add.
     * @param array $credentials  An array of login credentials.
     *
     * @throws Horde_Auth_Exception
     */
    public function authAddUser($userId, $credentials)
    {
    }

    /**
     * Update an existing user's credentials.
     *
     * @param string $oldId       The old user ID.
     * @param string $newId       The new user ID.
     * @param array $credentials  The new login credentials.
     *
     * @throws Horde_Auth_Exception
     */
    public function authUpdateUser($oldId, $newId, $credentials)
    {
    }

    /**
     * Deletes a user defined by authentication credentials.
     *
     * @param string $userId  The user ID to delete.
     *
     * @throws Horde_Auth_Exception
     */
    public function authRemoveUser($userId)
    {
    }

    /**
     * Does a user exist?
     *
     * @param string $userId  The user ID to check.
     *
     * @return boolean  True if the user exists.
     */
    public function authUserExists($userId)
    {
        return false;
    }

    /**
     * Lists all users in the system.
     *
     * @return array  The array of user IDs.
     * @throws Horde_Auth_Exception
     */
    public function authUserList()
    {
        return array();
    }

    /**
     * Reset a user's password.
     *
     * @param string $userId  The user id for which to reset the password.
     *
     * @return string  The new password.
     * @throws Horde_Auth_Exception
     */
    public function authResetPassword($userId)
    {
        return '';
    }


    // Horde_Core_Prefs_Ui functions.

    /**
     * Run on init when viewing prefs for an application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
    }

    /**
     * Determine active prefs when displaying a group. This is where all
     * suppress/overrides should be defined.
     *
     * This function may be run multiple times in a single page - once on init
     * and once after prefs are updated.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsGroup($ui)
    {
    }

    /**
     * Called when preferences are changed.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsCallback($ui)
    {
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the preferences page.
     */
    public function prefsSpecial($ui, $item)
    {
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
        return false;
    }


    // Horde_Config functions.

    /**
     * Returns values for <configspecial> configuration settings.
     *
     * @param string $what  The configuration setting to return.
     *
     * @return array  The values for the requested configuration setting.
     */
    public function configSpecialValues($what)
    {
        return array();
    }


    // Horde_Core_Sidebar method.

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
                                  array $params = array()) {}


    // Language change callback.

    /**
     * Code to run if the language preference changes.
     *
     * Called only in applications the user is currently authenticated to in
     * the current session.
     */
    public function changeLanguage()
    {
    }

}

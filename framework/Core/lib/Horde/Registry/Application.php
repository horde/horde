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
     * Cached values to add to the session after authentication.
     *
     * @var array
     */
    protected $_sessVars = array();

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
     * Code run on successful authentication.
     */
    final public function authenticated()
    {
        $this->updateSessVars();
        $this->_authenticated();
    }

    /**
     * Code run when the application is pushed on the stack for the first
     * time in a page access.
     */
    final public function init()
    {
        $this->_init();
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
    protected function _authenticated()
    {
    }

    /**
     * Code run when the application is pushed on the stack for the first
     * time in a page access. The entire Horde framework will be available,
     * but the user may not be authenticated.
     *
     * @throws Horde_Exception
     */
    protected function _init()
    {
    }

    /**
     * Application-specific code to run if application auth fails.
     * Called from Horde_Registry::appInit().
     *
     * @param Horde_Exception_PushApp $e  The exception object.
     */
    public function appInitFailure($e)
    {
    }


    // Menu generation methods.

    /**
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
    }

    /**
     * Add additional items to the sidebar.
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
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


    // Horde service methods.

    /**
     * Prepare data to deliver to browser for download.
     *
     * @param Horde_Variables $vars  Form variables provided to download
     *                               script. The filename is available in
     *                               the 'filename' parameter.
     *
     * @return array  Download data:
     *   - data: [REQUIRED] (mixed) Data. Either a stream or a string.
     *   - name: (string) Filename that overrides 'filename' URL parameter.
     *   - size: (integer) If set, used as size. If null, no size will be
     *           sent to browser. If not set, size will be automatically
     *           determined from data.
     *   - type: (string) MIME type to send (default:
     *           application/octet-stream).
     */
    public function download(Horde_Variables $vars)
    {
        return array();
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
     * Any session variables you want added should be set by calling
     * _addSessVars() internally within this method.
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
     * Any session variables you want added should be set by calling
     * _addSessVars() internally within this method.
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

    /**
     * Add session variables to the session.
     *
     * @param array $vars  Array of session variables to add to the session,
     *                     once it becomes available.
     */
    final protected function _addSessVars($vars)
    {
        if (!empty($vars)) {
            $this->_sessVars = array_merge($this->_sessVars, $vars);
            register_shutdown_function(array($this, 'updateSessVars'));
        }
    }

    /**
     * Updates cached session variable information into the active session.
     */
    final public function updateSessVars()
    {
        foreach ($this->_sessVars as $key => $val) {
            $GLOBALS['session']->set($this->_app, $key, $val);
        }
        $this->_sessVars = array();
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


    // Horde_Core_Topbar method.

    /**
     * Add node(s) to the sidebar tree.
     *
     * @param Horde_Tree_Renderer_Base $tree  Tree object.
     * @param string $parent         The current parent element.
     * @param array $params          Additional parameters.
     *
     * @throws Horde_Exception
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
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

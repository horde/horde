<?php
/**
 * Default class for the Horde Application API.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Registry_Application
{
    /**
     * Does this application support an ajax view?
     *
     * @var boolean
     */
    public $ajaxView = false;

    /**
     * The list of available authentication capabilities handled by this
     * application.
     * The full capability list can be found in Horde_Core_Auth_Application.
     *
     * @var array
     */
    public $auth = array();

    /**
     * The init params used.
     *
     * @var array
     */
    public $initParams = array();

    /**
     * Does this application support a mobile view?
     *
     * @var boolean
     */
    public $mobileView = false;

    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'unknown';

    /**
     * Has init() previously been called?
     *
     * @var boolean
     */
    protected $_initDone = false;

    /**
     * Application-specific code to run if application auth fails.
     * Called from Horde_Registry::appInit().
     *
     * @param Horde_Exception $e  The exception object.
     */
    public function appInitFailure($e)
    {
    }

    /**
     * Initialization. Does any necessary init needed to setup the full
     * environment for the application.
     *
     * Global constants defined:
     * <pre>
     * [APPNAME]_TEMPLATES - (string) Location of template files.
     * </pre>
     */
    final public function init()
    {
        if (!$this->_initDone) {
            $this->_initDone = true;

            $appname = Horde_String::upper($GLOBALS['registry']->getApp());
            if (!defined($appname . '_TEMPLATES')) {
                define($appname . '_TEMPLATES', $GLOBALS['registry']->get('templates'));
            }

            $this->_init();
        }
    }

    /**
     * Initialization code for an application.
     */
    protected function _init()
    {
    }

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
     * Does necessary authentication tasks reliant on a full app environment.
     *
     * @throws Horde_Auth_Exception
     */
    public function authAuthenticateCallback()
    {
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

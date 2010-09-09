<?php
/**
 * Default class for the Horde Application API.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
     * The list of disabled API calls.
     *
     * @var array
     */
    public $disabled = array();

    /**
     * The init params used.
     *
     * @var array
     */
    public $initParams = array();

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
    public function init()
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
     * Initialization code for an application should be defined in this
     * function.
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
     * Removes user data.
     *
     * @param string $user  Name of user to remove data for.
     *
     * @throws Horde_Exception
     */
    // public function removeUserData($user) {}


    // Horde permissions.

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    // public function perms() {}

    /**
     * Returns the specified permission for the given app permission.
     *
     * @param string $permission  The permission to check.
     * @param mixed $allowed      The allowed permissions.
     * @param array $opts         Additional options ('value').
     *
     * @return mixed  The value of the specified permission.
     */
    // public function hasPermission($permission, $allowed, $opts = array()) {}


    // Horde_Core_Auth_Application methods.

    /**
     * Return login parameters used on the login page.
     *
     * @return array  TODO
     */
    // public function authLoginParams()

    /**
     * Tries to authenticate with the server and create a session.
     *
     * @param string $userId      The username of the user.
     * @param array $credentials  Credentials of the user.
     *
     * @throws Horde_Auth_Exception
     */
    // public function authAuthenticate($userId, $credentials) {}

    /**
     * Tries to transparently authenticate with the server and create a
     * session.
     *
     * @param Horde_Core_Auth_Application $auth_ob  The authentication object.
     *
     * @return boolean  Whether transparent login is supported.
     * @throws Horde_Auth_Exception
     */
    // public function authTransparent($auth_ob) {}

    /**
     * Does necessary authentication tasks reliant on a full app environment.
     *
     * @throws Horde_Auth_Exception
     */
    // public function authAuthenticateCallback() {}

    /**
     * Adds a user defined by authentication credentials.
     *
     * @param string $userId      The userId to add.
     * @param array $credentials  An array of login credentials.
     *
     * @throws Horde_Auth_Exception
     */
    // public function authAddUser($userId, $credentials) {}

    /**
     * Deletes a user defined by authentication credentials.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Auth_Exception
     */
    // public function authRemoveUser($userId) {}

    /**
     * Lists all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    // public function authUserList() {}


    // Horde_Core_Prefs_Ui functions.

    /**
     * Code to run if the language preference changes.
     */
    // public function changeLanguage() {}

    /**
     * Populate dynamically-generated preference values.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    // public function prefsEnum($ui) {}

    /**
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    // public function prefsInit($ui) {}

    /**
     * Called when preferences are changed.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    // public function prefsCallback($ui) {}

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the preferences page.
     */
    // public function prefsSpecial($ui, $item) {}

    /**
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
     */
    // public function prefsSpecialUpdate($ui, $item) {}


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
    // public function sidebarCreate(Horde_Tree_Base $tree, $parent = null,
    //                               array $params = array()) {}


    // Language change callback.

    /**
     * Performs tasks necessary when the language is changed during the
     * session.
     */
    // public function changeLanguage() {}

}

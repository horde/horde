<?php
/**
 * Horde external API interface.
 *
 * This file defines Horde's external API interface. Other
 * applications can interact with Horde through this API.
 *
 * @package Horde
 */
class Horde_Api extends Horde_Registry_Api
{
    /**
     * Returns a list of adminstrative links
     */
    public function admin_list()
    {
        return array(
            'configuration' => array(
                'link' => '%application%/admin/setup/',
                'name' => _("_Setup"),
                'icon' => 'config.png'
            ),
            'users' => array(
                'link' => '%application%/admin/user.php',
                'name' => _("_Users"),
                'icon' => 'user.png'
            ),
            'groups' => array(
                'link' => '%application%/admin/groups.php',
                'name' => _("_Groups"),
                'icon' => 'group.png'
            ),
            'perms' => array(
                'link' => '%application%/admin/perms/index.php',
                'name' => _("_Permissions"),
                'icon' => 'perms.png'
            ),
            'alarms' => array(
                'link' => '%application%/admin/alarms.php',
                'name' => _("_Alarms"),
                'icon' => 'alerts/alarm.png'
            ),
            'datatree' => array(
                'link' => '%application%/admin/datatree.php',
                'name' => _("_DataTree"),
                'icon' => 'datatree.png'
            ),
            'sessions' => array(
                'link' => '%application%/admin/sessions.php',
                'name' => _("Sessions"),
                'icon' => 'user.png'
            ),
            'phpshell' => array(
                'link' => '%application%/admin/phpshell.php',
                'name' => _("P_HP Shell"),
                'icon' => 'mime/php.png'
            ),
            'sqlshell' => array(
                'link' => '%application%/admin/sqlshell.php',
                'name' => _("S_QL Shell"),
                'icon' => 'sql.png'
            ),
            'cmdshell' => array(
                'link' => '%application%/admin/cmdshell.php',
                'name' => _("_CLI"),
                'icon' => 'shell.png'
            )
        );
    }

    /**
     * Returns a list of the installed and registered applications.
     *
     * @param array $filter  An array of the statuses that should be returned.
     *                       Defaults to non-hidden.
     *
     * @return array  List of apps registered with Horde. If no applications are
     *                defined returns an empty array.
     */
    public function listApps($filter = null)
    {
        return $GLOBALS['registry']->listApps($filter);
    }

    /**
     * Returns all available registry APIs.
     *
     * @return array  The API list.
     */
    public function listAPIs()
    {
        return $GLOBALS['registry']->listAPIs();
    }

    /* Blocks. */

    /**
     * Returns a Horde_Block's title.
     *
     * @param string $app    Block application.
     * @param string $name   Block name.
     * @param array $params  Block parameters.
     *
     * @return string  The block title.
     */
    public function blockTitle($app, $name, $params = array())
    {
        try {
            $block = Horde_Block_Collection::getBlock($app, $name, $params);
            return $block->getTitle();
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Returns a Horde_Block's content.
     *
     * @param string $app    Block application.
     * @param string $name   Block name.
     * @param array $params  Block parameters.
     *
     * @return string  The block content.
     */
    public function blockContent($app, $name, $params = array())
    {
        try {
            $block = Horde_Block_Collection::getBlock($app, $name, $params);
            return $block->getContent();
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Returns a pretty printed list of all available blocks.
     *
     * @return array  A hash with block IDs as keys and application plus block
     *                block names as values.
     */
    public function blocks()
    {
        return Horde_Block_Collection::singleton()->getBlocksList();
    }

    /* User data. */

    /**
     * Returns the value of the requested preference.
     *
     * @param string $app   The application of the preference to retrieve.
     * @param string $pref  The name of the preference to retrieve.
     *
     * @return string  The value of the preference, null if it doesn't exist.
     */
    public function getPreference($app, $pref)
    {
        $GLOBALS['registry']->loadPrefs($app);
        return $GLOBALS['prefs']->getValue($pref);
    }

    /**
     * Sets a preference to the specified value, if the preference is allowed to
     * be modified.
     *
     * @param string $app   The application of the preference to modify.
     * @param string $pref  The name of the preference to modify.
     * @param string $val   The new value for this preference.
     */
    public function setPreference($app, $pref, $value)
    {
        $GLOBALS['registry']->loadPrefs($app);
        return $GLOBALS['prefs']->setValue($pref, $value);
    }

    /**
     * Removes user data.
     *
     * @param string $user  Name of user to remove data for.
     */
    public function removeUserData($user)
    {
        if (!Horde_Auth::isAdmin() && $user != Horde_Auth::getAuth()) {
            return PEAR::raiseError(_("You are not allowed to remove user data."));
        }

        global $conf;

        /* Error flag */
        $haveError = false;

        /* Remove user's prefs */
        $prefs = Horde_Prefs::singleton($conf['prefs']['driver'], null, $user);
        if (is_a($result = $prefs->clear(), 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            $haveError = true;
        }

        /* Remove user from all groups */
        require_once 'Horde/Group.php';
        $groups = Group::singleton();
        $allGroups = $groups->getGroupMemberships($user);
        if (is_a($groups, 'PEAR_Error')) {
            Horde::logMessage($allGroups, 'ERR');
            $haveError = true;
        } else {
            foreach (array_keys($allGroups) as $id) {
                $group = $groups->getGroupById($id);
                $group->removeUser($user, true);
            }
        }

        /* Remove the user from all application permissions */
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        $tree = $perms->getTree();
        if (is_a($tree, 'PEAR_Error')) {
            Horde::logMessage($tree, 'ERR');
            $haveError = true;
        } else {
            foreach (array_keys($tree) as $id) {
                $perm = $perms->getPermissionById($id);
                if (is_a($perm, 'PEAR_Error')) {
                    Horde::logMessage($perm, 'ERR');
                    $haveError = true;
                    continue;
                }
                if ($perms->getPermissions($perm, $user)) {
                    // The Horde_Perms::ALL is used if this is a matrix perm,
                    // otherwise it's ignored in the method and the entry is
                    // totally removed.
                    $perm->removeUserPermission($user, Horde_Perms::ALL, true);
                }
            }
        }

        if (!$haveError) {
            return true;
        } else {
            return PEAR::raiseError(sprintf(_("There was an error removing global data for %s. Details have been logged."), $user));
        }
    }

    /**
     * Removes user data from all applications.
     *
     * @param string $user  Name of user to remove data for.
     */
    public function removeUserDataFromAllApplications($user)
    {
        if (!Auth::isAdmin() && $user != Auth::getAuth()) {
            return PEAR::raiseError(_("You are not allowed to remove user data."));
        }

        /* Error flag */
        $haveError = false;

        /* Get all APIs */
        $apis = $this->listAPIs();
        if (is_a($apis, 'PEAR_Error')) {
            Horde::logMessage($apis, 'ERR');
            return PEAR::raiseError(_("No API found."));
        }
        foreach ($apis as $api) {
            if ($GLOBALS['registry']->hasAppMethod($api, 'removeUserData')) {
                $result = $GLOBALS['registry']->callAppMethod($api, 'removeUserData', array('args' => array($user)));
                if (is_a($result, 'PEAR_Error')) {
                    Horde::logMessage($result, 'ERR');
                    $haveError = true;
                }
            }
        }
        $result = $this->removeUserData($user);
        if (is_a($result, 'PEAR_Error')) {
            $haveError = true;
        }

        if (!$haveError) {
            return true;
        } else {
            return PEAR::raiseError(sprintf(_("There was an error removing global data for %s. Details have been logged."), $user));
        }
    }

    /* Groups. */

    /**
     * Adds a group to the groups system.
     *
     * @param string $name    The group's name.
     * @param string $parent  The group's parent's name.
     */
    public function addGroup($name, $parent = null)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to add groups."));
        }

        require_once 'Horde/Group.php';
        $groups = Group::singleton();

        if (empty($parent)) {
            $parent = GROUP_ROOT;
        }

        if (is_a($group = &$groups->newGroup($name, $parent), 'PEAR_Error')) {
            return $group;
        }

        if (is_a($result = $groups->addGroup($group), 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Removes a group from the groups system.
     *
     * @param string $name  The group's name.
     */
    public function removeGroup($name)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to delete groups."));
        }

        require_once 'Horde/Group.php';
        $groups = Group::singleton();

        if (is_a($group = &$groups->getGroup($name), 'PEAR_Error')) {
            return $group;
        }

        if (is_a($result = $groups->removeGroup($group, true), 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Adds a user to a group.
     *
     * @param string $name  The group's name.
     * @param string $user  The user to add.
     */
    public function addUserToGroup($name, $user)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to change groups."));
        }

        require_once 'Horde/Group.php';
        $groups = Group::singleton();

        if (is_a($group = &$groups->getGroup($name), 'PEAR_Error')) {
            return $group;
        }

        if (is_a($result = $group->addUser($user), 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Adds multiple users to a group.
     *
     * @param string $name  The group's name.
     * @param array $users  The users to add.
     */
    public function addUsersToGroup($name, $users)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to change groups."));
        }

        require_once 'Horde/Group.php';
        $groups = Group::singleton();

        if (is_a($group = &$groups->getGroup($name), 'PEAR_Error')) {
            return $group;
        }

        foreach ($users as $user) {
            $group->addUser($user, false);
        }

        if (is_a($result = $group->save(), 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Removes a user from a group.
     *
     * @param string $name  The group's name.
     * @param string $user  The user to add.
     */
    public function removeUserFromGroup($name, $user)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to change groups."));
        }

        require_once 'Horde/Group.php';
        $groups = Group::singleton();

        if (is_a($group = &$groups->getGroup($name), 'PEAR_Error')) {
            return $group;
        }

        if (is_a($result = $group->removeUser($user), 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Removes multiple users from a group.
     *
     * @param string $name  The group's name.
     * @param array $users  The users to add.
     */
    public function removeUsersFromGroup($name, $users)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to change groups."));
        }

        require_once 'Horde/Group.php';
        $groups = Group::singleton();

        if (is_a($group = &$groups->getGroup($name), 'PEAR_Error')) {
            return $group;
        }

        foreach ($users as $user) {
            if (is_a($result = $group->removeUser($user, false), 'PEAR_Error')) {
                return $result;
            }
        }

        if (is_a($result = $group->save(), 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Returns a list of users that are part of this group (and only this group)
     *
     * @param string $name  The group's name.
     *
     * @return array  The user list.
     */
    public function listUsersOfGroup($name)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to list users of groups."));
        }

        require_once 'Horde/Group.php';
        $groups = Group::singleton();

        if (is_a($group = &$groups->getGroup($name), 'PEAR_Error')) {
            return $group;
        }

        return $group->listUsers();
    }

    /* Shares. */

    /**
     * Adds a share to the shares system.
     *
     * @param string $shareRoot   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $shareTitle  The share's human readable title.
     * @param string $userName    The share's owner.
     */
    public function addShare($shareRoot, $shareName, $shareTitle, $userName)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to add shares."));
        }

        $shares = Horde_Share::singleton($shareRoot);

        if (is_a($share = &$shares->newShare($shareName), 'PEAR_Error')) {
            return $share;
        }
        $share->set('owner', $userName);
        $share->set('name', $shareTitle);

        if (is_a($result = $shares->addShare($share), 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Removes a share from the shares system permanently.
     *
     * @param string $shareRoot  The name of the share root, e.g. the
     *                           application that the share belongs to.
     * @param string $shareName  The share's name.
     */
    public function removeShare($shareRoot, $shareName)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to delete shares."));
        }

        $shares = Horde_Share::singleton($shareRoot);

        if (is_a($share = &$shares->getShare($shareName), 'PEAR_Error')) {
            return $share;
        }

        if (is_a($result = $shares->removeShare($share), 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Returns an array of all shares that $userName is the owner of.
     *
     * @param string $shareRoot  The name of the share root, e.g. the
     *                           application that the share belongs to.
     * @param string $userName   The share's owner.
     *
     * @return array  The list of shares.
     */
    public function listSharesOfOwner($shareRoot, $userName)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to list shares."));
        }

        $shares = Horde_Share::singleton($shareRoot);

        $share_list = &$shares->listShares($userName, Horde_Perms::SHOW, $userName);
        $myshares = array();
        foreach ($share_list as $share) {
            $myshares[] = $share->getName();
        }

        return $myshares;
    }

    /**
     * Gives a user certain privileges for a share.
     *
     * @param string $shareRoot   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $userName    The user's name.
     * @param array $permissions  A list of permissions (show, read, edit, delete).
     */
    public function addUserPermissions($shareRoot, $shareName, $userName,
        $permissions)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to change shares."));
        }

        $shares = Horde_Share::singleton($shareRoot);

        if (is_a($share = &$shares->getShare($shareName), 'PEAR_Error')) {
            return $share;
        }

        $perm = &$share->getPermission();
        foreach ($permissions as $permission) {
            $permission = Horde_String::upper($permission);
            if (defined('Horde_Perms::' . $permission)) {
                $perm->addUserPermission($userName, constant('Horde_Perms::' . $permission), false);
            }
        }

        if (is_a($result = $share->setPermission($perm), 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Gives a group certain privileges for a share.
     *
     * @param string $shareRoot   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $groupName   The group's name.
     * @param array $permissions  A list of permissions (show, read, edit, delete).
     */
    public function addGroupPermissions($shareRoot, $shareName, $groupName,
        $permissions)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to change shares."));
        }

        require_once 'Horde/Group.php';
        $shares = Horde_Share::singleton($shareRoot);
        $groups = Group::singleton();

        if (is_a($share = &$shares->getShare($shareName), 'PEAR_Error')) {
            return $share;
        }
        if (is_a($groupId = $groups->getGroupId($groupName), 'PEAR_Error')) {
            return $groupId;
        }

        $perm = &$share->getPermission();
        foreach ($permissions as $permission) {
            $permission = Horde_String::upper($permission);
            if (defined('Horde_Perms::' . $permission)) {
                $perm->addGroupPermission($groupId, constant('Horde_Perms::' . $permission), false);
            }
        }

        if (is_a($result = $share->setPermission($perm), 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Removes a user from a share.
     *
     * @param string $shareRoot   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $userName    The user's name.
     */
    public function removeUserPermissions($shareRoot, $shareName, $userName)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to change shares."));
        }

        $shares = Horde_Share::singleton($shareRoot);

        if (is_a($share = &$shares->getShare($shareName), 'PEAR_Error')) {
            return $share;
        }

        if (is_a($result = $share->removeUser($userName), 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Removes a group from a share.
     *
     * @param string $shareRoot   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $groupName   The group's name.
     */
    public function removeGroupPermissions($shareRoot, $shareName, $groupName)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to change shares."));
        }

        require_once 'Horde/Group.php';
        $shares = Horde_Share::singleton($shareRoot);
        $groups = Group::singleton();

        if (is_a($share = &$shares->getShare($shareName), 'PEAR_Error')) {
            return $share;
        }
        if (is_a($groupId = $groups->getGroupId($groupName), 'PEAR_Error')) {
            return $groupId;
        }

        if (is_a($result = $share->removeGroup($groupId), 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    /**
     * Returns an array of all user permissions on a share.
     *
     * @param string $shareRoot   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $userName    The user's name.
     *
     * @return array  All user permissions for this share.
     */
    public function listUserPermissions($shareRoot, $shareName, $userName)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to list share permissions."));
        }

        $perm_map = array(Horde_Perms::SHOW => 'show',
            Horde_Perms::READ => 'read',
            Horde_Perms::EDIT => 'edit',
            Horde_Perms::DELETE => 'delete');

        $shares = Horde_Share::singleton($shareRoot);

        if (is_a($share = &$shares->getShare($shareName), 'PEAR_Error')) {
            return $share;
        }

        $perm = &$share->getPermission();
        $permissions = $perm->getUserPermissions();
        if (empty($permissions[$userName])) {
            return array();
        }

        $user_permissions = array();
        foreach (array_keys(Perms::integerToArray($permissions[$userName])) as $permission) {
            $user_permissions[] = $perm_map[$permission];
        }

        return $user_permissions;
    }

    /**
     * Returns an array of all group permissions on a share.
     *
     * @param string $shareRoot   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $groupName   The group's name.
     *
     * @return array  All group permissions for this share.
     */
    public function listGroupPermissions($shareRoot, $shareName, $groupName)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to list share permissions."));
        }

        $perm_map = array(Horde_Perms::SHOW => 'show',
            Horde_Perms::READ => 'read',
            Horde_Perms::EDIT => 'edit',
            Horde_Perms::DELETE => 'delete');

        $shares = Horde_Share::singleton($shareRoot);

        if (is_a($share = &$shares->getShare($shareName), 'PEAR_Error')) {
            return $share;
        }

        $perm = &$share->getPermission();
        $permissions = $perm->getGroupPermissions();
        if (empty($permissions[$groupName])) {
            return array();
        }

        $group_permissions = array();
        foreach (array_keys(Perms::integerToArray($permissions[$groupName])) as $permission) {
            $group_permissions[] = $perm_map[$permission];
        }

        return $group_permissions;
    }

    /**
     * Returns a list of users which have have certain permissions on a share.
     *
     * @param string $shareRoot   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param array $permissions  A list of permissions (show, read, edit, delete).
     *
     * @return array  List of users with the specified permissions.
     */
    public function listUsersOfShare($shareRoot, $shareName, $permissions)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to list users of shares."));
        }

        $shares = Horde_Share::singleton($shareRoot);

        if (is_a($share = &$shares->getShare($shareName), 'PEAR_Error')) {
            return $share;
        }

        $perm = 0;
        foreach ($permissions as $permission) {
            $permission = Horde_String::upper($permission);
            if (defined('Horde_Perms::' . $permission)) {
                $perm &= constant('Horde_Perms::' . $permission);
            }
        }

        return $share->listUsers($perm);
    }

    /**
     * Returns a list of groups which have have certain permissions on a share.
     *
     * @param string $shareRoot   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param array $permissions  A list of permissions (show, read, edit, delete).
     *
     * @return array  List of groups with the specified permissions.
     */
    public function listGroupsOfShare($shareRoot, $shareName, $permissions)
    {
        if (!Horde_Auth::isAdmin()) {
            return PEAR::raiseError(_("You are not allowed to list groups of shares."));
        }

        $shares = Horde_Share::singleton($shareRoot);

        if (is_a($share = &$shares->getShare($shareName), 'PEAR_Error')) {
            return $share;
        }

        $perm = 0;
        foreach ($permissions as $permission) {
            $permission = Horde_String::upper($permission);
            if (defined('Horde_Perms::' . $permission)) {
                $perm &= constant('Horde_Perms::' . $permission);
            }
        }

        return $share->listGroups($perm);
    }

}

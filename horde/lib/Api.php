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
     * Returns a list of adminstrative links.
     *
     * @return array  Keys are link labels, values are array with these keys:
     * <pre>
     * 'link' - (string) Registry encoded link to page.
     * 'name' - (string) Gettext label for page.
     * 'icon' - (string) Graphic for page.
     * </pre>
     */
    public function admin_list()
    {
        $admin = array(
            'configuration' => array(
                'link' => '%application%/admin/config/',
                'name' => _("_Configuration"),
                'icon' => Horde_Themes::img('config.png')
            ),
            'users' => array(
                'link' => '%application%/admin/user.php',
                'name' => _("_Users"),
                'icon' => Horde_Themes::img('user.png')
            ),
            'groups' => array(
                'link' => '%application%/admin/groups.php',
                'name' => _("_Groups"),
                'icon' => Horde_Themes::img('group.png')
            ),
            'perms' => array(
                'link' => '%application%/admin/perms/index.php',
                'name' => _("_Permissions"),
                'icon' => Horde_Themes::img('perms.png')
            ),
            'alarms' => array(
                'link' => '%application%/admin/alarms.php',
                'name' => _("_Alarms"),
                'icon' => Horde_Themes::img('alerts/alarm.png')
            ),
            'datatree' => array(
                'link' => '%application%/admin/datatree.php',
                'name' => _("_DataTree"),
                'icon' => Horde_Themes::img('datatree.png')
            ),
            'sessions' => array(
                'link' => '%application%/admin/sessions.php',
                'name' => _("Sessions"),
                'icon' => Horde_Themes::img('user.png')
            ),
            'phpshell' => array(
                'link' => '%application%/admin/phpshell.php',
                'name' => _("P_HP Shell"),
                'icon' => Horde_Themes::img('mime/php.png')
            ),
            'sqlshell' => array(
                'link' => '%application%/admin/sqlshell.php',
                'name' => _("S_QL Shell"),
                'icon' => Horde_Themes::img('sql.png')
            ),
            'cmdshell' => array(
                'link' => '%application%/admin/cmdshell.php',
                'name' => _("_CLI"),
                'icon' => Horde_Themes::img('shell.png')
            )
        );

        if (!empty($GLOBALS['conf']['activesync']['enabled'])) {
            $admin['activesync'] = array(
                'link' => '%application%/admin/activesync.php',
                'name' => _("ActiveSync Devices"),
                'icon' => Horde_Themes::img('mobile.png')
            );
        }

        if (empty($GLOBALS['conf']['datatree']['driver']) ||
            $GLOBALS['conf']['datatree']['driver'] == 'null') {
            unset($admin['datatree']);
        }

        return $admin;
    }

    /**
     * Returns a list of the installed and registered applications.
     *
     * @param array $filter  An array of the statuses that should be returned.
     *                       Defaults to non-hidden.
     *
     * @return array  List of apps registered with Horde. If no applications
     *                are defined returns an empty array.
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
        $pushed = $GLOBALS['registry']->pushApp($app);
        $GLOBALS['registry']->loadPrefs($app);
        $value = $GLOBALS['prefs']->getValue($pref);
        if ($pushed) {
            $GLOBALS['registry']->popApp();
        }

        return $value;
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
        $pushed = $GLOBALS['registry']->pushApp($app);
        $GLOBALS['registry']->loadPrefs($app);
        $value = $GLOBALS['prefs']->setValue($pref, $value);
        if ($pushed) {
            $GLOBALS['registry']->popApp();
        }
    }

    /**
     * Removes user data.
     *
     * @param string $user      Name of user to remove data for.
     * @param boolean $allapps  Remove data from all applications?
     *
     * @throws Horde_Exception
     */
    public function removeUserData($user, $allapps = false)
    {
        global $conf, $injector, $registry;

        if (!$registry->isAdmin() && ($user != $registry->getAuth())) {
            throw new Horde_Exception(_("You are not allowed to remove user data."));
        }

        /* Error flag */
        $haveError = false;

        /* Remove user's prefs */
        $prefs = $injector->getInstance('Horde_Core_Factory_Prefs')->create('horde', array(
            'user' => $user
        ));
        try {
            $prefs->remove();
        } catch (Horde_Exception $e) {
            $haveError = true;
        }

        /* Remove user from all groups */
        $groups = $GLOBALS['injector']->getInstance('Horde_Group');
        try {
            $allGroups = $groups->getGroupMemberships($user);
            foreach (array_keys($allGroups) as $id) {
                $group = $groups->getGroupById($id);
                $group->removeUser($user, true);
            }
        } catch (Horde_Group_Exception $e) {
            Horde::logMessage($e, 'ERR');
            $haveError = true;
        }

        /* Remove the user from all application permissions */
        $perms = $injector->getInstance('Horde_Perms');
        try {
            $tree = $perms->getTree();
        } catch (Horde_Perms_Exception $e) {
            Horde::logMessage($e, 'ERR');
            $haveError = true;
            $tree = array();
        }

        foreach (array_keys($tree) as $id) {
            try {
                $perm = $perms->getPermissionById($id);
                if ($perms->getPermissions($perm, $user)) {
                    // The Horde_Perms::ALL is used if this is a matrix perm,
                    // otherwise it's ignored in the method and the entry is
                    // totally removed.
                    $perm->removeUserPermission($user, Horde_Perms::ALL, true);
                }
            } catch (Horde_Perms_Exception $e) {
                Horde::logMessage($e, 'ERR');
                $haveError = true;
            }
        }

        if ($allapps) {
            $registry->removeUserData($user);
        }

        if ($haveError) {
            throw new Horde_Exception(sprintf(_("There was an error removing global data for %s. Details have been logged."), $user));
        }
    }

    /* Groups. */

    /**
     * Adds a group to the groups system.
     *
     * @param string $name    The group's name.
     * @param string $parent  The group's parent's name.
     *
     * @throws Horde_Exception
     */
    public function addGroup($name, $parent = null)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to add groups."));
        }

        if (empty($parent)) {
            $parent = Horde_Group::ROOT;
        }

        try {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');
            $group = $groups->newGroup($name, $parent);
            $groups->addGroup($group);
        } catch (Horde_Group_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Removes a group from the groups system.
     *
     * @param string $name  The group's name.
     *
     * @throws Horde_Exception
     */
    public function removeGroup($name)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to delete groups."));
        }

        try {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');
            $group = $groups->getGroup($name);
            $groups->removeGroup($group, true);
        } catch (Horde_Group_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Adds a user to a group.
     *
     * @param string $name  The group's name.
     * @param string $user  The user to add.
     *
     * @throws Horde_Exception
     */
    public function addUserToGroup($name, $user)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to change groups."));
        }

        try {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');
            $group = $groups->getGroup($name);
            $group->addUser($user);
        } catch (Horde_Group_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Adds multiple users to a group.
     *
     * @param string $name  The group's name.
     * @param array $users  The users to add.
     *
     * @throws Horde_Exception
     */
    public function addUsersToGroup($name, $users)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to change groups."));
        }

        try {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');
            $group = $groups->getGroup($name);
            foreach ($users as $user) {
                $group->addUser($user, false);
            }
            $group->save();
        } catch (Horde_Group_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Removes a user from a group.
     *
     * @param string $name  The group's name.
     * @param string $user  The user to add.
     *
     * @throws Horde_Exception
     */
    public function removeUserFromGroup($name, $user)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to change groups."));
        }

        try {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');
            $group = $groups->getGroup($name);
            $group->removeUser($user);
        } catch (Horde_Group_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Removes multiple users from a group.
     *
     * @param string $name  The group's name.
     * @param array $users  The users to add.
     *
     * @throws Horde_Exception
     */
    public function removeUsersFromGroup($name, $users)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to change groups."));
        }

        try {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');
            $group = $groups->getGroup($name);
            foreach ($users as $user) {
                $group->removeUser($user, false);
            }
            $group->save();
        } catch (Horde_Group_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Returns a list of users that are part of this group (and only this group)
     *
     * @param string $name  The group's name.
     *
     * @return array  The user list.
     * @throws Horde_Exception
     */
    public function listUsersOfGroup($name)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to list users of groups."));
        }

        try {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');
            $group = $groups->getGroup($name);
            return $group->listUsers();
        } catch (Horde_Group_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /* Shares. */

    /**
     * Adds a share to the shares system.
     *
     * @param string $scope   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $shareTitle  The share's human readable title.
     * @param string $userName    The share's owner.
     *
     * @throws Horde_Exception
     */
    public function addShare($scope, $shareName, $shareTitle, $userName)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to add shares."));
        }

        $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create($scope);
        try {
            $share = $shares->newShare($GLOBALS['registry']->getAuth(), $shareName);
            $share->set('owner', $userName);
            $share->set('name', $shareTitle);
            $shares->addShare($share);
        } catch (Horde_Share_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Removes a share from the shares system permanently.
     *
     * @param string $scope      The name of the share root, e.g. the
     *                           application that the share belongs to.
     * @param string $shareName  The share's name.
     *
     * @throws Horde_Exception
     */
    public function removeShare($scope, $shareName)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exceptionr(_("You are not allowed to delete shares."));
        }

        $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create($scope);
        $share = $shares->getShare($shareName);
        try {
            $shares->removeShare($share);
        } catch (Horde_Share_Exception $e) {
            throw new Horde_Exception_Prior($e);
        }
    }

    /**
     * Returns an array of all shares that $userName is the owner of.
     *
     * @param string $scope      The name of the share root, e.g. the
     *                           application that the share belongs to.
     * @param string $userName   The share's owner.
     *
     * @return array  The list of shares.
     * @throws Horde_Exception
     */
    public function listSharesOfOwner($scope, $userName)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to list shares."));
        }

        $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create($scope);

        $share_list = $shares->listShares($userName,
                                          array('perm' => Horde_Perms::SHOW,
                                                'attributes' => $userName));
        $myshares = array();
        foreach ($share_list as $share) {
            $myshares[] = $share->getName();
        }

        return $myshares;
    }

    /**
     * Gives a user certain privileges for a share.
     *
     * @param string $scope       The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $userName    The user's name.
     * @param array $permissions  A list of permissions (show, read, edit, delete).
     *
     * @throws Horde_Exception
     */
    public function addUserPermissions($scope, $shareName, $userName,
                                       $permissions)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to change shares."));
        }

        try {
            $share = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Share')
                ->create($scope)
                ->getShare($shareName);
            $perm = $share->getPermission();
            foreach ($permissions as $permission) {
                $permission = Horde_String::upper($permission);
                if (defined('Horde_Perms::' . $permission)) {
                    $perm->addUserPermission($userName, constant('Horde_Perms::' . $permission), false);
                }
            }
            $share->setPermission($perm);
        } catch (Horde_Share_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Gives a group certain privileges for a share.
     *
     * @param string $scope       The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $groupName   The group's name.
     * @param array $permissions  A list of permissions (show, read, edit, delete).
     *
     * @throws Horde_Exception
     */
    public function addGroupPermissions($scope, $shareName, $groupName,
                                        $permissions)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to change shares."));
        }

        try {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');
            $groupId = $groups->getGroupId($groupName);
        } catch (Horde_Group_Exception $e) {
            throw new Horde_Exception($e);
        }

        try {
            $share = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Share')
                ->create($scope)
                ->getShare($shareName);
            $perm = $share->getPermission();
            foreach ($permissions as $permission) {
                $permission = Horde_String::upper($permission);
                if (defined('Horde_Perms::' . $permission)) {
                    $perm->addGroupPermission($groupId, constant('Horde_Perms::' . $permission), false);
                }
            }
            $share->setPermission($perm);
        } catch (Horde_Share_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Removes a user from a share.
     *
     * @param string $scope       The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $userName    The user's name.
     *
     * @throws Horde_Exception
     */
    public function removeUserPermissions($scope, $shareName, $userName)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to change shares."));
        }

        $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create($scope);
        $share = $shares->getShare($shareName);
        try {
            $share->removeUser($userName);
        } catch (Horde_Share_Exception $e) {
            throw new Horde_Exception($result);
        }
    }

    /**
     * Removes a group from a share.
     *
     * @param string $scope   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $groupName   The group's name.
     *
     * @throws Horde_Exception
     */
    public function removeGroupPermissions($scope, $shareName, $groupName)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to change shares."));
        }

        $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create($scope);
        $share = $shares->getShare($shareName);
        try {
            $groups = $GLOBALS['injector']->getInstance('Horde_Group');
            $groupId = $groups->getGroupId($groupName);
        } catch (Horde_Group_Exception $e) {
            throw new Horde_Exception($e);
        }
        try {
            $share->removeGroup($groupId);
        } catch (Horde_Share_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Returns an array of all user permissions on a share.
     *
     * @param string $scope  The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $userName    The user's name.
     *
     * @return array  All user permissions for this share.
     * @throws Horde_Exception
     */
    public function listUserPermissions($scope, $shareName, $userName)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to list share permissions."));
        }

        $perm_map = array(Horde_Perms::SHOW => 'show',
            Horde_Perms::READ => 'read',
            Horde_Perms::EDIT => 'edit',
            Horde_Perms::DELETE => 'delete');

        $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create($scope);
        $share = $shares->getShare($shareName);
        $perm = $share->getPermission();
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
     * @param string $scope   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param string $groupName   The group's name.
     *
     * @return array  All group permissions for this share.
     * @throws Horde_Exception
     */
    public function listGroupPermissions($scope, $shareName, $groupName)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to list share permissions."));
        }

        $perm_map = array(Horde_Perms::SHOW => 'show',
            Horde_Perms::READ => 'read',
            Horde_Perms::EDIT => 'edit',
            Horde_Perms::DELETE => 'delete');

        $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create($scope);
        $share = $shares->getShare($shareName);
        $perm = $share->getPermission();
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
     * @param string $scope   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param array $permissions  A list of permissions (show, read, edit, delete).
     *
     * @return array  List of users with the specified permissions.
     * @throws Horde_Exception
     */
    public function listUsersOfShare($scope, $shareName, $permissions)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to list users of shares."));
        }

        $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create($scope);
        $share = $shares->getShare($shareName);
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
     * @param string $scope   The name of the share root, e.g. the
     *                            application that the share belongs to.
     * @param string $shareName   The share's name.
     * @param array $permissions  A list of permissions (show, read, edit, delete).
     *
     * @return array  List of groups with the specified permissions.
     * @throws Horde_Exception
     */
    public function listGroupsOfShare($scope, $shareName, $permissions)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception(_("You are not allowed to list groups of shares."));
        }

        $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create($scope);
        $share = $shares->getShare($shareName);
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

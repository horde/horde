<?php
/**
 * Abstract class for storing Share information.
 *
 * This class should be extended for the more specific drivers.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Share
 */
abstract class Horde_Share_Object implements Serializable
{
    /**
     * A function to be called when a Horde_Share object is needed and not
     * available.
     *
     * @var callback
     */
    protected $_shareCallback;

    /**
     * The Horde_Share object which this share is associated with.
     * If this is empty, the $_shareCallback is called to obtain it.
     *
     * @var Horde_Share
     */
    protected $_shareOb;

    /**
     * Associates a Share object with this share, or provides a callback that
     * knows how to provide it.
     *
     * @param mixed Horde_Share | Callback $shareOb  The Share object.
     */
    public function setShareOb($shareOb)
    {
        if ($shareOb instanceof Horde_Share) {
            $this->_shareOb = $shareOb;
        } else {
            $this->_shareCallback = $shareOb;
        }
    }

    /**
     * Obtain this object's share driver.
     *
     * @return Horde_Share  The share driver.
     */
    public function getShareOb()
    {
        if (empty($this->_shareOb)) {
            $this->_shareOb = call_user_func($this->_shareCallback);
        }

        return $this->_shareOb;
    }

    /**
     * Sets an attribute value in this object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     *
     * @return boolean
     */
    public function set($attribute, $value)
    {
        return $this->_set($attribute, $value);
    }

    /**
     * Returns an attribute value from this object.
     *
     * @param string $attribute  The attribute to return.
     *
     * @return mixed  The value for $attribute.
     */
    public function get($attribute)
    {
        return $this->_get($attribute);
    }

    /**
     * Returns the ID of this share.
     *
     * @return string  The share's ID.
     */
    public function getId()
    {
        return $this->_getId();
    }

    /**
     * Returns the name of this share.
     *
     * @return string  The share's name.
     */
    public function getName()
    {
        return $this->_getName();
    }

    /**
     * Saves the current attribute values.
     *
     * @return boolean
     * @throws Horde_Exception
     */
    public function save()
    {
        $this->getShareOb()->runCallback('modify', array($this));
        return $this->_save();
    }

    /**
     * Gives a user a certain privilege for this share.
     *
     * @param string $userid       The userid of the user.
     * @param integer $permission  A Horde_Perms::* constant.
     */
    public function addUserPermission($userid, $permission)
    {
        $perm = $this->getPermission();
        $perm->addUserPermission($userid, $permission, false);
        $this->setPermission($perm);
    }

    /**
     * Removes a certain privilege for a user from this share.
     *
     * @param string $userid       The userid of the user.
     * @param integer $permission  A Horde_Perms::* constant.
     */
    public function removeUserPermission($userid, $permission)
    {
        $perm = $this->getPermission();
        $perm->removeUserPermission($userid, $permission, false);
        $this->setPermission($perm);
    }

    /**
     * Gives a group certain privileges for this share.
     *
     * @param string $group        The group to add permissions for.
     * @param integer $permission  A Horde_Perms::* constant.
     */
    public function addGroupPermission($group, $permission)
    {
        $perm = $this->getPermission();
        $perm->addGroupPermission($group, $permission, false);
        $this->setPermission($perm);
    }

    /**
     * Removes a certain privilege from a group.
     *
     * @param string $group         The group to remove permissions from.
     * @param constant $permission  A Horde_Perms::* constant.
     */
    public function removeGroupPermission($group, $permission)
    {
        $perm = $this->getPermission();
        $perm->removeGroupPermission($group, $permission, false);
        $this->setPermission($perm);
    }

    /**
     * Removes a user from this share.
     *
     * @param string $userid  The userid of the user to remove.
     */
    public function removeUser($userid)
    {
        /* Remove all $userid's permissions. */
        $perm = $this->getPermission();
        $perm->removeUserPermission($userid, Horde_Perms::SHOW, false);
        $perm->removeUserPermission($userid, Horde_Perms::READ, false);
        $perm->removeUserPermission($userid, Horde_Perms::EDIT, false);
        $perm->removeUserPermission($userid, Horde_Perms::DELETE, false);

        return $this->setPermission($perm);
    }

    /**
     * Removes a group from this share.
     *
     * @param integer $groupId  The group to remove.
     */
    public function removeGroup($groupId)
    {
        /* Remove all $groupId's permissions. */
        $perm = $this->getPermission();
        $perm->removeGroupPermission($groupId, Horde_Perms::SHOW, false);
        $perm->removeGroupPermission($groupId, Horde_Perms::READ, false);
        $perm->removeGroupPermission($groupId, Horde_Perms::EDIT, false);
        $perm->removeGroupPermission($groupId, Horde_Perms::DELETE, false);

        return $this->setPermission($perm);
    }

    /**
     * Returns an array containing all the userids of the users with access to
     * this share.
     *
     * @param integer $perm_level  List only users with this permission level.
     *                             Defaults to all users.
     *
     * @return array  The users with access to this share.
     */
    public function listUsers($perm_level = null)
    {
        $perm = $this->getPermission();
        $results = array_keys($perm->getUserPermissions($perm_level));
        // Always return the share's owner.
        if ($this->get('owner')) {
            array_push($results, $this->get('owner'));
        }
        return $results;
    }

    /**
     * Returns an array containing all the groupids of the groups with access
     * to this share.
     *
     * @param integer $perm_level  List only users with this permission level.
     *                             Defaults to all users.
     *
     * @return array  The IDs of the groups with access to this share.
     */
    public function listGroups($perm_level = null)
    {
        $perm = $this->getPermission();
        return array_keys($perm->getGroupPermissions($perm_level));
    }

    /**
     * Checks to see if a user has a given permission.
     *
     * @param string $userid       The userid of the user.
     * @param integer $permission  A Horde_Perms::* constant to test for.
     * @param string $creator      The creator of the event.
     *
     * @return boolean  Whether or not $userid has $permission.
     */
    abstract public function hasPermission($userid, $permission, $creator = null);

    /**
     * Sets the permission of this share.
     *
     * @param Horde_Perms_Permission $perm  Permission object.
     * @param boolean $update               Should the share be saved
     *                                      after this operation?
     *
     * @return boolean
     */
    abstract public function setPermission($perm, $update = true);

    /**
     * Returns the permission of this share.
     *
     * @return Horde_Perms_Permission  Permission object that represents the
     *                                 permissions on this share.
     */
    abstract public function getPermission();

}

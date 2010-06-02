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
class Horde_Share_Object
{
    /**
     * The Horde_Share object which this share came from - needed for updating
     * data in the backend to make changes stick, etc.
     *
     * @var Horde_Share
     */
    protected $_shareOb;

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    public function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['_shareOb']);
        $properties = array_keys($properties);
        return $properties;
    }

    /**
     * Associates a Share object with this share.
     *
     * @param Horde_Share $shareOb  The Share object.
     */
    public function setShareOb(Horde_Share $shareOb)
    {
        $this->_shareOb = $shareOb;
    }

    /**
     * Obtain this object's share driver.
     *
     * @return Horde_Share  The share driver.
     */
    public function getShareOb()
    {
        return $this->_shareOb;
    }

    /**
     * Sets an attribute value in this object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     *
     * @return mixed  True if setting the attribute did succeed, a PEAR_Error
     *                otherwise.
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
        try {
            Horde::callHook('share_modify', array($this));
        } catch (Horde_Exception_HookNotSet $e) {}

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
     * Locks an item from this share, or the entire share if no item defined.
     *
     * @param Horde_Lock $locks  The Horde_Lock object
     * @param string $item_uid   A uid of an item from this share.
     *
     * @return mixed   A lock ID on success, PEAR_Error on failure, false if:
     *                  - The share is already locked
     *                  - The item is already locked
     *                  - A share lock was requested and an item is already
     *                    locked in the share
     * @throws Horde_Share_Exception
     */
    public function lock(Horde_Lock $locks, $item_uid = null)
    {
        $shareid = $this->getId();

        // Default parameters.
        $locktype = Horde_Lock::TYPE_EXCLUSIVE;
        $timeout = 600;
        $itemscope = $this->_shareOb->getApp() . ':' . $shareid;

        if (!empty($item_uid)) {
            // Check if the share is locked. Share locks are placed at app
            // scope.
            try {
                $result = $locks->getLocks($this->_shareOb->getApp(), $shareid, $locktype);
            } catch (Horde_Lock_Exception $e) {
                throw new Horde_Share_Exception($e);
            }
            if (!empty($result)) {
                // Lock found.
                return false;
            }
            // Try to place the item lock at app:shareid scope.
            return $locks->setLock($GLOBALS['registry']->getAuth(), $itemscope, $item_uid,
                                   $timeout, $locktype);
        } else {
            // Share lock requested. Check for locked items.
            try {
                $result = $locks->getLocks($itemscope, null, $locktype);
            } catch (Horde_Lock_Exception $e) {
                throw new Horde_Share_Exception($e);
            }
            if (!empty($result)) {
                // Lock found.
                return false;
            }
            // Try to place the share lock
            return $locks->setLock($GLOBALS['registry']->getAuth(), $this->_shareOb->getApp(),
                                   $shareid, $timeout, $locktype);
        }
    }

    /**
     * Removes the lock for a lock ID.
     *
     * @param Horde_Lock $locks  The lock object
     * @param string $lockid     The lock ID as generated by a previous call
     *                           to lock().
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    public function unlock(Horde_Lock $locks, $lockid)
    {
        return $locks->clearLock($lockid);
    }

    /**
     * Checks for existing locks.
     *
     * First this checks for share locks and if none exists, checks for item
     * locks (if item_uid defined).  It will return the first lock found.
     *
     * @param Horde_Lock  $locks  The lock object.
     * @param string $item_uid    A uid of an item from this share.
     *
     * @return mixed   Hash with the found lock information in 'lock' and the
     *                 lock type ('share' or 'item') in 'type', or an empty
     *                 array if there are no locks, or a PEAR_Error on failure.
     */
    public function checkLocks(Horde_Lock $locks, $item_uid = null)
    {
        $shareid = $this->getId();
        $locktype = Horde_Lock::TYPE_EXCLUSIVE;

        // Check for share locks
        try {
            $result = $locks->getLocks($this->_shareOb->getApp(), $shareid, $locktype);
        } catch (Horde_Lock_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Horde_Share_Exception($e);
        }

        if (empty($result) && !empty($item_uid)) {
            // Check for item locks
            $locktargettype = 'item';
            try {
                $result = $locks->getLocks($this->_shareOb->getApp() . ':' . $shareid, $item_uid, $locktype);
            } catch (Horde_Lock_Exception $e) {
                Horde::logMessage($e, 'ERR');
                throw new Horde_Share_Exception($e->getMessage());
            }
        } else {
            $locktargettype = 'share';
        }

        if (empty($result)) {
            return array();
        }

        return array('type' => $locktargettype,
                     'lock' => reset($result));
    }

}

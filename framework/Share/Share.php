<?php
/**
 * Horde_Share:: provides an interface to all shares a user might have.  Its
 * methods take care of any site-specific restrictions configured in in the
 * application's prefs.php and conf.php files.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 * Copyright 2002-2007 Infoteck Internet <webmaster@infoteck.qc.ca>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @author  Mike Cochrame <mike@graftonhall.co.nz>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Share
 */
class Horde_Share {

    /**
     * The application we're managing shares for.
     *
     * @var string
     */
    var $_app;

    /**
     * The root of the Share tree.
     *
     * @var mixed
     */
    var $_root = null;

    /**
     * A cache of all shares that have been retrieved, so we don't hit the
     * backend again and again for them.
     *
     * @var array
     */
    var $_cache = array();

    /**
     * Id-name-map of already cached share objects.
     *
     * @var array
     */
    var $_shareMap = array();

    /**
     * Cache used for listShares().
     *
     * @var array
     */
    var $_listcache = array();

    /**
     * A list of objects that we're currently sorting, for reference during the
     * sorting algorithm.
     *
     * @var array
     */
    var $_sortList;

    /**
     * Attempts to return a reference to a concrete Horde_Share instance.
     *
     * It will only create a new instance if no Horde_Share instance currently
     * exists.
     *
     * @param string $app     The application that the shares relates to.
     * @param string $driver  Type of concrete Share subclass to return,
     *                        based on storage driver ($driver). The code is
     *                        dynamically included.
     *
     * @return Horde_Share  The concrete Share reference, or false on an error.
     */
    public static function singleton($app, $driver = null)
    {
        static $shares = array();

        // FIXME: This is a temporary solution until the configuration value
        // actually exists and all apps call this code in the correct fashion.
        $driver = basename($driver);
        if (empty($driver)) {
            if (!empty($GLOBALS['conf']['share']['driver'])) {
                $driver = $GLOBALS['conf']['share']['driver'];
            } else {
                $driver = 'datatree';
            }
        }

        $class = 'Horde_Share_' . $driver;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/Share/' . $driver . '.php';
        }

        $signature = $app . '_' . $driver;
        if (!isset($shares[$signature]) &&
            !empty($GLOBALS['conf']['share']['cache'])) {
            $session = new Horde_SessionObjects();
            $shares[$signature] = $session->query('horde_share_' . $app . '_' . $driver . '1');
        }

        if (empty($shares[$signature])) {
            if (class_exists($class)) {
                $shares[$signature] = new $class($app);
            } else {
                $result = PEAR::raiseError(sprintf(_("\"%s\" share driver not found."), $driver));
                return $result;
            }
        }

        if (!empty($GLOBALS['conf']['share']['cache'])) {
            register_shutdown_function(array($shares[$signature], 'shutdown'));
        }

        return $shares[$signature];
    }

    /**
     * Constructor.
     *
     * @param string $app  The application that the shares belong to.
     */
    function Horde_Share($app)
    {
        $this->_app = $app;
        $this->__wakeup();
    }

    /**
     * Initializes the object.
     *
     * @throws Horde_Exception
     */
    function __wakeup()
    {
        try {
            Horde::callHook('share_init', array($this, $this->_app));
        } catch (Horde_Exception_HookNotSet $e) {}
    }

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['_sortList']);
        $properties = array_keys($properties);
        return $properties;
    }

    /**
     * Stores the object in the session cache.
     */
    function shutdown()
    {
        $driver = str_replace('horde_share_', '', Horde_String::lower(get_class($this)));
        $session = new Horde_SessionObjects();
        $session->overwrite('horde_share_' . $this->_app . '_' . $driver, $this, false);
    }

    /**
     * Returns the application we're managing shares for.
     *
     * @return string  The application this share belongs to.
     */
    function getApp()
    {
        return $this->_app;
    }

    /**
     * Returns a Horde_Share_Object object corresponding to the given share
     * name, with the details retrieved appropriately.
     *
     * @param string $name  The name of the share to retrieve.
     *
     * @return Horde_Share_Object  The requested share.
     */
    function getShare($name)
    {
        if (isset($this->_cache[$name])) {
            return $this->_cache[$name];
        }

        $share = $this->_getShare($name);
        if (is_a($share, 'PEAR_Error')) {
            return $share;
        }
        $share->setShareOb($this);
        $this->_shareMap[$share->getId()] = $name;
        $this->_cache[$name] = $share;

        return $share;
    }

    /**
     * Returns a Horde_Share_Object object corresponding to the given unique
     * ID, with the details retrieved appropriately.
     *
     * @param string $cid  The id of the share to retrieve.
     *
     * @return Horde_Share_Object  The requested share.
     */
    function getShareById($cid)
    {
        if (!isset($this->_shareMap[$cid])) {
            $share = $this->_getShareById($cid);
            if (is_a($share, 'PEAR_Error')) {
                return $share;
            }
            $share->setShareOb($this);
            $name = $share->getName();
            $this->_cache[$name] = $share;
            $this->_shareMap[$cid] = $name;
        }

        return $this->_cache[$this->_shareMap[$cid]];
    }

    /**
     * Returns an array of Horde_Share_Object objects corresponding to the
     * given set of unique IDs, with the details retrieved appropriately.
     *
     * @param array $cids  The array of ids to retrieve.
     *
     * @return array  The requested shares.
     */
    function getShares($cids)
    {
        $all_shares = array();
        $missing_ids = array();
        foreach ($cids as $cid) {
            if (isset($this->_shareMap[$cid])) {
                $all_shares[$this->_shareMap[$cid]] = $this->_cache[$this->_shareMap[$cid]];
            } else {
                $missing_ids[] = $cid;
            }
        }

        if (count($missing_ids)) {
            $shares = $this->_getShares($missing_ids);
            if (is_a($shares, 'PEAR_Error')) {
                return $shares;
            }

            foreach (array_keys($shares) as $key) {
                $this->_cache[$key] = $shares[$key];
                $this->_cache[$key]->setShareOb($this);
                $this->_shareMap[$shares[$key]->getId()] = $key;
                $all_shares[$key] = $this->_cache[$key];
            }
        }

        return $all_shares;
    }

    /**
     * Lists *all* shares for the current app/share, regardless of
     * permissions.
     *
     * This is for admin functionality and scripting tools, and shouldn't be
     * called from user-level code!
     *
     * @return array  All shares for the current app/share.
     */
    function listAllShares()
    {
        $shares = $this->_listAllShares();
        if (is_a($shares, 'PEAR_Error') || !count($shares)) {
            return $shares;
        }

        $this->_sortList = $shares;
        uasort($shares, array($this, '_sortShares'));
        $this->_sortList = null;

        return $shares;
    }

    /**
     * Returns an array of all shares that $userid has access to.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param integer $perm      The level of permissions required.
     * @param mixed $attributes  Restrict the shares counted to those
     *                           matching $attributes. An array of
     *                           attribute/values pairs or a share owner
     *                           username.
     *
     * @return array  The shares the user has access to.
     */
    function listShares($userid, $perm = Horde_Perms::SHOW, $attributes = null,
                        $from = 0, $count = 0, $sort_by = null, $direction = 0)
    {
        $shares = $this->_listShares($userid, $perm, $attributes, $from,
                                     $count, $sort_by, $direction);
        if (!count($shares)) {
            return $shares;
        }
        if (is_a($shares, 'PEAR_Error')) {
            return $shares;
        }

        /* Make sure getShares() didn't return an error. */
        $shares = $this->getShares($shares);
        if (is_a($shares, 'PEAR_Error')) {
            return $shares;
        }

        if (is_null($sort_by)) {
            $this->_sortList = $shares;
            uasort($shares, array($this, '_sortShares'));
            $this->_sortList = null;
        }

        try {
            return Horde::callHook('share_list', array($userid, $perm, $attributes, $shares));
        } catch (Horde_Exception_HookNotSet $e) {}

        return $shares;
    }

    /**
     * Returns an array of all system shares.
     *
     * @return array  All system shares.
     */
    function listSystemShares()
    {
        return array();
    }

    /**
     * Returns the number of shares that $userid has access to.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param integer $perm      The level of permissions required.
     * @param mixed $attributes  Restrict the shares counted to those
     *                           matching $attributes. An array of
     *                           attribute/values pairs or a share owner
     *                           username.
     *
     * @return integer  The number of shares
     */
    function countShares($userid, $perm = Horde_Perms::SHOW, $attributes = null)
    {
        return $this->_countShares($userid, $perm, $attributes);
    }

    /**
     * Returns a new share object.
     *
     * @param string $name  The share's name.
     *
     * @return Horde_Share_Object  A new share object.
     */
    function newShare($name)
    {
        if (empty($name)) {
            return PEAR::raiseError('Share names must be non-empty');
        }
        $share = $this->_newShare($name);
        $share->setShareOb($this);
        $share->set('owner', Horde_Auth::getAuth());

        return $share;
    }

    /**
     * Adds a share to the shares system.
     *
     * The share must first be created with Horde_Share::newShare(), and have
     * any initial details added to it, before this function is called.
     *
     * @param Horde_Share_Object $share  The new share object.
     * @throws Horde_Exception
     */
    function addShare($share)
    {
        if (!is_a($share, 'Horde_Share_Object')) {
            return PEAR::raiseError('Shares must be Horde_Share_Object objects or extend that class.');
        }

        try {
            Horde::callHook('share_add', array($share));
        } catch (Horde_Exception_HookNotSet $e) {}

        $result = $this->_addShare($share);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Store new share in the caches. */
        $id = $share->getId();
        $name = $share->getName();
        $this->_cache[$name] = $share;
        $this->_shareMap[$id] = $name;

        /* Reset caches that depend on unknown criteria. */
        $this->_listCache = array();

        return $result;
    }

    /**
     * Removes a share from the shares system permanently.
     *
     * @param Horde_Share_Object $share  The share to remove.
     *
     * @throws Horde_Exception
     */
    function removeShare($share)
    {
        if (!is_a($share, 'Horde_Share_Object')) {
            return PEAR::raiseError('Shares must be Horde_Share_Object objects or extend that class.');
        }

        try {
            Horde::callHook('share_remove', array($share));
        } catch (Horde_Exception_HookNotSet $e) {}

        /* Remove share from the caches. */
        $id = $share->getId();
        unset($this->_shareMap[$id]);
        unset($this->_cache[$share->getName()]);

        /* Reset caches that depend on unknown criteria. */
        $this->_listCache = array();

        return $this->_removeShare($share);
    }

    /**
     * Checks if a share exists in the system.
     *
     * @param string $share  The share to check.
     *
     * @return boolean  True if the share exists.
     */
    function exists($share)
    {
        if (isset($this->_cache[$share])) {
            return true;
        }

        return $this->_exists($share);
    }

    /**
     * Finds out what rights the given user has to this object.
     *
     * @see Horde_Perms::getPermissions
     *
     * @param mixed $share  The share that should be checked for the users
     *                      permissions.
     * @param string $user  The user to check for.
     *
     * @return mixed  A bitmask of permissions, a permission value, or an array
     *                of permission values the user has, depending on the
     *                permission type and whether the permission value is
     *                ambiguous. False if there is no such permsission.
     */
    function getPermissions($share, $user = null)
    {
        if (is_a($share, 'PEAR_Error')) {
            Horde::logMessage($share, 'ERR');
            return false;
        }

        if (!is_a($share, 'Horde_Share_Object')) {
            $share = $this->getShare($share);
            if (is_a($share, 'PEAR_Error')) {
                Horde::logMessage($share, 'ERR');
                return false;
            }
        }

        $perm = $share->getPermission();
        return $GLOBALS['injector']->getInstance('Horde_Perms')->getPermissions($perm, $user);
    }

    /**
     * Returns the Identity for a particular share owner.
     *
     * @deprecated
     *
     * @param mixed $share  The share to fetch the Identity for - either the
     *                      string name, or the Horde_Share_Object object.
     *
     * @return Identity  An Identity instance.
     */
    function getIdentityByShare($share)
    {
        if (!is_a($share, 'Horde_Share_Object')) {
            $share = $this->getShare($share);
            if (is_a($share, 'PEAR_Error')) {
                return null;
            }
        }

        return $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($share->get('owner'));
    }

    /**
     * Utility function to be used with uasort() for sorting arrays of
     * Horde_Share objects.
     *
     * Example:
     * <code>
     * uasort($list, array('Horde_Share', '_sortShares'));
     * </code>
     *
     * @access protected
     */
    function _sortShares($a, $b)
    {
        $aParts = explode(':', $a->getName());
        $bParts = explode(':', $b->getName());

        $min = min(count($aParts), count($bParts));
        $idA = '';
        $idB = '';
        for ($i = 0; $i < $min; $i++) {
            if ($idA) {
                $idA .= ':';
                $idB .= ':';
            }
            $idA .= $aParts[$i];
            $idB .= $bParts[$i];

            if ($idA != $idB) {
                $curA = isset($this->_sortList[$idA]) ? $this->_sortList[$idA]->get('name') : '';
                $curB = isset($this->_sortList[$idB]) ? $this->_sortList[$idB]->get('name') : '';
                return strnatcasecmp($curA, $curB);
            }
        }

        return count($aParts) > count($bParts);
    }

}

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
class Horde_Share_Object {

    /**
     * The Horde_Share object which this share came from - needed for updating
     * data in the backend to make changes stick, etc.
     *
     * @var Horde_Share
     */
    var $_shareOb;

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    function __sleep()
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
    function setShareOb($shareOb)
    {
        if (!is_a($shareOb, 'Horde_Share')) {
            return PEAR::raiseError('This object needs a Horde_Share instance as storage handler!');
        }
        $this->_shareOb = $shareOb;
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
    function set($attribute, $value)
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
    function get($attribute)
    {
        return $this->_get($attribute);
    }

    /**
     * Returns the ID of this share.
     *
     * @return string  The share's ID.
     */
    function getId()
    {
        return $this->_getId();
    }

    /**
     * Returns the name of this share.
     *
     * @return string  The share's name.
     */
    function getName()
    {
        return $this->_getName();
    }

    /**
     * Saves the current attribute values.
     *
     * @throws Horde_Exception
     */
    function save()
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
    function addUserPermission($userid, $permission)
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
    function removeUserPermission($userid, $permission)
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
    function addGroupPermission($group, $permission)
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
    function removeGroupPermission($group, $permission)
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
    function removeUser($userid)
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
    function removeGroup($groupId)
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
    function listUsers($perm_level = null)
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
    function listGroups($perm_level = null)
    {
        $perm = $this->getPermission();
        return array_keys($perm->getGroupPermissions($perm_level));
    }

    /**
     * Locks an item from this share, or the entire share if no item defined.
     *
     * @param string $item_uid  A uid of an item from this share.
     *
     * @return mixed   A lock ID on success, PEAR_Error on failure, false if:
     *                  - The share is already locked
     *                  - The item is already locked
     *                  - A share lock was requested and an item is already
     *                    locked in the share
     */
    function lock($item_uid = null)
    {
        try {
            $locks = $GLOBALS['injector']->getInstance('Horde_Lock');
        } catch (Horde_Lock_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return PEAR::raiseError($e->getMessage());
        }

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
                return PEAR::raiseError($e->getMessage);
            }
            if (!empty($result)) {
                // Lock found.
                return false;
            }
            // Try to place the item lock at app:shareid scope.
            return $locks->setLock(Horde_Auth::getAuth(), $itemscope, $item_uid,
                                   $timeout, $locktype);
        } else {
            // Share lock requested. Check for locked items.
            try {
                $result = $locks->getLocks($itemscope, null, $locktype);
            } catch (Horde_Lock_Exception $e) {
                return PEAR::raiseError($e->getException);
            }
            if (!empty($result)) {
                // Lock found.
                return false;
            }
            // Try to place the share lock
            return $locks->setLock(Horde_Auth::getAuth(), $this->_shareOb->getApp(),
                                   $shareid, $timeout, $locktype);
        }
    }

    /**
     * Removes the lock for a lock ID.
     *
     * @param string $lockid  The lock ID as generated by a previous call
     *                        to lock().
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function unlock($lockid)
    {
        try {
            $locks = $GLOBALS['injector']->getInstance('Horde_Lock');
        } catch (Horde_Lock_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return PEAR::raiseError($e->getMessage());
        }

        return $locks->clearLock($lockid);
    }

    /**
     * Checks for existing locks.
     *
     * First this checks for share locks and if none exists, checks for item
     * locks (if item_uid defined).  It will return the first lock found.
     *
     * @param string $item_uid  A uid of an item from this share.
     *
     * @return mixed   Hash with the found lock information in 'lock' and the
     *                 lock type ('share' or 'item') in 'type', or an empty
     *                 array if there are no locks, or a PEAR_Error on failure.
     */
    function checkLocks($item_uid = null)
    {
        try {
            $locks = $GLOBALS['injector']->getInstance('Horde_Lock');
        } catch (Horde_Lock_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return PEAR::raiseError($e->getMessage());
        }

        $shareid = $this->getId();
        $locktype = Horde_Lock::TYPE_EXCLUSIVE;

        // Check for share locks
        try {
            $result = $locks->getLocks($this->_shareOb->getApp(), $shareid, $locktype);
        } catch (Horde_Lock_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return PEAR::raiseError($e->getMessage());
        }

        if (empty($result) && !empty($item_uid)) {
            // Check for item locks
            $locktargettype = 'item';
            try {
                $result = $locks->getLocks($this->_shareOb->getApp() . ':' . $shareid, $item_uid, $locktype);
            } catch (Horde_Lock_Exception $e) {
                Horde::logMessage($e, 'ERR');
                return PEAR::raiseError($e->getMessage());
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

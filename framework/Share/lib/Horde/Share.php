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
class Horde_Share
{
    /**
     * The application we're managing shares for.
     *
     * @var string
     */
    protected $_app;

    /**
     * The root of the Share tree.
     *
     * @var mixed
     */
    protected $_root = null;

    /**
     * A cache of all shares that have been retrieved, so we don't hit the
     * backend again and again for them.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Id-name-map of already cached share objects.
     *
     * @var array
     */
    protected $_shareMap = array();

    /**
     * Cache used for listShares().
     *
     * @var array
     */
    protected $_listcache = array();

    /**
     * A list of objects that we're currently sorting, for reference during the
     * sorting algorithm.
     *
     * @var array
     */
    protected $_sortList;

    /**
     * The Horde_Share_Object subclass to instantiate objects as
     *
     * @var string
     */
    protected $_shareObject;

    /**
     * The Horde_Perms object
     *
     * @var Horde_Perms
     */
    protected $_permsObject;

    /**
     * Constructor.
     *
     * @param string $app  The application that the shares belong to.
     * @param Horde_Perms  The permissions object
     */
    public function __construct($app, Horde_Perms $perms)
    {
        $this->_app = $app;
        $this->_permsObject = $perms;
        $this->__wakeup();
    }

    /**
     * Initializes the object.
     *
     * @throws Horde_Exception
     */
    public function __wakeup()
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
    public function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['_sortList']);
        $properties = array_keys($properties);
        return $properties;
    }

    /**
     * Returns the application we're managing shares for.
     *
     * @return string  The application this share belongs to.
     */
    public function getApp()
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
    public function getShare($name)
    {
        if (isset($this->_cache[$name])) {
            return $this->_cache[$name];
        }

        $share = $this->_getShare($name);
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
    public function getShareById($cid)
    {
        if (!isset($this->_shareMap[$cid])) {
            $share = $this->_getShareById($cid);
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
    public function getShares($cids)
    {
        $all_shares = $missing_ids = array();
        foreach ($cids as $cid) {
            if (isset($this->_shareMap[$cid])) {
                $all_shares[$this->_shareMap[$cid]] = $this->_cache[$this->_shareMap[$cid]];
            } else {
                $missing_ids[] = $cid;
            }
        }

        if (count($missing_ids)) {
            $shares = $this->_getShares($missing_ids);
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
    public function listAllShares()
    {
        $shares = $this->_listAllShares();
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
    public function listShares($userid, $perm = Horde_Perms::SHOW, $attributes = null,
                        $from = 0, $count = 0, $sort_by = null, $direction = 0)
    {
        $shares = $this->_listShares($userid, $perm, $attributes, $from,
                                     $count, $sort_by, $direction);
        if (!count($shares)) {
            return $shares;
        }

        $shares = $this->getShares($shares);
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
    public function listSystemShares()
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
    public function countShares($userid, $perm = Horde_Perms::SHOW, $attributes = null)
    {
        return $this->_countShares($userid, $perm, $attributes);
    }

    /**
     * Returns a new share object.
     *
     * @param string $name  The share's name.
     *
     * @return Horde_Share_Object  A new share object.
     * @throws Horde_Share_Exception
     */
    public function newShare($name)
    {
        if (empty($name)) {
            throw new Horde_Share_Exception('Share names must be non-empty');
        }
        $share = $this->_newShare($name);
        $share->setShareOb($this);
        $share->set('owner', $GLOBALS['registry']->getAuth());

        return $share;
    }

    /**
     * Adds a share to the shares system.
     *
     * The share must first be created with Horde_Share::newShare(), and have
     * any initial details added to it, before this function is called.
     *
     * @param Horde_Share_Object $share  The new share object.
     *
     * @return boolean
     * @throws Horde_Share_Exception
     */
    public function addShare(Horde_Share_Object $share)
    {
        try {
            Horde::callHook('share_add', array($share));
        } catch (Horde_Exception_HookNotSet $e) {}

        $result = $this->_addShare($share);

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
     * @throws Horde_Share_Exception
     */
    public function removeShare(Horde_Share_Object $share)
    {
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
    public function exists($share)
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
    public function getPermissions($share, $user = null)
    {
        if (!($share instanceof Horde_Share_Object)) {
            $share = $this->getShare($share);
        }

        return $this->_permsObject->getPermissions($share->getPermission(), $user);
    }

    /**
     * Set the class type to use for creating share objects.
     *
     * @var string $classname  The classname to use.
     */
    public function setShareClass($classname)
    {
        $this->_shareObject = $classname;
    }

    /**
     * Getter for Horde_Perms object
     *
     * @return Horde_Perms
     */
    public function getPermsObject()
    {
        return $this->_permsObject;
    }

    /**
     * Convert TO the storage driver's charset. Individual share objects should
     * implement this method if needed.
     *
     * @param array $data  Data to be converted.
     */
    public function toDriverCharset($data)
    {
        // noop
    }
    
    /**
     * Utility function to be used with uasort() for sorting arrays of
     * Horde_Share objects.
     *
     * Example:
     * <code>
     * uasort($list, array('Horde_Share', '_sortShares'));
     * </code>
     */
    protected function _sortShares($a, $b)
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

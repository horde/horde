<?php
/**
 * Base class for all Horde_Share drivers.
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
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Share
 */
abstract class Horde_Share_Base
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
     * The current user
     *
     * @var string
     */
    protected $_user;

    /**
     * The Horde_Group driver
     *
     * @var Horde_Group
     */
    protected $_groups;

    /**
     * A callback that is passed to the share objects for setting the objects'
     * Horde_Share object.
     *
     * @var callback
     */
    protected $_shareCallback;

    /**
     * Logger
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Configured callbacks. We currently support:
     *<pre>
     * add      - Called immediately before a new share is added. Receives the
     *            share object as a parameter.
     * modify   - Called immediately before a share object's changes are saved
     *            to storage. Receives the share object as a parameter.
     * remove   - Called immediately before a share is removed from storage.
     *            Receives the share object as a parameter.
     * list     - Called immediately after a list of shares is received from
     *            storage. Passed the userid, share list, and any parameters
     *            passed to the listShare call. Should return the (possibly
     *            modified) share list. @see listShares() for more
     *            info.
     *</pre>
     *
     * @var array
     */
    protected $_callbacks;

    /**
     * Constructor.
     *
     * @param string $app          The application that the shares belong to
     * @param string $user         The current user
     * @param Horde_Perms $perms   The permissions object
     * @param Horde_Group $groups  The Horde_Group object
     *
     */
    public function __construct($app, $user, Horde_Perms $perms, Horde_Group $groups)
    {
        $this->_app = $app;
        $this->_user = $user;
        $this->_permsObject = $perms;
        $this->_groups = $groups;
        $this->_logger = new Horde_Support_Stub();
    }

    /**
     * Set a logger object.
     *
     * @inject
     *
     * @var Horde_Log_Logger $logger  The logger object.
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * (re)connect the share object to this share driver.
     *
     * @param Horde_Share_Object $object
     */
    public function initShareObject(Horde_Share_Object $object)
    {
        $object->setShareOb($this->_shareCallback);
    }

    public function setShareCallback($callback)
    {
        $this->_shareCallback = $callback;
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
        $this->_shareMap[$share->getId()] = $name;
        $this->_cache[$name] = $share;

        return $share;
    }

    /**
     * Returns a Horde_Share_Object_sql object corresponding to the given
     * share name, with the details retrieved appropriately.
     *
     * @param string $name  The name of the share to retrieve.
     *
     * @return Horde_Share_Object  The requested share.
     * @throws Horde_Exception_NotFound
     * @throws Horde_Share_Exception
     */
    abstract protected function _getShare($name);

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
            $name = $share->getName();
            $this->_cache[$name] = $share;
            $this->_shareMap[$cid] = $name;
        }

        return $this->_cache[$this->_shareMap[$cid]];
    }

    /**
     * Returns a Horde_Share_Object_sql object corresponding to the given
     * unique ID, with the details retrieved appropriately.
     *
     * @param integer $id  The id of the share to retrieve.
     *
     * @return Horde_Share_Object_sql  The requested share.
     * @throws Horde_Share_Exception, Horde_Exception_NotFound
     */
    abstract protected function _getShareById($id);

    /**
     * Returns an array of Horde_Share_Object objects corresponding to the
     * given set of unique IDs, with the details retrieved appropriately.
     *
     * @param array $cids  The array of ids to retrieve.
     *
     * @return array  The requested shares.
     */
    public function getShares(array $cids)
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
                $this->_shareMap[$shares[$key]->getId()] = $key;
                $all_shares[$key] = $this->_cache[$key];
            }
        }

        return $all_shares;
    }

    /**
     * Returns an array of Horde_Share_Object_sql objects corresponding
     * to the given set of unique IDs, with the details retrieved
     * appropriately.
     *
     * @param array $ids  The array of ids to retrieve.
     *
     * @return array  The requested shares.
     * @throws Horde_Share_Exception
     */
    abstract protected function _getShares(array $ids);

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
     * Lists *all* shares for the current app/share, regardless of permissions.
     *
     * @return array  All shares for the current app/share.
     * @throws Horde_Share_Exception
     */
    abstract protected function _listAllShares();

    /**
     * Returns an array of all shares that $userid has access to.
     *
     * @param string $userid  The userid of the user to check access for.
     * @param array $params   Additional parameters for the search.
     *<pre>
     *  'perm'        Require this level of permissions. Horde_Perms constant.
     *  'attributes'  Restrict shares to these attributes. A hash or username.
     *  'from'        Offset. Start at this share
     *  'count'       Limit.  Only return this many.
     *  'sort_by'     Sort by attribute.
     *  'direction'   Sort by direction.
     *</pre>
     *
     * @return array  The shares the user has access to.
     */
    public function listShares($userid, array $params = array())
    {
        $params = array_merge(array('perm' => Horde_Perms::SHOW,
                                    'attributes' => null,
                                    'from' => 0,
                                    'count' => 0,
                                    'sort_by' => null,
                                    'direction' => 0),
                              $params);

        $shares = $this->_listShares($userid, $params);
        if (!count($shares)) {
            return $shares;
        }

        $shares = $this->getShares($shares);
        if (is_null($params['sort_by'])) {
            $this->_sortList = $shares;
            uasort($shares, array($this, '_sortShares'));
            $this->_sortList = null;
        }

        // Run the results through the callback, if configured.
        if (!empty($this->_callbacks['list'])) {
            return $this->runCallback('list', array($userid, $shares, $params));
        }

        return $shares;
    }

    /**
     * Returns an array of all shares that $userid has access to.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param array  $params     See listShares().
     *
     * @return array  The shares the user has access to.
     */
    abstract protected function _listShares($userid, array $params = array());

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
    public function countShares($userid, $perm = Horde_Perms::SHOW,
                                $attributes = null)
    {
        return count($this->_listShares($userid, array('perm' => $perm, 'attributes' => $attributes)));
    }

    /**
     * Returns a new share object.
     *
     * @param string $owner The share owner name.
     * @param string $name  The share's name.
     *
     * @return Horde_Share_Object  A new share object.
     * @throws Horde_Share_Exception
     */
    public function newShare($owner, $name = '')
    {
        $share = $this->_newShare($name);
        $share->set('owner', $owner);
        $share->setShareOb(empty($this->_shareCallback) ? $this : $this->_shareCallback);

        return $share;
    }

    /**
     * Returns a new share object.
     *
     * @param string $name   The share's name.
     *
     * @return Horde_Share_Object  A new share object
     * @throws InvalidArgumentException
     */
    abstract protected function _newShare($name);

    /**
     * Adds a share to the shares system.
     *
     * The share must first be created with newShare(), and have any initial
     * details added to it, before this function is called.
     *
     * @param Horde_Share_Object $share  The new share object.
     *
     * @throws Horde_Share_Exception
     */
    public function addShare(Horde_Share_Object $share)
    {
        // Run the results through the callback, if configured.
        $this->runCallback('add', array($share));
        $this->_addShare($share);

        /* Store new share in the caches. */
        $id = $share->getId();
        $name = $share->getName();
        $this->_cache[$name] = $share;
        $this->_shareMap[$id] = $name;

        /* Reset caches that depend on unknown criteria. */
        $this->_listcache = array();
    }

    /**
     * Adds a share to the shares system.
     *
     * The share must first be created with
     * Horde_Share_sql::_newShare(), and have any initial details added
     * to it, before this function is called.
     *
     * @param Horde_Share_Object $share  The new share object.
     */
    abstract protected function _addShare(Horde_Share_Object $share);

    /**
     * Removes a share from the shares system permanently.
     *
     * @param Horde_Share_Object $share  The share to remove.
     *
     * @throws Horde_Share_Exception
     */
    public function removeShare(Horde_Share_Object $share)
    {
        // Run the results through the callback, if configured.
        $this->runCallback('remove', array($share));

        /* Remove share from the caches. */
        $id = $share->getId();
        unset($this->_shareMap[$id]);
        unset($this->_cache[$share->getName()]);

        /* Reset caches that depend on unknown criteria. */
        $this->_listcache = array();

        $this->_removeShare($share);
    }

    /**
     * Removes a share from the shares system permanently.
     *
     * @param Horde_Share_Object $share  The share to remove.
     *
     * @throws Horde_Share_Exception
     */
    abstract protected function _removeShare(Horde_Share_Object $share);

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
     * Checks if a share exists in the system.
     *
     * @param string $share  The share to check.
     *
     * @return boolean  True if the share exists.
     * @throws Horde_Share_Exception
     */
    abstract protected function _exists($share);

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
        return $data;
    }

    /**
     * Add a callback to the collection
     *
     * @param string $type
     * @param array $callback
     */
    public function addCallback($type, $callback)
    {
        $this->_callbacks[$type] = $callback;
    }

    /**
     * Returns the share's list cache.
     *
     * @return array
     */
    public function getListCache()
    {
        return $this->_listcache;
    }

    /**
     * Set the list cache.
     *
     * @param array $cache
     */
    public function setListCache($cache)
    {
        $this->_listcache = $cache;
    }

    /**
     * Give public access to call the share callbacks. Needed to run the
     * callbacks from the Horde_Share_Object objects.
     *
     * @param string $type   The callback to run
     * @param array $params  The parameters to pass to the callback.
     *
     * @return mixed
     */
    public function runCallback($type, $params)
    {
        if (!empty($this->_callbacks[$type])) {
            return call_user_func_array($this->_callbacks[$type], $params);
        }
    }

    /**
     * Expire the current list cache. This would be needed anytime a share is
     * either added, deleted, had a change in owner, parent, or perms.
     *
     */
    public function expireListCache()
    {
        $this->_listcache = array();
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

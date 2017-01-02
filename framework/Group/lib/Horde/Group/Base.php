<?php
/**
 * Copyright 1999-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Group
 */

/**
 * Horde_Group_Base is the base class for all drivers of the Horde group
 * system.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Group
 */
abstract class Horde_Group_Base
{
    /** Cache prefix. */
    const CACHE_PREFIX = 'horde_group_';

    /** Cache version. */
    const CACHE_VERSION = 1;

    /**
     * Cache object.
     *
     * @since Horde_Group 2.1.0
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * Constructor.
     */
    public function __construct($params = array())
    {
        if (isset($params['cache'])) {
            $this->_cache = $params['cache'];
        } else {
            $this->_cache = new Horde_Support_Stub();
        }
    }

    /**
     * Returns whether the group backend is read-only.
     *
     * @return boolean
     */
    public function readOnly()
    {
        return true;
    }

    /**
     * Returns whether groups can be renamed.
     *
     * @return boolean
     */
    public function renameSupported()
    {
        return true;
    }

    /**
     * Sets a cache object.
     *
     * @inject
     * @since Horde_Group 2.1.0
     * @param Horde_Cache $cache  The cache object.
     */
    public function setCache(Horde_Cache $cache)
    {
        $this->_cache = $cache;
    }

    /**
     * Returns the cache object.
     *
     * @since Horde_Group 2.1.0
     * @return Horde_Cache
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * Creates a new group.
     *
     * @param string $name   A group name.
     * @param string $email  The group's email address.
     *
     * @return mixed  The ID of the created group.
     * @throws Horde_Group_Exception
     */
    public function create($name, $email = null)
    {
        // Create group.
        $gid = $this->_create($name, $email);

        // Update caches.
        if (($list = $this->_getListCache()) !== null) {
            $list[$gid] = $name;
            $this->_setListCache($list);
        }
        try {
            $this->_cache->set($this->_sig('name_' . $gid), $name);
            $this->_cache->set($this->_sig('exists_' . $gid), 1);
            $this->_cache->set(
                $this->_sig('data_' . $gid),
                serialize(array('name' => $name, 'email' => $email))
            );
        } catch (Horde_Cache_Exception $e) {
        }

        return $gid;
    }

    /**
     * Creates a new group.
     *
     * @param string $name   A group name.
     * @param string $email  The group's email address.
     *
     * @return mixed  The ID of the created group.
     * @throws Horde_Group_Exception
     */
    protected function _create($name, $email = null)
    {
        throw new Horde_Group_Exception('This group backend is read-only.');
    }

    /**
     * Renames a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $name  The new name.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function rename($gid, $name)
    {
        // Rename group.
        $this->_rename($gid, $name);

        // Update list cache, if propagated.
        if (($list = $this->_getListCache()) !== null) {
            $list[$gid] = $name;
            $this->_setListCache($list);
        }

        // Update data and name cache.
        $sig = $this->_sig('data_' . $gid);
        try {
            if ($data = $this->_cache->get($sig, 0)) {
                $data = @unserialize($data);
                $data['name'] = $name;
                $this->_cache->set($sig, serialize($data));
            }
            $this->_cache->set($this->_sig('name_' . $gid), $name);
        } catch (Horde_Cache_Exception $e) {
        }
    }

    /**
     * Renames a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $name  The new name.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    protected function _rename($gid, $name)
    {
        throw new Horde_Group_Exception('This group backend is read-only.');
    }

    /**
     * Removes a group.
     *
     * @param mixed $gid  A group ID.
     *
     * @throws Horde_Group_Exception
     */
    public function remove($gid)
    {
        // Remove group.
        $this->_remove($gid);

        // Update caches.
        if (($list = $this->_getListCache()) !== null) {
            unset($list[$gid]);
            $this->_setListCache($list);
        }
        try {
            $this->_cache->expire($this->_sig('name_' . $gid));
            $this->_cache->expire($this->_sig('data_' . $gid));
            $this->_cache->set($this->_sig('exists_' . $gid), 0);
        } catch (Horde_Cache_Exception $e) {
        }
    }

    /**
     * Removes a group.
     *
     * @param mixed $gid  A group ID.
     *
     * @throws Horde_Group_Exception
     */
    protected function _remove($gid)
    {
        throw new Horde_Group_Exception('This group backend is read-only.');
    }

    /**
     * Checks if a group exists.
     *
     * @param mixed $gid  A group ID.
     *
     * @return boolean  True if the group exists.
     * @throws Horde_Group_Exception
     */
    public function exists($gid)
    {
        // Check list cache.
        if ((($list = $this->_getListCache()) !== null) &&
            isset($list[$gid])) {
            return true;
        }

        // Check "exists" cache.
        if (is_bool($exists = $this->_checkExistsCache($gid))) {
            return $exists;
        }

        // Existance check.
        $exists = $this->_exists($gid);

        // Update "exists" cache.
        try {
            $this->_cache->set($this->_sig('exists_' . $gid), (int)$exists);
        } catch (Horde_Cache_Exception $e) {
        }

        return $exists;
    }

    protected function _checkExistsCache($gid)
    {
        // Use strlen() to catch "0" and "1".
        try {
            if (strlen($exists = $this->_cache->get($this->_sig('exists_' . $gid), 0))) {
                return (bool)$exists;
            }
        } catch (Horde_Cache_Exception $e) {
        }
    }

    /**
     * Checks if a group exists.
     *
     * @param mixed $gid  A group ID.
     *
     * @return boolean  True if the group exists.
     * @throws Horde_Group_Exception
     */
    abstract protected function _exists($gid);

    /**
     * Returns a group name.
     *
     * @param mixed $gid  A group ID.
     *
     * @return string  The group's name.
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getName($gid)
    {
        // Check list cache.
        if (($list = $this->_getListCache()) !== null) {
            if (!isset($list[$gid])) {
                throw new Horde_Exception_NotFound();
            }
            return $list[$gid];
        }

        // Check "exists" cache.
        if ($this->_checkExistsCache($gid) === false) {
            throw new Horde_Exception_NotFound();
        }

        // Check name cache.
        $sig = $this->_sig('name_' . $gid);
        try {
            if ($name = $this->_cache->get($sig, 0)) {
                return $name;
            }
        } catch (Horde_Cache_Exception $e) {
        }

        // Retrieve name.
        $name = $this->_getName($gid);

        // Update name cache.
        try {
            $this->_cache->set($sig, $name);
        } catch (Horde_Cache_Exception $e) {
        }

        return $name;
    }

    /**
     * Returns a group name.
     *
     * @param mixed $gid  A group ID.
     *
     * @return string  The group's name.
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    abstract protected function _getName($gid);

    /**
     * Returns all available attributes of a group.
     *
     * @param mixed $gid  A group ID.
     *
     * @return array  The group's data.
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getData($gid)
    {
        // Check list cache.
        if (($list = $this->_getListCache()) !== null &&
            !isset($list[$gid])) {
            throw new Horde_Exception_NotFound();
        }

        // Check "exists" cache.
        if ($this->_checkExistsCache($gid) === false) {
            throw new Horde_Exception_NotFound();
        }

        // Check data cache.
        $sig = $this->_sig('data_' . $gid);
        try {
            if ($data = $this->_cache->get($sig, 0)) {
                return @unserialize($data);
            }
        } catch (Horde_Cache_Exception $e) {
        }

        // Retrieve data.
        $data = $this->_getData($gid);

        // Update data cache.
        try {
            $this->_cache->set($sig, serialize($data));
        } catch (Horde_Cache_Exception $e) {
        }

        return $data;
    }

    /**
     * Returns all available attributes of a group.
     *
     * @param mixed $gid  A group ID.
     *
     * @return array  The group's date.
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    abstract protected function _getData($gid);

    /**
     * Sets one or more attributes of a group.
     *
     * @param mixed $gid               A group ID.
     * @param array|string $attribute  An attribute name or a hash of
     *                                 attributes.
     * @param string $value            An attribute value if $attribute is a
     *                                 string.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function setData($gid, $attribute, $value = null)
    {
        // Store data.
        $this->_setData($gid, $attribute, $value);

        // Update data cache.
        $sig = $this->_sig('data_' . $gid);
        try {
            if ($data = $this->_cache->get($sig, 0)) {
                $data = @unserialize($data);
            } else {
                $data = array();
            }
        } catch (Horde_Cache_Exception $e) {
        }
        if (is_array($attribute)) {
            $data = $attribute;
        } else {
            $data[$attribute] = $value;
        }
        try {
            $this->_cache->set($sig, serialize($data));
        } catch (Horde_Cache_Exception $e) {
        }

        // Update name and list caches.
        $name = null;
        if (is_array($attribute)) {
            if (isset($attribute['name'])) {
                $name = $attribute['name'];
            }
        } elseif ($attribute == 'name') {
            $name = $value;
        }
        if ($name) {
            try {
                $this->_cache->set($this->_sig('name_' . $gid), $name);
            } catch (Horde_Cache_Exception $e) {
            }
            if (($list = $this->_getListCache()) !== null) {
                $list[$gid] = $name;
                $this->_setListCache($list);
            }
        }
    }

    /**
     * Sets one or more attributes of a group.
     *
     * @param mixed $gid               A group ID.
     * @param array|string $attribute  An attribute name or a hash of
     *                                 attributes.
     * @param string $value            An attribute value if $attribute is a
     *                                 string.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    protected function _setData($gid, $attribute, $value = null)
    {
        throw new Horde_Group_Exception('This group backend is read-only.');
    }

    /**
     * Returns a list of all groups a user may see, with IDs as keys and names
     * as values.
     *
     * @param string $member  Only return groups that this user is a member of.
     *
     * @return array  All existing groups.
     * @throws Horde_Group_Exception
     */
    public function listAll($member = null)
    {
        if (!is_null($member)) {
            return $this->listGroups($member);
        }

        // Check list cache.
        if (($list = $this->_getListCache()) !== null) {
            return $list;
        }

        // Retrieve all groups.
        $list = $this->_listAll();

        // Update list cache.
        $this->_setListCache($list);

        return $list;
    }

    /**
     * Returns a list of all groups a user may see, with IDs as keys and names
     * as values.
     *
     * @param string $member  Only return groups that this user is a member of.
     *
     * @return array  All existing groups.
     * @throws Horde_Group_Exception
     */
    abstract protected function _listAll();

    /**
     * Returns a list of users in a group.
     *
     * @param mixed $gid  A group ID.
     *
     * @return array  List of group users.
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function listUsers($gid)
    {
        // Check user cache.
        $sig = $this->_sig('users_' . $gid);
        try {
            if ($users = $this->_cache->get($sig, 0)) {
                return @unserialize($users);
            }
        } catch (Horde_Cache_Exception $e) {
        }

        $users = $this->_listUsers($gid);

        // Update users cache.
        try {
            $this->_cache->set($sig, serialize($users));
        } catch (Horde_Cache_Exception $e) {
        }

        return $users;
    }

    /**
     * Returns a list of users in a group.
     *
     * @param mixed $gid  A group ID.
     *
     * @return array  List of group users.
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    abstract protected function _listUsers($gid);

    /**
     * Returns a list of groups a user belongs to.
     *
     * @param string $user  A user name.
     *
     * @return array  A list of groups, with IDs as keys and names as values.
     * @throws Horde_Group_Exception
     */
    public function listGroups($user)
    {
        // Check list cache.
        if (($list = $this->_getListCache($user)) !== null) {
            return $list;
        }

        // Retrieve all groups.
        $list = $this->_listGroups($user);

        // Update list cache.
        $this->_setListCache($list, $user);

        return $list;
    }

    /**
     * Returns a list of groups a user belongs to.
     *
     * @param string $user  A user name.
     *
     * @return array  A list of groups, with IDs as keys and names as values.
     * @throws Horde_Group_Exception
     */
    abstract protected function _listGroups($user);

    /**
     * Add a user to a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $user  A user name.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function addUser($gid, $user)
    {
        $this->_addUser($gid, $user);

        // Update users cache.
        $sig = $this->_sig('users_' . $gid);
        try {
            if ($users = $this->_cache->get($sig, 0)) {
                $users = @unserialize($users);
                $users[] = $user;
                $this->_cache->set($sig, serialize($users));
            }
        } catch (Horde_Cache_Exception $e) {
        }
    }

    /**
     * Add a user to a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $user  A user name.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    protected function _addUser($gid, $user)
    {
        throw new Horde_Group_Exception('This group backend is read-only.');
    }

    /**
     * Removes a user from a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $user  A user name.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function removeUser($gid, $user)
    {
        $this->_removeUser($gid, $user);

        // Update users cache.
        $sig = $this->_sig('users_' . $gid);
        try {
            if ($users = $this->_cache->get($sig, 0)) {
                $users = array_flip(@unserialize($users));
                unset($users[$user]);
                $this->_cache->set($sig, serialize(array_keys($users)));
            }
        } catch (Horde_Cache_Exception $e) {
        }
    }

    /**
     * Removes a user from a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $user  A user name.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    protected function _removeUser($gid, $user)
    {
        throw new Horde_Group_Exception('This group backend is read-only.');
    }

    /**
     * Searches for group names.
     *
     * @param string $name  A search string.
     *
     * @return array  A list of matching groups, with IDs as keys and names as
     *                values.
     * @throws Horde_Group_Exception
     */
    public function search($name)
    {
        // Check search cache.
        $sig = $this->_sig('search_' . hash('md5', $name));
        try {
            if ($groups = $this->_cache->get($sig, 0)) {
                return @unserialize($groups);
            }
        } catch (Horde_Cache_Exception $e) {
        }

        $groups = $this->_search($name);

        // Update groups cache.
        try {
            $this->_cache->set($sig, serialize($groups));
        } catch (Horde_Cache_Exception $e) {
        }

        return $groups;
    }

    /**
     * Searches for group names.
     *
     * @param string $name  A search string.
     *
     * @return array  A list of matching groups, with IDs as keys and names as
     *                values.
     * @throws Horde_Group_Exception
     */
    abstract protected function _search($name);

    /**
     * Returns a full cache key.
     *
     * @param string $key  The internal key.
     *
     * @return string  The full key for Horde_Cache consumption.
     */
    protected function _sig($key)
    {
        return self::CACHE_PREFIX . self::CACHE_VERSION . '_' . $key;
    }

    /**
     * Returns the cached group list.
     *
     * @param string $user  A user name.
     *
     * @return array  The group list or null if not cached.
     */
    protected function _getListCache($user = null)
    {
        $sig = $this->_sig('list');
        if (!is_null($user)) {
            $sig .= '_' . $user;
        }
        try {
            if ($list = $this->_cache->get($sig, 0)) {
                return @unserialize($list);
            }
        } catch (Horde_Cache_Exception $e) {
        }
    }

    /**
     * Sets the cached group list.
     *
     * @param array $list   A group list.
     * @param string $user  A user name.
     */
    protected function _setListCache(array $list, $user = null)
    {
        $sig = $this->_sig('list');
        if (!is_null($user)) {
            $sig .= '_' . $user;
        }
        try {
            $this->_cache->set($sig, serialize($list));
        } catch (Horde_Cache_Exception $e) {
        }
    }
}

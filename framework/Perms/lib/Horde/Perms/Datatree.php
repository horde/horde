<?php
/**
 * The Horde_Perms_Datatree:: class provides a DataTree driver for the Horde
 * permissions system.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Horde_Perms
 */
class Horde_Perms_Datatree extends Horde_Perms
{
    /**
     * Pointer to a DataTree instance to manage the different permissions.
     *
     * @var DataTree
     */
    protected $_datatree;

    /**
     * Incrementing version number if cached classes change.
     *
     * @var integer
     */
    private $_cacheVersion = 2;

    /**
     * Cache for getPermission().
     *
     * @var array
     */
    protected $_permsCache = array();

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters (in addition to base
     *                       Horde_Perms parameters):
     * <pre>
     * 'datatree' - (DataTree) A datatree object. [REQUIRED]
     * </pre>
     *
     * @throws Horde_Perms_Exception
     */
    public function __construct($params = array())
    {
        if (empty($params['datatree'])) {
            throw new Horde_Perms_Exception('You must configure a DataTree backend.');
        }

        $this->_datatree = $params['datatree'];

        parent::__construct($params);
    }

    /**
     * Returns a new permissions object.
     *
     * @param string $name  The permission's name.
     *
     * @return DataTreeObject_Permissions  A new permissions object.
     */
    public function newPermission($name)
    {
        $type = 'matrix';
        $params = null;

        if ($pos = strpos($name, ':')) {
            try {
                $info = $this->getApplicationPermissions(substr($name, 0, $pos));
                if (isset($info['type']) && isset($info['type'][$name])) {
                    $type = $info['type'][$name];
                }

                if (isset($info['params']) && isset($info['params'][$name])) {
                    $params = $info['params'][$name];
                }
            } catch (Horde_Perms_Exception $e) {}
        }

        $perm = new Horde_Perms_Permission_DataTreeObject($name, $this->_cacheVersion, $type, $params);
        $perm->setCacheOb($this->_cache);
        $perm->setDataTree($this->_datatree);

        return $perm;
    }

    /**
     * Returns a permission object corresponding to the named permission,
     * with the users and other data retrieved appropriately.
     *
     * @param string $name  The name of the permission to retrieve.
     *
     * @return TODO
     */
    public function getPermission($name)
    {
        if (isset($this->_permsCache[$name])) {
            return $this->_permsCache[$name];
        }

        $perm = $this->_cache->get('perm_' . $this->_cacheVersion . $name, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($perm === false) {
            $perm = $this->_datatree->getObject($name, 'Horde_Perms_Permission_DataTreeObject');
            $perm->setCacheVersion($this->_cacheVersion);
            $this->_cache->set('perm_' . $this->_cacheVersion . $name, serialize($perm), $GLOBALS['conf']['cache']['default_lifetime']);
            $this->_permsCache[$name] = $perm;
        } else {
            $this->_permsCache[$name] = unserialize($perm);
        }

        $this->_permsCache[$name]->setCacheOb($this->_cache);
        $this->_permsCache[$name]->setDataTree($this->_datatree);

        return $this->_permsCache[$name];
    }

    /**
     * Returns a permission object corresponding to the given unique ID,
     * with the users and other data retrieved appropriately.
     *
     * @param integer $cid  The unique ID of the permission to retrieve.
     */
    public function getPermissionById($cid)
    {
        if ($cid == Horde_Perms::ROOT) {
            return $this->newPermission(Horde_Perms::ROOT);
        }
        $perm = $this->_datatree->getObjectById($cid, 'Horde_Perms_Permission_DataTreeObject');
        $perm->setCacheVersion($this->_cacheVersion);
        return $perm;
    }

    /**
     * Adds a permission to the permissions system. The permission must first
     * be created with newPermission(), and have any initial users added to
     * it, before this function is called.
     *
     * @param Horde_Perms_Permission_DataTreeObject $perm  The new perm
     *                                                     object.
     * @throws Horde_Perms_Exception
     */
    public function addPermission(Horde_Perms_Permission_DataTreeObject $perm)
    {
        $name = $perm->getName();
        if (empty($name)) {
            throw Horde_Perms_Exception('Permission names must be non-empty.');
        }
        $this->_cache->expire('perm_' . $this->_cacheVersion . $name);
        $this->_cache->expire('perm_exists_' . $this->_cacheVersion . $name);

        return $this->_datatree->add($perm);
    }

    /**
     * Removes a permission from the permissions system permanently.
     *
     * @param Horde_Perms_Permission_DataTreeObject $perm  The permission to
     *                                                     remove.
     * @param boolean $force                               Force to remove
     *                                                     every child.
     */
    public function removePermission(Horde_Perms_Permission_DataTreeObject $perm, $force = false)
    {
        $keys = $this->_datatree->get(DATATREE_FORMAT_FLAT, $perm->name, true);
        foreach ($keys as $key) {
            $this->_cache->expire('perm_' . $this->_cacheVersion . $key);
            $this->_cache->expire('perm_exists_' . $this->_cacheVersion . $key);
        }

        return $this->_datatree->remove($perm->name, $force);
    }

    /**
     * Returns the unique identifier of this permission.
     *
     * @param Horde_Perms_Permission_DataTreeObject $perm  The permission
     *                                                     object to get the
     *                                                     ID of.
     *
     * @return integer  The unique id.
     */
    public function getPermissionId($permission)
    {
        return $this->_datatree->getId($permission->getName());
    }

    /**
     * Checks if a permission exists in the system.
     *
     * @param string $permission  The permission to check.
     *
     * @return boolean  True if the permission exists.
     */
    public function exists($permission)
    {
        $key = 'perm_exists_' . $this->_cacheVersion . $permission;
        $exists = $this->_cache->get($key, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($exists === false) {
            $exists = $this->_datatree->exists($permission);
            $this->_cache->set($key, (string)$exists);
        }

        return (bool)$exists;
    }

    /**
     * Returns a list of parent permissions.
     *
     * @param string $child  The name of the child to retrieve parents for.
     *
     * @return array  A hash with all parents in a tree format.
     */
    public function getParents($child)
    {
        return $this->_datatree->getParents($child);
    }

    /**
     * Returns a child's direct parent ID.
     *
     * @param mixed $child  Either the object, an array containing the
     *                      path elements, or the object name for which
     *                      to look up the parent's ID.
     *
     * @return mixed  The unique ID of the parent or PEAR_Error on error.
     */
    public function getParent($child)
    {
        return $this->_datatree->getParent($child);
    }

    /**
     * Returns all permissions of the system in a tree format.
     *
     * @return array  A hash with all permissions in a tree format.
     */
    public function getTree()
    {
        return $this->_datatree->get(DATATREE_FORMAT_FLAT, Horde_Perms::ROOT, true);
    }

}

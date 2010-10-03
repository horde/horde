<?php
/**
 * Extension of the Horde_Permission class for storing permission
 * information in the SQL driver.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Duck <duck@obala.net>
 * @category Horde
 * @package  Horde_Perms
 */
class Horde_Perms_Permission_SqlObject extends Horde_Perms_Permission
{
    /**
     * The string permission id.
     *
     * @var string
     */
    protected $_id;

    /**
     * Cache object.
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * Database handle for saving changes.
     *
     * @var Horde_Db_Adapter_Base
     */
    protected $_db;

    /**
     * Tasks to run on serialize().
     *
     * @return array  Parameters that are stored.
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_class_vars(__CLASS__)), array('_cache', '_db'));
    }

    /**
     * Sets the helper functions within the object.
     *
     * @param Horde_Cache $cache         The cache object.
     * @param Horde_Db_Adapter_Base $db  The database object.
     */
    public function setObs(Horde_Cache $cache, Horde_Db_Adapter_Base $db)
    {
        $this->_cache = $cache;
        $this->_db = $db;
    }

    /**
     * Get permission ID.
     *
     * @return TODO
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set permission id.
     *
     * @param string $id  Permission ID.
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * Saves any changes to this object to the backend permanently. New
     * objects are added instead.
     *
     * @throws Horde_Perms_Exception
     */
    public function save()
    {
        if (!isset($this->_db)) {
            throw new Horde_Perms_Exception('Cannot save because the DB instances has not been set in this object.');
        }

        $name = $this->getName();
        if (empty($name)) {
            throw new Horde_Perms_Exception('Permission names must be non-empty');
        }

        $query = 'UPDATE horde_perms SET perm_data = ? WHERE perm_id = ?';
        $params = array(serialize($this->data), $this->getId());

        try {
            $this->_db->update($query, $params);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Perms_Exception($e);
        }

        $this->_cache->expire('perm_sql_' . $this->_cacheVersion . $name);
        $this->_cache->expire('perm_sql_exists_' . $this->_cacheVersion . $name);
    }

}

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
     * Database handle for saving changes.
     *
     * @var DB
     */
    protected $_write_db;

    /**
     * Associates a DB object with this share.
     *
     * @param DB $write_db  The DB object.
     */
    public function setSqlOb($write_db)
    {
        $this->_write_db = $write_db;
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
        $name = $this->getName();
        if (empty($name)) {
            throw new Horde_Perms_Exception('Permission names must be non-empty');
        }
        $query = 'UPDATE horde_perms SET perm_data = ? WHERE perm_id = ?';
        $params = array(serialize($this->data), $this->getId());
        $result = $this->_write_db->query($query, $params);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Perms_Exception($result);
        }

        $cache = $GLOBALS['injector']->getInstance('Horde_Cache');
        $cache->expire('perm_sql_' . $this->_cacheVersion . $name);
        $cache->expire('perm_sql_exists_' . $this->_cacheVersion . $name);
    }

}

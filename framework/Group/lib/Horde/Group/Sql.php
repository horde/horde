<?php
/**
 * This class provides an SQL driver for the Horde group system.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Duck <duck@obala.net>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
class Horde_Group_Sql extends Horde_Group_Base
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     */
    public function __construct($params)
    {
        if (!isset($params['db'])) {
            throw new Horde_Group_Exception('The \'db\' parameter is missing.');
        }
        $this->_db = $params['db'];
    }

    /**
     * Returns whether the group backend is read-only.
     *
     * @return boolean
     */
    public function readOnly()
    {
        return false;
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
        try {
            return $this->_db->insert(
                'INSERT INTO horde_groups (group_name, group_email) VALUES (?, ?)',
                array($name, $email));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }

    /**
     * Renames a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $name  The new name.
     *
     * @throws Horde_Group_Exception
     */
    public function rename($gid, $name)
    {
        try {
            return $this->_db->update(
                'UPDATE horde_groups SET group_name = ? WHERE group_uid = ?',
                array($name, $gid));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
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
        try {
            $this->_db->beginDbTransaction();
            $this->_db->delete(
                'DELETE FROM horde_groups_members WHERE group_uid = ?',
                array($gid));
            $this->_db->delete(
                'DELETE FROM horde_groups WHERE group_uid = ?',
                array($gid));
            $this->_db->commitDbTransaction();
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
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
    public function exists($gid)
    {
        try {
            return (bool)$this->_db->selectValue(
                'SELECT 1 FROM horde_groups WHERE group_uid = ?',
                array($gid));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }

    /**
     * Returns a group name.
     *
     * @param mixed $gid  A group ID.
     *
     * @return string  The group's name.
     * @throws Horde_Group_Exception
     */
    public function getName($gid)
    {
        try {
            return $this->_db->selectValue(
                'SELECT group_name FROM horde_groups WHERE group_uid = ?',
                array($gid));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }

    /**
     * Returns a list of all groups, with IDs as keys and names as values.
     *
     * @return array  All existing groups.
     * @throws Horde_Group_Exception
     */
    public function listAll()
    {
        try {
            return $this->_db->selectAssoc('SELECT group_uid, group_name FROM horde_groups');
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }

    /**
     * Returns a list of users in a group.
     *
     * @param mixed $gid  A group ID.
     *
     * @return array  List of group users.
     * @throws Horde_Group_Exception
     */
    public function listUsers($gid)
    {
        try {
            return $this->_db->selectValues(
                'SELECT user_uid FROM horde_groups_members WHERE group_uid = ? ORDER BY user_uid ASC',
                array($gid));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }

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
        try {
            return $this->_db->selectAssoc(
                'SELECT g.group_uid AS group_uid, g.group_name AS group_name FROM horde_groups g, horde_groups_members m WHERE m.user_uid = ? AND g.group_uid = m.group_uid ORDER BY g.group_name',
                array($user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }

    /**
     * Add a user to a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $user  A user name.
     *
     * @throws Horde_Group_Exception
     */
    public function addUser($gid, $user)
    {
        try {
            $this->_db->insert(
                'INSERT INTO horde_groups_members (group_uid, user_uid) VALUES (?, ?)', array($gid, $user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }

    /**
     * Removes a user from a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $user  A user name.
     *
     * @throws Horde_Group_Exception
     */
    public function removeUser($gid, $user)
    {
        try {
            $this->_db->delete(
                'DELETE FROM horde_groups_members WHERE group_uid = ? AND user_uid = ?',
                array($gid, $user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
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
        try {
            return $this->_db->selectAssoc(
                'SELECT group_uid, group_name FROM horde_groups WHERE group_name LIKE ?',
                array('%' . $name . '%'));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }
}

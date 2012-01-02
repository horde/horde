<?php
/**
 * This class provides a driver for the Horde group system based on Turba
 * contact lists.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Group
 */
class Horde_Group_Contactlists extends Horde_Group_Base
{
    /**
     * API object.
     *
     * @var Horde_Registry_Caller
     */
    protected $_api;

    /**
     * True if we are inside a listAll() call.
     *
     * This is used to protect against infinite recursion when the contacts API
     * is using groups to determine the available contact lists.
     *
     * @var boolean
     */
    protected $_inListAll = false;

    /**
     * True if we are inside a listGroups() call.
     *
     * This is used to protect against infinite recursion when the contacts API
     * is using groups to determine the available contact lists.
     *
     * @var boolean
     */
    protected $_inListGroups = false;

    /**
     * Constructor.
     */
    public function __construct($params)
    {
        if (!isset($params['api'])) {
            throw new Horde_Group_Exception('The \'api\' parameter is missing.');
        }
        $this->_api = $params['api'];
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
            return (bool)$this->_api->getGroupObject($gid);
        } catch (Horde_Exception $e) {
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
     * @throws Horde_Exception_NotFound
     */
    public function getName($gid)
    {
        $group = $this->getData($gid);
        return $group['name'];
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
    public function getData($gid)
    {
        try {
            $group = $this->_api->getGroupObject($gid);
        } catch (Horde_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
        if (!$group) {
            throw new Horde_Exception_NotFound('Group "' . $gid . '" not found');
        }
        return $group;
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
        if ($this->_inListAll) {
            return array();
        }
        $this->_inListAll = true;

        $list = array();
        try {
            foreach ($this->_api->getGroupObjects() as $id => $group) {
                $list[$id] = $group['name'];
            }
        } catch (Horde_Exception $e) {
            $this->_inListAll = false;
            throw new Horde_Group_Exception($e);
        }

        if (!is_null($member)) {
            $list = array_intersect_assoc($list, $this->listGroups($member));
        }

        $this->_inListAll = false;
        return $list;
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
    public function listUsers($gid)
    {
        try {
            return $this->_api->getGroupMembers($gid);
        } catch (Horde_Exception $e) {
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
        if ($this->_inListGroups) {
            return array();
        }
        $this->_inListGroups = true;
        try {
            $groups = $this->_api->getGroupMemberships($user);
        } catch (Horde_Exception $e) {
            $this->_inListGroups = false;
            throw new Horde_Group_Exception($e);
        }
        $this->_inListGroups = false;
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
    public function search($name)
    {
    }
}

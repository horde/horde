<?php
/**
 * Copyright 1999-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Group
 */

/**
 * The Horde_Group_File class provides an implementation of the Horde
 * group system for integration with /etc/group or a custom group file.
 *
 * File format is:
 * group_name:encrypted_passwd:GID:user_list
 *
 * encrypted_passwd is 'x' if shadow is used, user_list is comma separated
 * Note: the gid is normally the group name. See 'use_gid' parameter.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Group
 */
class Horde_Group_File extends Horde_Group_Base
{
    /**
     * List of groups parsed from the group file.
     *
     * @var array
     */
    protected $_groups = array();

    /**
     * Constructor.
     */
    public function __construct($params)
    {
        /* disable caching for now, otherwise we would need to
           monitor mtime changes on the input file and build our
           cache signature in _sig() including the mtime.
           Caching might even be more overhead for this lightweight backend.
        */
        if (isset($params['cache'])) {
            $params['cache'] = new Horde_Support_Stub();
        }

        $use_gid = false;
        if (isset($params['use_gid'])) {
            $use_gid = $params['use_gid'];
        }

        parent::__construct($params);

        $fp = @fopen($params['filename'], 'r');
        if (!$fp) {
            throw new Horde_Group_Exception(
                'Cannot open ' . $params['filename']);
        }

        while (!feof($fp)) {
            $line = fgets($fp);

            // file format: group_name:encrypted_passwd:GID:user_list
            if (!preg_match('/(.*?):.*?:(\\d+?):(.*)/', $line, $m)) {
                continue;
            }

            // either use gid from file or group name as id
            $id = $use_gid ? $m[2] : $m[1];
            $this->_groups[$id] = array(
                'name' => $m[1],
                'users' => explode(',', $m[3])
            );
        }
        fclose($fp);
    }

    /**
     * Checks if a group exists.
     *
     * @param mixed $gid  A group ID.
     *
     * @return boolean  True if the group exists.
     * @throws Horde_Group_Exception
     */
    protected function _exists($gid)
    {
        return isset($this->_groups[$gid]);
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
    protected function _getName($gid)
    {
        if (!$this->_exists($gid)) {
            throw new Horde_Exception_NotFound();
        }
        return $this->_groups[$gid]['name'];
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
    protected function _getData($gid)
    {
        if (!$this->_exists($gid)) {
            throw new Horde_Exception_NotFound();
        }

        return array();
    }

    /**
     * Returns a list of all groups a user may see, with IDs as keys and names
     * as values.
     *
     * @return array  All existing groups.
     * @throws Horde_Group_Exception
     */
    protected function _listAll()
    {
        $groups = array();
        foreach ($this->_groups as $id => $group) {
            $groups[$id] = $group['name'];
        }
        return $groups;
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
    protected function _listUsers($gid)
    {
        if (!$this->exists($gid)) {
            throw new Horde_Exception_NotFound();
        }
        return $this->_groups[$gid]['users'];
    }

    /**
     * Returns a list of groups a user belongs to.
     *
     * @param string $user  A user name.
     *
     * @return array  A list of groups, with IDs as keys and names as values.
     * @throws Horde_Group_Exception
     */
    protected function _listGroups($user)
    {
        $groups = array();
        foreach ($this->_groups as $id => $group) {
            if (in_array($user, $group['users'])) {
                $groups[$id] = $group['name'];
            }
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
    protected function _search($name)
    {
        $groups = array();
        foreach ($this->_groups as $id => $group) {
            if (stripos($group['name'], $name) !== false) {
                $groups[$id] = $group['name'];
            }
        }
        return $groups;
    }
}

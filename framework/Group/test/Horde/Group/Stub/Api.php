<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Group
 * @subpackage UnitTests
 * @copyright  2011 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */
class Horde_Group_Stub_Api
{
    protected $_groups = array(
        'localsql:79ad3f08f267d15056650ee642a90b82' => array(
            'id' => '79ad3f08f267d15056650ee642a90b82',
            'members' => 'a:1:{i:0;s:3:"joe";}',
            'email' => 'me@example.com',
            'name' => 'My Group'),
        'localsql:f44d8744352d9d3b6a5a1a72831e4cf4' => array(
            'id' => 'f44d8744352d9d3b6a5a1a72831e4cf4',
            'members' => 'a:2:{i:0;s:3:"joe";i:1;s:4:"jane";}',
            'email' => null,
            'name' => 'My Other Group'),
        'localsql:43959c113d25605fbce585a46ff495d6' => array(
            'id' => '43959c113d25605fbce585a46ff495d6',
            'members' => 'b:0;',
            'email' => null,
            'name' => 'Not My Group'));

    /**
     * Returns all contact groups.
     *
     * @return array  A list of group hashes.
     * @throws Horde_Exception
     */
    public function getGroupObjects()
    {
        return $this->_groups;
    }

    /**
     * Returns all contact groups that the specified user is a member of.
     *
     * @param string $user           The user
     * @param boolean $parentGroups  Include user as a member of the any
     *                               parent group as well.
     *
     * @return array  An array of group identifiers that the specified user is a
     *                member of.
     * @throws Horde_Exception
     */
    public function getGroupMemberships($user, $parentGroups = false)
    {
        $groups = array();
        foreach ($this->_groups as $id => $group) {
            $members = unserialize($group['members']);
            if (is_array($members) && in_array($user, $members)) {
                $groups[$id] = $group['name'];
            }
        }
        return $groups;
    }

    /**
     * Returns a contact group hash.
     *
     * @param string $gid  The group identifier.
     *
     * @return array  A hash defining the group.
     * @throws Horde_Exception
     */
    public function getGroupObject($gid)
    {
        if (!isset($this->_groups[$gid])) {
            return array();
        }
        $group = $this->_groups[$gid];
        unset($group['id']);
        return $group;
    }

    /**
     * Returns a list of all members belonging to a contact group.
     *
     * @param string $gid         The group identifier
     * @param boolean $subGroups  Also include members of any subgroups?
     *
     * @return array An array of group members (identified by email address).
     * @throws Horde_Exception
     */
    public function getGroupMembers($gid, $subGroups = false)
    {
        if (!isset($this->_groups[$gid])) {
            return array();
        }
        return unserialize($this->_groups[$gid]['members']);
    }
}

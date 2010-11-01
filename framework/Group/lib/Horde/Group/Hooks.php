<?php
/**
 * This class provides the Horde groups system with the addition of adding
 * support for hook functions to define if a user is in a group.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jason Rust <jrust@rustyparts.com>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
class Horde_Group_Hooks extends Horde_Group
{
    /**
     * @var boolean
     */
    protected $_hookFunction = false;

    /**
     * Constructor.
     *
     * @params array $params
     */
    public function __construct($params)
    {
        parent::__construct($params);
        Horde::loadConfiguration('hooks.php', null, 'horde');
        $this->_hookFunction = function_exists('_group_hook');
    }

    /**
     * Get a list of every group that $user is in.
     *
     * @param string  $user          The user to get groups for.
     * @param boolean $parentGroups  Also return the parents of any groups?
     *
     * @return array  An array of all groups the user is in.
     * @throws Horde_Group_Exception
     */
    public function getGroupMemberships($user, $parentGroups = false)
    {
        $memberships = parent::getGroupMemberships($user, $parentGroups);
        if (!$this->_hookFunction) {
            return $memberships;
        }

        $groups = $this->listGroups();
        foreach ($groups as $gid => $groupName) {
            if (empty($memberships[$gid]) && _group_hook($groupName, $user)) {
                $memberships += array($gid => $groupName);
            }

            if ($parentGroups) {
                $memberships += $this->getGroupParentList($gid);
            }
        }

        return $memberships;
    }

    /**
     * Say if a user is a member of a group or not.
     *
     * @param string  $user       The name of the user.
     * @param integer $gid        The ID of the group.
     * @param boolean $subgroups  Return true if the user is in any subgroups
     *                            of $group, also.
     *
     * @return boolean
     */
    public function userIsInGroup($user, $gid, $subgroups = true)
    {
        return ($this->_hookFunction && _group_hook($this->getGroupName($gid), $user)) ||
                parent::userIsInGroup($user, $gid, $subgroups);
    }

}

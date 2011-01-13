<?php

class Horde_Group_Test extends Horde_Group {
    public function __construct()
    {
    }

    public function __wakeup()
    {
    }

    public function userIsInGroup($user, $gid, $subgroups = true)
    {
        return $user == 'john' && $gid == 'mygroup';
    }

    public function getGroupMemberships($user, $parentGroups = false)
    {
        return $user == 'john' ? array('mygroup' => 'mygroup') : array();
    }
}

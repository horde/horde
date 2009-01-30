<?php

require_once dirname(__FILE__) . '/sql.php';

/**
 * Folks_Friends:: defines an API for implementing storage backends for
 * Folks.
 *
 * $Id: shared.php 1247 2009-01-30 15:01:34Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Friends_shared extends  Folks_Friends_sql  {

    /**
     * friends list ID
     *
     * @var int
     */
    private $_friends;

    /**
     * Black list ID
     *
     * @var int
     */
    private $_blacklist;

    /**
     * Get user friends and blacklist group id
     */
    private function _getIds()
    {
        if ($this->_friends && $this->_blacklist) {
            return;
        }

        $GLOBALS['folks_shares'] = Horde_Share::singleton('folks');
        $groups = $GLOBALS['folks_shares']->listShares($this->_user, PERMS_READ);
        if ($groups instanceof PEAR_Error) {
            return $groups;
        }

        foreach ($groups as $id => $group) {
            if ($group->get('type') == self::BLACKLIST) {
                $this->_blacklist = $group->getId();
            } elseif ($group->get('type') == self::WHITELIST) {
                $this->_friends = $group->getId();
            }
        }
    }

    /**
     * Get user blacklist
     *
     * @return array of users blacklist
     */
    protected function _getBlacklist()
    {
        $this->_getIds();

        if (empty($this->_blacklist)) {
            return array();
        }

        parent::_getBlacklist();
    }

    /**
     * Add user to a blacklist list
     *
     * @param string $user   Usersame
     */
    protected function _addBlacklisted($user)
    {
        $this->_getIds();

        if (empty($this->_blacklist)) {
            $result = $this->addGroup('_BLACKLIST_', self::BLACKLIST);
            if ($result instanceof PEAR_Error) {
                return $result;
            }
        }

        parent::_addBlacklisted($user);
    }

    /**
     * Remove user from blacklist list
     *
     * @param string $user   Usersame
     */
    protected function _removeBlacklisted($user)
    {
        $this->_getIds();

        if (empty($this->_blacklist)) {
            return false;
        }

        parent::_removeBlacklisted($user);
    }

    /**
     * Add user to a friend list
     *
     * @param string $friend   Friend's usersame
     * @param string $group   Group to add friend to
     */
    protected function _addFriend($friend, $group = null)
    {
        $this->_getIds();

        if (empty($this->_friends)) {
            $result = $this->addGroup('_FRIENDS_', self::WHITELIST);
            if ($result instanceof PEAR_Error) {
                return $result;
            }
        }

        parent::_addFriend($friend, $group);
    }

    /**
     * Remove user from a fiend list
     *
     * @param string $friend   Friend's usersame
     * @param string $group   Group to remove friend from
     */
    protected function _removeFriend($friend, $group = null)
    {
        $this->_getIds();

        if (empty($this->_friends)) {
            return true;
        }

        parent::_removeFriend($friend, $group);
    }

    /**
     * Get user friends
     *
     * @param string $group  Get friens only from this group
     * @param boolean $ask   Show all users or only them who approved us
     *
     * @return array of users (in group)
     */
    protected function _getFriends($group = null, $ask = false)
    {
        $this->_getIds();

        if (empty($this->_friends)) {
            return array();
        }

        parent::_getFriends($group, $ask);
    }

    /**
     * Get user groups
     */
    public function getGroups()
    {
        $GLOBALS['folks_shares'] = Horde_Share::singleton('folks');
        $groups = $GLOBALS['folks_shares']->listShares($this->_user, PERMS_READ);
        if ($groups instanceof PEAR_Error) {
            return $groups;
        }

        $list = array();
        foreach ($groups as $id => $group) {
            $list[$id] = $group->get('name');

            // set friends ids
            if ($group->get('type') == self::BLACKLIST) {
                $this->_blacklist = $id;
            } elseif ($group->get('type') == self::WHITELIST) {
                $this->_friends = $id;
            }
        }

        return $list;
    }

    /**
     * Delete user group
     *
     * @param string $group   Group to delete
     */
    public function removeGroup($group)
    {
        $GLOBALS['folks_shares'] = Horde_Share::singleton('folks');

        $share = $GLOBALS['folks_shares']->getShareById($group);
        if ($share instanceof PEAR_Error) {
            return $share;
        }

        $query = 'DELETE FROM ' . $this->_params['friends'] . ' WHERE user_uid = ? AND group_id = ?';
        $result = $this->_write_db->query($query, array($this->_user, $share->getId()));
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        return $GLOBALS['folks_shares']->_removeShare($share);
    }

    /**
     * Delete user group
     *
     * @param string $group   Group to delete
     */
    public function addGroup($group, $type = null)
    {
        $GLOBALS['folks_shares'] = Horde_Share::singleton('folks');

        $share = $GLOBALS['folks_shares']->newShare(hash('md5', microtime()));
        if ($share instanceof PEAR_Error) {
            return $share;
        }

        $share->set('name', $group);

        if ($type !== null) {
            $share->set('type', $type);
        }

        return $GLOBALS['folks_shares']->addShare($share);
    }
}
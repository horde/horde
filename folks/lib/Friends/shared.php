<?php

require_once dirname(__FILE__) . '/sql.php';

/**
 * Folks_Friends:: defines an API for implementing storage backends for
 * Folks.
 *
 * $Id: shared.php 1247 2009-01-30 15:01:34Z duck $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Friends_shared extends  Folks_Friends_sql {

    /**
     * Share holder
     *
     * @var int
     */
    private $_shares;

    /**
     * friends list ID
     *
     * @var int
     */
    private $_whitelist;

    /**
     * Black list ID
     *
     * @var int
     */
    private $_blacklist;

    /**
     * Get whitelist ID
     */
    protected function _id($id)
    {
        switch ($id) {

        case self::BLACKLIST;
            return $this->_blacklist;

        case self::WHITELIST;
            return $this->_whitelist;

        default:
            return $id;

        }
    }

    /**
     * Get user friends and blacklist group id
     */
    private function _getIds()
    {
        if ($this->_whitelist && $this->_blacklist) {
            return;
        }

        $GLOBALS['folks_shares'] = Horde_Share::singleton('folks');
        $groups = $GLOBALS['folks_shares']->listShares($this->_user, PERMS_READ);
        if ($groups instanceof PEAR_Error) {
            return $groups;
        }

        foreach ($groups as $id => $group) {
            if ($group->get('name') == '__FRIENDS__') {
                $this->_whitelist = $group->getId();
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

        // No blacklist even created
        if (empty($this->_blacklist)) {
            return array();
        }

        return parent::_getBlacklist();
    }

    /**
     * Add user to a blacklist list
     *
     * @param string $user   Usersame
     */
    protected function _addBlacklisted($user)
    {
        $this->_getIds();

        // Create blacklist
        if (empty($this->_blacklist)) {
            $group_id = $this->addGroup('_BLACKLIST_', self::BLACKLIST);
            if ($group_id instanceof PEAR_Error) {
                return $group_id;
            }
            $this->_blacklist = $group_id;
        }

        return parent::_addBlacklisted($user);
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

        if (empty($this->_whitelist)) {
            $group_id = $this->addGroup('_FRIENDS_', self::WHITELIST);
            if ($group_id instanceof PEAR_Error) {
                return $group_id;
            }
            $this->_whitelist = $group_id;
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

        if (empty($this->_whitelist)) {
            return true;
        }

        parent::_removeFriend($friend, $group);
    }

    /**
     * Get user friends
     *
     * @param string $group  Get friens only from this group
     *
     * @return array of users (in group)
     */
    protected function _getFriends($group = null)
    {
        $this->_getIds();

        if (empty($this->_whitelist)) {
            return array();
        }

        parent::_getFriends($group);
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

        /** TODO: USE TRANSLATEDN NAMES ??? */

        $list = array();
        foreach ($groups as $group) {
            if ($group->get('name') == '__FRIENDS__') {
                $this->_whitelist = $id;
                $list[$group->getId()] = _("Friends");
            } else {
                $list[$group->getId()] = $group->get('name');
            }
        }

        return $list;
    }

    /**
     * Rename user group
     *
     * @param integer $group   Group ID to delete
     */
    public function renameGroup($group, $name)
    {
        if (empty($name)) {
            return PEAR::raiseError(_("A group names cannot be empty"));
        }

        $GLOBALS['folks_shares'] = Horde_Share::singleton('folks');

        $share = $GLOBALS['folks_shares']->getShareById($group);
        if ($share instanceof PEAR_Error) {
            return $share;
        }

        // Only owners of a group can delete them
        if (Auth::getAuth() != $share->get('owner') &&
            !Auth::isAdmin('folks:admin')) {
            return PEAR::raiseError("You can rename only your own groups.");
        }

        $share->set('name', $name);
        $result = $share->save();
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        $this->_cache->expire('folksGroups' . $this->_user);

        return true;
    }

    /**
     * Delete user group
     *
     * @param integer $group   Group ID to delete
     */
    public function removeGroup($group)
    {
        $GLOBALS['folks_shares'] = Horde_Share::singleton('folks');

        $share = $GLOBALS['folks_shares']->getShareById($group);
        if ($share instanceof PEAR_Error) {
            return $share;
        }

        // Only owners of a group can delete them
        if (Auth::getAuth() != $share->get('owner') &&
            !Auth::isAdmin('folks:admin')) {
            return PEAR::raiseError("You can delete only your own groups.");
        }

        $query = 'DELETE FROM ' . $this->_params['friends']
                    . ' WHERE user_uid = ' . $share->_shareOb->_write_db->quote($this->_user)
                    . ' AND group_id = ' . $share->_shareOb->_write_db->quote($share->getId());
        $result = $share->_shareOb->_write_db->exec($query);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        $result = $GLOBALS['folks_shares']->removeShare($share);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        $this->_cache->expire('folksGroups' . $this->_user);
        $this->_cache->expire('folksFriends' . $this->_user . $group);

        return true;
    }

    /**
     * Add group
     *
     * @param string $group   Group name
     */
    public function addGroup($name)
    {
        if (empty($name)) {
            return PEAR::raiseError(_("A group names cannot be empty"));
        }

        $groups = $this->getGroups();
        if (in_array($name, $groups)) {
            return PEAR::raiseError(sprintf(_("You already have a group named \"%s\"."), $name));
        }

        $GLOBALS['folks_shares'] = Horde_Share::singleton('folks');

        $share = $GLOBALS['folks_shares']->newShare(hash('md5', microtime()));
        if ($share instanceof PEAR_Error) {
            return $share;
        }

        $share->set('name', $name);
        $result = $GLOBALS['folks_shares']->addShare($share);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        return $share->getId();
    }
}
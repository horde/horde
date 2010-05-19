<?php

require_once dirname(__FILE__) . '/sql.php';

/**
 * Folks_Friends:: defines an API for implementing storage backends for
 * Folks.
 *
 * $Id: shared.php 1247 2009-01-30 15:01:34Z duck $
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
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
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array('groups_add' => true);

    /**
     * Get user owning group
     *
     * @param integer Get group ID
     *
     * @param string Owner
     */
    public function getGroupOwner($group)
    {
        $GLOBALS['folks_shares'] = $GLOBALS['injector']->getInstance('Horde_Share')->getScope();

        $share = $GLOBALS['folks_shares']->getShareById($group);
        if ($share instanceof PEAR_Error) {
            return $share;
        }

        return $share->get('owner');
    }

    /**
     * Get user groups
     */
    protected function _getGroups()
    {
        $GLOBALS['folks_shares'] = $GLOBALS['injector']->getInstance('Horde_Share')->getScope();

        $groups = $GLOBALS['folks_shares']->listShares($this->_user, Horde_Perms::READ);
        if ($groups instanceof PEAR_Error) {
            return $groups;
        }

        $list = array();
        foreach ($groups as $group) {
            $list[$group->getId()] = $group->get('name');
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

        $GLOBALS['folks_shares'] = $GLOBALS['injector']->getInstance('Horde_Share')->getScope();

        $share = $GLOBALS['folks_shares']->getShareById($group);
        if ($share instanceof PEAR_Error) {
            return $share;
        }

        // Only owners of a group can delete them
        if (!Horde_Auth::getAuth() ||
            (Horde_Auth::getAuth() != $share->get('owner') &&
             !Horde_Auth::isAdmin('folks:admin'))) {
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
        $GLOBALS['folks_shares'] = $GLOBALS['injector']->getInstance('Horde_Share')->getScope();

        $share = $GLOBALS['folks_shares']->getShareById($group);
        if ($share instanceof PEAR_Error) {
            return $share;
        }

        // Only owners of a group can delete them
        if (!Horde_Auth::getAuth() ||
            (Horde_Auth::getAuth() != $share->get('owner') &&
             !Horde_Auth::isAdmin('folks:admin'))) {
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

        $GLOBALS['folks_shares'] = $GLOBALS['injector']->getInstance('Horde_Share')->getScope();

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
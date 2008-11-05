<?php
/**
 * Folks_Friends:: defines an API for implementing storage backends for
 * Folks.
 *
 * $Id: letter.php 777 2008-08-21 09:23:07Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Friends_letter extends Folks_Friends {

    /**
     * Get user blacklist
     *
     * @return array of users blacklist
     */
    protected function _getBlacklist()
    {
        return $GLOBALS['registry']->callByPackage('letter', 'getBlacklist', array($this->_user));
    }

    /**
     * Add user to a blacklist list
     *
     * @param string $user   Usersame
     */
    protected function _addBlacklisted($user)
    {
        return $GLOBALS['registry']->callByPackage('letter', 'addFriend', array($user, 'blacklist', $this->_user));
    }
    
    /**
     * Remove user from a fiend list
     *
     * @param string $user   Usersame
     */
    protected function _removeBlacklisted($user)
    {
        return $GLOBALS['registry']->callByPackage('letter', 'addFriend', array($user, 'blacklist', $this->_user));
    }
    
    /**
     * Add user to a friend list
     *
     * @param string $friend   Friend's usersame
     * @param string $group  Group to add friend to
     */
    protected function _addFriend($friend, $group = null)
    {
        return $GLOBALS['registry']->callByPackage('letter', 'addFriend', array($friend, 'whitelist', $this->_user));
    }
    
    /**
     * Remove user from a fiend list
     *
     * @param string $friend   Friend's usersame
     * @param string $group   Group to remove friend from
     */
    protected function _removeFriend($friend,  $group = null)
    {
        return $GLOBALS['registry']->callByPackage('letter', 'addFriend', array($friend, 'whitelist', $this->_user));
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
        return $GLOBALS['registry']->callByPackage('letter', 'getFriends', array($this->_user, 'whitelist'));
    }
    
    /**
     * Get user groups
     */
    public function getGroups()
    {
        return array('whitelist' => _("Whitelist"),
                                'blacklist' => _("Blacklist"));
    }
}


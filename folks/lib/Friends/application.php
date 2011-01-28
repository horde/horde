<?php
/**
 * Folks external application firends implementaton
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Friends_application extends Folks_Friends {

    /**
     * Add user to a friend list
     *
     * @param string $friend   Friend's usersame
     */
    protected function _addFriend($friend)
    {
        if (!$GLOBALS['registry']->hasMethod('addFriend', $this->_params['app'])) {
            return PEAR::raiseError(_("Unsupported"));
        }

        return $GLOBALS['registry']->callByPackage(
            $this->_params['app'], 'addFriend', array($friend));
    }

    /**
     * Remove user from a fiend list
     *
     * @param string $friend   Friend's usersame
     */
    public function removeFriend($friend)
    {
        if (!$GLOBALS['registry']->hasMethod('removeFriend', $this->_params['app'])) {
            return PEAR::raiseError(_("Unsupported"));
        }

        return $GLOBALS['registry']->callByPackage(
            $this->_params['app'], 'removeFriend', array($friend));
    }

    /**
     * Get user friends
     *
     * @return array of users
     */
    public function getFriends()
    {
        if (!$GLOBALS['registry']->hasMethod('getFriends', $this->_params['app'])) {
            return PEAR::raiseError(_("Unsupported"));
        }

        return $GLOBALS['registry']->callByPackage(
            $this->_params['app'], 'getFriends', array($this->_user));
    }

    /**
     * Get user blacklist
     *
     * @return array of users blacklist
     */
    public function getBlacklist()
    {
        if (!$GLOBALS['registry']->hasMethod('getBlacklist', $this->_params['app'])) {
            return PEAR::raiseError(_("Unsupported"));
        }

        return $GLOBALS['registry']->callByPackage(
            $this->_params['app'], 'getBlacklist', array($this->_user));
    }

    /**
     * Add user to a blacklist list
     *
     * @param string $user   Usersame
     */
    protected function _addBlacklisted($user)
    {
        if (!$GLOBALS['registry']->hasMethod('addBlacklisted', $this->_params['app'])) {
            return PEAR::raiseError(_("Unsupported"));
        }

        return $GLOBALS['registry']->callByPackage(
            $this->_params['app'], 'addBlacklisted', array($user));
    }

    /**
     * Add user to a blacklist list
     *
     * @param string $user   Usersame
     */
    public function removeBlacklisted($user)
    {
        if (!$GLOBALS['registry']->hasMethod('removeBlacklisted', $this->_params['app'])) {
            return PEAR::raiseError(_("Unsupported"));
        }

        return $GLOBALS['registry']->callByPackage(
            $this->_params['app'], 'removeBlacklisted', array($user));
    }

    /**
     * Get avaiable groups
     */
    protected function _getGroups()
    {
        if (!$GLOBALS['registry']->hasMethod('getGroups', $this->_params['app'])) {
            return array();
        }

        return $GLOBALS['registry']->callByPackage(
            $this->_params['app'], 'getGroups');
    }
}

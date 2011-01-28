<?php
/**
 * Folks_Friends:: defines an API for implementing storage backends for
 * Folks.
 *
 * $Id: sql.php 1247 2009-01-30 15:01:34Z duck $
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Friends_sql extends Folks_Friends {

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    private $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    private $_write_db;

    /**
     * Constructs a new SQL storage object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    protected function __construct($params = array())
    {
        parent::__construct($params);

        $this->_params = $params;
        $this->_connect();
    }

    /**
     * Get user blacklist
     *
     * @return array of users blacklist
     */
    protected function _getBlacklist()
    {
        $query = 'SELECT friend_uid FROM ' . $this->_params['blacklist']
                . ' WHERE user_uid = ? '
                . ' ORDER BY friend_uid ASC';

        return $this->_db->getCol($query, 0, array($this->_user));
    }

    /**
     * Add user to a blacklist list
     *
     * @param string $user   Usersame
     */
    protected function _addBlacklisted($user)
    {
        $query = 'INSERT INTO ' . $this->_params['blacklist']
                        . ' (user_uid, friend_uid) VALUES (?, ?)';

        return $this->_write_db->query($query, array($this->_user, $user));
    }

    /**
     * Remove user from blacklist list
     *
     * @param string $user   Usersame
     */
    protected function _removeBlacklisted($user)
    {
        $query = 'DELETE FROM ' . $this->_params['blacklist']
                    . ' WHERE user_uid = ? AND friend_uid = ?';

        return $this->_write_db->query($query, array($this->_user, $user));
    }

    /**
     * Add user to a friend list
     *
     * @param string $friend   Friend's usersame
     */
    protected function _addFriend($friend)
    {
        $approve = $this->needsApproval($friend) ? 1 : 0;

        $query = 'INSERT INTO ' . $this->_params['friends']
                . ' (user_uid, friend_uid, friend_ask) VALUES (?, ?, ?)';

        return $this->_write_db->query($query, array($this->_user, $friend, $approve));
    }

    /**
     * Approve our friend to add us to his userlist
     *
     * @param string $friend  Friedn username
     */
    protected function _approveFriend($friend)
    {
        $query = 'UPDATE ' . $this->_params['friends']
                . ' SET friend_ask = ? WHERE user_uid = ? AND friend_uid = ?';

        $result = $this->_write_db->query($query, array(0, $friend, $this->_user));
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        // Add user even to firend's friend list
        $query = 'REPLACE INTO ' . $this->_params['friends']
                . ' (user_uid, friend_uid, friend_ask) VALUES (?, ?, ?)';

        return $this->_write_db->query($query, array($this->_user, $friend, 0));
    }

    /**
     * Remove user from a fiend list
     *
     * @param string $friend   Friend's usersame
     */
    protected function _removeFriend($friend)
    {
        $query = 'DELETE FROM ' . $this->_params['friends']
                    . ' WHERE user_uid = ? AND friend_uid = ?';

        return $this->_write_db->query($query, array($this->_user, $friend));
    }

    /**
     * Get user friends
     *
     * @return array of user's friends
     */
    protected function _getFriends()
    {
        $query = 'SELECT friend_uid FROM ' . $this->_params['friends']
                . ' WHERE user_uid = ? and friend_ask = ?'
                . ' ORDER BY friend_uid ASC';

        return $this->_db->getCol($query, 0, array($this->_user, 0));
    }

    /**
     * Get friends that does not confirm the current user yet
     */
    public function waitingApprovalFrom()
    {
        $query = 'SELECT friend_uid FROM ' . $this->_params['friends']
                . ' WHERE user_uid = ? AND friend_ask = ?'
                . ' ORDER BY friend_uid ASC';

        return $this->_db->getCol($query, 0, array($this->_user, 1));
    }

    /**
     * Get friends that does not confirm the current user yet
     */
    public function waitingApprovalFor()
    {
        $query = 'SELECT user_uid FROM ' . $this->_params['friends']
                . ' WHERE friend_uid = ? AND friend_ask = ?'
                . ' ORDER BY user_uid ASC';

        return $this->_db->getCol($query, 0, array($this->_user, 1));
    }

    /**
     * Get users who have you on friendlist
     *
     * @return array users
     */
    public function friendOf()
    {
        $query = 'SELECT user_uid FROM ' . $this->_params['friends']
                . ' WHERE friend_uid = ? AND friend_ask = ?'
                . ' ORDER BY friend_uid ASC';

        return $this->_db->getCol($query, 0, array($this->_user, 0));
    }

    /**
     * Get user groups
     */
    protected function _getGroups()
    {
        return array(_("Friends"));
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success.
     * @throws Horde_Exception
     */
    protected function _connect()
    {
        $this->_params = array_merge(array(
            'blacklist' => 'folks_blacklist',
            'friends' => 'folks_friends'
        ), $this->_params);

        $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create('read', 'folks', 'storage');
        $this->_write_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create('rw', 'folks', 'storage');

        return true;
    }
}

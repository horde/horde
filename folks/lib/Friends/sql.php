<?php
/**
 * Folks_Friends:: defines an API for implementing storage backends for
 * Folks.
 *
 * $Id: sql.php 1008 2008-10-24 09:07:35Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Friends_sql extends Folks_Friends {

    const WHITELIST = 0;
    const BLACKLIST = 1;

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
     * friends list ID
     *
     * @var int
     */
    private $_friends = 0;

    /**
     * Black list ID
     *
     * @var int
     */
    private $_blacklist = 1;

    /**
     * Constructs a new SQL storage object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
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
        $query = 'SELECT friend_uid FROM ' . $this->_params['friends']
                . ' WHERE user_uid = ? AND group_id = ? '
                . ' ORDER BY friend_uid ASC';

        return $this->_db->getCol($query, 0, array($this->_user, self::BLACKLIST));
    }

    /**
     * Add user to a blacklist list
     *
     * @param string $user   Usersame
     */
    protected function _addBlacklisted($user)
    {
        $query = 'INSERT INTO ' . $this->_params['friends']
                        . ' (user_uid, group_id, friend_uid, friend_ask) VALUES (?, ?, ?, ?)';
        return $this->_write_db->query($query, array($this->_user, self::BLACKLIST, $user, 0));
    }

    /**
     * Remove user from blacklist list
     *
     * @param string $user   Usersame
     */
    protected function _removeBlacklisted($user)
    {
        $query = 'DELETE FROM ' . $this->_params['friends'] . ' WHERE user_uid = ? AND group_id = ? AND friend_uid = ?';
        return $this->_write_db->query($query, array($this->_user, self::BLACKLIST, $user));
    }

    /**
     * Add user to a friend list
     *
     * @param string $friend   Friend's usersame
     * @param string $group   Group to add friend to
     */
    protected function _addFriend($friend, $group = null)
    {
        $approve = $this->needsApproval($friend) ? 1 : 0;
        $query = 'INSERT INTO ' . $this->_params['friends']
                . ' (user_uid, group_id, friend_uid, friend_ask) VALUES (?, ?, ?, ?)';
        return $this->_write_db->query($query, array($this->_user, self::WHITELIST, $friend, $approve));
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
                . ' (user_uid, group_id, friend_uid, friend_ask) VALUES (?, ?, ?, ?)';
        return $this->_write_db->query($query, array($this->_user, self::WHITELIST, $friend, 0));
    }

    /**
     * Remove user from a fiend list
     *
     * @param string $friend   Friend's usersame
     * @param string $group   Group to remove friend from
     */
    protected function _removeFriend($friend, $group = null)
    {
        $query = 'DELETE FROM ' . $this->_params['friends'] . ' WHERE user_uid = ? AND group_id = ? AND friend_uid = ?';
        return $this->_write_db->query($query, array($this->_user, self::WHITELIST, $friend));
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
        $query = 'SELECT friend_uid FROM ' . $this->_params['friends']
                . ' WHERE user_uid = ? AND group_id = ?'
                . ' ORDER BY friend_uid ASC';

        return $this->_db->getCol($query, 0, array($this->_user, self::WHITELIST));
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
    public function getPossibleFriends()
    {
        $query = 'SELECT user_uid FROM ' . $this->_params['friends']
                . ' WHERE friend_uid = ? AND group_id = ? AND friend_ask = ?'
                . ' ORDER BY friend_uid ASC';

        return $this->_db->getCol($query, 0, array($this->_user, self::WHITELIST, 0));
    }

    /**
     * Get users friends birthdays
     *
     * @return array users
     */
    public function getBirthdays()
    {
        return false;
    }

    /**
     * Get user groups
     */
    public function getGroups()
    {
        return array(self::WHITELIST => _("Whitelist"),
                        self::BLACKLIST  => _("Blacklist"));
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success; exits (Horde::fatal()) on error.
     */
    protected function _connect()
    {
        $this->_params = Horde::getDriverConfig('storage', 'sql');

        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $this->_params['username'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }

        /* Connect to the SQL server using the supplied parameters. */
        $this->_write_db = DB::connect($this->_params,
                                        array('persistent' => !empty($this->_params['persistent'])));
        if ($this->_write_db instanceof PEAR_Error) {
            Horde::fatal($this->_write_db, __FILE__, __LINE__);
        }

        // Set DB portability options.
        switch ($this->_write_db->phptype) {
        case 'mssql':
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;
        default:
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        /* Check if we need to set up the read DB connection seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->_db = DB::connect($params,
                                      array('persistent' => !empty($params['persistent'])));
            if ($this->_db instanceof PEAR_Error) {
                Horde::fatal($this->_db, __FILE__, __LINE__);
            }

            // Set DB portability options.
            switch ($this->_db->phptype) {
            case 'mssql':
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;
            default:
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            }

        } else {
            /* Default to the same DB handle for the writer too. */
            $this->_db =& $this->_write_db;
        }

        return true;
    }
}
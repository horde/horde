<?php
/**
 * Folks storage implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:<pre>
 *      'phptype'       The database type (e.g. 'pgsql', 'mysql', etc.).
 *      'table'         The name of the foo table in 'database'.
 *      'charset'       The database's internal charset.</pre>
 *
 * Required by some database implementations:<pre>
 *      'database'      The name of the database.
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.</pre>
 *
 * The table structure can be created by the scripts/sql/folks_foo.sql
 * script.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Driver_sql extends Folks_Driver {

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
    public function __construct($params = array())
    {
        $this->_params = $params;
        $this->_connect();
    }

    /**
     * Get usersnames online
     */
    protected function _getOnlineUsers()
    {
        return $this->_db->getCol('SELECT user_uid FROM ' . $this->_params['online'] . ' WHERE user_uid <> ""');
    }

    /**
     * Get last visitors
     *
     * @param integer $limit   Username to check
     *
     * @return array  users
     */
    protected function _getRecentVisitors($limit = 10)
    {
        $sql = 'SELECT user_uid FROM ' . $this->_params['online']
            . ' WHERE user_uid <> "" AND user_uid <> "0" '
            . ' ORDER BY time_last_click DESC';

        $result = $this->_db->limitQuery($sql, 0, $limit);
        $value = $result->fetchRow(DB_FETCHMODE_ORDERED);
        return $value[0];
    }

    /**
     * Get random users
     *
     * @param integer $limit   Username to check
     * @param boolean $online   User is online?
     *
     * @return array  users
     */
    protected function _getRandomUsers($limit = 10, $online = false)
    {
        if ($online) {
            $sql = 'SELECT u.user_uid FROM ' . $this->_params['table'] . ' u, .' . $this->_params['online'] . ' o '
                . ' WHERE u.user_picture = 1 AND o.user_uid = u.user_uid ORDER BY RAND()';
        } else {
            $sql = 'SELECT user_uid FROM ' . $this->_params['table']
                . ' WHERE user_picture = 1 ORDER BY RAND()';
        }
        
        $result = $this->_db->limitQuery($sql, 0, $limit);
        $value = $result->fetchRow(DB_FETCHMODE_ORDERED);

        return $value[0];
    }

    /**
     * Get usersnames online
     */
    protected function _updateOnlineStatus()
    {
        $query = 'REPLACE INTO ' . $this->_params['online'] . ' (user_uid, ip_address, time_last_click) VALUES (?, ?, ?)';
        return $this->_write_db->query($query, array($GLOBALS['registry']->getAuth(), $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_TIME']));
    }

    /**
     * Delete users online
     */
    protected function _deleteOnlineStatus($to)
    {
        $query = 'DELETE FROM ' . $this->_params['online'] . ' WHERE time_last_click < ?';
        return $this->_write_db->query($query, array($to));
    }

    /**
     * Remove user if is online
     */
    public function deleteOnlineUser($user)
    {
        $query = 'DELETE FROM ' . $this->_params['online'] . ' WHERE user_uid = ?';
        return $this->_write_db->query($query, array($user));
    }

    /**
     * Get users by attributes
     */
    public function getUsers($criteria = array(), $from = 0, $count = 0)
    {
        $binds = $this->_buildWhere($criteria, false);

        if (!isset($criteria['sort_by'])) {
            $criteria['sort_by'] = $GLOBALS['prefs']->getValue('sort_by');
        }
        if (isset($criteria['sort_dir'])) {
            $criteria['sort_dir'] = $criteria['sort_dir'] ? 'ASC' : 'DESC';
        } else {
            $criteria['sort_dir'] = $GLOBALS['prefs']->getValue('sort_dir') ? 'ASC' : 'DESC';
        }

        $binds[0] = 'SELECT u.* ' . $binds[0]
                    . ' ORDER BY u.' . $criteria['sort_by']
                    . ' ' . $criteria['sort_dir'];

        if ($count) {
            $binds[0] = $this->_db->modifyLimitQuery($binds[0], $from, $count);
        }

        return $this->_db->getAssoc($binds[0], false, $binds[1], DB_FETCHMODE_ASSOC);
    }

    /**
     * Count users by attributes
     */
    public function countUsers($criteria = array())
    {
        $binds = $this->_buildWhere($criteria, true);
        $binds[0] = 'SELECT COUNT(*) ' . $binds[0];

        return $this->_db->getOne($binds[0], $binds[1]);
    }

    /**
     * Build attributes query
     *
     * @return array  An array containing sql statement and parameters
     */
    private function _buildWhere($criteria = array())
    {
        static $parts;

        $id = serialize($criteria);
        if (isset($parts[$id])) {
            return $parts[$id];
        }

        if (empty($criteria)) {
            $parts[$id] = array(' FROM ' . $this->_params['table'] . ' u', array());
            return $parts[$id];
        }

        $tables = $this->_params['table'] . ' u ';
        $params = array();
        $where = '';

        // WORD
        if (!empty($criteria['word']) && !empty($criteria['by'])) {
            foreach ($criteria['by'] as $key) {
                $where .= ' AND u.user_' . $key . ' LIKE ?';
                $params[] = '%' . $criteria['word'] . '%';
            }
        }

        // EMAIL
        if (!empty($criteria['email'])) {
            $where .= ' AND u.user_email = ?';
            $params[] = $criteria['email'];
        }

        // AGES
        if (isset($criteria['birthday'])) {
            if (is_array($criteria['birthday'])) {
                $where .= ' AND DAYOFYEAR(u.user_birthday) >= ? AND DAYOFYEAR(u.user_birthday)  <= ?';
                $params[] = date('z', $criteria['birthday']['from']) + 1;
                $params[] = date('z', $criteria['birthday']['to']) + 1;
            } else {
                $where .= ' AND u.user_birthday LIKE ?';
                $params[] = '%' . $criteria['birthday'];
            }
        }

        if (isset($criteria['age_from'])) {
            $where .= ' AND u.user_birthday <= ?';
            $params[] = (date('Y') - $criteria['age_from']) . '-' . date('m-d');
        }

        if (isset($criteria['age_to'])) {
            $where .= ' AND u.user_birthday >= ?';
            $params[] = (date('Y') - $criteria['age_to']) . '-' . date('m-d');
        }

        // GOES OUT
        if (isset($criteria['out'])) {
            $tables .= ', ' . $this->_params['out'] . ' g ';
            $where .= ' AND u.user_uid = g.user_uid AND g.out_from >= ? AND g.out_to <= ? ';
            $params[] = $criteria['out']['from'];
            $params[] = $criteria['out']['to'];
        }

        // COUNTERS
        if (isset($criteria['has'])) {
            foreach ($criteria['has'] as $key) {
                if ($key == 'picture') {
                    $where .= ' AND u.user_picture > 0';
                } else {
                    $where .= ' AND u.count_' . $key . ' > 0';
                }
            }
        }

        // ONLINE
        if (isset($criteria['online'])) {
            $tables .= ', ' . $this->_params['online'] . ' o ';
            $where .= ' AND o.user_uid <> "" AND o.user_uid = u.user_uid';
        }

        // Gander
        if (isset($criteria['user_gender'])) {
            $where .= ' AND user_gender = ? ';
            $params[] = (int)$criteria['user_gender'];
        }

        // City
        if (isset($criteria['user_city'])) {
            $where .= ' AND user_city LIKE ? ';
            $params[] = '%' . $criteria['user_city'] . '%';
        }

        $sql = ' FROM ' . $tables;
        if (!empty($where)) {
            $sql .= ' WHERE ' . substr($where, 4);
        }

        $parts[$id] = array($sql, $params);

        return $parts[$id];
    }

    /**
     * Formats a password using the current encryption.
     *
     * @param string $user      User we are getting password for
     *
     * @return string  The encrypted password.
     */
    protected function _getCryptedPassword($user)
    {
        $query = 'SELECT user_password FROM ' . $this->_params['table'] . ' WHERE user_uid = ?';
        return $this->_db->getOne($query, array($user));
    }

    /**
     * Get user profile
     *
     * @param string $user   Username
     */
    protected function _getProfile($user)
    {
        $query = 'SELECT user_email, user_status, user_url, user_description, user_comments, '
                . ' user_city, user_country, user_gender, user_birthday, user_video, '
                . ' last_online, last_online_on, activity_log, user_vacation, activity, popularity, '
                . ' user_picture, count_classifieds, count_news, count_videos, '
                . ' count_attendances, count_wishes, count_galleries, count_blogs '
                . ' FROM  ' . $this->_params['table'] . ' WHERE user_uid = ?';

        $result = $this->_db->getRow($query, array(strval($user)), DB_FETCHMODE_ASSOC);
        if ($result instanceof PEAR_Error) {
            return $result;
        } elseif (empty($result)) {
            return PEAR::raiseError(sprintf(_("User \"%s\" does not exists."), $user));
        }

        return $result;
    }

    /**
     * Save basic user profile
     *
     * @param array  $data   A hash containing profile data
     * @param string $user   Username
     */
    protected function _saveProfile($data, $user)
    {
        if (empty($data['user_picture'])) {
            unset($data['user_picture']);
        } else {
            $image = $this->_saveImage($data['user_picture']['file'], $user);
            if ($image instanceof PEAR_Error) {
                return $image;
            }
            $data['user_picture'] = 1;
        }

        $query = 'UPDATE ' . $this->_params['table'] . ' SET '
                        . implode(' = ?, ', array_keys($data))
                        . ' = ? WHERE user_uid = ?';

        $data[0] = $user;

        return $this->_write_db->query($query, $data);
    }

    /**
     * Update user comments count
     *
     * @param string $user   Username
     */
    public function updateComments($user, $reset = false)
    {
        $query = 'UPDATE ' . $this->_params['table'] . ' SET count_comments = ';
        if ($reset) {
            $query .= '0';
        } else {
            $query .= 'count_comments + 1';
        }
        $query .= ' WHERE user_uid = ?';

        return $this->_write_db->query($query, array($user));
    }

    /**
     * Delete user image
     *
     * @param string $user   Username
     */
    protected function _deleteImage($user)
    {
        $query = 'UPDATE ' . $this->_params['table'] . ' SET user_picture = 0 WHERE user_uid = ?';
        return $this->_write_db->query($query, array($user));
    }

    /**
     * Log user view
     *
     * @param string $user   Username
     */
    protected function _logView($id)
    {
        $query = 'REPLACE INTO ' . $this->_params['views'] . ' (view_uid, user_uid, view_time) VALUES (?, ?, ?)';
        return $this->_write_db->query($query, array($id, $GLOBALS['registry']->getAuth(), $_SERVER['REQUEST_TIME']));
    }

    /**
     * Get user groups
     *
     * @param string $user   Username
     */
    public function getViews()
    {
        $query = 'SELECT user_uid FROM ' . $this->_params['views'] . ' WHERE view_uid = ?';
        return $this->_db->getCol($query, 0, array($GLOBALS['registry']->getAuth()));
    }

   /**
    * Check if user exist
    *
    * @param string $user    Username
    *
    * @return boolean
    */
    public function userExists($user)
    {
        $query = 'SELECT 1 FROM ' . $this->_params['table'] . ' WHERE user_uid = ?';
        $result = $this->_db->getOne($query, array($user));
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        return (boolean)$result;
    }

    /**
     * Adds a set of authentication credentials.
     *
     * @param string $userId  The userId to add.
     * @param array $credentials  The credentials to use.
     *
     * @return boolean true|PEAR_Error
     */
    public function addUser($user, $credentials)
    {
        // password and mail will be added later with the addextra hook
        $query = 'INSERT INTO ' . $this->_params['table']
                    . ' (user_uid, user_status, user_password, user_email, signup_at, signup_by) '
                    . ' VALUES (?, ?, ?, ?, NOW(), ?)';
        $params = array($user, 'inactive', $credentials['password'],
                        rand() . '@' . $_SERVER['REMOTE_ADDR'],
                        $_SERVER['REMOTE_ADDR']);

        return $this->_write_db->query($query, $params);
    }

   /**
    * Delete user
    *
    * @param string $user    Username
    *
    * @return boolean
    */
    protected function _deleteUser($user)
    {
        $tables = array($this->_params['table'],
                        $this->_params['attributes'],
                        $this->_params['friends'],
                        $this->_params['testimonials'],
                        $this->_params['online'],
                        $this->_params['views'],
                        $this->_params['out']);

        foreach ($tables as $table) {
            $query = 'DELETE FROM ' . $table . ' WHERE user_uid = ?';
            $result = $this->_write_db->query($query, array($user));
            if ($result instanceof PEAR_Error) {
                return $result;
            }
        }

        return true;
    }

   /**
    * Save search criteria
    *
    * @param string $criteria    Search criteria
    * @param string $name    Search name
    */
    protected function _saveSearch($criteria, $name)
    {
        $query = 'INSERT INTO ' . $this->_params['search'] . ' (user_uid, search_name, search_criteria) VALUES (?, ?, ?)';

        return $this->_write_db->query($query, array($GLOBALS['registry']->getAuth(), $name, $criteria));
    }

   /**
    * Get saved search
    *
    * @return array saved searches
    */
    protected function _getSavedSearch()
    {
        $query = 'SELECT search_name FROM ' . $this->_params['search'] . ' WHERE user_uid = ?';

        return $this->_db->getCol($query, 'search_name', $GLOBALS['registry']->getAuth());
    }

   /**
    * Get saved search criteria
    *
    * @param string $name    Username
    *
    * @return array  search criteria
    */
    protected function _getSearchCriteria($name)
    {
        $query = 'SELECT search_criteria FROM ' . $this->_params['search'] . ' WHERE user_uid = ? AND search_name = ?';

        return $this->_db->getOne($query, array($GLOBALS['registry']->getAuth(), $name));
    }

   /**
    * Delete saved search
    *
    * @param string $name    Username
    */
    protected function _deleteSavedSearch($name)
    {
        $query = 'DELETE FROM ' . $this->_params['search'] . ' WHERE user_uid = ? AND search_name = ?';

        return $this->_write_db->query($query, array($GLOBALS['registry']->getAuth(), $name));
    }

   /**
    * Log users actions
    *
    * @param string $message    Log message
    * @param string $scope    Scope
    * @param string $user    Username
    *
    * @return true on success
    */
    protected function _logActivity($message, $scope, $user)
    {
        $query = 'INSERT INTO ' . $this->_params['activity']
                . ' (user_uid, activity_message, activity_scope, activity_date) VALUES (?, ?, ?, ?)';

        return $this->_write_db->query($query, array($user, $message, $scope, $_SERVER['REQUEST_TIME']));
    }

   /**
    * Get user's activity
    *
    * @param string $user    Username
    * @param string $activity    Number of actions to return
    *
    * @return array    Activity log
    */
    protected function _getActivity($user, $limit)
    {
        $query = 'SELECT activity_message, activity_scope, activity_date, user_uid FROM '
                . $this->_params['activity'] . ' WHERE user_uid = ? '
                . 'ORDER BY activity_date DESC';
        $query = $this->_db->modifyLimitQuery($query, 0, $limit);

        return $this->_db->getAll($query, array($user), DB_FETCHMODE_ASSOC);
    }

   /**
    * Delete users activity
    *
    * @param string $scope    Scope
    * @param integer $date    Date
    * @param string $user    Username
    *
    * @return true on success
    */
    protected function _deleteActivity($scope, $date, $user)
    {
        $query = 'DELETE FROM ' . $this->_params['activity']
                . ' WHERE user_uid = ? AND activity_scope = ? AND activity_date = ?';

        return $this->_write_db->query($query, array($user, $scope, $date));
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success.
     * @throws Horde_Exception
     */
    private function _connect()
    {
        $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create('read', 'folks', 'storage');
        $this->_write_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create('rw', 'folks', 'storage');

        return true;
    }
}

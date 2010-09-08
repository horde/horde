<?php
/**
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Content
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Content
 */
class Content_Users_Manager
{
    /**
     * Database adapter
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Tables
     * @var array
     */
    protected $_tables = array(
        'users' => 'rampage_users',
    );

    public function __construct(Horde_Db_Adapter_Base $db)
    {
        $this->_db = $db;
    }

    /**
     * Ensure that an array of users exist in storage. Create any that don't,
     * return user_ids for all.
     *
     * @param array $users  An array of users. Values typed as an integer
     *                        are assumed to already be an user_id.
     *
     * @return array  An array of user_ids.
     */
    public function ensureUsers($users)
    {
        if (!is_array($users)) {
            $users = array($users);
        }

        $userIds = array();
        $userName = array();

        // Anything already typed as an integer is assumed to be a user id.
        foreach ($users as $userIndex => $user) {
            if (is_int($user)) {
                $userIds[$userIndex] = $user;
            } else {
                $userName[$user] = $userIndex;
            }
        }

        // Get the ids for any users that already exist.
        try {
            if (count($userName)) {
                foreach ($this->_db->selectAll('SELECT user_id, user_name FROM ' . $this->_t('users') . ' WHERE user_name IN ('.implode(',', array_map(array($this->_db, 'quote'), array_keys($userName))).')') as $row) {
                    $userIndex = $userName[$row['user_name']];
                    unset($userName[$row['user_name']]);
                    $userIds[$userIndex] = $row['user_id'];
                }
            }

            // Create any users that didn't already exist
            foreach ($userName as $user => $userIndex) {
                $userIds[$userIndex] = $this->_db->insert('INSERT INTO ' . $this->_t('users') . ' (user_name) VALUES (' . $this->_db->quote($user) . ')');
            }
        } catch (Horde_Db_Exception $e) {
            throw new Content_Exception($e);
        }

        return $userIds;
    }

    /**
     * Shortcut for getting a table name.
     *
     * @param string $tableType
     *
     * @return string  Configured table name.
     */
    protected function _t($tableType)
    {
        return $this->_db->quoteTableName($this->_tables[$tableType]);
    }

}

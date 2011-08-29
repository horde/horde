<?php
/**
 * Sam storage implementation for PHP's PEAR database abstraction layer.
 *
 * Required parameters:<pre>
 *   'phptype'       The database type (ie. 'pgsql', 'mysql', etc.).</pre>
 *
  * Optional preferences:<pre>
 *   'table'         The name of the Sam options table in 'database'.
 *                   DEFAULT: 'userpref'</pre>
 *
 * Required by some database implementations:<pre>
 *   'hostspec'      The hostname of the database server.
 *   'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *   'database'      The name of the database.
 *   'username'      The username with which to connect to the database.
 *   'password'      The password associated with 'username'.
 *   'options '      Additional options to pass to the database.
 *   'port'          The port on which to connect to the database.
 *   'tty'           The TTY on which to connect to the database.</pre>
 *
 * The table structure can be created by the scripts/sql/amavisd_*.sql
 * script appropriate for your database, or modified from one that is
 * available.
 *
 * @author  Max Kalika <max@horde.org>
 * @package Sam
 */

/**
 * Backend-specific 'false' value.
 */
define('_SAM_OPTION_OFF', 'N');

/**
 * Backend-specific 'true' value.
 */
define('_SAM_OPTION_ON',  'Y');

class Sam_Driver_Amavisd_Sql extends Sam_Driver
{
    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    protected $_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * List of the capabilities supported by this driver.
     *
     * @var array
     */
    protected $_capabilities = array('tag_level',
                               'hit_level',
                               'kill_level',
                               'rewrite_sub',
                               'spam_extension',
                               'virus_extension',
                               'banned_extension',
                               'spam_quarantine',
                               'allow_virus',
                               'allow_spam',
                               'allow_banned',
                               'allow_header',
                               'skip_virus',
                               'skip_spam',
                               'skip_banned',
                               'skip_header',
                               'whitelist_from',
                               'blacklist_from');

    /**
     * Constructs a new SQL storage object.
     *
     * @param string $user   The user who owns these SPAM options.
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($user, $params = array())
    {
        global $conf;

        $this->_user = $user;
        $this->_params = array_merge($conf['sql'], $params);
    }

    /**
     * Converts a Sam table name to a table name that Amavisd-new will use.
     *
     * @access private
     *
     * @param string $table  The Sam table to lookup.
     *
     * @return string  The converted Amavisd-new table or the original name if
     *                 no match is found.
     */
    protected function _mapNameToTable($table)
    {
        return isset($this->_params['table_map'][$table]['name'])
               ? $this->_params['table_map'][$table]['name'] : $table;
    }

    /**
     * Converts a Sam attribute from a specific table to a field that
     * Amavisd-new will use.
     *
     * @access private
     *
     * @param string $table      The Sam table to lookup.
     * @param string $attribute  The Sam attribute to convert.
     *
     * @return string  The converted Amavisd-new field or the original
     *                 attribute if no match is found.
     */
    protected function _mapAttributeToField($table, $attribute)
    {
        return isset($this->_params['table_map'][$table]['field_map'][$attribute])
               ? $this->_params['table_map'][$table]['field_map'][$attribute] : $attribute;
    }

    /**
     * Converts a Amavisd-new field from a specific table to a Sam attribute.
     *
     * @access private
     *
     * @param string $table  The Sam table to lookup.
     * @param string $field  The Amavisd-new field to convert.
     *
     * @return string  The converted Sam attribute or the original field if no
     *                 match is found.
     */
    protected function _mapFieldToAttribute($table, $field)
    {
        $attribute_map = array();
        if (isset($this->_params['table_map'][$table]['field_map'])) {
            $attribute_map = array_flip($this->_params['table_map'][$table]['field_map']);
        }

        return isset($attribute_map[$field]) ? $attribute_map[$field] : $field;
    }

    /*
     * Create an Amavisd-new recipient for policy, whitelist and blacklist
     * storage and retrieval.
     *
     * @access private
     *
     * @return mixed  The id of the newly created recipient or a PEAR_Error
     *                object on failure.
     */
    protected function _createUserID()
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Build the recipient creation query. */
        $query = sprintf('INSERT INTO %s (%s) VALUES (?)',
                         $this->_mapNameToTable('recipients'),
                         $this->_mapAttributeToField('recipients', 'email'));
        $values = array($this->_user);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_createUserID(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Cannot create recipient %s: %s"),
                                            $this->_user, $result->getMessage()));
        } else {
            $GLOBALS['notification']->push(sprintf(_("Recipient created: %s"),
                                                   $this->_user), 'horde.success');
            return $this->_lookupUserID();
        }
    }

    /*
     * Lookup an Amavisd-new recipient for policy, whitelist and blacklist
     * storage and retrieval.
     *
     * This function will cache the found ID for quicker lookup on subsequent
     * calls.
     *
     * @access private
     *
     * @return mixed  The ID of the found or newly created recipient or a
     *                PEAR_Error object on failure.
     */
    protected function _lookupUserID()
    {
        static $_userID;

        if (!empty($_userID)) {
            return $_userID;
        }

        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Build the recipient lookup query. */
        $query = sprintf('SELECT %s FROM %s WHERE %s = ?',
                         $this->_mapAttributeToField('recipients', 'id'),
                         $this->_mapNameToTable('recipients'),
                         $this->_mapAttributeToField('recipients', 'email'));
        $values = array($this->_user);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_lookupUserID(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->getOne($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        } elseif (is_null($result)) {
            $_userID = $this->_createUserID();
        } else {
            $_userID = $result;
        }

        return $_userID;
    }

    /*
     * Lookup an Amavisd-new policy for storage and retrieval.
     *
     * @access private
     *
     * @return mixed  The results of the of the policy lookup. Can be the ID of
     *                the policy, null if not found, or a PEAR_Error object on
     *                failure.
     */
    protected function _lookupPolicyID()
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Build the policy lookup query. */
        $query = sprintf('SELECT %s FROM %s WHERE %s = ?',
                         $this->_mapAttributeToField('policies', 'id'),
                         $this->_mapNameToTable('policies'),
                         $this->_mapAttributeToField('policies', 'name'));
        $values = array($this->_user);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_lookupPolicyID(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->getOne($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
        }

        return $result;
    }

    /**
     * Retrieve an option set from the storage backend.
     *
     * @access private
     *
     * @return mixed  Array of field-value pairs or a PEAR_Error object on
     *                failure.
     */
    protected function _retrieve()
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Find the user id. */
        $userID = $this->_lookupUserID();
        if (is_a($userID, 'PEAR_Error')) {
            return $userID;
        }

        /* Find the policy id. */
        $policyID = $this->_lookupPolicyID();
        if (is_a($policyID, 'PEAR_Error')) {
            return $policyID;
        }

        $return = array();

        /* Build the SQL query for SPAM policy. */
        $query = sprintf('SELECT * FROM %s WHERE %s = ?',
                         $this->_mapNameToTable('policies'),
                         $this->_mapAttributeToField('policies', 'id'));
        $values = array($policyID);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_retrieve(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->getRow($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        /* Loop through elements of the result, retrieving options. */
        if (!is_null($result)) {
            foreach ($result as $field => $value) {
                $attribute = $this->_mapFieldToAttribute('policies', $field);
                if ($this->hasCapability($attribute) && !is_null($value)) {
                    $return[$attribute] = $value;
                }
            }
        }

        /* Build the SQL query for whitelists and blacklists. */
        $query = sprintf('SELECT %s, %s FROM %s WHERE %s = ?',
                         $this->_mapAttributeToField('wblists', 'sender'),
                         $this->_mapAttributeToField('wblists', 'type'),
                         $this->_mapNameToTable('wblists'),
                         $this->_mapAttributeToField('wblists', 'recipient'));
        $values = array($userID);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_retrieve(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        /* Loop through results, retrieving whitelists and blacklists. */
        while (($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) &&
               !is_a($row, 'PEAR_Error')) {
            $type = $row[$this->_mapAttributeToField('wblists', 'type')];
            $senderID = $row[$this->_mapAttributeToField('wblists', 'sender')];

            /* Only proceed if sender is listed white or black. */
            if (preg_match('/[WYBN]/i', $type)) {
                $query = sprintf('SELECT %s FROM %s WHERE %s = ?',
                                 $this->_mapAttributeToField('senders', 'email'),
                                 $this->_mapNameToTable('senders'),
                                 $this->_mapAttributeToField('senders', 'id'));
                $values = array($senderID);

                /* Log the query at a DEBUG log level. */
                Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_retrieve(): %s', $query),
                                  __FILE__, __LINE__, PEAR_LOG_DEBUG);

                $sender = $this->_db->getOne($query, $values);
                if (is_a($sender, 'PEAR_Error')) {
                    Horde::logMessage($sender, __FILE__, __LINE__, PEAR_LOG_ERR);
                    return $sender;
                } else {
                    $list = preg_match('/[WY]/i', $type) ? 'whitelist_from' : 'blacklist_from';
                    if (isset($return[$list])) {
                        if (!in_array($sender, $return[$list])) {
                            $return[$list][] = $sender;
                        }
                    } else {
                        $return[$list] = array($sender);
                    }
                }
            }
        }
        $result->free();

        if (is_a($row, 'PEAR_Error')) {
            Horde::logMessage($row, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $row;
        }

        return $return;
    }

    /**
     * Retrieves the user options and stores them in the member array.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    public function retrieve()
    {
        $options = $this->_retrieve();
        if (!is_a($options, 'PEAR_Error')) {
            $this->_options = $options;
        } else {
            return $options;
        }

        return true;
    }

    /**
     * Store an option set from the member array to the storage backend.
     *
     * @access private
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    protected function _store()
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Check if the policy already exists. */
        $policyID = $this->_lookupPolicyID();
        if (is_a($policyID, 'PEAR_Error')) {
            return $policyID;
        }

        /* Delete existing policy. */
        if (!is_null($policyID)) {
            $query = sprintf('DELETE FROM %s WHERE %s = ?',
                             $this->_mapNameToTable('policies'),
                             $this->_mapAttributeToField('policies', 'name'));
            $values = array($this->_user);

            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                              __FILE__, __LINE__, PEAR_LOG_DEBUG);

            $result = $this->_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $result;
            }
        }

        /* Insert new policy (everything but whitelists and blacklists). */
        $insertKeys = $insertVals = array();
        foreach ($this->_options as $attribute => $value) {
            if ($attribute != 'whitelist_from' && $attribute != 'blacklist_from') {
                $insertKeys[] = $this->_mapAttributeToField('policies', $attribute);
                $insertVals[] = strlen($value) ? $value : null;
            }
        }
        if (count($insertKeys)) {
            $query = sprintf('INSERT INTO %s (%s, %s) VALUES (%s)',
                             $this->_mapNameToTable('policies'),
                             $this->_mapAttributeToField('policies', 'name'),
                             implode(', ', $insertKeys),
                             implode(', ', array_fill(0, count($insertVals) + 1, '?')));
            $values = array_merge(array($this->_user), $insertVals);

            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                              __FILE__, __LINE__, PEAR_LOG_DEBUG);

            $result = $this->_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $result;
            }
        }

        /* Get the new policy id for the recipients table. */
        $policyID = $this->_lookupPolicyID();
        if (is_a($policyID, 'PEAR_Error')) {
            return $policyID;
        }

        /* Update recipients with new policy id. */
        $query = sprintf('UPDATE %s SET %s = ? WHERE %s = ?',
                         $this->_mapNameToTable('recipients'),
                         $this->_mapAttributeToField('recipients', 'policy_id'),
                         $this->_mapAttributeToField('recipients', 'email'));
        $values = array($policyID, $this->_user);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        /* Find the user id. */
        $userID = $this->_lookupUserID();
        if (is_a($userID, 'PEAR_Error')) {
            return $userID;
        }

        $existing = array('whitelist_from' => array(), 'blacklist_from' => array());

        /* Build the SQL query for whitelists and blacklists. */
        $query = sprintf('SELECT %s, %s FROM %s WHERE %s = ?',
                         $this->_mapAttributeToField('wblists', 'sender'),
                         $this->_mapAttributeToField('wblists', 'type'),
                         $this->_mapNameToTable('wblists'),
                         $this->_mapAttributeToField('wblists', 'recipient'));
        $values = array($userID);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        /* Loop through results, retrieving whitelists and blacklists. */
        while (($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) &&
               !is_a($row, 'PEAR_Error')) {
            $type = $row[$this->_mapAttributeToField('wblists', 'type')];
            $senderID = $row[$this->_mapAttributeToField('wblists', 'sender')];

            /* Only proceed if sender is listed white or black. */
            if (preg_match('/[WYBN]/i', $type)) {
                $query = sprintf('SELECT %s FROM %s WHERE %s = ?',
                                 $this->_mapAttributeToField('senders', 'email'),
                                 $this->_mapNameToTable('senders'),
                                 $this->_mapAttributeToField('senders', 'id'));
                $values = array($senderID);

                /* Log the query at a DEBUG log level. */
                Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                                  __FILE__, __LINE__, PEAR_LOG_DEBUG);

                $sender = $this->_db->getOne($query, $values);
                if (is_a($sender, 'PEAR_Error')) {
                    Horde::logMessage($sender, __FILE__, __LINE__, PEAR_LOG_ERR);
                    return $sender;
                } else {
                    $list = preg_match('/[WY]/i', $type) ? 'whitelist_from' : 'blacklist_from';
                    /* User removed an address from a list. */
                    if (!isset($this->_options[$list]) || !in_array($sender, $this->_options[$list])) {
                        $query = sprintf('DELETE FROM %s WHERE %s = ? AND %s = ?',
                                         $this->_mapNameToTable('wblists'),
                                         $this->_mapAttributeToField('wblists', 'sender'),
                                         $this->_mapAttributeToField('wblists', 'recipient'));
                        $values = array($senderID, $userID);

                        /* Log the query at a DEBUG log level. */
                        Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

                        $deleted = $this->_db->query($query, $values);
                        if (is_a($deleted, 'PEAR_Error')) {
                            Horde::logMessage($deleted, __FILE__, __LINE__, PEAR_LOG_ERR);
                            return $deleted;
                        }

                        /* Check if there is anyone else using this sender
                         * address. */
                        $query = sprintf('SELECT 1 FROM %s WHERE %s = ?',
                                         $this->_mapNameToTable('wblists'),
                                         $this->_mapAttributeToField('wblists', 'sender'));
                        $values = array($senderID);

                        /* Log the query at a DEBUG log level. */
                        Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

                        /* No one else needs this sender address, delete it
                         * from senders table. */
                        if (is_null($this->_db->getOne($query, $values))) {
                            $query = sprintf('DELETE FROM %s WHERE %s = ?',
                                             $this->_mapNameToTable('senders'),
                                             $this->_mapAttributeToField('senders', 'id'));
                            $values = array($senderID);

                            /* Log the query at a DEBUG log level. */
                            Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                                              __FILE__, __LINE__, PEAR_LOG_DEBUG);

                            $deleted = $this->_db->query($query, $values);
                            if (is_a($deleted, 'PEAR_Error')) {
                                Horde::logMessage($deleted, __FILE__, __LINE__, PEAR_LOG_ERR);
                                return $deleted;
                            }
                        }
                    } else {
                        $existing[$list][] = $sender;
                    }
                }
            }
        }
        $result->free();

        if (is_a($row, 'PEAR_Error')) {
            Horde::logMessage($row, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $row;
        }

        /* Check any additions to the lists. */
        foreach (array('whitelist_from' => 'W', 'blacklist_from' => 'B') as $list => $type) {
            if (isset($this->_options[$list])) {
                foreach ($this->_options[$list] as $sender) {
                    if (!in_array($sender, $existing[$list])) {

                        /* Check if this sender address exists already. */
                        $query = sprintf('SELECT %s FROM %s WHERE %s = ?',
                                         $this->_mapAttributeToField('senders', 'id'),
                                         $this->_mapNameToTable('senders'),
                                         $this->_mapAttributeToField('senders', 'email'));
                        $values = array($sender);

                        /* Log the query at a DEBUG log level. */
                        Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

                        $wb_result = $this->_db->getOne($query, $values);
                        if (is_null($wb_result)) {
                            /* Address doesn't exist, add it. */
                            $query = sprintf('INSERT INTO %s (%s) VALUES (?)',
                                             $this->_mapNameToTable('senders'),
                                             $this->_mapAttributeToField('senders', 'email'));
                            $values = array($sender);

                            /* Log the query at a DEBUG log level. */
                            Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                                              __FILE__, __LINE__, PEAR_LOG_DEBUG);

                            $result = $this->_db->query($query, $values);
                            if (is_a($result, 'PEAR_Error')) {
                                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                                return $result;
                            }

                            $query = sprintf('SELECT %s FROM %s WHERE %s = ?',
                                             $this->_mapAttributeToField('senders', 'id'),
                                             $this->_mapNameToTable('senders'),
                                             $this->_mapAttributeToField('senders', 'email'));
                            $values = array($sender);

                            /* Log the query at a DEBUG log level. */
                            Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                                              __FILE__, __LINE__, PEAR_LOG_DEBUG);

                            $senderID = $this->_db->getOne($query, $values);
                            if (is_a($senderID, 'PEAR_Error')) {
                                Horde::logMessage($senderID, __FILE__, __LINE__, PEAR_LOG_ERR);
                                return $senderID;
                            }

                            $query = sprintf('INSERT INTO %s (%s, %s, %s) VALUES (?, ?, ?)',
                                             $this->_mapNameToTable('wblists'),
                                             $this->_mapAttributeToField('wblists', 'recipient'),
                                             $this->_mapAttributeToField('wblists', 'sender'),
                                             $this->_mapAttributeToField('wblists', 'type'));
                            $values = array($userID, $senderID, $type);

                            /* Log the query at a DEBUG log level. */
                            Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                                              __FILE__, __LINE__, PEAR_LOG_DEBUG);

                            $result = $this->_db->query($query, $values);
                            if (is_a($result, 'PEAR_Error')) {
                                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                                return $result;
                            }
                        } else {
                            /* Address exists, use it's ID */
                            $query = sprintf('INSERT INTO %s (%s, %s, %s) VALUES (?, ?, ?)',
                                             $this->_mapNameToTable('wblists'),
                                             $this->_mapAttributeToField('wblists', 'recipient'),
                                             $this->_mapAttributeToField('wblists', 'sender'),
                                             $this->_mapAttributeToField('wblists', 'type'));
                            $values = array($userID, $wb_result, $type);

                            /* Log the query at a DEBUG log level. */
                            Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                                              __FILE__, __LINE__, PEAR_LOG_DEBUG);

                            $result = $this->_db->query($query, $values);
                            if (is_a($result, 'PEAR_Error')) {
                                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                                return $result;
                            }
                        }
                    }
                }
            }
        }

        /* Remove any disjoined sender IDs. */
        $query = sprintf('DELETE FROM %s WHERE %s = ?',
                         $this->_mapNameToTable('wblists'),
                         $this->_mapAttributeToField('wblists', 'recipient'));
        $values = array('');

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Sam_Driver_Amavisd_Sql::_store(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        return true;
    }

    /**
     * Stores the user options from the member array.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    public function store()
    {
        return $this->_store();
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @access private
     *
     * @return mixed    True on success or a PEAR_Error object on failure.
     */
    public function _connect()
    {
        if (!$this->_connected) {
            Horde::assertDriverConfig($this->_params, 'amavisd_sql',
                array('phptype'),
                'Sam backend', 'backends.php', '$backends');
            if (!isset($this->_params['table'])) {
                $this->_params['table'] = 'userpref';
            }

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
            require_once 'DB.php';
            $this->_db = &DB::connect($this->_params,
                                      array('persistent' => !empty($this->_params['persistent'])));
            if (is_a($this->_db, 'PEAR_Error')) {
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

            $this->_connected = true;
        }

        return true;
    }

}

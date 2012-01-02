<?php
/**
 * Sam SQL storage implementation using Horde_Db.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Max Kalika <max@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Sam
 */
class Sam_Driver_Amavisd_Sql extends Sam_Driver_Base
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

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
     * Constructor.
     *
     * @param string $user   A user name.
     * @param array $params  Class parameters:
     *                       - db:    (Horde_Db_Adapater) A database handle.
     *                       - table_map: (array) A map of table and field
     *                         names. See config/backends.php.
     */
    public function __construct($user, $params = array())
    {
        foreach (array('db', 'table_map') as $param) {
            if (!isset($params[$param])) {
                throw new InvalidArgumentException(
                    sprintf('"%s" parameter is missing', $param));
            }
        }

        $this->_db = $params['db'];
        unset($params['db']);

        parent::__construct($user, $params);
    }

    /**
     * Retrieves user preferences from the backend.
     *
     * @throws Sam_Exception
     */
    public function retrieve()
    {
        /* Find the user id. */
        $userID = $this->_lookupUserID();

        /* Find the policy id. */
        $policyID = $this->_lookupPolicyID();

        /* Query for SPAM policy. */
        try {
            $result = $this->_db->selectOne(
                sprintf('SELECT * FROM %s WHERE %s = ?',
                        $this->_mapNameToTable('policies'),
                        $this->_mapAttributeToField('policies', 'id')),
                array($policyID));
        } catch (Horde_Db_Exception $e) {
            throw new Sam_Exception($e);
        }

        /* Loop through elements of the result, retrieving options. */
        if ($result) {
            foreach ($result as $field => $value) {
                $attribute = $this->_mapFieldToAttribute('policies', $field);
                if ($this->hasCapability($attribute) && !is_null($value)) {
                    $this->_options[$attribute] = $value;
                }
            }
        }

        /* Query for whitelists and blacklists. */
        try {
            $result = $this->_db->select(
                sprintf('SELECT %s, %s FROM %s WHERE %s = ?',
                        $this->_mapAttributeToField('wblists', 'sender'),
                        $this->_mapAttributeToField('wblists', 'type'),
                        $this->_mapNameToTable('wblists'),
                        $this->_mapAttributeToField('wblists', 'recipient')),
                array($userID));
        } catch (Horde_Db_Exception $e) {
            throw new Sam_Exception($e);
        }

        /* Loop through results, retrieving whitelists and blacklists. */
        foreach ($result as $row) {
            $type = $row[$this->_mapAttributeToField('wblists', 'type')];
            $senderID = $row[$this->_mapAttributeToField('wblists', 'sender')];

            /* Only proceed if sender is listed white or black. */
            if (preg_match('/[WYBN]/i', $type)) {
                try {
                    $sender = $this->_db->selectValue(
                        sprintf('SELECT %s FROM %s WHERE %s = ?',
                                $this->_mapAttributeToField('senders', 'email'),
                                $this->_mapNameToTable('senders'),
                                $this->_mapAttributeToField('senders', 'id')),
                        array($senderID));
                } catch (Horde_Db_Exception $e) {
                    throw new Sam_Exception($e);
                }

                $list = preg_match('/[WY]/i', $type)
                    ? 'whitelist_from'
                    : 'blacklist_from';
                if (isset($this->_options[$list])) {
                    if (!in_array($sender, $this->_options[$list])) {
                        $this->_options[$list][] = $sender;
                    }
                } else {
                    $this->_options[$list] = array($sender);
                }
            }
        }
    }

    /**
     * Stores user preferences and default values in the backend.
     *
     * @param boolean $defaults  Whether to store the global defaults instead
     *                           of user options. Unused.
     *
     * @throws Sam_Exception
     */
    public function store($defaults = false)
    {
        /* Check if the policy already exists. */
        $policyID = $this->_lookupPolicyID();

        /* Delete existing policy. */
        if ($policyID !== false) {
            try {
                $this->_db->delete(
                    sprintf('DELETE FROM %s WHERE %s = ?',
                            $this->_mapNameToTable('policies'),
                            $this->_mapAttributeToField('policies', 'name')),
                    array($this->_user));
            } catch (Horde_Db_Exception $e) {
                throw new Sam_Exception($e);
            }
        }

        /* Insert new policy (everything but whitelists and blacklists). */
        $insertKeys = $insertVals = array();
        foreach ($this->_options as $attribute => $value) {
            if ($attribute != 'whitelist_from' &&
                $attribute != 'blacklist_from') {
                $insertKeys[] = $this->_mapAttributeToField('policies', $attribute);
                $insertVals[] = strlen($value) ? $value : null;
            }
        }
        if (count($insertKeys)) {
            try {
                $this->_db->insert(
                    sprintf('INSERT INTO %s (%s, %s) VALUES (%s)',
                            $this->_mapNameToTable('policies'),
                            $this->_mapAttributeToField('policies', 'name'),
                            implode(', ', $insertKeys),
                            implode(', ', array_fill(0, count($insertVals) + 1, '?'))),
                    array_merge(array($this->_user), $insertVals));
            } catch (Horde_Db_Exception $e) {
                throw new Sam_Exception($e);
            }
        }

        /* Get the new policy id for the recipients table. */
        $policyID = $this->_lookupPolicyID();

        /* Update recipients with new policy id. */
        try {
            $this->_db->update(
                sprintf('UPDATE %s SET %s = ? WHERE %s = ?',
                         $this->_mapNameToTable('recipients'),
                         $this->_mapAttributeToField('recipients', 'policy_id'),
                         $this->_mapAttributeToField('recipients', 'email')),
                array($policyID, $this->_user));
        } catch (Horde_Db_Exception $e) {
            throw new Sam_Exception($e);
        }

        /* Find the user id. */
        $userID = $this->_lookupUserID();

        /* Query for whitelists and blacklists. */
        try {
            $result = $this->_db->select(
                sprintf('SELECT %s, %s FROM %s WHERE %s = ?',
                        $this->_mapAttributeToField('wblists', 'sender'),
                        $this->_mapAttributeToField('wblists', 'type'),
                        $this->_mapNameToTable('wblists'),
                        $this->_mapAttributeToField('wblists', 'recipient')),
                array($userID));
        } catch (Horde_Db_Exception $e) {
            throw new Sam_Exception($e);
        }

        /* Loop through results, retrieving whitelists and blacklists. */
        $existing = array('whitelist_from' => array(),
                          'blacklist_from' => array());
        foreach ($result as $row) {
            $type = $row[$this->_mapAttributeToField('wblists', 'type')];
            $senderID = $row[$this->_mapAttributeToField('wblists', 'sender')];

            /* Only proceed if sender is listed white or black. */
            if (preg_match('/[WYBN]/i', $type)) {
                try {
                    $sender = $this->_db->selectValue(
                        sprintf('SELECT %s FROM %s WHERE %s = ?',
                                $this->_mapAttributeToField('senders', 'email'),
                                $this->_mapNameToTable('senders'),
                                $this->_mapAttributeToField('senders', 'id')),
                        array($senderID));
                } catch (Horde_Db_Exception $e) {
                    throw new Sam_Exception($e);
                }

                $list = preg_match('/[WY]/i', $type)
                    ? 'whitelist_from'
                    : 'blacklist_from';

                if (isset($this->_options[$list]) &&
                    in_array($sender, $this->_options[$list])) {
                    $existing[$list][] = $sender;
                } else {
                    /* User removed an address from a list. */
                    try {
                        $this->_db->delete(
                            sprintf('DELETE FROM %s WHERE %s = ? AND %s = ?',
                                    $this->_mapNameToTable('wblists'),
                                    $this->_mapAttributeToField('wblists', 'sender'),
                                    $this->_mapAttributeToField('wblists', 'recipient')),
                            array($senderID, $userID));
                    } catch (Horde_Db_Exception $e) {
                        throw new Sam_Exception($e);
                    }

                    /* Check if there is anyone else using this sender
                     * address. */
                    $query = sprintf('SELECT 1 FROM %s WHERE %s = ?',
                                     $this->_mapNameToTable('wblists'),
                                     $this->_mapAttributeToField('wblists', 'sender'));
                    if (!$this->_db->selectValue($query, array($senderID))) {
                        /* No one else needs this sender address, delete it
                         * from senders table. */
                        try {
                            $this->_db->delete(
                                sprintf('DELETE FROM %s WHERE %s = ?',
                                         $this->_mapNameToTable('senders'),
                                         $this->_mapAttributeToField('senders', 'id')),
                                array($senderID));
                        } catch (Horde_Db_Exception $e) {
                            throw new Sam_Exception($e);
                        }
                    }
                }
            }
        }

        /* Check any additions to the lists. */
        foreach (array('whitelist_from' => 'W', 'blacklist_from' => 'B') as $list => $type) {
            if (!isset($this->_options[$list])) {
                continue;
            }

            foreach ($this->_options[$list] as $sender) {
                if (in_array($sender, $existing[$list])) {
                    continue;
                }

                /* Check if this sender address exists already. */
                $wb_result = $this->_db->selectValue(
                    sprintf('SELECT %s FROM %s WHERE %s = ?',
                            $this->_mapAttributeToField('senders', 'id'),
                            $this->_mapNameToTable('senders'),
                            $this->_mapAttributeToField('senders', 'email')),
                    array($sender));

                if ($wb_result !== false) {
                    /* Address exists, use it's ID */
                    $senderID = $wb_result;
                } else {
                    /* Address doesn't exist, add it. */
                    try {
                        $this->_db->insert(
                            sprintf('INSERT INTO %s (%s) VALUES (?)',
                                    $this->_mapNameToTable('senders'),
                                    $this->_mapAttributeToField('senders', 'email')),
                            array($sender));
                    } catch (Horde_Db_Exception $e) {
                        throw new Sam_Exception($e);
                    }

                    try {
                        $senderID = $this->_db->selectValue(
                            sprintf('SELECT %s FROM %s WHERE %s = ?',
                                    $this->_mapAttributeToField('senders', 'id'),
                                    $this->_mapNameToTable('senders'),
                                    $this->_mapAttributeToField('senders', 'email')),
                            array($sender));
                    } catch (Horde_Db_Exception $e) {
                        throw new Sam_Exception($e);
                    }
                }

                try {
                    $this->_db->insert(
                        sprintf('INSERT INTO %s (%s, %s, %s) VALUES (?, ?, ?)',
                                $this->_mapNameToTable('wblists'),
                                $this->_mapAttributeToField('wblists', 'recipient'),
                                $this->_mapAttributeToField('wblists', 'sender'),
                                $this->_mapAttributeToField('wblists', 'type')),
                        array($userID, $senderID, $type));
                } catch (Horde_Db_Exception $e) {
                    throw new Sam_Exception($e);
                }
            }
        }

        /* Remove any disjoined sender IDs. */
        try {
            $this->_db->delete(
                sprintf('DELETE FROM %s WHERE %s = ?',
                        $this->_mapNameToTable('wblists'),
                        $this->_mapAttributeToField('wblists', 'recipient')),
                array(''));
        } catch (Horde_Db_Exception $e) {
            throw new Sam_Exception($e);
        }
    }

    /**
     * Converts a boolean option to a backend specific value.
     *
     * @param boolean $boolean  The value to convert.
     *
     * @return mixed  Y if true and N if false.
     */
    public function booleanToOption($boolean)
    {
        return $boolean ? 'Y' : 'N';
    }

    /**
     * Converts a Sam table name to a table name that Amavisd-new will use.
     *
     * @param string $table  The Sam table to lookup.
     *
     * @return string  The converted Amavisd-new table or the original name if
     *                 no match is found.
     */
    protected function _mapNameToTable($table)
    {
        return isset($this->_params['table_map'][$table]['name'])
            ? $this->_params['table_map'][$table]['name']
            : $table;
    }

    /**
     * Converts a Sam attribute from a specific table to a field that
     * Amavisd-new will use.
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
            ? $this->_params['table_map'][$table]['field_map'][$attribute]
            : $attribute;
    }

    /**
     * Converts a Amavisd-new field from a specific table to a Sam attribute.
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

    /**
     * Creates an Amavisd-new recipient for policy, whitelist and blacklist
     * storage and retrieval.
     *
     * @return string  The id of the newly created recipient.
     * @throws Sam_Exception
     */
    protected function _createUserID()
    {
        try {
            $this->_db->insert(
                sprintf('INSERT INTO %s (%s) VALUES (?)',
                        $this->_mapNameToTable('recipients'),
                        $this->_mapAttributeToField('recipients', 'email')),
                array($this->_user));
        } catch (Horde_Db_Exception $e) {
            throw new Sam_Exception(sprintf(_("Cannot create recipient %s: %s"),
                                            $this->_user, $e->getMessage()));
        }
        $GLOBALS['notification']->push(sprintf(_("Recipient created: %s"),
                                               $this->_user), 'horde.success');
        return $this->_lookupUserID();
    }

    /**
     * Returns an Amavisd-new recipient for policy, whitelist and blacklist
     * storage and retrieval.
     *
     * @return string  The ID of the found or newly created recipient.
     * @throws Sam_Exception
     */
    protected function _lookupUserID()
    {
        try {
            $userID = $this->_db->selectValue(
                sprintf('SELECT %s FROM %s WHERE %s = ?',
                        $this->_mapAttributeToField('recipients', 'id'),
                        $this->_mapNameToTable('recipients'),
                        $this->_mapAttributeToField('recipients', 'email')),
                array($this->_user));
        } catch (Horde_Db_Exception $e) {
            throw new Sam_Exception($e);
        }

        if ($userID === false) {
            $userID = $this->_createUserID();
        }

        return $userID;
    }

    /**
     * Returns an Amavisd-new policy for storage and retrieval.
     *
     * @return string  The results of the of the policy lookup. Can be the ID
     *                 of the policy, false if not found.
     * @throws Sam_Exception
     */
    protected function _lookupPolicyID()
    {
        try {
            return $this->_db->selectValue(
                sprintf('SELECT %s FROM %s WHERE %s = ?',
                        $this->_mapAttributeToField('policies', 'id'),
                        $this->_mapNameToTable('policies'),
                        $this->_mapAttributeToField('policies', 'name')),
                array($this->_user));
        } catch (Horde_Db_Exception $e) {
            return false;
        }
    }
}

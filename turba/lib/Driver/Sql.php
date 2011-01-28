<?php
/**
 * Turba directory driver implementation for the Horde_Db database abstraction
 * layer.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Jon Parise <jon@csh.rit.edu>
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class Turba_Driver_Sql extends Turba_Driver
{
    /**
     * What can this backend do?
     *
     * @var array
     */
    protected $_capabilities = array(
        'delete_addressbook' => true,
        'delete_all' => true
    );

    /**
     * count() cache.
     *
     * @var array
     */
    protected $_countCache = array();

    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param string $name   The source name
     * @param array $params  Additional parameters needed:
     * <pre>
     * 'db' - (Horde_Db_Adapter) A DB Adapter object.
     * </pre>
     */
    public function __construct($name = '', array $params = array())
    {
        if (empty($params['db'])) {
            throw new InvalidArgumentException(_("Missing required Horde_Db_Adapter obejct"));
        }
        $this->_db = $params['db'];
        unset($params['db']);

        parent::__construct($name, $params);
    }

    /**
     * Returns the number of contacts of the current user in this address book.
     *
     * @return integer  The number of contacts that the user owns.
     */
    public function count()
    {
        $test = $this->getContactOwner();
        if (!isset($this->_countCache[$test])) {
            /* Build up the full query. */
            $query = 'SELECT COUNT(*) FROM ' . $this->_params['table'] .
                     ' WHERE ' . $this->toDriver('__owner') . ' = ?';
            $values = array($test);

            /* Run query. */
            try {
                $this->_countCache[$test] = $this->_db->selectValue($query, $values);
            } catch (Horde_Db_Exception $e) {
                $this->_countCache[$test] = 0;
            }
        }

        return $this->_countCache[$test];
    }

    /**
     * Searches the SQL database with the given criteria and returns a
     * filtered list of results. If the criteria parameter is an empty array,
     * all records will be returned.
     *
     * @param array $criteria      Array containing the search criteria.
     * @param array $fields        List of fields to return.
     * @param array $blobFields    TODO
     * @param array $appendWhere   An additional where clause to append.
     *                             Array should contain 'sql' and 'params'
     *                             params are used as bind parameters.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _search($criteria, $fields, $blobFields = array(), $appendWhere = array())
    {
        /* Build the WHERE clause. */
        $where = '';
        $values = array();

        if (count($criteria) || !empty($this->_params['filter'])) {
            foreach ($criteria as $key => $vals) {
                if ($key == 'OR' || $key == 'AND') {
                    if (!empty($where)) {
                        $where .= ' ' . $key . ' ';
                    }
                    $binds = $this->_buildSearchQuery($key, $vals);
                    $where .= '(' . $binds[0] . ')';
                    $values += $binds[1];
                }
            }
            $where = ' WHERE ' . $where;
            if (count($criteria) && !empty($this->_params['filter'])) {
                $where .= ' AND ';
            }
            if (!empty($this->_params['filter'])) {
                $where .= $this->_params['filter'];
            }
            if (count($appendWhere)) {
                $where .= ' AND ' . $appendWhere['sql'];
                $values = array_merge($values, $appendWhere['params']);
            }
        } elseif (count($appendWhere)) {
            $where = ' WHERE ' . $appendWhere['sql'];
            $values = array_merge($values, $appendWhere['params']);
        }

        /* Build up the full query. */
        $query = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $this->_params['table'] . $where;

        try {
            return $this->_parseRead($blobFields, $this->_db->selectAll($query, $values));
        } catch (Horde_Db_Exception $e) {
            throw new Turba_Exception($e);
        }
    }

    protected function _parseRead($blobFields, $result)
    {
        $results = array();

        foreach ($result as $row) {
            $entry = array();

            foreach ($row as $field => $val) {
                if (isset($blobFields[$field])) {
                    switch ($this->_db->adapterName()) {
                    case 'PDO_PostgreSQL':
                        if (is_resource($val)) {
                            $tmp = stream_get_contents($val);
                            fclose($val);
                            $val = $tmp;
                        }
                        // Fall-through

                    default:
                        $entry[$field] = $val;
                        break;
                    }
                } else {
                    $entry[$field] = $this->_convertFromDriver($val);
                }
            }

            $results[] = $entry;
        }

        return $results;
    }

    /**
     * Prepares field lists for searchDuplicates().
     *
     * @param array $array  A list of field names.
     *
     * @return array  A prepared list of field names.
     */
    protected function _buildFields($array)
    {
        foreach ($array as &$entry) {
            $entry = is_array($entry)
                ? implode(',', $this->_buildFields($entry))
                : 'a1.' . $entry;
        }

        return $array;
    }

    /**
     * Builds the WHERE conditions for searchDuplicates().
     *
     * @param array $array  A list of field names.
     *
     * @return array  A list of WHERE conditions.
     */
    protected function _buildWhere($array)
    {
        foreach ($array as &$entry) {
            if (is_array($entry)) {
                $entry = reset($entry);
            }
            $entry = 'a1.' . $entry . ' IS NOT NULL AND a1.' . $entry . ' <> \'\'';
        }

        return $array;
    }

    /**
     * Builds the JOIN conditions for searchDuplicates().
     *
     * @param array $array  A list of field names.
     *
     * @return array  A list of JOIN conditions.
     */
    protected function _buildJoin($array)
    {
        foreach ($array as &$entry) {
            $entry = is_array($entry)
                ? implode(' AND ', $this->_buildJoin($entry))
                : 'a1.' . $entry . ' = a2.' . $entry;
        }

        return $array;
    }

    /**
     * Searches the current address book for duplicate entries.
     *
     * Duplicates are determined by comparing email and name or last name and
     * first name values.
     *
     * @return array  A hash with the following format:
     *                <code>
     *                array('name' => array('John Doe' => Turba_List, ...), ...)
     *                </code>
     * @throws Turba_Exception
     */
    public function searchDuplicates()
    {
        $owner = $this->getContactOwner();
        $fields = array();
        if (is_array($this->map['name'])) {
            if (in_array('lastname', $this->map['name']['fields']) &&
                isset($this->map['lastname'])) {
                $field = array($this->map['lastname']);
                if (in_array('firstname', $this->map['name']['fields']) &&
                    isset($this->map['firstname'])) {
                    $field[] = $this->map['firstname'];
                }
                $fields[] = $field;
            }
        } else {
            $fields[] = $this->map['name'];
        }
        if (isset($this->map['email'])) {
            $fields[] = $this->map['email'];
        }
        $nameFormat = $GLOBALS['prefs']->getValue('name_format');;
        if ($nameFormat != 'first_last' && $nameFormat != 'last_first') {
            $nameFormat = 'first_last';
        }

        $order = $this->_buildFields($fields);
        $joins = $this->_buildJoin($fields);
        $where = $this->_buildWhere($fields);

        $duplicates = array();
        for ($i = 0; $i < count($joins); $i++) {
            /* Build up the full query. */
            $query = sprintf('SELECT DISTINCT a1.%s FROM %s a1 JOIN %s a2 ON %s AND a1.%s <> a2.%s WHERE a1.%s = ? AND a2.%s = ? AND %s ORDER BY %s',
                             $this->map['__key'],
                             $this->_params['table'],
                             $this->_params['table'],
                             $joins[$i],
                             $this->map['__key'],
                             $this->map['__key'],
                             $this->map['__owner'],
                             $this->map['__owner'],
                             $where[$i],
                             $order[$i]);

            /* Run query. */
            try {
                $ids = $this->_db->selectValues($query, array($owner, $owner));
            } catch (Horde_Db_Exception $e) {
                throw new Turba_Exception($e);
            }

            $field = ($i == 0)
                ? 'name'
                : array_search($fields[$i], $this->map);

            $contacts = array();
            foreach ($ids as $id) {
                $contact = $this->getObject($id);
                $value = $contact->getValue($field);
                if ($field == 'name') {
                    $value = Turba::formatName($contact, $nameFormat);
                }
                /* HACK! */
                if ($field == 'email') {
                    $value = Horde_String::lower($value);
                }
                if (!isset($contacts[$value])) {
                    $contacts[$value] = new Turba_List();
                }
                $contacts[$value]->insert($contact);
            }
            if ($contacts) {
                $duplicates[$field] = $contacts;
            }
        }

        return $duplicates;
    }

    /**
     * Reads the given data from the SQL database and returns the
     * results.
     *
     * @param string $key         The primary key field to use.
     * @param mixed $ids          The ids of the contacts to load.
     * @param string $owner       Only return contacts owned by this user.
     * @param array $fields       List of fields to return.
     * @param array $blob_fields  List of fields that contain binary data.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _read($key, $ids, $owner, array $fields,
                             array $blob_fields = array())
    {
        $values = array();

        $in = '';
        if (is_array($ids)) {
            if (!count($ids)) {
                return array();
            }

            foreach ($ids as $id) {
                $in .= empty($in) ? '?' : ', ?';
                $values[] = $this->_convertToDriver($id);
            }
            $where = $key . ' IN (' . $in . ')';
        } else {
            $where = $key . ' = ?';
            $values[] = $this->_convertToDriver($ids);
        }
        if (isset($this->map['__owner'])) {
            $where .= ' AND ' . $this->map['__owner'] . ' = ?';
            $values[] = $this->_convertToDriver($owner);
        }
        if (!empty($this->_params['filter'])) {
            $where .= ' AND ' . $this->_params['filter'];
        }

        $query  = 'SELECT ' . implode(', ', $fields) . ' FROM '
            . $this->_params['table'] . ' WHERE ' . $where;

        try {
            return $this->_parseRead($blob_fields, $this->_db->selectAll($query, $values));
        } catch (Horde_Db_Exception $e) {
            throw new Turba_Exception($e);
        }
    }

    /**
     * Adds the specified object to the SQL database.
     *
     * TODO
     *
     * @throws Turba_Exception
     */
    protected function _add($attributes, $blob_fields = array())
    {
        list($fields, $values) = $this->_prepareWrite($attributes, $blob_fields);
        $query  = 'INSERT INTO ' . $this->_params['table']
            . ' (' . implode(', ', $fields) . ')'
            . ' VALUES (' . str_repeat('?, ', count($values) - 1) . '?)';

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Turba_Exception($e);
        }
    }

    protected function _prepareWrite($attributes, $blob_fields)
    {
        $fields = $values = array();

        foreach ($attributes as $field => $value) {
            $fields[] = $field;

            if (!empty($value) && isset($blob_fields[$field])) {
                switch ($this->_db->adapterName()) {
                case 'PDO_PostgreSQL':
                    $values[] = bin2hex($value);
                    break;

                default:
                    $values[] = $value;
                    break;
                }
            } else {
                $values[] = $this->_convertToDriver($value);
            }
        }

        return array($fields, $values);
    }

    /**
     * TODO
     */
    protected function _canAdd()
    {
        return true;
    }

    /**
     * Deletes the specified object from the SQL database.
     *
     * @throws Turba_Exception
     */
    protected function _delete($object_key, $object_id)
    {
        $query = 'DELETE FROM ' . $this->_params['table'] .
                 ' WHERE ' . $object_key . ' = ?';
        $values = array($object_id);

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Turba_Exception($e);
        }
    }

    /**
     * Deletes all contacts from a specific address book.
     *
     * @throws Turba_Exception
     */
    protected function _deleteAll($sourceName = null)
    {
        if (!$GLOBALS['registry']->getAuth()) {
            throw new Turba_Exception('Permission denied');
        }

        /* Get owner id */
        $values = empty($sourceName)
            ? array($GLOBALS['registry']->getAuth())
            : array($sourceName);

        /* Need a list of UIDs so we can notify History */
        $query = 'SELECT '. $this->map['__uid'] . ' FROM ' . $this->_params['table'] . ' WHERE owner_id = ?';

        try {
            $ids = $this->_db->selectValues($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Turba_Exception($e);
        }

        /* Do the deletion */
        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE owner_id = ?';

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Turba_Exception($e);
        }

        /* Update Horde_History */
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        try {
            foreach ($ids as $id) {
                // This is slightly hackish, but it saves us from having to
                // create and save an array of Turba_Objects before we delete
                // them, just to be able to calculate this using
                // Turba_Object#getGuid
                $guid = 'turba:' . $this->getName() . ':' . $id;
                $history->log($guid, array('action' => 'delete'), true);
            }
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
        }
    }

    /**
     * Saves the specified object in the SQL database.
     *
     * @param Turba_Object $object  The object to save.
     *
     * @return string  The object id, possibly updated.
     * @throws Turba_Exception
     */
    function _save(Turba_Object $object)
    {
        list($object_key, $object_id) = each($this->toDriverKeys(array('__key' => $object->getValue('__key'))));
        $attributes = $this->toDriverKeys($object->getAttributes());
        $blob_fields = $this->toDriverKeys($this->getBlobs());

        $where = $object_key . ' = ?';
        unset($attributes[$object_key]);

        list($fields, $values) = $this->_prepareWrite($attributes, $blob_fields);

        $values[] = $object_id;

        $query = 'UPDATE ' . $this->_params['table'] . ' SET ' . implode(' = ?, ', $fields) . ' = ? WHERE ' . $where;

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Turba_Exception($e);
        }

        return $object_id;
    }

    /**
     * Creates an object key for a new object.
     *
     * @param array $attributes  The attributes (in driver keys) of the
     *                           object being added.
     *
     * @return string  A unique ID for the new object.
     */
    protected function _makeKey(array $attributes)
    {
        return strval(new Horde_Support_Randomid());
    }

    /**
     * Builds a piece of a search query.
     *
     * @param string $glue      The glue to join the criteria (OR/AND).
     * @param array  $criteria  The array of criteria.
     *
     * @return array  An SQL fragment and a list of values suitable for binding
     *                as an array.
     */
    protected function _buildSearchQuery($glue, array $criteria)
    {
        $clause = '';
        $values = array();

        foreach ($criteria as $key => $vals) {
            if (!empty($vals['OR']) || !empty($vals['AND'])) {
                if (!empty($clause)) {
                    $clause .= ' ' . $glue . ' ';
                }
                $binds = $this->_buildSearchQuery(!empty($vals['OR']) ? 'OR' : 'AND', $vals);
                $clause .= '(' . $binds[0] . ')';
                $values = array_merge($values, $binds[1]);
            } else {
                if (isset($vals['field'])) {
                    if (!empty($clause)) {
                        $clause .= ' ' . $glue . ' ';
                    }
                    $rhs = $this->_convertToDriver($vals['test']);
                    $binds = Horde_SQL::buildClause($this->_db, $vals['field'], $vals['op'], $rhs, true, array('begin' => !empty($vals['begin'])));
                    if (is_array($binds)) {
                        $clause .= $binds[0];
                        $values = array_merge($values, $binds[1]);
                    } else {
                        $clause .= $binds;
                    }
                } else {
                    foreach ($vals as $test) {
                        if (!empty($test['OR']) || !empty($test['AND'])) {
                            if (!empty($clause)) {
                                $clause .= ' ' . $glue . ' ';
                            }
                            $binds = $this->_buildSearchQuery(!empty($vals['OR']) ? 'OR' : 'AND', $test);
                            $clause .= '(' . $binds[0] . ')';
                            $values = array_merge($values, $binds[1]);
                        } else {
                            if (!empty($clause)) {
                                $clause .= ' ' . $key . ' ';
                            }
                            $rhs = $this->_convertToDriver($test['test']);
                            if ($rhs == '' && $test['op'] == '=') {
                                $clause .= '(' . Horde_SQL::buildClause($this->_db, $test['field'], '=', $rhs) . ' OR ' . $test['field'] . ' IS NULL)';
                            } else {
                                $binds = Horde_SQL::buildClause($this->_db, $test['field'], $test['op'], $rhs, true, array('begin' => !empty($test['begin'])));
                                if (is_array($binds)) {
                                    $clause .= $binds[0];
                                    $values = array_merge($values, $binds[1]);
                                } else {
                                    $clause .= $binds;
                                }
                            }
                        }
                    }
                }
            }
        }

        return array($clause, $values);
    }

    /**
     * Converts a value from the driver's charset to the default charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    protected function _convertFromDriver($value)
    {
        return Horde_String::convertCharset($value, $this->_db->getOption('charset'), 'UTF-8');
    }

    /**
     * Converts a value from the default charset to the driver's charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    protected function _convertToDriver($value)
    {
        return Horde_String::convertCharset($value, 'UTF-8', $this->_db->getOption('charset'));
    }

    /**
     * Remove all entries owned by the specified user.
     *
     * @param string $user  The user's data to remove.
     *
     * @throws Horde_Exception_PermissionDenied
     */
    public function removeUserData($user)
    {
        // Make sure we are being called by an admin.
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception_PermissionDenied(_("Permission denied"));
        }

        $this->_deleteAll($user);
    }

    /**
     * Obtain Turba_List of items to get TimeObjects out of.
     *
     * @param Horde_Date $start  The starting date.
     * @param Horde_Date $end    The ending date.
     * @param string $field      The address book field containing the
     *                           timeObject information (birthday, anniversary)
     *
     * @return Turba_List  Object list.
     * @throws Turba_Exception
     */
    protected function _getTimeObjectTurbaList($start, $end, $field)
    {
        $t_object = $this->toDriver($field);
        $criteria = $this->makesearch(
            array('__owner' => $this->getContactOwner()),
            'AND',
            array($this->toDriver('__owner') => true),
            false);

        // Limit to entries that actually contain a birthday and that are in the
        // date range we are looking for.
        $criteria['AND'][] = array(
            'field' => $t_object,
            'op' => '<>',
            'test' => ''
        );

        if ($start->year == $end->year) {
            $start = sprintf('%02d-%02d', $start->month, $start->mday);
            $end = sprintf('%02d-%02d', $end->month, $end->mday);
            $where = array('sql' => $t_object . ' IS NOT NULL AND SUBSTR('
                           . $t_object . ', 6, 5) BETWEEN ? AND ?',
                           'params' => array($start, $end));
        } else {
            $months = array();
            $diff = ($end->month + 12) - $start->month;
            $newDate = new Horde_Date(array(
                'month' => $start->month,
                'mday' => $start->mday,
                'year' => $start->year
            ));
            for ($i = 0; $i <= $diff; ++$i) {
                $months[] = sprintf('%02d', $newDate->month++);
            }
            $where = array('sql' => $t_object . ' IS NOT NULL AND SUBSTR('
                           . $t_object . ', 6, 2) IN ('
                           . str_repeat('?,', count($months) - 1) . '?)',
                           'params' => $months);
        }

        $fields_pre = array(
            '__key', '__type', '__owner', 'name', 'birthday', 'category',
            'anniversary'
        );

        $fields = array();
        foreach ($fields_pre as $field) {
            $result = $this->toDriver($field);
            if (is_array($result)) {
                foreach ($result as $composite_field) {
                    $composite_result = $this->toDriver($composite_field);
                    if ($composite_result) {
                        $fields[] = $composite_result;
                    }
                }
            } elseif ($result) {
                $fields[] = $result;
            }
        }

        return $this->_toTurbaObjects($this->_search($criteria, $fields, array(), $where));
    }

}

<?php
/**
 * Preferences storage implementation for a SQL database.
 *
 * Copyright 1999-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jon Parise <jon@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Prefs
 */
class Horde_Prefs_Storage_Sql extends Horde_Prefs_Storage_Base
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param string $user   The username.
     * @param array $params  Configuration parameters.
     * <pre>
     * 'db' - (Horde_Db_Adapter) [REQUIRED] The DB instance.
     * 'table' - (string) The name of the prefs table.
     *           DEFAULT: 'horde_prefs'
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct($user, array $params = array())
    {
        if (!isset($params['db'])) {
            throw new InvalidArgumentException('Missing db parameter.');
        }
        $this->_db = $params['db'];
        unset($params['db']);

        $params = array_merge(array(
            'table' => 'horde_prefs'
        ), $params);

        parent::__construct($user, $params);
    }

    /**
     * Returns the charset of the DB backend.
     *
     * @return string  The connection's charset.
     */
    public function getCharset()
    {
        return $this->_db->getOption('charset');
    }

    /**
     */
    public function get($scope_ob)
    {
        $charset = $this->_db->getOption('charset');
        $query = 'SELECT pref_scope, pref_name, pref_value FROM ' .
            $this->_params['table'] . ' ' .
            'WHERE pref_uid = ? AND pref_scope = ?';
        $values = array($this->_params['user'], $scope_ob->scope);

        try {
            $result = $this->_db->selectAll($query, $values);
            $columns = $this->_db->columns($this->_params['table']);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Prefs_Exception($e);
        }

        foreach ($result as $row) {
            $name = trim($row['pref_name']);
            $value = $columns['pref_value']->binaryToString($row['pref_value']);
            $scope_ob->set($name, Horde_String::convertCharset($value, $charset, 'UTF-8'));
        }

        return $scope_ob;
    }

    /**
     */
    public function store($scope_ob)
    {
        $charset = $this->_db->getOption('charset');

        // For each preference, check for an existing table row and
        // update it if it's there, or create a new one if it's not.
        foreach ($scope_ob->getDirty() as $name) {
            $value = $scope_ob->get($name);
            $values = array($this->_params['user'], $name, $scope_ob->scope);

            if (is_null($value)) {
                $query = 'DELETE FROM ' . $this->_params['table'] .
                    ' WHERE pref_uid = ? AND pref_name = ?' .
                    ' AND pref_scope = ?';

                try {
                    $this->_db->delete($query, $values);
                } catch (Horde_Db_Exception $e) {
                    throw new Horde_Prefs_Exception($e);
                }
            } else {
                // Does a row already exist for this preference?
                $query = 'SELECT 1 FROM ' . $this->_params['table'] .
                    ' WHERE pref_uid = ? AND pref_name = ?' .
                    ' AND pref_scope = ?';

                try {
                    $check = $this->_db->selectValue($query, $values);
                } catch (Horde_Db_Exception $e) {
                    throw new Horde_Prefs_Exception($e);
                }

                /* Driver has no support for storing locked status. */
                $value = Horde_String::convertCharset($value, 'UTF-8', $charset);
                $value = new Horde_Db_Value_Binary($value);

                if (empty($check)) {
                    // Insert a new row.
                    $query = 'INSERT INTO ' . $this->_params['table'] . ' ' .
                        '(pref_uid, pref_scope, pref_name, pref_value) VALUES' .
                        '(?, ?, ?, ?)';
                    $values = array(
                        $this->_params['user'],
                        $scope_ob->scope,
                        $name,
                        $value
                    );

                    try {
                        $this->_db->insert($query, $values);
                    } catch (Horde_Db_Exception $e) {
                        throw new Horde_Prefs_Exception($e);
                    }
                } else {
                    // Update the existing row.
                    $query = 'UPDATE ' . $this->_params['table'] .
                        ' SET pref_value = ?' .
                        ' WHERE pref_uid = ?' .
                        ' AND pref_name = ?' .
                        ' AND pref_scope = ?';
                    $values = array(
                        $value,
                        $this->_params['user'],
                        $name,
                        $scope_ob->scope
                    );

                    try {
                        $this->_db->update($query, $values);
                    } catch (Horde_Db_Exception $e) {
                        throw new Horde_Prefs_Exception($e);
                    }
                }
            }
        }
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
        $query = 'DELETE FROM ' . $this->_params['table'] .
                 ' WHERE pref_uid = ?';
        $values = array($this->_params['user']);

        if (!is_null($scope)) {
            $query .= ' AND pref_scope = ?';
            $values[] = $scope;

            if (!is_null($pref)) {
                $query .= ' AND pref_name = ?';
                $values[] = $pref;
            }
        }

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Prefs_Exception($e);
        }
    }

    /**
     * Lists all available scopes.
     *
     * @since Horde_Prefs 1.1.0
     *
     * @return array The list of scopes stored in the backend.
     */
    public function listScopes()
    {
        $query = 'SELECT ' . $this->_db->distinct('pref_scope') . ' FROM '
            . $this->_params['table'];
        try {
            return $this->_db->selectValues($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Prefs_Exception($e);
        }
    }

}

<?php
/**
 * Preferences storage implementation for a SQL database.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jon Parise <jon@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Prefs
 */
class Horde_Prefs_Storage_Sql extends Horde_Prefs_Storage
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
        } catch (Horde_Db_Exception $e) {
            throw Horde_Prefs_Exception($e);
        }

        foreach ($result as $row) {
            $name = trim($row['pref_name']);

            switch ($this->_db->adapterName()) {
            case 'PDO_PostgreSQL':
                // TODO: Should be done in DB driver
                if (is_resource($row['pref_value'])) {
                    $val = stream_get_contents($row['pref_value']);
                    fclose($row['pref_value']);
                    $row['pref_value'] = $val;
                }
                $row['pref_value'] = pg_unescape_bytea($row['pref_value']);
                break;
            }

            $scope_ob->set($name, Horde_String::convertCharset($row['pref_value'], $charset, 'UTF-8'));
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

                switch ($this->_db->adapterName()) {
                case 'PDO_PostgreSQL':
                    // TODO: Should be done in DB driver
                    $value = pg_escape_bytea($value);
                    break;
                }

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
                        throw Horde_Prefs_Exception($e);
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
                        throw Horde_Prefs_Exception($e);
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
            throw Horde_Prefs_Exception($e);
        }
    }

}

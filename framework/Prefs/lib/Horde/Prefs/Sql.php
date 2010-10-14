<?php
/**
 * Preferences storage implementation for PHP's PEAR database
 * abstraction layer.
 *
 * The table structure for the Prefs system is in
 * scripts/sql/horde_prefs.sql.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Prefs
 */
class Horde_Prefs_Sql extends Horde_Prefs
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
     * @param string $scope  The scope for this set of preferences.
     * @param array $opts    See factory() for list of options.
     * @param array $params  A hash containing any additional configuration
     *                       or connection parameters a subclass might need.
     * <pre>
     * 'db' - (Horde_Db_Adapter) [REQUIRED] The DB instance.
     * 'table' - (string) The name of the prefs table.
     *           DEFAULT: 'horde_prefs'
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    protected function __construct($scope, $opts, $params)
    {
        if (!isset($params['db'])) {
            throw new InvalidArgumentException('Missing db parameter.');
        }
        $this->_db = $params['db'];
        unset($params['db']);

        $params = array_merge(array(
            'table' => 'horde_prefs'
        ), $params);

        parent::__construct($scope, $opts, $params);
    }

    /**
     * Retrieves the requested set of preferences from the user's database
     * entry.
     *
     * @param string $scope  Scope specifier.
     */
    protected function _retrieve($scope)
    {
        $query = 'SELECT pref_scope, pref_name, pref_value FROM ' .
            $this->_params['table'] . ' ' .
            'WHERE pref_uid = ? AND pref_scope = ?';
        $values = array($this->getUser(), $scope);

        try {
            $result = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            if ($this->_opts['logger']) {
                $this->_opts['logger']->log('No preferences were retrieved.', 'DEBUG');
            }
            return;
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

            if (isset($this->_scopes[$scope][$name])) {
                $this->_scopes[$scope][$name]['m'] &= ~self::PREFS_DEFAULT;
                $this->_scopes[$scope][$name]['v'] = $row['pref_value'];
            } else {
                // This is a shared preference.
                $this->_scopes[$scope][$name] = array(
                    'd' => null,
                    'm' => 0,
                    'v' => $row['pref_value']
                );
            }
        }
    }

    /**
     * Stores preferences to the SQL server.
     *
     * @throws Horde_Exception
     */
    public function store()
    {
        // Get the list of preferences that have changed. If there are
        // none, no need to hit the backend.
        if (!($dirty_prefs = $this->_dirtyPrefs())) {
            return;
        }

        // For each preference, check for an existing table row and
        // update it if it's there, or create a new one if it's not.
        foreach ($dirty_prefs as $scope => $prefs) {
            $updated = array();

            foreach ($prefs as $name => $pref) {
                // Don't store locked preferences.
                if ($this->_scopes[$scope][$name]['m'] & self::LOCKED) {
                    continue;
                }

                // Does a row already exist for this preference?
                $query = 'SELECT 1 FROM ' . $this->_params['table'] .
                    ' WHERE pref_uid = ? AND pref_name = ?' .
                    ' AND pref_scope = ?';
                $values = array($this->getUser(), $name, $scope);

                try {
                    $check = $this->_db->selectValue($query, $values);
                } catch (Horde_Db_Exception $e) {
                    return;
                }

                $value = strval(isset($pref['v']) ? $pref['v'] : null);

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
                        $this->getUser(),
                        $scope,
                        $name,
                        $value
                    );

                    try {
                        $this->_db->insert($query, $values);
                    } catch (Horde_Db_Exception $e) {
                        return;
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
                        $this->getUser(),
                        $name,
                        $scope
                    );

                    try {
                        $this->_db->update($query, $values);
                    } catch (Horde_Db_Exception $e) {
                        return;
                    }
                }

                // Clean the pref since it was just saved.
                $this->_scopes[$scope][$name]['m'] &= ~self::DIRTY;

                $updated[$name] = $this->_scopes[$scope][$name];
            }

            // Update the cache for this scope.
            $this->_cache->update($scope, $updated);
        }
    }

    /**
     * Clears all preferences from the backend.
     *
     * @throws Horde_Exception
     */
    public function clear()
    {
        // Build the SQL query.
        $query = 'DELETE FROM ' . $this->_params['table'] .
            ' WHERE pref_uid = ?';
        $values = array($this->getUser());

        // Execute the query.
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {}

        // Cleanup.
        parent::clear();
    }

}

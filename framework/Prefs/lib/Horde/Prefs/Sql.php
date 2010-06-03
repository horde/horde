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
 * @package  Prefs
 */
class Horde_Prefs_Sql extends Horde_Prefs
{
    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter_Base
     */
    protected $_db;

    /**
     * Returns the charset used by the concrete preference backend.
     *
     * @return string  The preference backend's charset.
     */
    public function getCharset()
    {
        return $this->_params['charset'];
    }

    /**
     * Retrieves the requested set of preferences from the user's database
     * entry.
     *
     * @param string $scope  Scope specifier.
     */
    protected function _retrieve($scope)
    {
        try {
            $this->_connect();
        } catch (Horde_Exception $e) {
            if (empty($_SESSION['prefs_cache']['unavailable'])) {
                $_SESSION['prefs_cache']['unavailable'] = true;
                if (isset($GLOBALS['notification'])) {
                    $GLOBALS['notification']->push(_("The preferences backend is currently unavailable and your preferences have not been loaded. You may continue to use the system with default settings."));
                }
            }
            return;
        }

        $query = 'SELECT pref_scope, pref_name, pref_value FROM ' .
            $this->_params['table'] . ' ' .
            'WHERE pref_uid = ? AND pref_scope = ?';
        $values = array($this->_user, $scope);

        try {
            $result = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            Horde::logMessage('No preferences were retrieved.', 'DEBUG');
            return;
        }

        foreach ($result as $row) {
            $name = trim($row['pref_name']);

            switch ($this->_db->adapterName()) {
            case 'PDO_PostgreSQL':
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
        $dirty_prefs = $this->_dirtyPrefs();
        if (!$dirty_prefs) {
            return;
        }

        $this->_connect();

        // For each preference, check for an existing table row and
        // update it if it's there, or create a new one if it's not.
        foreach ($dirty_prefs as $scope => $prefs) {
            foreach ($prefs as $name => $pref) {
                // Don't store locked preferences.
                if ($this->_scopes[$scope][$name]['m'] & self::LOCKED) {
                    continue;
                }

                // Does a row already exist for this preference?
                $query = 'SELECT 1 FROM ' . $this->_params['table'] .
                    ' WHERE pref_uid = ? AND pref_name = ?' .
                    ' AND pref_scope = ?';
                $values = array($this->_user, $name, $scope);

                try {
                    $check = $this->_db->selectValue($query, $values);
                } catch (Horde_Db_Exception $e) {
                    Horde::logMessage('Failed checking prefs for ' . $this->_user . ': ' . $e->getMessage(), 'ERR');
                    return;
                }

                $value = strval(isset($pref['v']) ? $pref['v'] : null);

                switch ($this->_db->adapterName()) {
                case 'PDO_PostgreSQL':
                    $value = pg_escape_bytea($value);
                    break;
                }

                if (empty($check)) {
                    // Insert a new row.
                    $query = 'INSERT INTO ' . $this->_params['table'] . ' ' .
                        '(pref_uid, pref_scope, pref_name, pref_value) VALUES' .
                        '(?, ?, ?, ?)';
                    $values = array(
                        $this->_user,
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
                        $this->_user,
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
            }

            // Update the cache for this scope.
            $this->_cacheUpdate($scope, array_keys($prefs));
        }
    }

    /**
     * Clears all preferences from the backend.
     *
     * @throws Horde_Exception
     */
    public function clear()
    {
        $this->_connect();

        // Build the SQL query.
        $query = 'DELETE FROM ' . $this->_params['table'] .
            ' WHERE pref_uid = ?';
        $values = array($this->_user);

        // Execute the query.
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {}

        // Cleanup.
        parent::clear();
    }

    /**
     * Converts a value from the driver's charset to the specified charset.
     *
     * @param mixed $value     A value to convert.
     * @param string $charset  The charset to convert to.
     *
     * @return mixed  The converted value.
     */
    public function convertFromDriver($value, $charset)
    {
        static $converted = array();

        if (is_array($value)) {
            return Horde_String::convertCharset($value, $this->_params['charset'], $charset);
        }

        if (is_bool($value)) {
            return $value;
        }

        if (!isset($converted[$charset][$value])) {
            $converted[$charset][$value] = Horde_String::convertCharset($value, $this->_params['charset'], $charset);
        }

        return $converted[$charset][$value];
    }

    /**
     * Converts a value from the specified charset to the driver's charset.
     *
     * @param mixed $value  A value to convert.
     * @param string $charset  The charset to convert from.
     *
     * @return mixed  The converted value.
     */
    public function convertToDriver($value, $charset)
    {
        return Horde_String::convertCharset($value, $charset, $this->_params['charset']);
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @throws Horde_Exception
     */
    protected function _connect()
    {
        if ($this->_connected) {
            return;
        }

        $this->_db = $GLOBALS['injector']->getInstance('Horde_Db')->getDb('horde', 'prefs');
        $this->_params = array_merge(array(
            'table' => 'horde_prefs'
        ), Horde::getDriverConfig('prefs'));
        $this->_connected = true;
    }

}

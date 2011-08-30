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
 *   'options'       Additional options to pass to the database.
 *   'port'          The port on which to connect to the database.
 *   'tty'           The TTY on which to connect to the database.</pre>

 * The table structure can be created by the scripts/sql/spamd_*.sql
 * script appropriate for your database, or modified from one that is
 * available.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chris Bowlby <cbowlby@tenthpowertech.com>
 * @author  Max Kalika <max@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Sam
 */
class Sam_Driver_Spamd_Sql extends Sam_Driver_Spamd_Base
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

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
        $this->_capabilities[] = 'global_defaults';
        $this->_params = array_merge($conf['sql'], $params);
    }

    /**
     * Retrieve an option set from the storage backend.
     *
     * @param boolean $defaults  Whether to retrieve the global defaults
     *                           instead of user options.
     *
     * @return array  Array of option-value pairs.
     * @throws Sam_Exception
     */
    protected function _retrieve($defaults = false)
    {
        if ($defaults) {
            $user = isset($this->_params['global_user'])
                ? $this->_params['global_user']
                : '@GLOBAL';
        } else {
            $user = $this->_user;
        }

        try {
            $result = $this->_db->select(
                'SELECT * FROM ' . $this->_params['table'] . ' WHERE username = ?',
                array($user));
        } catch (Horde_Db_Exception $e) {
            throw new Sam_Exception($e);
        }

        /* Loop through rows, retrieving options. */
        $return = array();
        foreach ($result as $row) {
            $attribute = $this->_mapOptionToAttribute($row['preference']);

            if (isset($return[$attribute])) {
                if (!is_array($return[$attribute])) {
                    $return[$attribute] = array($return[$attribute]);
                }
                if (!in_array($row['value'], $return[$attribute])) {
                    $return[$attribute][] = $row['value'];
                }
            } else {
                $return[$attribute] = $row['value'];
            }
        }

        return $return;
    }

    /**
     * Retrieves the global defaults and user options and stores them in the
     * appropriate member array (options or defaults).
     *
     * @throws Sam_Exception
     */
    public function retrieve()
    {
        /* Load defaults for any options the user hasn't already overridden. */
        $this->_defaults = $this->_retrieve(true);

        $this->_options = array_merge($this->_defaults, $this->_retrieve());
    }

    /**
     * Store an option set from the appropriate member array (options or
     * defaults) to the storage backend.
     *
     * @param boolean $defaults  Whether to store the global defaults instead
     *                           of user options.
     *
     * @throws Sam_Exception
     */
    protected function _store($defaults = false)
    {
        if ($defaults) {
            $store = $this->_defaults;
            $user = isset($this->_params['global_user'])
                ? $this->_params['global_user']
                : '@GLOBAL';
        } else {
            $store = $this->_options;
            $user = $this->_user;
        }

        foreach ($store as $attribute => $value) {
            $option = $this->_mapAttributeToOption($attribute);

            /* Delete the option if it is the same as the default */
            if (!$defaults && isset($this->_defaults[$attribute]) &&
                $this->_defaults[$attribute] === $value) {
                try {
                    $this->_db->delete(
                        'DELETE FROM ' . $this->_params['table']
                        . ' WHERE username = ? AND preference = ?',
                        array($user, $option));
                } catch (Horde_Db_Exception $e) {
                    throw new Sam_Exception($e);
                }
                continue;
            }

            if (is_array($value)) {
                try {
                    $this->_db->delete(
                        'DELETE FROM ' . $this->_params['table']
                        . ' WHERE username = ? AND preference = ?',
                        array($user, $option));
                } catch (Horde_Db_Exception $e) {
                    throw new Sam_Exception($e);
                }

                foreach ($value as $address) {
                    /* Don't save email addresses already in defaults. */
                    if (!$defaults && isset($this->_defaults[$attribute]) &&
                        ((is_array($this->_defaults[$attribute]) &&
                          in_array($address, $this->_defaults[$attribute])) ||
                         $this->_defaults[$attribute] === $address)) {
                        continue;
                    }

                    try {
                        $this->_db->insert(
                            'INSERT INTO ' . $this->_params['table']
                            . ' (username, preference, value)'
                            . ' VALUES (?, ?, ?)',
                            array($user, $option, $address));
                    } catch (Horde_Db_Exception $e) {
                        throw new Sam_Exception($e);
                    }
                }
            } else {
                try {
                    $result = $this->_db->selectValue(
                        'SELECT 1 FROM ' . $this->_params['table']
                        . ' WHERE username = ? AND preference = ?',
                    array($user, $option));
                } catch (Horde_Db_Exception $e) {
                    throw new Sam_Exception($e);
                }

                try {
                    if (is_null($result)) {
                        $this->_db->insert(
                            'INSERT INTO ' . $this->_params['table']
                            . ' (username, preference, value)'
                            . ' VALUES (?, ?, ?)',
                        array($user, $option, $value));
                    } else {
                        $this->_db->insert(
                            'UPDATE ' . $this->_params['table']
                            . ' SET value = ?'
                            . ' WHERE username = ? AND preference = ?',
                        array($value, $user, $option, $option, $value));
                    }
                } catch (Horde_Db_Exception $e) {
                    throw new Sam_Exception($e);
                }
            }
        }
    }

    /**
     * Stores the global defaults or user options from the appropriate
     * member array (options or defaults).
     *
     * @param boolean $defaults  Whether to store the global defaults instead
     *                           of user options.
     *
     * @throws Sam_Exception
     */
    public function store($defaults = false)
    {
        $this->_store($defaults);
    }
}

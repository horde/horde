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
     * @access private
     *
     * @param boolean $defaults  Whether to retrieve the global defaults
     *                           instead of user options.
     *
     * @return mixed    Array of option-value pairs or a PEAR_Error object
     *                  on failure.
     */
    protected function _retrieve($defaults = false)
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        if ($defaults) {
            $user = isset($this->_params['global_user'])
                    ? $this->_params['global_user'] : '@GLOBAL';
        } else {
            $user = $this->_user;
        }

        /* Build the SQL query. */
        $query = 'SELECT * FROM ' . $this->_params['table'] .
                 ' WHERE username = ?';
        $values = array($user);

        $return = array();

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Sam_Driver_Spamd_Sql::_retrieve(): %s', $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        /* Loop through rows, retrieving options. */
        while (($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) && !is_a($row, 'PEAR_Error')) {
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
        $result->free();

        if (is_a($row, 'PEAR_Error')) {
            return $row;
        }

        return $return;
    }

    /**
     * Retrieves the global defaults and user options and stores them in the
     * appropriate member array (options or defaults).
     *
     * @return mixed    True on success or a PEAR_Error object on failure.
     */
    public function retrieve()
    {
        /* Load defaults for any options the user hasn't already overridden. */
        $defaults = $this->_retrieve(true);
        if (!is_a($defaults, 'PEAR_Error')) {
            $this->_defaults = $defaults;
        } else {
            return $defaults;
        }

        $this->_options = $this->_defaults;
        $options = $this->_retrieve();
        if (!is_a($options, 'PEAR_Error')) {
            $this->_options = array_merge($this->_options, $options);
        } else {
            return $options;
        }

        return true;
    }

    /**
     * Store an option set from the appropriate member array (options or
     * defaults) to the storage backend.
     *
     * @access private
     *
     * @param boolean $defaults  Whether to store the global defaults instead
     *                           of user options.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    protected function _store($defaults = false)
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        if ($defaults) {
            $store = $this->_defaults;
            $user = isset($this->_params['global_user'])
                        ? $this->_params['global_user'] : '@GLOBAL';
        } else {
            $store = $this->_options;
            $user = $this->_user;
        }

        foreach ($store as $attribute => $value) {
            $option = $this->_mapAttributeToOption($attribute);

            /* Delete the option if it is the same as the default */
            if (!$defaults && isset($this->_defaults[$attribute]) &&
                $this->_defaults[$attribute] === $value) {
                $query = 'DELETE FROM ' . $this->_params['table'] .
                         ' WHERE username = ? AND preference = ?';
                $values = array($user, $option);
                /* Log the query at a DEBUG log level. */
                Horde::logMessage(sprintf('Sam_Driver_Spamd_Sql::_store(): %s', $query),
                                  __FILE__, __LINE__, PEAR_LOG_DEBUG);
                $this->_db->query($query, $values);
                continue;
            }

            if (is_array($value)) {
                $query = 'DELETE FROM ' . $this->_params['table'] .
                         ' WHERE username = ? AND preference = ?';
                $values = array($user, $option);
                /* Log the query at a DEBUG log level. */
                Horde::logMessage(sprintf('Sam_Driver_Spamd_Sql::_store(): %s', $query),
                                  __FILE__, __LINE__, PEAR_LOG_DEBUG);
                $this->_db->query($query, $values);

                foreach ($value as $address) {
                    /* Don't save email addresses already in defaults. */
                    if (!$defaults && isset($this->_defaults[$attribute]) &&
                        ((is_array($this->_defaults[$attribute]) &&
                          in_array($address, $this->_defaults[$attribute])) ||
                         $this->_defaults[$attribute] === $address)) {
                        continue;
                    }

                    $query = 'INSERT INTO ' . $this->_params['table'] .
                             ' (username, preference, value)' .
                             ' VALUES (?, ?, ?)';
                    $values = array($user, $option, $address);
                    /* Log the query at a DEBUG log level. */
                    Horde::logMessage(sprintf('Sam_Driver_Spamd_Sql::_store(): %s', $query),
                                      __FILE__, __LINE__, PEAR_LOG_DEBUG);
                    $result = $this->_db->query($query, $values);
                    if (is_a($result, 'PEAR_Error')) {
                        Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                        return $result;
                    }
                }
            } else {
                $query = 'SELECT 1 FROM ' . $this->_params['table'] .
                         ' WHERE username = ? AND preference = ?';
                $values = array($user, $option);

                /* Log the query at a DEBUG log level. */
                Horde::logMessage(sprintf('Sam_Driver_Spamd_Sql::_store(): %s', $query),
                                  __FILE__, __LINE__, PEAR_LOG_DEBUG);

                $result = $this->_db->getOne($query, $values);
                if (is_a($result, 'PEAR_Error')) {
                    Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                    return $result;
                }

                if (is_null($result)) {
                    $query = 'INSERT INTO ' . $this->_params['table'] .
                             ' (username, preference, value)' .
                             ' VALUES (?, ?, ?)';
                    $values = array($user, $option, $value);
                } else {
                    $query = 'UPDATE ' . $this->_params['table'] .
                             ' SET value = ?' .
                             ' WHERE username = ? AND preference = ?';
                    $values = array($value, $user, $option);
                }

                /* Log the query at a DEBUG log level. */
                Horde::logMessage(sprintf('Sam_Driver_Spamd_Sql::_store(): %s', $query),
                                  __FILE__, __LINE__, PEAR_LOG_DEBUG);

                $result = $this->_db->query($query, $values);
                if (is_a($result, 'PEAR_Error')) {
                    Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                    return $result;
                }
            }
        }

        return true;
    }

    /**
     * Stores the global defaults or user options from the appropriate
     * member array (options or defaults).
     *
     * @param boolean $defaults  Whether to store the global defaults instead
     *                           of user options.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    public function store($defaults = false)
    {
        return $this->_store($defaults);
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @access private
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    protected function _connect()
    {
        if (!$this->_connected) {
            Horde::assertDriverConfig($this->_params, 'spamd_sql',
                array('phptype'),
                'SAM backend', 'backends.php', '$backends');
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

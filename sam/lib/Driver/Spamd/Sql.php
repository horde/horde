<?php
/**
 * Sam SQL storage implementation using Horde_Db.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
     * Constructor.
     *
     * @param string $user   A user name.
     * @param array $params  Class parameters:
     *                       - db:    (Horde_Db_Adapater) A database handle.
     *                       - table: (string) The name of the preference table.
     *                       - global_user: (string, optional) A user name to
     *                         use when setting global preferences. Defaults to
     *                         '@GLOBAL'.
     */
    public function __construct($user, $params = array())
    {
        foreach (array('db', 'table') as $param) {
            if (!isset($params[$param])) {
                throw new InvalidArgumentException(
                    sprintf('"%s" parameter is missing', $param));
            }
        }

        $this->_db = $params['db'];
        unset($params['db']);
        $params = array_merge(array('global_user' => '@GLOBAL'), $params);
        $this->_capabilities[] = 'global_defaults';

        parent::__construct($user, $params);
    }

    /**
     * Retrieves user preferences and default values from the backend.
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
        $user = $defaults ? $this->_params['global_user'] : $this->_user;

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
     * Stores user preferences and default values in the backend.
     *
     * @param boolean $defaults  Whether to store the global defaults instead
     *                           of user options.
     *
     * @throws Sam_Exception
     */
    public function store($defaults = false)
    {
        if ($defaults) {
            $store = $this->_defaults;
            $user = $this->_params['global_user'];
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
                    if (!$result) {
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
                        array($value, $user, $option));
                    }
                } catch (Horde_Db_Exception $e) {
                    throw new Sam_Exception($e);
                }
            }
        }
    }
}

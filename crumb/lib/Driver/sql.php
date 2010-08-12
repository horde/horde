<?php
/**
 * Crumb storage implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:<pre>
 *      'phptype'       The database type (e.g. 'pgsql', 'mysql', etc.).
 *      'table'         The name of the foo table in 'database'.
 *      'charset'       The database's internal charset.</pre>
 *
 * Required by some database implementations:<pre>
 *      'database'      The name of the database.
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.</pre>
 *
 * The table structure can be created by the scripts/sql/crumb_foo.sql
 * script.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Crumb
 */
class Crumb_Driver_sql extends Crumb_Driver {

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    var $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    var $_write_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     *
     * @var boolean
     */
    var $_connected = false;

    /**
     * Constructs a new SQL storage object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Crumb_Driver_sql($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Retrieves the list of clients from the database.
     *
     * @return boolean|PEAR_Error  True on success, PEAR_Error on failure.
     */
    function _listClients()
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Build the SQL query. */
        $query = 'SELECT * FROM ' . $this->_params['table'];

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Crumb_Driver_sql::_listClients(): %s', $query), 'DEBUG');

        /* Execute the query. */
        $result = $this->_db->getAll($query, array(), DB_FETCHMODE_ASSOC);

        return $result;
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success.
     */
    function _connect()
    {
        if ($this->_connected) {
            return true;
        }

        $this->_db = $GLOBALS['injector']->getInstance('Horde_Db_Pear')->getDb('read', 'crumb', 'storage');
        $this->_write_db = $GLOBALS['injector']->getInstance('Horde_Db_Pear')->getDb('rw', 'crumb', 'storage');

        return true;
    }

    /**
     * Disconnects from the SQL server and cleans up the connection.
     *
     * @return boolean  True on success, false on failure.
     */
    function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            $this->_db->disconnect();
            $this->_write_db->disconnect();
        }

        return true;
    }

}

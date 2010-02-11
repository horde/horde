<?php
/**
 * Skeleton storage implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:
 * <pre>
 * 'phptype' - The database type (e.g. 'pgsql', 'mysql', etc.).
 * 'table' - The name of the foo table in 'database'.
 * 'charset' - The database's internal charset.
 * </pre>
 *
 * Required by some database implementations:
 * <pre>
 * 'database' - The name of the database.
 * 'hostspec' - The hostname of the database server.
 * 'protocol' - The communication protocol ('tcp', 'unix', etc.).
 * 'username' - The username with which to connect to the database.
 * 'password' - The password associated with 'username'.
 * 'options' - Additional options to pass to the database.
 * 'tty' - The TTY on which to connect to the database.
 * 'port' - The port on which to connect to the database.
 * </pre>
 *
 * The table structure can be created by the scripts/sql/skeleton_foo.sql
 * script.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Your Name <you@example.com>
 * @package Skeleton
 */
class Skeleton_Driver_Sql extends Skeleton_Driver
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
     * @var DB
     */
    protected $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    protected $_write_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * Constructs a new SQL storage object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Retrieves the foos from the database.
     */
    public function retrieve()
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Build the SQL query. */
        $query = 'SELECT * FROM ' . $this->_params['table'] . ' WHERE foo = ?';
        $values = array($this->_params['bar']);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Skeleton_Driver_Sql::retrieve(): %s', $query), __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->query($query, $values);

        if (is_a($result instanceof PEAR_Error)) {
            throw Horde_Exception_Prior($result);
        }

        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if ($row instanceof PEAR_Error) {
            throw Horde_Exception_Prior($row);
        }

        /* Store the retrieved values in the foo variable. */
        $this->_foo = array();
        while ($row && !($row instanceof PEAR_Error)) {
            /* Add this new foo to the $_foo list. */
            $this->_foo[] = $row;

            /* Advance to the new row in the result set. */
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        }

        $result->free();
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

        Horde::assertDriverConfig($this->_params, 'storage',
                                  array('phptype', 'charset', 'table'));

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
        $this->_write_db = DB::connect($this->_params,
                                       array('persistent' => !empty($this->_params['persistent'])));
        if ($this->_write_db instanceof PEAR_Error) {
            throw Horde_Exception_Prior($this->_write_db);
        }

        // Set DB portability options.
        switch ($this->_write_db->phptype) {
        case 'mssql':
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;

        default:
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        /* Check if we need to set up the read DB connection seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->_db = DB::connect($params,
                                     array('persistent' => !empty($params['persistent'])));
            if ($this->_db instanceof PEAR_Error) {
                throw Horde_Exception_Prior($this->_db);
            }

            // Set DB portability options.
            switch ($this->_db->phptype) {
            case 'mssql':
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;

            default:
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            }

        } else {
            /* Default to the same DB handle for the writer too. */
            $this->_db = $this->_write_db;
        }

        $this->_connected = true;
    }

    /**
     * Disconnects from the SQL server and cleans up the connection.
     */
    protected function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            $this->_db->disconnect();
            $this->_write_db->disconnect();
        }
    }

}

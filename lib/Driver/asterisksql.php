<?php
/**
 * Operator storage implementation for PHP's PEAR database abstraction layer.
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
 * The table structure can be created by the scripts/sql/operator_foo.sql
 * script.
 *
 * $Horde: incubator/operator/lib/Driver/asterisksql.php,v 1.1 2008/04/19 01:26:06 bklang Exp $
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Your Name <you@example.com>
 * @package Operator
 */
class Operator_Driver_asterisksql extends Operator_Driver {

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
    function Operator_Driver_asterisksql($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Get CDR data from the database
     *
     * @return boolean|PEAR_Error  True on success, PEAR_Error on failure.
     */
    function getData($accountcode = '', $start, $end)
    {

        // Use the query to make the MySQL driver look like the CDR-CSV driver
        $sql  = 'SELECT accountcode, src, dst, dcontext, clid, channel, ' .
                'dstchannel, lastapp, lastdata, calldate AS start, ' .
                'calldate AS answer, calldate AS end, duration, ' .
                'billsec, disposition, amaflags, userfield, uniqueid ' .
                ' FROM ' . $this->_params['table'];

        // Filter by account code
        $sql .= ' WHERE accountcode = ? ';
        if (!is_a($start, 'Horde_Date')) {
            $start = new Horde_Date($start);
            if (is_a($start, 'PEAR_Error')) {
                return $start;
            }
        }
        $sql .= ' AND calldate > ? ';

        if (!is_a($end, 'Horde_Date')) {
            $end = new Horde_Date($end);
            if (is_a($end, 'PEAR_Error')) {
                return $end;
            }
        }
        $sql .= ' AND calldate < ? ';
        $values = array($accountcode,
                        $start->strftime('%Y-%m-%d %T'),
                        $end->strftime('%Y-%m-%d %T'));

        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Operator_Driver_asterisksql::retrieve(): %s', $sql),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        return $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return boolean  True on success; exits (Horde::fatal()) on error.
     */
    function _connect()
    {
        if ($this->_connected) {
            return true;
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
        require_once 'DB.php';
        $this->_write_db = &DB::connect($this->_params,
                                        array('persistent' => !empty($this->_params['persistent'])));
        if (is_a($this->_write_db, 'PEAR_Error')) {
            Horde::fatal($this->_write_db, __FILE__, __LINE__);
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
            $this->_db = &DB::connect($params,
                                      array('persistent' => !empty($params['persistent'])));
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

        } else {
            /* Default to the same DB handle for the writer too. */
            $this->_db =& $this->_write_db;
        }

        $this->_connected = true;

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

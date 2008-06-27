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
 * $Horde: incubator/operator/lib/Driver/asterisksql.php,v 1.4 2008/06/27 17:17:11 bklang Exp $
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
    function getData($start, $end, $accountcode = null)
    {

        // Use the query to make the MySQL driver look like the CDR-CSV driver
        $sql  = 'SELECT accountcode, src, dst, dcontext, clid, channel, ' .
                'dstchannel, lastapp, lastdata, calldate AS start, ' .
                'calldate AS answer, calldate AS end, duration, ' .
                'billsec, disposition, amaflags, userfield, uniqueid ' .
                ' FROM ' . $this->_params['table'];
        $values = array();

        // Start Date
        if (!is_a($start, 'Horde_Date')) {
            $start = new Horde_Date($start);
            if (is_a($start, 'PEAR_Error')) {
                return $start;
            }
        }
        $sql .= ' AND calldate > ? ';
        $values[] = $start->strftime('%Y-%m-%d %T');

        // End Date
        if (!is_a($end, 'Horde_Date')) {
            $end = new Horde_Date($end);
            if (is_a($end, 'PEAR_Error')) {
                return $end;
            }
        }
        $sql .= ' AND calldate < ? ';
        $values[] =  $end->strftime('%Y-%m-%d %T');

        // Filter by account code
        if ($accountcode !== null) {
            $sql .= ' WHERE accountcode LIKE ? ';
            $values[] = $accountcode;
        }

        // Filter by destination context
        if ($dcontext !== null) {
            $sql .= ' WHERE dcontext LIKE ? ';
            $values[] = $dcontext;
        }

        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Operator_Driver_asterisksql::getData(): %s', $sql),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        return $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
    }

    /**
     * Get summary call statistics per-month for a given time range, account and
     * destination.
     *
     * @param Horde_Date startdate  Start of the statistics window
     * @param Horde_Date enddate    End of the statistics window
     * @param string accountcode    Name of the accont for statistics.  Defaults
     *                              to null meaning all accounts.
     * @param string dcontext       Destination of calls.  Defaults to null.
     *
     *
     * @return array|PEAR_Error     Array of call statistics.  The key of each
     *                              element is the month name in date('Y-m')
     *                              format and the value being an array of
     *                              statistics for calls placed that month. This
     *                              method will additionall return PEAR_Error
     *                              on failure.
     */
    function getCallStatsByMonth($start, $end, $accountcode = null,
                                 $dcontext = null)
    {
        if (!is_a($start, 'Horde_Date') || !is_a($end, 'Horde_Date')) {
            Horde::logMessage('Start ane end date must be Horde_Date objects.', __FILE__, __LINE__, PEAR_LOG_ERR);
            return PEAR::raiseError(_("Internal error.  Details have been logged for the administrator."));
        }

        /* Make sure we have a valid database connection. */
        $this->_connect();

        // We always compare entire months.
        $start->mday = 1;
        $end->mday = Horde_Date::daysInMonth($end->month, $end->year);


        // Construct the queries we will be running below
        // Use 1=1 to make constructing the filter string easier
        $numcalls_query = 'SELECT COUNT(*) AS count FROM ' .
                          $this->_params['table'] . ' WHERE 1=1';

        $minutes_query = 'SELECT SUM(duration)/60 AS minutes FROM ' .
                         $this->_params['table'] . ' WHERE 1=1';

        $failed_query = 'SELECT COUNT(disposition) AS failed FROM ' .
                         $this->_params['table'] . ' WHERE ' .
                         'disposition="failed"';
        $values = array();

        // Shared SQL filter
        $filter = '';

        // Filter by account code
        if ($accountcode !== null) {
            $filter .= ' AND accountcode LIKE ?';
            $values[] = $accountcode;
        }

        // Filter by destination context
        if ($dcontext !== null) {
            $filter .= ' AND dcontext LIKE ?';
            $values[] = $dcontext;
        }

        // Filter by the date range (filled in below)
        $filter .= ' AND calldate >= ? AND calldate < ?';

        $stats = array();

        // Copy the object so we can reuse the start date below
        $curdate = new Horde_Date($start);

        // FIXME: Is there a more efficient way to do this?  Perhaps
        //        lean more on the SQL engine?
        while($curdate->compareDate($end) <= 0) {
            $curvalues = $values;
            $curvalues[] = $curdate->strftime('%Y-%m-%d %T');

            // Index for the results array
            $index = $curdate->strftime('%Y-%m');
             
            // Find the first day of the next month
            $curdate->month++;
            $curdate->correct();
            $curvalues[] = $curdate->strftime('%Y-%m-%d %T');

            $sql = $numcalls_query . $filter;
            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Operator_Driver_asterisksql::getCallStats(): %s', $sql), __FILE__, __LINE__, PEAR_LOG_DEBUG);

            $res = $this->_db->getOne($sql, $curvalues);
            if (is_a($res, 'PEAR_Error')) {
                Horde::logMessage($res, __FILE__, __LINE__, PEAR_LOG_ERR);
                return PEAR::raiseError(_("Internal error.  Details have been logged for the administrator."));
            }
            $stats[$index]['numcalls'] = $res;

            $sql = $minutes_query . $filter;
            Horde::logMessage(sprintf('Operator_Driver_asterisksql::getCallStats(): %s', $sql), __FILE__, __LINE__, PEAR_LOG_DEBUG);
            $res = $this->_db->getOne($minutes_query . $filter, $curvalues);
            if (is_a($res, 'PEAR_Error')) {
                Horde::logMessage($res, __FILE__, __LINE__, PEAR_LOG_ERR);
                return PEAR::raiseError(_("Internal error.  Details have been logged for the administrator."));
            }
            $stats[$index]['minutes'] = $res;

            $sql = $failed_query . $filter;
            Horde::logMessage(sprintf('Operator_Driver_asterisksql::getCallStats(): %s', $sql), __FILE__, __LINE__, PEAR_LOG_DEBUG);
            $res = $this->_db->getOne($sql, $curvalues);
            if (is_a($res, 'PEAR_Error')) {
                Horde::logMessage($res, __FILE__, __LINE__, PEAR_LOG_ERR);
                return PEAR::raiseError(_("Internal error.  Details have been logged for the administrator."));
            }
            $stats[$index]['failed'] = $res;
       }
             
       return $stats;
    }

    /**
     * Attempts to open a connection to the SQL server.
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

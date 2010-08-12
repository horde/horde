<?php
/**
 * Operator storage implementation for PHP's PEAR database abstraction layer.
 *
 * The table structure can be created by the scripts/sql/operator_foo.sql
 * script.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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
     * Get call detail records from the database
     *
     * @return array  [0] contains summary statistics; [1] is an array of the
     *                actual call records.
     * @throws Operator_Exception|Horde_Date_Exception
     */
    protected function _getRecords($start, $end, $accountcode = null, $dcontext = null,
                         $rowstart = 0, $rowlimit = null)
    {

        // Use the query to make the MySQL driver look like the CDR-CSV driver
        $sql  = 'SELECT accountcode, src, dst, dcontext, clid, channel, ' .
                'dstchannel, lastapp, lastdata, calldate AS start, ' .
                'calldate AS answer, calldate AS end, duration, ' .
                'billsec, disposition, amaflags, userfield, uniqueid ' .
                ' FROM ' . $this->_params['table'] . ' WHERE %s';
        $filter = array();
        $values = array();

        if (!is_numeric($rowstart)) {
            Horde::logMessage('Invalid start row requested.', 'ERR');
            throw new Operator_Exception(_("Internal error.  Details have been logged for the administrator."));
        }
        if (!is_null($rowlimit) && !is_numeric($rowlimit)) {
            Horde::logMessage('Invalid row limit requested.', 'ERR');
            throw new Operator_Exception(_("Internal error.  Details have been logged for the administrator."));
        }

        $filter[] = 'calldate >= ?';
        $values[] = $start->strftime('%Y-%m-%d %T');
        $filter[] = 'calldate < ?';
        $values[] =  $end->strftime('%Y-%m-%d %T');

        // Filter by account code
        if ($accountcode !== null) {
            $filter[] = 'accountcode LIKE ?';
            $values[] = $accountcode;
        } else {
            $filter[] = 'accountcode = ""';
        }

        // Filter by destination context
        if ($dcontext !== null) {
            $filter[] = 'dcontext LIKE ?';
            $values[] = $dcontext;
        } else {
            $filter[] = 'dcontext = ""';
        }

        /* Make sure we have a valid database connection. */
        $this->_connect();

        $filterstring = implode(' AND ', $filter);
        $sql = sprintf($sql, $filterstring);
        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Operator_Driver_asterisksql::getData(): %s', $sql), 'DEBUG');

        /* Execute the query. */
        if (is_null($rowlimit)) {
            $res = $this->_db->query($sql, $values);
        } else {
            $res = $this->_db->limitQuery($sql, $rowstart, $rowlimit, $values);
        }
        if (is_a($res, 'PEAR_Error')) {
            Horde::logMessage($res, 'ERR');
            throw new Operator_Exception(_("Internal error.  Details have been logged for the administrator."));
        }

        $data = array();
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $data[] = $row;
        }

        // Get summary statistics on the requested criteria
        $sql = 'SELECT COUNT(*) AS numcalls, SUM(duration)/60 AS minutes, ' .
               'SUM(CASE disposition WHEN "FAILED" THEN 1 ELSE 0 END) AS ' .
               'failed FROM ' . $this->_params['table'] . ' WHERE %s';
        $sql = sprintf($sql, $filterstring);
        Horde::logMessage(sprintf('Operator_Driver_asterisksql::getData(): %s', $sql), 'DEBUG');

        /* Execute the query. */
        $res = $this->_db->getRow($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($res, 'PEAR_Error')) {
            Horde::logMessage($res, 'ERR');
            throw new Operator_Exception(_("Internal error.  Details have been logged for the administrator."));
        }

        return array($res, $data);
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
     * @return array                Array of call statistics.  The key of each
     *                              element is the month name in date('Y-m')
     *                              format and the value being an array of
     *                              statistics for calls placed that month.
     * @throws Operator_Exception|Horde_Date_Exception
     */
    protected function _getMonthlyCallStats($start, $end, $accountcode = null,
                                 $dcontext = null)
    {
        if (!is_a($start, 'Horde_Date') || !is_a($end, 'Horde_Date')) {
            Horde::logMessage('Start ane end date must be Horde_Date objects.', 'ERR');
            throw new Operator_Exception(_("Internal error.  Details have been logged for the administrator."));
        }

        /* Make sure we have a valid database connection. */
        $this->_connect();

        // Construct the queries we will be running below
        // Use 1=1 to make constructing the filter string easier
        $numcalls_query = 'SELECT MONTH(calldate) AS month, ' .
                          'YEAR(calldate) AS year, ' .
                          'COUNT(*) AS numcalls FROM ' .
                          $this->_params['table'] . ' WHERE %s ' .
                          'GROUP BY year, month';

        $minutes_query = 'SELECT MONTH(calldate) AS month, ' .
                         'YEAR(calldate) AS year, ' .
                         'SUM(duration)/60 AS minutes FROM ' .
                         $this->_params['table'] . ' WHERE %s ' .
                         'GROUP BY year, month';

        $failed_query = 'SELECT MONTH(calldate) AS month, ' .
                        'YEAR(calldate) AS year, ' .
                        'COUNT(disposition) AS failed FROM ' .
                        $this->_params['table'] . ' ' .
                        'WHERE disposition="failed" AND %s ' .
                        'GROUP BY year, month';

        // Shared SQL filter
        $filter = array();
        $values = array();

        // Filter by account code
        if ($accountcode !== null) {
            $filter[] = 'accountcode LIKE ?';
            $values[] = $accountcode;
        } else {
            $filter[] = 'accountcode = ""';
        }

        // Filter by destination context
        if ($dcontext !== null) {
            $filter[] = 'dcontext LIKE ?';
            $values[] = $dcontext;
        } else {
            $filter[] =  'dcontext = ""';
        }

        // Filter by the date range (filled in below)
        $filter[] = 'calldate >= ?';
        $values[] = $start->strftime('%Y-%m-%d %T');
        $filter[] = 'calldate < ?';
        $values[] = $end->strftime('%Y-%m-%d %T');

        $filterstring = implode(' AND ', $filter);

        $stats = array();

        /* Log the query at a DEBUG log level. */
        $sql = sprintf($numcalls_query, $filterstring);
        Horde::logMessage(sprintf('Operator_Driver_asterisksql::getCallStats(): Values: %s', print_r($values, true)), 'DEBUG');
        Horde::logMessage(sprintf('Operator_Driver_asterisksql::getCallStats(): %s', $sql), 'DEBUG');
        $numcalls_res = $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($numcalls_res, 'PEAR_Error')) {
            Horde::logMessage($numcalls_res, 'ERR');
            throw new Operator_Exception(_("Internal error.  Details have been logged for the administrator."));
        }

        $sql = sprintf($minutes_query, $filterstring);
        Horde::logMessage(sprintf('Operator_Driver_asterisksql::getCallStats(): %s', $sql), 'DEBUG');
        $minutes_res = $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($minutes_res, 'PEAR_Error')) {
            Horde::logMessage($minutes_res, 'ERR');
            throw new Operator_Exception(_("Internal error.  Details have been logged for the administrator."));
        }

        $sql = sprintf($failed_query, $filterstring);
        Horde::logMessage(sprintf('Operator_Driver_asterisksql::getCallStats(): %s', $sql), 'DEBUG');
        $failed_res = $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
        if (is_a($failed_res, 'PEAR_Error')) {
            Horde::logMessage($failed_res, 'ERR');
            throw new Operator_Exception(_("Internal error.  Details have been logged for the administrator."));
        }

        // Normalize the results from the database.  This is done because
        // the database will not return values if there are no data that match
        // the query.  For example if there were no calls in the month of June
        // the results will not have any rows with data for June.  Instead of
        // searching through the results for each month we stuff the values we
        // have into a temporary array and then create the return value below
        // using 0 values where necessary.
        $numcalls = array();
        foreach ($numcalls_res as $row) {
            $numcalls[$row['year']][$row['month']] = $row['numcalls'];
        }
        $minutes = array();
        foreach ($minutes_res as $row) {
            $minutes[$row['year']][$row['month']] = $row['minutes'];
        }
        $failed = array();
        foreach ($failed_res as $row) {
            $failed[$row['year']][$row['month']] = $row['failed'];
        }

        $s_numcalls = array();
        $s_minutes = array();
        $s_failed = array();
        while($start->compareDate($end) <= 0) {
            $index = $start->strftime('%Y-%m');
            $year = $start->year;
            $month = $start->month;

            if (empty($numcalls[$year]) || empty($numcalls[$year][$month])) {
                $s_numcalls[$index] = 0;
            } else {
                $s_numcalls[$index] = $numcalls[$year][$month];
            }

            if (empty($minutes[$year]) || empty($minutes[$year][$month])) {
                $s_minutes[$index] = 0;
            } else {
                $s_minutes[$index] = $minutes[$year][$month];
            }

            if (empty($failed[$year]) || empty($failed[$year][$month])) {
                $s_failed[$index] = 0;
            } else {
                $s_failed[$index] = $failed[$year][$month];
            }

            // Find the first day of the next month
            $start->month++;
        }

        $info = Operator::getGraphInfo('numcalls');
        $stats['numcalls'] = array($info['title'] => $s_numcalls);
        $info = Operator::getGraphInfo('minutes');
        $stats['minutes'] = array($info['title'] => $s_minutes);
//        $info = Operator::getGraphInfo('failed');
//        $stats['failed'] = array($info['title'] => $s_failed);

        return $stats;
    }

    public function getAccountCodes()
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        $sql = 'SELECT DISTINCT(accountcode) FROM ' . $this->_params['table'] .
               ' ORDER BY accountcode';
        Horde::logMessage(sprintf('Operator_Driver_asterisksql::getAccountCodes(): %s', $sql), 'DEBUG');
        $res = $this->_db->getCol($sql, 'accountcode');
        if (is_a($res, 'PEAR_Error')) {
            Horde::logMessage($res, 'ERR');
            throw new Operator_Exception(_("Internal error.  Details have been logged for the administrator."));
        }

        return $res;
    }

    /**
     * Attempts to open a connection to the SQL server.
     *
     * @return boolean  True on success.
     * @throws Horde_Exception
     */
    protected function _connect()
    {
        if ($this->_connected) {
            return true;
        }

        $this->_db = $GLOBALS['injector']->getInstance('Horde_Db_Pear')->getDb('read', 'operator', 'storage');
        $this->_write_db = $GLOBALS['injector']->getInstance('Horde_Db_Pear')->getDb('rw', 'operator', 'storage');

        return true;
    }

    /**
     * Disconnects from the SQL server and cleans up the connection.
     *
     * @return boolean  True on success, false on failure.
     */
    protected function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            $this->_db->disconnect();
            $this->_write_db->disconnect();
        }

        return true;
    }

}

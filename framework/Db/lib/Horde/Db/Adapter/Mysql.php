<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */

/**
 * MySQL Improved Horde_Db_Adapter
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Mysql extends Horde_Db_Adapter_Base
{
    /**
     * Mysql database connection handle.
     * @var resource
     */
    protected $_connection = null;

    /**
     * Last auto-generated insert_id
     * @var integer
     */
    protected $_insertId;

    /**
     * @var string
     */
    protected $_schemaClass = 'Horde_Db_Adapter_Mysql_Schema';


    /*##########################################################################
    # Public
    ##########################################################################*/

    /**
     * Returns the human-readable name of the adapter.  Use mixed case - one
     * can always use downcase if needed.
     *
     * @return  string
     */
    public function adapterName()
    {
        return 'MySQL';
    }

    /**
     * Does this adapter support migrations?  Backend specific, as the
     * abstract adapter always returns +false+.
     *
     * @return  boolean
     */
    public function supportsMigrations()
    {
        return true;
    }


    /*##########################################################################
    # Connection Management
    ##########################################################################*/

    /**
     * Connect to the db
     */
    public function connect()
    {
        if ($this->_active) {
            return;
        }

        $config = $this->_parseConfig();

        $oldTrackErrors = ini_set('track_errors', 1);
        $mysql = @mysql_connect($config['host'], $config['username'], $config['password']);
        ini_set('track_errors', $oldTrackErrors);

        if (!$mysql) {
            throw new Horde_Db_Exception('Connect failed: ' . $php_errormsg);
        }
        if (!mysql_select_db($config['dbname'])) {
            throw new Horde_Db_Exception('Could not select database: ' . $config['dbname']);
        }

        $this->_connection = $mysql;
        $this->_active     = true;
    }

    /**
     * Disconnect from db
     */
    public function disconnect()
    {
        if ($this->_connection) { @mysql_close($this->_connection); }
        $this->_connection = null;
        $this->_active = false;
    }

    /**
     * Check if the connection is active
     *
     * @return  boolean
     */
    public function isActive()
    {
        return isset($this->_connection) && mysql_ping($this->_connection);
    }


    /*##########################################################################
    # Quoting
    ##########################################################################*/

    /**
     * Quotes a string, escaping any ' (single quote) and \ (backslash)
     * characters..
     *
     * @param   string  $string
     * @return  string
     */
    public function quoteString($string)
    {
        return "'" . mysql_real_escape_string($string, $this->_connection) . "'";
    }


    /*##########################################################################
    # Database Statements
    ##########################################################################*/

    /**
     * Returns an array of records with the column names as keys, and
     * column values as values.
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     * @return  array
     */
    public function select($sql, $arg1 = null, $arg2 = null)
    {
        return new Horde_Db_Adapter_Mysql_Result($this, $sql, $arg1, $arg2);
    }

    /**
     * Returns an array of record hashes with the column names as keys and
     * column values as values.
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     */
    public function selectAll($sql, $arg1 = null, $arg2 = null)
    {
        $result = $this->execute($sql, $arg1, $arg2);
        $rows = array();
        if ($result) {
            while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Returns a record hash with the column names as keys and column values
     * as values.
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     * @return  array
     */
    public function selectOne($sql, $arg1=null, $arg2=null)
    {
        $result = $this->execute($sql, $arg1, $arg2);
        return $result ? mysql_fetch_array($result, MYSQL_ASSOC) : array();
    }

    /**
     * Returns a single value from a record
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     * @return  string
     */
    public function selectValue($sql, $arg1=null, $arg2=null)
    {
        $result = $this->selectOne($sql, $arg1, $arg2);
        return $result ? current($result) : null;
    }

    /**
     * Returns an array of the values of the first column in a select:
     *   select_values("SELECT id FROM companies LIMIT 3") => [1,2,3]
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     */
    public function selectValues($sql, $arg1=null, $arg2=null)
    {
        $values = array();
        $result = $this->execute($sql, $arg1, $arg2);
        if ($result) {
            while ($row = mysql_fetch_row($result)) {
                $values[] = $row[0];
            }
        }
        return $values;
    }

    /**
     * Executes the SQL statement in the context of this connection.
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     */
    public function execute($sql, $arg1=null, $arg2=null)
    {
        if (is_array($arg1)) {
            $sql = $this->_replaceParameters($sql, $arg1);
            $name = $arg2;
        } else {
            $name = $arg1;
        }

        $t = new Horde_Support_Timer();
        $t->push();

        $this->last_query = $sql;
        $stmt = mysql_query($sql, $this->_connection);
        if (!$stmt) {
            $this->_logInfo($sql, 'QUERY FAILED: ' . mysql_error($this->_connection));
            $this->_logInfo($sql, $name);
            throw new Horde_Db_Exception('QUERY FAILED: ' . mysql_error($this->_connection) . "\n\n" . $sql,
                                         $this->_errorCode(null, mysql_errno($this->_connection)));
        }

        $this->_logInfo($sql, $name, $t->pop());

        $this->_rowCount = mysql_affected_rows($this->_connection);
        $this->_insertId = mysql_insert_id($this->_connection);
        return $stmt;
    }

    /**
     * Returns the last auto-generated ID from the affected table.
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     * @param   string  $pk
     * @param   int     $idValue
     * @param   string  $sequenceName
     */
    public function insert($sql, $arg1 = null, $arg2 = null, $pk = null, $idValue = null, $sequenceName = null)
    {
        $this->execute($sql, $arg1, $arg2);
        return isset($idValue) ? $idValue : $this->_insertId;
    }

    /**
     * Begins the transaction (and turns off auto-committing).
     */
    public function beginDbTransaction()
    {
        $this->_transactionStarted = true;
        $this->last_query = 'SET AUTOCOMMIT=0; BEGIN';
        @mysql_query('SET AUTOCOMMIT=0', $this->_connection) && @mysql_query('BEGIN', $this->_connection);
    }

    /**
     * Commits the transaction (and turns on auto-committing).
     */
    public function commitDbTransaction()
    {
        $this->last_query = 'COMMIT; SET AUTOCOMMIT=1';
        @mysql_query('COMMIT', $this->_connection) && @mysql_query('SET AUTOCOMMIT=1', $this->_connection);
        $this->_transactionStarted = false;
    }

    /**
     * Rolls back the transaction (and turns on auto-committing). Must be
     * done if the transaction block raises an exception or returns false.
     */
    public function rollbackDbTransaction()
    {
        if (!$this->_transactionStarted) {
            return;
        }

        $this->last_query = 'ROLLBACK; SET AUTOCOMMIT=1';
        @mysql_query('ROLLBACK', $this->_connection) && @mysql_query('SET AUTOCOMMIT=1', $this->_connection);
        $this->_transactionStarted = false;
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * Return a standard error code
     *
     * @param   string   $sqlstate
     * @param   integer  $errno
     * @return  integer
     */
    protected function _errorCode($sqlstate, $errno)
    {
        /*@TODO do something with standard sqlstate vs. MySQL error codes vs. whatever else*/
        return $errno;
    }

    /**
     * Parse configuration array into options for mysql_connect
     *
     * @throws  Horde_Db_Exception
     * @return  array  [host, username, password, dbname]
     */
    protected function _parseConfig()
    {
        // check required config keys are present
        $required = array('username');
        $diff = array_diff_key(array_flip($required), $this->_config);
        if (! empty($diff)) {
            $msg = 'Required config missing: ' . implode(', ', array_keys($diff));
            throw new Horde_Db_Exception($msg);
        }

        $rails2mysqli = array('database' => 'dbname');
        foreach ($rails2mysqli as $from => $to) {
            if (isset($this->_config[$from])) {
                $this->_config[$to] = $this->_config[$from];
                unset($this->_config[$from]);
            }
        }

        if (isset($this->_config['port'])) {
            if (empty($this->_config['host'])) {
                $msg = 'host is required if port is specified';
                throw new Horde_Db_Exception($msg);
            }
            $this->_config['host'] .= ':' . $this->_config['port'];
            unset($this->_config['port']);
        }

        if (!empty($this->_config['socket'])) {
            if (!empty($this->_config['host']) && $this->_config['host'] != 'localhost') {
                $msg = 'can only specify host or socket, not both';
                throw new Horde_Db_Exception($msg);
            }
            $this->_config['host'] = ':' . $this->_config['socket'];
            unset($this->_config['socket']);
        }

        $config = $this->_config;

        if (!isset($config['host']))      $config['host'] = null;
        if (!isset($config['username']))  $config['username'] = null;
        if (!isset($config['password']))  $config['password'] = null;
        if (!isset($config['dbname']))    $config['dbname'] = null;

        return $config;
    }

}

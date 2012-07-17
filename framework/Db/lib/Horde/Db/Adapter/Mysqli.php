<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */

/**
 * MySQL Improved Horde_Db_Adapter
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Mysqli extends Horde_Db_Adapter_Base
{
    /**
     * Mysqli database connection object.
     * @var mysqli
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

    /**
     * @var boolean
     */
    protected $_hasMysqliFetchAll = false;


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
        return 'MySQLi';
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
     *
     * MySQLi can connect using SSL if $config contains an 'ssl' sub-array
     * containing the following keys:
     *     + key      The path to the key file.
     *     + cert     The path to the certificate file.
     *     + ca       The path to the certificate authority file.
     *     + capath   The path to a directory that contains trusted SSL
     *                CA certificates in pem format.
     *     + cipher   The list of allowable ciphers for SSL encryption.
     *
     * Example of how to connect using SSL:
     * <code>
     * $config = array(
     *     'username' => 'someuser',
     *     'password' => 'apasswd',
     *     'hostspec' => 'localhost',
     *     'database' => 'thedb',
     *     'ssl'      => array(
     *         'key'      => 'client-key.pem',
     *         'cert'     => 'client-cert.pem',
     *         'ca'       => 'cacert.pem',
     *         'capath'   => '/path/to/ca/dir',
     *         'cipher'   => 'AES',
     *     ),
     * );
     *
     * $db = new Horde_Db_Adapter_Mysqli($config);
     * </code>
     */
    public function connect()
    {
        if ($this->_active) {
            return;
        }

        $config = $this->_parseConfig();

        if (!empty($config['ssl'])) {
            $mysqli = mysqli_init();
            $mysqli->ssl_set(
                empty($config['ssl']['key'])    ? null : $config['ssl']['key'],
                empty($config['ssl']['cert'])   ? null : $config['ssl']['cert'],
                empty($config['ssl']['ca'])     ? null : $config['ssl']['ca'],
                empty($config['ssl']['capath']) ? null : $config['ssl']['capath'],
                empty($config['ssl']['cipher']) ? null : $config['ssl']['cipher']
            );
            $mysqli->real_connect(
                $config['host'], $config['username'], $config['password'],
                $config['dbname'], $config['port'], $config['socket']);
        } else {
            $mysqli = new mysqli(
                $config['host'], $config['username'], $config['password'],
                $config['dbname'], $config['port'], $config['socket']);
        }
        if (mysqli_connect_errno()) {
            throw new Horde_Db_Exception('Connect failed: (' . mysqli_connect_errno() . ') ' . mysqli_connect_error(), mysqli_connect_errno());
        }

        // If supported, request real datatypes from MySQL instead of returning
        // everything as a string.
        if (defined('MYSQLI_OPT_INT_AND_FLOAT_NATIVE')) {
            $mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);
        }

        $this->_connection = $mysqli;
        $this->_active     = true;

        // Set the default charset. http://dev.mysql.com/doc/refman/5.1/en/charset-connection.html
        if (!empty($config['charset'])) {
            $this->setCharset($config['charset']);
        }

        $this->_hasMysqliFetchAll = function_exists('mysqli_fetch_all');
    }

    /**
     * Disconnect from db
     */
    public function disconnect()
    {
        if ($this->_connection) { $this->_connection->close(); }
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
        $this->_lastQuery = 'SELECT 1';
        return isset($this->_connection) && $this->_connection->query('SELECT 1');
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
        return "'".$this->_connection->real_escape_string($string)."'";
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
    public function select($sql, $arg1=null, $arg2=null)
    {
        return new Horde_Db_Adapter_Mysqli_Result($this, $sql, $arg1, $arg2);
    }

    /**
     * Returns an array of record hashes with the column names as keys and
     * column values as values.
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     */
    public function selectAll($sql, $arg1=null, $arg2=null)
    {
        $result = $this->execute($sql, $arg1, $arg2);
        if ($this->_hasMysqliFetchAll) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $rows = array();
            if ($result) {
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $rows[] = $row;
                }
            }
        }
        return $rows;
    }

    /**
     * Returns a record hash with the column names as keys and column values as
     * values.
     *
     * @param string $sql   A query.
     * @param mixed  $arg1  Either an array of bound parameters or a query name.
     * @param string $arg2  If $arg1 contains bound parameters, the query name.
     *
     * @return array|boolean  A record hash or false if no record found.
     */
    public function selectOne($sql, $arg1 = null, $arg2 = null)
    {
        $result = $this->execute($sql, $arg1, $arg2);
        $result = $result ? $result->fetch_array(MYSQLI_ASSOC) : array();
        return is_null($result) ? false : $result;
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
            while ($row = $result->fetch_row()) {
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

        $this->_lastQuery = $sql;
        $stmt = $this->_connection->query($sql);
        if (!$stmt) {
            $this->_logInfo($sql, 'QUERY FAILED: ' . $this->_connection->error);
            $this->_logInfo($sql, $name);
            throw new Horde_Db_Exception('QUERY FAILED: ' . $this->_connection->error . "\n\n" . $sql,
                                         $this->_errorCode($this->_connection->sqlstate, $this->_connection->errno));
        }

        $this->_logInfo($sql, $name, $t->pop());
        //@TODO if ($this->_connection->info) $this->_loginfo($sql, $this->_connection->info);
        //@TODO also log warnings? http://php.net/mysqli.warning-count and http://php.net/mysqli.get-warnings

        $this->_rowCount = $this->_connection->affected_rows;
        $this->_insertId = $this->_connection->insert_id;
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
    public function insert($sql, $arg1=null, $arg2=null, $pk=null, $idValue=null, $sequenceName=null)
    {
        $this->execute($sql, $arg1, $arg2);
        return isset($idValue) ? $idValue : $this->_insertId;
    }

    /**
     * Begins the transaction (and turns off auto-committing).
     */
    public function beginDbTransaction()
    {
        $this->_connection->autocommit(false);
        $this->_transactionStarted++;
    }

    /**
     * Commits the transaction (and turns on auto-committing).
     */
    public function commitDbTransaction()
    {
        parent::commitDbTransaction();
        if (!$this->_transactionStarted) {
            $this->_connection->autocommit(true);
        }
    }

    /**
     * Rolls back the transaction (and turns on auto-committing). Must be
     * done if the transaction block raises an exception or returns false.
     */
    public function rollbackDbTransaction()
    {
        parent::rollbackDbTransaction();
        if (!$this->_transactionStarted) {
            $this->_connection->autocommit(true);
        }
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
     * Parse configuration array into options for MySQLi constructor.
     *
     * @throws  Horde_Db_Exception
     * @return  array  [host, username, password, dbname, port, socket]
     */
    protected function _parseConfig()
    {
        $this->_checkRequiredConfig(array('username'));

        $rails2mysqli = array('database' => 'dbname');
        foreach ($rails2mysqli as $from => $to) {
            if (isset($this->_config[$from])) {
                $this->_config[$to] = $this->_config[$from];
                unset($this->_config[$from]);
            }
        }

        if (!empty($this->_config['host']) &&
            $this->_config['host'] == 'localhost') {
            $this->_config['host'] = '127.0.0.1';
        }

        if (isset($this->_config['port'])) {
            if (empty($this->_config['host'])) {
                throw new Horde_Db_Exception('Host is required if port is specified');
            }
        }

        $config = $this->_config;

        if (!isset($config['host']))     $config['host'] = null;
        if (!isset($config['username'])) $config['username'] = null;
        if (!isset($config['password'])) $config['password'] = null;
        if (!isset($config['dbname']))   $config['dbname'] = null;
        if (!isset($config['port']))     $config['port'] = null;
        if (!isset($config['socket']))   $config['socket'] = null;

        return $config;
    }

}

<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Oci8 extends Horde_Db_Adapter_Base
{
    /**
     * Schema class to use.
     *
     * @var string
     */
    protected $_schemaClass = 'Horde_Db_Adapter_Oracle_Schema';


    /*#########################################################################
    # Public
    #########################################################################*/

    /**
     * Returns the human-readable name of the adapter.  Use mixed case - one
     * can always use downcase if needed.
     *
     * @return string
     */
    public function adapterName()
    {
        return 'Oracle';
    }

    /**
     * Does this adapter support migrations?
     *
     * @return boolean
     */
    public function supportsMigrations()
    {
        return true;
    }


    /*#########################################################################
    # Connection Management
    #########################################################################*/

    /**
     * Connect to the db
     */
    public function connect()
    {
        if ($this->_active) {
            return;
        }

        $this->_checkRequiredConfig(array('username'));

        if (!isset($this->_config['tns']) && empty($this->_config['host'])) {
            throw new Horde_Db_Exception('Either a TNS name or a host name must be specified');
        }

        if (isset($this->_config['tns'])) {
            $connection = $this->_config['tns'];
        } else {
            $connection = $this->_config['host'];
            if (!empty($this->_config['port'])) {
                $connection .= ':' . $this->_config['port'];
            }
            if (!empty($this->_config['service'])) {
                $connection .= '/' . $this->_config['service'];
            }
            if (!empty($this->_config['type'])) {
                $connection .= ':' . $this->_config['type'];
            }
            if (!empty($this->_config['instance'])) {
                $connection .= '/' . $this->_config['instance'];
            }
        }
        $oci = oci_connect(
            $this->_config['username'],
            isset($this->_config['password']) ? $this->_config['password'] : '',
            $connection,
            $this->_config['charset']
        );
        if (!$oci) {
            if ($error = oci_error()) {
                throw new Horde_Db_Exception(
                    sprintf(
                        'Connect failed: (%d) %s',
                        $error['code'],
                        $error['message']
                    ),
                    $error['code']
                );
            } else {
                throw new Horde_Db_Exception('Connect failed');
            }
        }

        $this->_connection = $oci;
        $this->_active     = true;

        $this->execute("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
    }


    /*#########################################################################
    # Quoting
    #########################################################################*/

    /**
     * Quotes a string, escaping any special characters.
     *
     * @param   string  $string
     * @return  string
     */
    public function quoteString($string)
    {
        return "'" . str_replace("'", "''", $string) . "'";
    }


    /*#########################################################################
    # Database Statements
    #########################################################################*/

    /**
     * Returns an array of records with the column names as keys, and
     * column values as values.
     *
     * @param string $sql    SQL statement.
     * @param mixed $arg1    Either an array of bound parameters or a query
     *                       name.
     * @param string $arg2   If $arg1 contains bound parameters, the query
     *                       name.
     *
     * @return Horde_Db_Adapter_Oracle_Result
     * @throws Horde_Db_Exception
     */
    public function select($sql, $arg1 = null, $arg2 = null)
    {
        return new Horde_Db_Adapter_Oracle_Result($this, $sql, $arg1, $arg2);
    }

    /**
     * Returns an array of record hashes with the column names as keys and
     * column values as values.
     *
     * @param string $sql   SQL statement.
     * @param mixed $arg1   Either an array of bound parameters or a query
     *                      name.
     * @param string $arg2  If $arg1 contains bound parameters, the query
     *                      name.
     *
     * @return array
     * @throws Horde_Db_Exception
     */
    public function selectAll($sql, $arg1 = null, $arg2 = null)
    {
        $stmt = $this->execute($sql, $arg1, $arg2);
        $result = oci_fetch_all($stmt, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
        if ($result === false) {
            $this->_handleError($stmt, 'selectAll');
        }
        foreach ($rows as &$row) {
            $row = array_change_key_case($row, CASE_LOWER);
        }
        return $rows;
    }

    /**
     * Returns a record hash with the column names as keys and column values
     * as values.
     *
     * @param string $sql   SQL statement.
     * @param mixed $arg1   Either an array of bound parameters or a query
     *                      name.
     * @param string $arg2  If $arg1 contains bound parameters, the query
     *                      name.
     *
     * @return array
     * @throws Horde_Db_Exception
     */
    public function selectOne($sql, $arg1 = null, $arg2 = null)
    {
        if ($row = oci_fetch_assoc($this->execute($sql, $arg1, $arg2))) {
            return array_change_key_case(
                $row,
                CASE_LOWER
            );
        }
    }

    /**
     * Returns a single value from a record
     *
     * @param string $sql   SQL statement.
     * @param mixed $arg1   Either an array of bound parameters or a query
     *                      name.
     * @param string $arg2  If $arg1 contains bound parameters, the query
     *                      name.
     *
     * @return string
     * @throws Horde_Db_Exception
     */
    public function selectValue($sql, $arg1 = null, $arg2 = null)
    {
        $stmt = $this->execute($sql, $arg1, $arg2);
        if (!oci_fetch($stmt)) {
            return;
        }
        if (($result = oci_result($stmt, 1)) === false) {
            $this->_handleError($stmt, 'selectValue');
        }
        return $result;
    }

    /**
     * Returns an array of the values of the first column in a select:
     *   selectValues("SELECT id FROM companies LIMIT 3") => [1,2,3]
     *
     * @param string $sql   SQL statement.
     * @param mixed $arg1   Either an array of bound parameters or a query
     *                      name.
     * @param string $arg2  If $arg1 contains bound parameters, the query
     *                      name.
     *
     * @return array
     * @throws Horde_Db_Exception
     */
    public function selectValues($sql, $arg1 = null, $arg2 = null)
    {
        $stmt = $this->execute($sql, $arg1, $arg2);
        $values = array();
        while (oci_fetch($stmt)) {
            if (($result = oci_result($stmt, 1)) === false) {
                $this->_handleError($stmt, 'selectValues');
            }
            $values[] = $result;
        }
        return $values;
    }

    /**
     * Executes the SQL statement in the context of this connection.
     *
     * @param string $sql   SQL statement.
     * @param mixed $arg1   Either an array of bound parameters or a query
     *                      name.
     * @param string $arg2  If $arg1 contains bound parameters, the query
     *                      name.
     *
     * @return resource
     * @throws Horde_Db_Exception
     */
    public function execute($sql, $arg1 = null, $arg2 = null)
    {
        if (is_array($arg1)) {
            $sql = $this->_replaceParameters($sql, $arg1);
            $name = $arg2;
        } else {
            $name = $arg1;
        }

        $t = new Horde_Support_Timer;
        $t->push();

        $this->_lastQuery = $sql;
        $stmt = @oci_parse($this->_connection, $sql);
        if (!$stmt ||
            !@oci_execute($stmt, $this->_transactionStarted ? OCI_NO_AUTO_COMMIT : OCI_COMMIT_ON_SUCCESS)) {
            $error = oci_error($stmt ?: $this->_connection);
            if ($stmt) {
                oci_free_statement($stmt);
            }
            $this->_logInfo($sql, $name);
            $this->_logError($sql, 'QUERY FAILED: ' . $error['message']);
            throw new Horde_Db_Exception(
                $this->_errorMessage($error),
                $error['code']
            );
        }

        $this->_logInfo($sql, $name, $t->pop());
        $this->_rowCount = oci_num_rows($stmt);

        return $stmt;
    }

    /**
     * Inserts a row into a table.
     *
     * @param string $sql           SQL statement.
     * @param array|string $arg1    Either an array of bound parameters or a
     *                              query name.
     * @param string $arg2          If $arg1 contains bound parameters, the
     *                              query name.
     * @param string $pk            The primary key column.
     * @param integer $idValue      The primary key value. This parameter is
     *                              required if the primary key is inserted
     *                              manually.
     * @param string $sequenceName  The sequence name.
     *
     * @return integer  Last inserted ID.
     * @throws Horde_Db_Exception
     */
    public function insert($sql, $arg1 = null, $arg2 = null, $pk = null,
                           $idValue = null, $sequenceName = null)
    {
        $this->execute($sql, $arg1, $arg2);

        return $idValue
            ? $idValue
            : ($sequenceName
               ? $this->selectOne('SELECT ' . $this->quoteColumnName($sequenceName) . '.currval FROM dual')
               : null);
    }

    /**
     * Begins the transaction (and turns off auto-committing).
     */
    public function beginDbTransaction()
    {
        $this->_transactionStarted++;
    }

    /**
     * Commits the transaction (and turns on auto-committing).
     */
    public function commitDbTransaction()
    {
        $this->_transactionStarted--;
        if (!$this->_transactionStarted) {
            if (!oci_commit($this->_connection)) {
                $this->_handleError($this->_connection, 'commitDbTransaction');
            }
        }
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

        $this->_transactionStarted = 0;

        if (!oci_rollback($this->_connection)) {
            $this->_handleError($this->_connection, 'rollbackDbTransaction');
        }
    }

    /**
     * Appends LIMIT and OFFSET options to a SQL statement.
     *
     * @param string $sql     SQL statement.
     * @param array $options  Hash with 'limit' and (optional) 'offset' values.
     *
     * @return string
     */
    public function addLimitOffset($sql, $options)
    {
        if (isset($options['limit'])) {
            $offset = isset($options['offset']) ? $options['offset'] : 0;
            $limit = $options['limit'] + $offset;
            $sql = "SELECT * FROM ($sql) WHERE ROWNUM <= $limit AND ROWNUM > $offset";
        }
        return $sql;
    }


    /*#########################################################################
    # Protected
    #########################################################################*/

    /**
     * Creates a formatted error message from a oci_error() result hash.
     *
     * @param array $error  Hash returned from oci_error().
     *
     * @return string  The formatted error message.
     */
    protected function _errorMessage($error)
    {
        return 'QUERY FAILED: ' . $error['message']
            . "\n\nat offset " . $error['offset']
            . "\n" . $error['sqltext'];
    }

    /**
     * Log and throws an exception for the last error.
     *
     * @param resource $resource  The resource (connection or statement) to
     *                            call oci_error() upon.
     * @param string $method      The calling method.
     *
     * @throws Horde_Db_Exception
     */
    protected function _handleError($resource, $method)
    {
        $error = oci_error($resource);
        $this->_logError(
            $error['message'],
            'Horde_Db_Adapter_Oci8::' . $method. '()'
        );
        throw new Horde_Db_Exception(
            $this->_errorMessage($error),
            $error['code']
        );
    }
}

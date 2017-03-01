<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2017 Horde LLC (http://www.horde.org/)
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
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */
abstract class Horde_Db_Adapter_Pdo_Base extends Horde_Db_Adapter_Base
{
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

        list($dsn, $user, $pass) = $this->_parseConfig();

        try {
            $pdo = @new PDO($dsn, $user, $pass);
        } catch (PDOException $e) {
            $msg = 'Could not instantiate PDO. PDOException: '
                . $e->getMessage();
            $this->_logError($msg, '');

            $e2 = new Horde_Db_Exception($msg);
            $e2->logged = true;
            throw $e2;
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $this->_connection = $pdo;
        $this->_active     = true;
    }

    /**
     * Check if the connection is active
     *
     * @return  boolean
     */
    public function isActive()
    {
        $this->_lastQuery = $sql = 'SELECT 1';
        try {
            return isset($this->_connection) &&
                $this->_connection->query($sql);
        } catch (PDOException $e) {
            throw new Horde_Db_Exception($e);
        }
    }


    /*##########################################################################
    # Database Statements
    ##########################################################################*/

    /**
     * Returns an array of records with the column names as keys, and
     * column values as values.
     *
     * @param string  $sql   SQL statement.
     * @param mixed $arg1    Either an array of bound parameters or a query
     *                       name.
     * @param string $arg2   If $arg1 contains bound parameters, the query
     *                       name.
     *
     * @return Horde_Db_Adapter_Pdo_Result
     * @throws Horde_Db_Exception
     */
    public function select($sql, $arg1 = null, $arg2 = null)
    {
        return new Horde_Db_Adapter_Pdo_Result($this, $sql, $arg1, $arg2);
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
        $stmt = $this->execute($sql, $arg1, $arg2);
        if (!$stmt) {
            return array();
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Required to really close the connection.
        $stmt = null;
        return $result;
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
        $stmt = $this->execute($sql, $arg1, $arg2);
        if (!$stmt) {
            return array();
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        // Required to really close the connection.
        $stmt = null;
        return $result;
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
        $stmt = $this->execute($sql, $arg1, $arg2);
        if (!$stmt) {
            return null;
        }
        $result = $stmt->fetchColumn(0);
        // Required to really close the connection.
        $stmt = null;
        return $result;
    }

    /**
     * Returns an array of the values of the first column in a select:
     *   selectValues("SELECT id FROM companies LIMIT 3") => [1,2,3]
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     */
    public function selectValues($sql, $arg1=null, $arg2=null)
    {
        $stmt = $this->execute($sql, $arg1, $arg2);
        if (!$stmt) {
            return null;
        }
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        // Required to really close the connection.
        $stmt = null;
        return $result;
    }

    /**
     * Returns an array where the keys are the first column of a select, and the
     * values are the second column:
     *
     *   selectAssoc("SELECT id, name FROM companies LIMIT 3") => [1 => 'Ford', 2 => 'GM', 3 => 'Chrysler']
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     */
    public function selectAssoc($sql, $arg1=null, $arg2=null)
    {
        $stmt = $this->execute($sql, $arg1, $arg2);
        if (!$stmt) {
            return null;
        }
        $result = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        // Required to really close the connection.
        $stmt = null;
        return $result;
    }

    /**
     * Executes the SQL statement in the context of this connection.
     *
     * @deprecated  Deprecated for external usage. Use select() instead.
     *
     * @param string $sql   SQL statement.
     * @param mixed $arg1   Either an array of bound parameters or a query
     *                      name.
     * @param string $arg2  If $arg1 contains bound parameters, the query
     *                      name.
     *
     * @return PDOStatement
     * @throws Horde_Db_Exception
     */
    public function execute($sql, $arg1 = null, $arg2 = null)
    {
        if (is_array($arg1)) {
            $query = $this->_replaceParameters($sql, $arg1);
            $name = $arg2;
        } else {
            $name = $arg1;
            $query = $sql;
            $arg1 = array();
        }

        $t = new Horde_Support_Timer;
        $t->push();

        try {
            $this->_lastQuery = $query;
            $stmt = $this->_connection->query($query);
        } catch (PDOException $e) {
            $this->_logInfo($sql, $arg1, $name);
            $this->_logError($query, 'QUERY FAILED: ' . $e->getMessage());
            throw new Horde_Db_Exception($e);
        }

        $this->_logInfo($sql, $arg1, $name, $t->pop());
        $this->_rowCount = $stmt ? $stmt->rowCount() : 0;

        return $stmt;
    }

    /**
     * Use a PDO prepared statement to execute a query. Used when passing
     * values to insert/update as a stream resource.
     *
     * @param  string $sql           The SQL statement. Includes '?' placeholder
     *     for binding non-stream values. Stream values are bound using a
     *     placeholders named like ':binary0', ':binary1' etc...
     *
     * @param  array $values        An array of non-stream values.
     * @param  array $binary_values An array of stream resources.
     *
     * @throws  Horde_Db_Exception
     */
    protected function _executePrepared($sql, $values, $binary_values)
    {
        $query = $this->_replaceParameters($sql, $values);
        try {
            $stmt = $this->_connection->prepare($query);
            foreach ($binary_values as $key => $bvalue) {
                rewind($bvalue);
                $stmt->bindParam(':binary' . $key, $bvalue, PDO::PARAM_LOB);
            }
        } catch (PDOException $e) {
            $this->_logInfo($sql, $values, null);
            $this->_logError($sql, 'QUERY FAILED: ' . $e->getMessage());
            throw new Horde_Db_Exception($e);
        }

        $t = new Horde_Support_Timer;
        $t->push();

        try {
            $this->_lastQuery = $sql;
            $stmt->execute();
        } catch (PDOException $e) {
            $this->_logInfo($sql, $values, null);
            $this->_logError($sql, 'QUERY FAILED: ' . $e->getMessage());
            throw new Horde_Db_Exception($e);
        }

        $t = new Horde_Support_Timer;
        $t->push();

        $this->_logInfo($sql, $values, null, $t->pop());
        $this->_rowCount = $stmt->rowCount();
    }

    /**
     * Inserts a row including BLOBs into a table.
     *
     * @since Horde_Db 2.4.0
     *
     * @param string $table     The table name.
     * @param array $fields     A hash of column names and values. BLOB/CLOB
     *                          columns must be provided as Horde_Db_Value
     *                          objects.
     * @param string $pk        The primary key column.
     * @param mixed  $idValue   The primary key value. This parameter is
     *                          required if the primary key is inserted
     *                          manually.
     *
     * @return integer  Last inserted ID.
     * @throws Horde_Db_Exception
     */
    public function insertBlob($table, $fields, $pk = null, $idValue = null)
    {
        $placeholders = $values = $binary = array();
        $binary_cnt = 0;
        foreach ($fields as $name => $value) {
            if ($value instanceof Horde_Db_Value_Binary) {
                $placeholders[] = ':binary' . $binary_cnt++;
                $binary[] = $value->stream;
            } else {
                $placeholders[] = '?';
                $values[] = $value;
            }
        }

        $query = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteTableName($table),
            implode(', ', array_map(array($this, 'quoteColumnName'), array_keys($fields))),
            implode(', ', $placeholders)
        );

        if ($binary_cnt > 0) {
            $this->_executePrepared($query, $values, $binary);

            try {
                return $idValue
                    ? $idValue
                    : $this->_connection->lastInsertId(null);
            } catch (PDOException $e) {
                throw new Horde_Db_Exception($e);
            }
        }

        return $this->insert($query, $fields, null, $pk, $idValue);
    }

    /**
     * Updates rows including BLOBs into a table.
     *
     * @since Horde_Db 2.4.0
     *
     * @param string $table        The table name.
     * @param array $fields        A hash of column names and values. BLOB/CLOB
     *                             columns must be provided as
     *                             Horde_Db_Value objects.
     * @param string|array $where  A WHERE clause. Either a complete clause or
     *                             an array containing a clause with
     *                             placeholders and a list of values.
     *
     * @throws Horde_Db_Exception
     */
    public function updateBlob($table, $fields, $where = null)
    {
        if (is_array($where)) {
            $where = $this->_replaceParameters($where[0], $where[1]);
        }

        $values = $binary_values = $fnames = array();
        $binary_cnt = 0;

        foreach ($fields as $field => $value) {
            if ($value instanceof Horde_Db_Value) {
                $fnames[] = $this->quoteColumnName($field) . ' = :binary' . $binary_cnt++;
                $binary_values[] = $value->stream;
            } else {
                $fnames[] = $this->quoteColumnName($field) . ' = ?';
                $values[] = $value;
            }
        }

        $query = sprintf(
            'UPDATE %s SET %s%s',
            $this->quoteTableName($table),
            implode(', ', $fnames),
            strlen($where) ? ' WHERE ' . $where : ''
        );

        if ($binary_cnt > 0) {
            $this->_executePrepared($query, $values, $binary_values);
            return $this->_rowCount;
        }

        return $this->update($query, $fields);
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
     * @param mixed  $idValue       The primary key value. This parameter is
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

        try {
            return $idValue
                ? $idValue
                : $this->_connection->lastInsertId($sequenceName);
        } catch (PDOException $e) {
            throw new Horde_Db_Exception($e);
        }
    }

    /**
     * Begins the transaction (and turns off auto-committing).
     */
    public function beginDbTransaction()
    {
        if (!$this->_transactionStarted) {
            try {
                $this->_connection->beginTransaction();
            } catch (PDOException $e) {
                throw new Horde_Db_Exception($e);
            }
        }
        $this->_transactionStarted++;
    }

    /**
     * Commits the transaction (and turns on auto-committing).
     */
    public function commitDbTransaction()
    {
        $this->_transactionStarted--;
        if (!$this->_transactionStarted) {
            try {
                $this->_connection->commit();
            } catch (PDOException $e) {
                throw new Horde_Db_Exception($e);
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

        try {
            $this->_connection->rollBack();
        } catch (PDOException $e) {
            throw new Horde_Db_Exception($e);
        }
        $this->_transactionStarted = 0;
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
        try {
            return $this->_connection->quote($string);
        } catch (PDOException $e) {
            throw new Horde_Db_Exception($e);
        }
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    protected function _normalizeConfig($params)
    {
        // Normalize config parameters to what PDO expects.
        $normalize = array('database' => 'dbname',
                           'hostspec' => 'host');

        foreach ($normalize as $from => $to) {
            if (isset($params[$from])) {
                $params[$to] = $params[$from];
                unset($params[$from]);
            }
        }

        return $params;
    }

    protected function _buildDsnString($params)
    {
        $dsn = $this->_config['adapter'] . ':';
        foreach ($params as $k => $v) {
            if (strlen($v)) {
                $dsn .= "$k=$v;";
            }
        }
        return rtrim($dsn, ';');
    }

    /**
     * Parse configuration array into options for PDO constructor.
     *
     * @throws  Horde_Db_Exception
     * @return  array  [dsn, username, password]
     */
    protected function _parseConfig()
    {
        $this->_checkRequiredConfig(array('adapter', 'username'));

        // try an empty password if it's not set.
        if (!isset($this->_config['password'])) {
            $this->_config['password'] = '';
        }

        // collect options to build PDO Data Source Name (DSN) string
        $dsnOpts = $this->_config;
        unset(
            $dsnOpts['adapter'],
            $dsnOpts['username'],
            $dsnOpts['password'],
            $dsnOpts['protocol'],
            $dsnOpts['persistent'],
            $dsnOpts['charset'],
            $dsnOpts['phptype'],
            $dsnOpts['socket']
        );

        // return DSN and user/pass for connection
        return array(
            $this->_buildDsnString($this->_normalizeConfig($dsnOpts)),
            $this->_config['username'],
            $this->_config['password']);
    }
}

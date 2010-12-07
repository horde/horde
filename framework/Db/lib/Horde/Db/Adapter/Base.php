<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */
abstract class Horde_Db_Adapter_Base implements Horde_Db_Adapter
{
    /**
     * Config options.
     *
     * @var array
     */
    protected $_config = array();

    /**
     * DB connection.
     *
     * @var mixed
     */
    protected $_connection = null;

    /**
     * Has a transaction been started?
     *
     * @var boolean
     */
    protected $_transactionStarted = false;

    /**
     * Row count of last action.
     *
     * @var integer
     */
    protected $_rowCount = null;

    /**
     * Runtime of last query.
     *
     * @var integer
     */
    protected $_runtime;

    /**
     * Is connection active?
     *
     * @var boolean
     */
    protected $_active = null;

    /**
     * Cache object.
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * Log object.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Schema object.
     *
     * @var Horde_Db_Adapter_Base_Schema
     */
    protected $_schema = null;

    /**
     * Schema class to use.
     *
     * @var string
     */
    protected $_schemaClass = null;

    /**
     * List of schema methods.
     *
     * @var array
     */
    protected $_schemaMethods = array();


    /*##########################################################################
    # Construct/Destruct
    ##########################################################################*/

    /**
     * Constructor.
     *
     * @param array $config  Configuration options and optional objects.
     */
    public function __construct($config)
    {
        /* Can't set cache/logger in constructor - these objects may use DB
         * for storage. Add stubs for now - they have to be manually set
         * later with setCache() and setLogger(). */
        $this->_cache = new Horde_Support_Stub();
        $this->_logger = new Horde_Support_Stub();

        $this->_config  = $config;
        $this->_runtime = 0;

        if (!$this->_schemaClass) {
            $this->_schemaClass = __CLASS__ . '_Schema';
        }

        $this->connect();
    }

    /**
     * Free any resources that are open.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Serialize callback.
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_class_vars(__CLASS__)), array('_active', '_connection'));
    }

    /**
     * Unserialize callback.
     */
    public function __wakeup()
    {
        $this->_schema->setAdapter($this);
        $this->connect();
    }

    /**
     * Returns an adaptor option set through the constructor.
     *
     * @param string $option  The option to return.
     *
     * @return mixed  The option value or null if option doesn't exist or is
     *                not set.
     */
    public function getOption($option)
    {
        return isset($this->_config[$option]) ? $this->_config[$option] : null;
    }

    /*##########################################################################
    # Dependency setters/getters
    ##########################################################################*/

    /**
     * Set a cache object.
     *
     * @inject
     *
     * @var Horde_Cache $logger  The cache object.
     */
    public function setCache(Horde_Cache $cache)
    {
        $this->_cache = $cache;
    }

    /**
     * @return Horde_Cache
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * Set a logger object.
     *
     * @inject
     *
     * @var Horde_Log_Logger $logger  The logger object.
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * return Horde_Log_Logger
     */
    public function getLogger()
    {
        return $this->_logger;
    }


    /*##########################################################################
    # Object composition
    ##########################################################################*/

    /**
     * Delegate calls to the schema object.
     *
     * @param  string  $method
     * @param  array   $args
     *
     * @return mixed  TODO
     * @throws BadMethodCallException
     */
    public function __call($method, $args)
    {
        if (!$this->_schema) {
            // Create the database-specific (but not adapter specific) schema
            // object.
            $this->_schema = new $this->_schemaClass($this, array(
                'cache' => $this->_cache,
                'logger' => $this->_logger
            ));
            $this->_schemaMethods = array_flip(get_class_methods($this->_schema));
        }

        if (isset($this->_schemaMethods[$method])) {
            return call_user_func_array(array($this->_schema, $method), $args);
        }

        $support = new Horde_Support_Backtrace();
        $context = $support->getContext(2);
        $caller = $context['function'];
        if (isset($context['class'])) {
            $caller = $context['class'] . '::' . $caller;
        }
        throw new BadMethodCallException('Call to undeclared method "' . get_class($this) . '::' . $method . '" from "' . $caller . '"');
    }


    /*##########################################################################
    # Public
    ##########################################################################*/

    /**
     * Returns the human-readable name of the adapter.  Use mixed case - one
     * can always use downcase if needed.
     *
     * @return string
     */
    public function adapterName()
    {
        return 'Base';
    }

    /**
     * Does this adapter support migrations?  Backend specific, as the
     * abstract adapter always returns +false+.
     *
     * @return boolean
     */
    public function supportsMigrations()
    {
        return false;
    }

    /**
     * Does this adapter support using DISTINCT within COUNT?  This is +true+
     * for all adapters except sqlite.
     *
     * @return boolean
     */
    public function supportsCountDistinct()
    {
        return true;
    }

    /**
     * Should primary key values be selected from their corresponding
     * sequence before the insert statement?  If true, next_sequence_value
     * is called before each insert to set the record's primary key.
     * This is false for all adapters but Firebird.
     *
     * @return boolean
     */
    public function prefetchPrimaryKey($tableName = null)
    {
        return false;
    }

    /**
     * Reset the timer
     *
     * @return integer
     */
    public function resetRuntime()
    {
        $runtime = $this->_runtime;
        $this->_runtime = 0;

        return $this->_runtime;
    }


    /*##########################################################################
    # Connection Management
    ##########################################################################*/

    /**
     * Is the connection active?
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->_active;
    }

    /**
     * Reconnect to the db.
     */
    public function reconnect()
    {
        $this->disconnect();
        $this->connect();
    }

    /**
     * Disconnect from db.
     */
    public function disconnect()
    {
        $this->_connection = null;
        $this->_active = false;
    }

    /**
     * Provides access to the underlying database connection. Useful for when
     * you need to call a proprietary method such as postgresql's
     * lo_* methods.
     *
     * @return resource
     */
    public function rawConnection()
    {
        return $this->_connection;
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
     * @return PDOStatement
     * @throws Horde_Db_Exception
     */
    public function select($sql, $arg1 = null, $arg2 = null)
    {
        return $this->execute($sql, $arg1, $arg2);
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
        $rows = array();
        $result = $this->select($sql, $arg1, $arg2);
        if ($result) {
            foreach ($result as $row) {
                $rows[] = $row;
            }
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
        $result = $this->selectAll($sql, $arg1, $arg2);
        return $result
            ? next($result)
            : array();
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
        $result = $this->selectOne($sql, $arg1, $arg2);

        return $result
            ? next($result)
            : null;
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
        $result = $this->selectAll($sql, $arg1, $arg2);
        $values = array();
        foreach ($result as $row) {
            $values[] = next($row);
        }
        return $values;
    }

    /**
     * Returns an array where the keys are the first column of a select, and the
     * values are the second column:
     *
     *   selectAssoc("SELECT id, name FROM companies LIMIT 3") => [1 => 'Ford', 2 => 'GM', 3 => 'Chrysler']
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
    public function selectAssoc($sql, $arg1 = null, $arg2 = null)
    {
        $result = $this->selectAll($sql, $arg1, $arg2);
        $values = array();
        foreach ($result as $row) {
            $values[next($row)] = next($row);
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
     * @return PDOStatement
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

        try {
            $stmt = $this->_connection->query($sql);
        } catch (Exception $e) {
            $this->_logError($sql, 'QUERY FAILED: ' . $e->getMessage());
            $this->_logInfo($sql, $name);
            throw new Horde_Db_Exception((string)$e->getMessage(), (int)$e->getCode());
        }

        $this->_logInfo($sql, $name, $t->pop());
        $this->_rowCount = $stmt ? $stmt->rowCount() : 0;

        return $stmt;
    }

    /**
     * Returns the last auto-generated ID from the affected table.
     *
     * @param string $sql           SQL statement.
     * @param mixed $arg1           Either an array of bound parameters or a
     *                              query name.
     * @param string $arg2          If $arg1 contains bound parameters, the
     *                              query name.
     * @param string $pk            TODO
     * @param integer $idValue      TODO
     * @param string $sequenceName  TODO
     *
     * @return integer  Last inserted ID.
     * @throws Horde_Db_Exception
     */
    public function insert($sql, $arg1 = null, $arg2 = null, $pk = null,
                           $idValue = null, $sequenceName = null)
    {
        $this->execute($sql, $arg1, $arg2);

        return isset($idValue)
            ? $idValue
            : $this->_connection->lastInsertId();
    }

    /**
     * Executes the update statement and returns the number of rows affected.
     *
     * @param string $sql   SQL statement.
     * @param mixed $arg1   Either an array of bound parameters or a query
     *                      name.
     * @param string $arg2  If $arg1 contains bound parameters, the query
     *                      name.
     *
     * @return integer  Number of rows affected.
     * @throws Horde_Db_Exception
     */
    public function update($sql, $arg1 = null, $arg2 = null)
    {
        $this->execute($sql, $arg1, $arg2);
        return $this->_rowCount;
    }

    /**
     * Executes the delete statement and returns the number of rows affected.
     *
     * @param string $sql   SQL statement.
     * @param mixed $arg1   Either an array of bound parameters or a query
     *                      name.
     * @param string $arg2  If $arg1 contains bound parameters, the query
     *                      name.
     *
     * @return integer  Number of rows affected.
     * @throws Horde_Db_Exception
     */
    public function delete($sql, $arg1 = null, $arg2 = null)
    {
        $this->execute($sql, $arg1, $arg2);
        return $this->_rowCount;
    }

    /**
     * Check if a transaction has been started.
     *
     * @return boolean  True if transaction has been started.
     */
    public function transactionStarted()
    {
        return $this->_transactionStarted;
    }

    /**
     * Begins the transaction (and turns off auto-committing).
     */
    public function beginDbTransaction()
    {
        $this->_transactionStarted = true;
        $this->_connection->beginTransaction();
    }

    /**
     * Commits the transaction (and turns on auto-committing).
     */
    public function commitDbTransaction()
    {
        $this->_connection->commit();
        $this->_transactionStarted = false;
    }

    /**
     * Rolls back the transaction (and turns on auto-committing). Must be
     * done if the transaction block raises an exception or returns false.
     */
    public function rollbackDbTransaction()
    {
        if (! $this->_transactionStarted) { return; }

        $this->_connection->rollBack();
        $this->_transactionStarted = false;
    }

    /**
     * Appends +LIMIT+ and +OFFSET+ options to a SQL statement.
     *
     * @param string $sql     SQL statement.
     * @param array $options  TODO
     *
     * @return string
     */
    public function addLimitOffset($sql, $options)
    {
        if (isset($options['limit']) && $limit = $options['limit']) {
            if (isset($options['offset']) && $offset = $options['offset']) {
                $sql .= " LIMIT $offset, $limit";
            } else {
                $sql .= " LIMIT $limit";
            }
        }
        return $sql;
    }

    /**
     * TODO
     */
    public function sanitizeLimit($limit)
    {
        return (strpos($limit, ',') !== false)
            ? implode(',', array_map('intval', explode(',', $limit)))
            : intval($limit);
    }

    /**
     * Appends a locking clause to an SQL statement.
     * This method *modifies* the +sql+ parameter.
     *
     *   # SELECT * FROM suppliers FOR UPDATE
     *   add_lock! 'SELECT * FROM suppliers', :lock => true
     *   add_lock! 'SELECT * FROM suppliers', :lock => ' FOR UPDATE'
     *
     * @param string &$sql    SQL statment.
     * @param array $options  TODO.
     */
    public function addLock(&$sql, array $options = array())
    {
        $sql .= (isset($options['lock']) && is_string($options['lock']))
            ? ' ' . $lock
            : ' FOR UPDATE';
    }

    /**
     * Inserts the given fixture into the table. Overridden in adapters that
     * require something beyond a simple insert (eg. Oracle).
     *
     * @param TODO $fixture    TODO
     * @param TODO $tableName  TODO
     *
     * @return  TODO
     */
    public function insertFixture($fixture, $tableName)
    {
        /*@TODO*/
        return $this->execute("INSERT INTO #{quote_table_name(table_name)} (#{fixture.key_list}) VALUES (#{fixture.value_list})", 'Fixture Insert');
    }

    /**
     * TODO
     *
     * @param string $tableName  TODO
     *
     * @return string  TODO
     */
    public function emptyInsertStatement($tableName)
    {
        return 'INSERT INTO ' . $this->quoteTableName($tableName) . ' VALUES(DEFAULT)';
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * Replace ? in a SQL statement with quoted values from $args
     *
     * @param string $sql  SQL statement.
     * @param array $args  TODO
     *
     * @return string  Modified SQL statement.
     * @throws Horde_Db_Exception
     */
    protected function _replaceParameters($sql, $args)
    {
        $paramCount = substr_count($sql, '?');
        if (count($args) != $paramCount) {
            throw new Horde_Db_Exception('Parameter count mismatch');
        }

        $sqlPieces = explode('?', $sql);
        $sql = array_shift($sqlPieces);
        while (count($sqlPieces)) {
            $sql .= $this->quote(array_shift($args)) . array_shift($sqlPieces);
        }
        return $sql;
    }

    /**
     * Logs the SQL query for debugging.
     *
     * @param string $sql     SQL statement.
     * @param string $name    TODO
     * @param float $runtime  Runtime interval.
     */
    protected function _logInfo($sql, $name, $runtime = null)
    {
        /*@TODO */
        $name = (empty($name) ? '' : $name)
              . (empty($runtime) ? '' : sprintf(" (%.4fs)", $runtime));
        $this->_logger->debug($this->_formatLogEntry($name, $sql));
    }

    protected function _logError($error, $name, $runtime = null)
    {
        /*@TODO */
        $name = (empty($name) ? '' : $name)
              . (empty($runtime) ? '' : sprintf(" (%.4fs)", $runtime));
        $this->_logger->err($this->_formatLogEntry($name, $sql));
    }

    /**
     * Formats the log entry.
     *
     * @param string $message  Message.
     * @param string $sql      SQL statment.
     *
     * @return string  Formatted log entry.
     */
    protected function _formatLogEntry($message, $sql)
    {
        return "SQL $message  \n\t" . wordwrap(preg_replace("/\s+/", ' ', $sql), 70, "\n\t  ", 1);
    }
}

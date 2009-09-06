<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
abstract class Horde_Db_Adapter_Base
{
    /**
     * Config options
     * @var array
     */
    protected $_config = array();

    /**
     * @var mixed
     */
    protected $_connection = null;

    /**
     * @var boolean
     */
    protected $_transactionStarted = false;

    /**
     * @var int
     */
    protected $_rowCount = null;

    /**
     * @var int
     */
    protected $_runtime = null;

    /**
     * @var boolean
     */
    protected $_active = null;

    /**
     * @var Cache object
     */
    protected $_cache = null;

    /**
     * @var Logger
     */
    protected $_logger = null;

    /**
     * @var Horde_Db_Adapter_Abstract_Schema
     */
    protected $_schema = null;

    /**
     * @var string
     */
    protected $_schemaClass = null;

    /**
     * @var array
     */
    protected $_schemaMethods = array();


    /*##########################################################################
    # Construct/Destruct
    ##########################################################################*/

    /**
     * @param array $config Configuration options and optional objects (logger,
     * cache, etc.)
     */
    public function __construct($config)
    {
        // Create a stub if we don't have a useable cache.
        if (isset($config['cache'])
            && is_callable(array($config['cache'], 'get'))
            && is_callable(array($config['cache'], 'set'))) {
            $this->_cache = $config['cache'];
            unset($config['cache']);
        } else {
            $this->_cache = new Horde_Support_Stub;
        }

        // Create a stub if we don't have a useable logger.
        if (isset($config['logger'])
            && is_callable(array($config['logger'], 'log'))) {
            $this->_logger = $config['logger'];
            unset($config['logger']);
        } else {
            $this->_logger = new Horde_Support_Stub;
        }

        // Default to UTF-8
        if (!isset($config['charset'])) {
            $config['charset'] = 'UTF-8';
        }

        $this->_config  = $config;
        $this->_runtime = 0;

        // Create the database-specific (but not adapter specific) schema
        // object.
        if (!$this->_schemaClass)
            $this->_schemaClass = get_class($this).'_Schema';
        $this->_schema = new $this->_schemaClass($this, array(
            'cache' => $this->_cache,
            'logger' => $this->_logger));
        $this->_schemaMethods = array_flip(get_class_methods($this->_schema));

        $this->connect();
    }

    /**
     * Free any resources that are open.
     */
    public function __destruct()
    {
        $this->disconnect();
    }


    /*##########################################################################
    # Object factory
    ##########################################################################*/

    /**
     * Delegate calls to the schema object.
     *
     * @param  string  $method
     * @param  array   $args
     */
    public function componentFactory($component, $args)
    {
        $class = str_replace('_Schema', '', $this->_schemaClass) . '_' . $component;
        if (class_exists($class)) {
            $class = new ReflectionClass($class);
        } else {
            $class = new ReflectionClass('Horde_Db_Adapter_Abstract_' . $component);
        }

        return $class->newInstanceArgs($args);
    }


    /*##########################################################################
    # Object composition
    ##########################################################################*/

    /**
     * Delegate calls to the schema object.
     *
     * @param  string  $method
     * @param  array   $args
     */
    public function __call($method, $args)
    {
        if (isset($this->_schemaMethods[$method])) {
            return call_user_func_array(array($this->_schema, $method), $args);
        }

        throw new BadMethodCallException('Call to undeclared method "'.$method.'"');
    }


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
        return 'Abstract';
    }

    /**
     * Does this adapter support migrations?  Backend specific, as the
     * abstract adapter always returns +false+.
     *
     * @return  boolean
     */
    public function supportsMigrations()
    {
        return false;
    }

    /**
     * Does this adapter support using DISTINCT within COUNT?  This is +true+
     * for all adapters except sqlite.
     *
     * @return  boolean
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
     */
    public function prefetchPrimaryKey($tableName = null)
    {
        return false;
    }

    /**
     * Reset the timer
     *
     * @return  int
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
     * Connect to the db
     */
    abstract public function connect();

    /**
     * Is the connection active
     *
     * @return  boolean
     */
    public function isActive()
    {
        return $this->_active;
    }

    /**
     * Reconnect to the db
     */
    public function reconnect()
    {
        $this->disconnect();
        $this->connect();
    }

    /**
     * Disconnect from db
     */
    public function disconnect()
    {
        $this->_connection = null;
        $this->_active = false;
    }

    /**
     * Provides access to the underlying database connection. Useful for when
     * you need to call a proprietary method such as postgresql's lo_* methods
     *
     * @return  resource
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
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     * @return  Traversable
     */
    public function select($sql, $arg1 = null, $arg2 = null)
    {
        return $this->execute($sql, $arg1, $arg2);
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
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     * @return  array
     */
    public function selectOne($sql, $arg1 = null, $arg2 = null)
    {
        $result = $this->selectAll($sql, $arg1, $arg2);
        return $result ? next($result) : array();
    }

    /**
     * Returns a single value from a record
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     * @return  string
     */
    public function selectValue($sql, $arg1 = null, $arg2 = null)
    {
        $result = $this->selectOne($sql, $arg1, $arg2);
        return $result ? next($result) : null;
    }

    /**
     * Returns an array of the values of the first column in a select:
     *   selectValues("SELECT id FROM companies LIMIT 3") => [1,2,3]
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
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
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
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
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
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
            $this->_logInfo($sql, 'QUERY FAILED: ' . $e->getMessage());
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
        return isset($idValue) ? $idValue : $this->_connection->lastInsertId();
    }

    /**
     * Executes the update statement and returns the number of rows affected.
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     */
    public function update($sql, $arg1 = null, $arg2 = null)
    {
        $this->execute($sql, $arg1, $arg2);
        return $this->_rowCount;
    }

    /**
     * Executes the delete statement and returns the number of rows affected.
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     */
    public function delete($sql, $arg1 = null, $arg2 = null)
    {
        $this->execute($sql, $arg1, $arg2);
        return $this->_rowCount;
    }

    /**
     * Check if a transaction has been started
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
     * @param   string  $sql
     * @param   array   $options
     * @return  string
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

    public function sanitizeLimit($limit)
    {
        if (strpos($limit, ',') !== false) {
            return implode(',', array_map(create_function('$i', 'return (int)$i;'), explode(',', $limit)));
        } else return (int)$limit;
    }

    /**
     * Appends a locking clause to an SQL statement.
     * This method *modifies* the +sql+ parameter.
     *   # SELECT * FROM suppliers FOR UPDATE
     *   add_lock! 'SELECT * FROM suppliers', :lock => true
     *   add_lock! 'SELECT * FROM suppliers', :lock => ' FOR UPDATE'
     */
    public function addLock(&$sql, $options = array())
    {
        if (isset($options['lock']) && is_string($options['lock'])) {
            $sql .= ' ' . $lock;
        } else {
            $sql .= ' FOR UPDATE';
        }
    }

    /**
     * Inserts the given fixture into the table. Overridden in adapters that
     * require something beyond a simple insert (eg. Oracle).
     */
    public function insertFixture($fixture, $tableName)
    {
        /*@TODO*/
        return $this->execute("INSERT INTO #{quote_table_name(table_name)} (#{fixture.key_list}) VALUES (#{fixture.value_list})", 'Fixture Insert');
    }

    public function emptyInsertStatement($tableName)
    {
        return 'INSERT INTO '.$this->quoteTableName($tableName).' VALUES(DEFAULT)';
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * Replace ? in a SQL statement with quoted values from $args
     *
     * @param   string  $sql
     * @param   array   $args
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
     * @param   string  $sql
     * @param   string  $name
     * @param   float   $runtime
     */
    protected function _logInfo($sql, $name, $runtime = null)
    {
        /*@TODO */
        $name = (empty($name) ? '' : $name)
            . (empty($runtime) ? '' : " ($runtime ms)");
        $this->_logger->info($this->_formatLogEntry($name, $sql));
    }

    /**
     * Formats the log entry.
     *
     * @param   string  $message
     * @param   string  $sql
     */
    protected function _formatLogEntry($message, $sql)
    {
        $sql = preg_replace("/\s+/", ' ', $sql);
        $sql = "\n\t".wordwrap($sql, 70, "\n\t  ", 1);
        return "SQL $message  $sql";
    }

}

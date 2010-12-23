<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Michael J. Rubinsky <mrubinsk@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */

/**
 * The Horde_Db_Adapter_SplitRead:: class wraps two individual adapters to
 * provide support for split read/write database setups.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Michael J. Rubinsky <mrubinsk@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_SplitRead implements Horde_Db_Adapter
{
    /**
     * The read adapter
     *
     * @var Horde_Db_Adapter
     */
    private $_read;
    
    /**
     * The write adapter
     * 
     * @var Horde_Db_Adapter
     */
    private $_write;

    /**
     * Const'r
     *
     * @param Horde_Db_Adapter $read
     * @param Horde_Db_Adapter $write
     */
    public function __construct(Horde_Db_Adapter $read, Horde_Db_Adapter $write)
    {
        $this->_read = $read;
        $this->_write = $write;
    }

    /**
     * Delegate unknown methods to the _write adapter.
     *
     * @param string $method
     * @param array $args
     */
    public function __call($method, $args)
    {
        $result = call_user_func_array(array($this->_write, $method), $args);
        $this->last_query = $this->_write->last_query;
        return $result;
    }

    /**
     * Returns the human-readable name of the adapter.  Use mixed case - one
     * can always use downcase if needed.
     *
     * @return string
     */
    public function adapterName()
    {
        return 'SplitRead';
    }

    /**
     * Does this adapter support migrations? 
     *
     * @return boolean
     */
    public function supportsMigrations()
    {
        return $this->_write->supportsMigrations();
    }

    /**
     * Does this adapter support using DISTINCT within COUNT?  This is +true+
     * for all adapters except sqlite.
     *
     * @return boolean
     */
    public function supportsCountDistinct()
    {
        return $this->_read->supportsCountDistinct();
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
        return $this->_write->prefetchPrimaryKey($tableName);
    }

    /*##########################################################################
    # Connection Management
    ##########################################################################*/

    /**
     * Connect to the db.
     * @TODO: Lazy connect?
     *
     */
    public function connect()
    {
        $this->_write->connect();
        $this->_read->connect();
    }

    /**
     * Is the connection active?
     *
     * @return boolean
     */
    public function isActive()
    {
        return ($this->_read->isActive() && $this->_write->isActive());
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
        $this->_read->disconnect();
        $this->_write->disconnect();
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
        return $this->_write->rawConnection();
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
        $result = $this->_read->select($sql, $arg1, $arg2);
        $this->last_query = $this->_read->last_query;
        return $result;
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
        $result = $this->_read->selectAll($sql, $arg1, $arg2);
        $this->last_query = $this->_read->last_query;
        return $result;
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
        $result = $this->_read->selectOne($sql, $arg1, $arg2);
        $this->last_query = $this->_read->last_query;
        return $result;
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
        $result = $this->_read->selectValue($sql, $arg1, $arg2);
        $this->last_query = $this->_read->last_query;
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
        $result = $this->_read->selectValues($sql, $arg1, $arg2);
        $this->last_query = $this->_read->last_query;
        return $result;
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
        $result = $this->_read->selectAssoc($sql, $arg1, $arg2);
        $this->last_query = $this->_read->last_query;
        return $result;
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
        // Can't assume this will always be a read action, use _write.
        $result = $this->_write->execute($sql, $arg1, $arg2);
        $this->last_query = $this->_write->last_query;
        return $result;
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
        $result = $this->_write->insert($sql, $arg1, $arg2, $pk, $idValue, $sequenceName);
        $this->last_query = $this->_write->last_query;
        return $result;
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
        $result = $this->_write->update($sql, $arg1, $arg2);
        $this->last_query = $this->_write->last_query;
        return $result;
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
        $result = $this->_write->delete($sql, $arg1, $arg2);
        $this->last_query = $this->_write->last_query;
        return $result;
    }

    /**
     * Check if a transaction has been started.
     *
     * @return boolean  True if transaction has been started.
     */
    public function transactionStarted()
    {
        $result = $this->_write->transactionStarted();
        $this->last_query = $this->_write->last_query;
        return $result;
    }
    /**
     * Begins the transaction (and turns off auto-committing).
     */
    public function beginDbTransaction()
    {
        $result = $this->_write->beginDbTransaction();
        $this->last_query = $this->_write->last_query;
        return $result;
    }

    /**
     * Commits the transaction (and turns on auto-committing).
     */
    public function commitDbTransaction()
    {
        $result = $this->_write->commitDbTransaction();
        $this->last_query = $this->_write->last_query;
        return $result;
    }

    /**
     * Rolls back the transaction (and turns on auto-committing). Must be
     * done if the transaction block raises an exception or returns false.
     */
    public function rollbackDbTransaction()
    {
        $result = $this->_write->rollbackDbTransaction();
        $this->last_query = $this->_write->last_query;
        return $result;
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
        $result = $this->_read->addLimitOffset($sql, $options);
        $this->last_query = $this->_write->last_query;
        return $result;
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
        $this->_write->addLock($sql, $options);
        $this->last_query = $this->_write->last_query;
    }
}

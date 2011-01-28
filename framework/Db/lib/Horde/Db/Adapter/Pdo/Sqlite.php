<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
 * PDO_SQLite Horde_Db_Adapter
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Pdo_Sqlite extends Horde_Db_Adapter_Pdo_Base
{
    /**
     * @var string
     */
    protected $_schemaClass = 'Horde_Db_Adapter_Sqlite_Schema';

    /**
     * SQLite version number
     * @var integer
     */
    protected $_sqliteVersion;

    /**
     * @return  string
     */
    public function adapterName()
    {
        return 'PDO_SQLite';
    }

    /**
     * @return  boolean
     */
    public function supportsMigrations()
    {
        return true;
    }

    /**
     * Does this adapter support using DISTINCT within COUNT?  This is +true+
     * for all adapters except sqlite.
     *
     * @return  boolean
     */
    public function supportsCountDistinct()
    {
        return $this->_sqliteVersion >= '3.2.6';
    }

    public function supportsAutoIncrement()
    {
        return $this->_sqliteVersion >= '3.1.0';
    }


    /*##########################################################################
    # Connection Management
    ##########################################################################*/

    /**
     * Connect to the db.
     *
     * @throws Horde_Db_Exception
     */
    public function connect()
    {
        if ($this->_active) {
            return;
        }

        parent::connect();

        $this->_connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $this->last_query = 'PRAGMA full_column_names=0';
        $retval = $this->_connection->exec('PRAGMA full_column_names=0');
        if ($retval === false) {
            $error = $this->_connection->errorInfo();
            throw new Horde_Db_Exception($error[2]);
        }

        $this->last_query = 'PRAGMA short_column_names=1';
        $retval = $this->_connection->exec('PRAGMA short_column_names=1');
        if ($retval === false) {
            $error = $this->_connection->errorInfo();
            throw new Horde_Db_Exception($error[2]);
        }

        $this->last_query = 'SELECT sqlite_version(*)';
        $this->_sqliteVersion = $this->selectValue('SELECT sqlite_version(*)');
    }


    /*##########################################################################
    # Database Statements
    ##########################################################################*/

    /**
     * Executes the SQL statement in the context of this connection.
     *
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     */
    public function execute($sql, $arg1=null, $arg2=null)
    {
        return $this->_catchSchemaChanges('execute', array($sql, $arg1, $arg2));
    }

    /**
     * Begins the transaction (and turns off auto-committing).
     */
    public function beginDbTransaction()
    {
        return $this->_catchSchemaChanges('beginDbTransaction');
    }

    /**
     * Commits the transaction (and turns on auto-committing).
     */
    public function commitDbTransaction()
    {
        return $this->_catchSchemaChanges('commitDbTransaction');
    }

    /**
     * Rolls back the transaction (and turns on auto-committing). Must be
     * done if the transaction block raises an exception or returns false.
     */
    public function rollbackDbTransaction()
    {
        return $this->_catchSchemaChanges('rollbackDbTransaction');
    }

    /**
     * SELECT ... FOR UPDATE is redundant since the table is locked.
     */
    public function addLock(&$sql, array $options = array())
    {
    }

    public function emptyInsertStatement($tableName)
    {
        return 'INSERT INTO '.$this->quoteTableName($tableName).' VALUES(NULL)';
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    protected function _catchSchemaChanges($method, $args = array())
    {
        try {
            return call_user_func_array(array($this, "parent::$method"), $args);
        } catch (Exception $e) {
            if (preg_match('/database schema has changed/i', $e->getMessage())) {
                $this->reconnect();
                return call_user_func_array(array($this, "parent::$method"), $args);
            } else {
                throw $e;
            }
        }
    }

    protected function _buildDsnString($params)
    {
        return 'sqlite:' . $params['dbname'];
    }

    /**
     * Parse configuration array into options for PDO constructor
     *
     * @throws  Horde_Db_Exception
     * @return  array  [dsn, username, password]
     */
    protected function _parseConfig()
    {
        // check required config keys are present
        if (empty($this->_config['database']) && empty($this->_config['dbname'])) {
            $msg = 'Either dbname or database is required';
            throw new Horde_Db_Exception($msg);
        }

        // collect options to build PDO Data Source Name (DSN) string
        $dsnOpts = $this->_config;
        unset($dsnOpts['adapter'], $dsnOpts['username'], $dsnOpts['password']);

        // return DSN and dummy user/pass for connection
        return array($this->_buildDsnString($this->_normalizeConfig($dsnOpts)), '', '');
    }

}

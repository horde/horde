<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
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
            $msg = "Could not instantiate PDO with DSN \"$dsn\".  PDOException: "
                . $e->getMessage();
            throw new Horde_Db_Exception($msg);
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
        $this->last_query = 'SELECT 1';
        return isset($this->_connection) &&
               $this->_connection->query('SELECT 1');
    }


    /*##########################################################################
    # Database Statements
    ##########################################################################*/

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
        return $result ? $result->fetchAll(PDO::FETCH_ASSOC) : array();
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
        return $result ? $result->fetch(PDO::FETCH_ASSOC) : array();
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
        $result = $this->execute($sql, $arg1, $arg2);
        return $result ? $result->fetchColumn(0) : null;
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
        $result = $this->execute($sql, $arg1, $arg2);
        return $result ? $result->fetchAll(PDO::FETCH_COLUMN, 0) : array();
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
        // PDO::FETCH_KEY_PAIR is only available since PHP 5.2.3
        if (version_compare(PHP_VERSION, '5.2.3') < 0) {
            return parent::selectAssoc($sql, $arg1, $arg2);
        }
        $result = $this->execute($sql, $arg1, $arg2);
        return $result ? $result->fetchAll(PDO::FETCH_KEY_PAIR) : array();
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
        return $this->_connection->quote($string);
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    protected function _checkRequiredConfig()
    {
        // check required config keys are present
        $required = array('adapter', 'username');
        $diff = array_diff_key(array_flip($required), $this->_config);
        if (! empty($diff)) {
            $msg = 'Required config missing: ' . implode(', ', array_keys($diff));
            throw new Horde_Db_Exception($msg);
        }

        // try an empty password if it's not set.
        if (!isset($this->_config['password'])) {
            $this->_config['password'] = '';
        }
    }

    protected function _normalizeConfig($params)
    {
        // normalize config parameters to what PDO expects
        $normalize = array('database' => 'dbname', 'socket' => 'unix_socket', 'hostspec' => 'host');
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
            $dsn .= "$k=$v;";
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
        $this->_checkRequiredConfig();

        // collect options to build PDO Data Source Name (DSN) string
        $dsnOpts = $this->_config;
        unset(
            $dsnOpts['adapter'],
            $dsnOpts['username'],
            $dsnOpts['password'],
            $dsnOpts['protocol'],
            $dsnOpts['persistent'],
            $dsnOpts['charset'],
            $dsnOpts['phptype']
        );

        // return DSN and user/pass for connection
        return array(
            $this->_buildDsnString($this->_normalizeConfig($dsnOpts)),
            $this->_config['username'],
            $this->_config['password']);
    }
}

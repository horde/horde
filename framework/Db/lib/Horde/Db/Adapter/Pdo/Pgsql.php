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
 * PDO_PostgreSQL Horde_Db_Adapter
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Pdo_Pgsql extends Horde_Db_Adapter_Pdo_Base
{
    /**
     * @var string
     */
    protected $_schemaClass = 'Horde_Db_Adapter_Postgresql_Schema';

    /**
     * @return  string
     */
    public function adapterName()
    {
        return 'PDO_PostgreSQL';
    }

    /**
     * @return  boolean
     */
    public function supportsMigrations()
    {
        return true;
    }

    /**
     * Does PostgreSQL support standard conforming strings?
     * @return  boolean
     */
    public function supportsStandardConformingStrings()
    {
        // Temporarily set the client message level above error to prevent unintentional
        // error messages in the logs when working on a PostgreSQL database server that
        // does not support standard conforming strings.
        $clientMinMessageOld = $this->getClientMinMessages();
        $this->setClientMinMessages('panic');

        $hasSupport = $this->selectValue('SHOW standard_conforming_strings');

        $this->setClientMinMessages($clientMinMessagesOld);
        return $hasSupport;
    }

    public function supportsInsertWithReturning()
    {
        return $this->postgresqlVersion() >= 80200;
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

        $retval = $this->_connection->exec("SET datestyle TO 'iso'");
        if ($retval === false) {
            $error = $this->_connection->errorInfo();
            throw new Horde_Db_Exception($error[2]);
        }

        // Money type has a fixed precision of 10 in PostgreSQL 8.2 and below, and as of
        // PostgreSQL 8.3 it has a fixed precision of 19. PostgreSQLColumn.extract_precision
        // should know about this but can't detect it there, so deal with it here.
        Horde_Db_Adapter_Postgresql_Column::$moneyPrecision = ($this->postgresqlVersion() >= 80300) ? 19 : 10;

        $this->_configureConnection();
    }


    /*##########################################################################
    # Quoting
    ##########################################################################*/

    /**
     * @return  string
     */
    public function quotedStringPrefix()
    {
        // Use escape string syntax if available. We cannot do this lazily when encountering
        // the first string, because that could then break any transactions in progress.
        // See: http://www.postgresql.org/docs/current/static/runtime-config-compatible.html
        // If PostgreSQL doesn't know the standard_conforming_strings parameter then it doesn't
        // support escape string syntax. Don't override the inherited quotedStringPrefix.
        if ($this->supportsStandardConformingStrings()) {
            return 'E';
        }

        return '';
    }


    /*##########################################################################
    # Database Statements
    ##########################################################################*/

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
    public function insert($sql, $arg1 = null, $arg2 = null, $pk = null,
                           $idValue = null, $sequenceName = null)
    {
        // Extract the table from the insert sql. Yuck.
        $temp = explode(' ', $sql, 4);
        $table = str_replace('"', '', $temp[2]);

        // Try an insert with 'returning id' if available (PG >= 8.2)
        if ($this->supportsInsertWithReturning()) {
            if (!$pk) list($pk, $sequenceName) = $this->pkAndSequenceFor($table);
            if ($pk) {
                $id = $this->selectValue($sql.' RETURNING '.$this->quoteColumnName($pk), $arg1, $arg2);
                return $id;
            }
        }

        // Otherwise, insert then grab last_insert_id.
        if ($insertId = parent::insert($sql, $arg1, $arg2, $pk, $idValue, $sequenceName)) {
            return $insertId;
        }

        // If neither pk nor sequence name is given, look them up.
        if (!($pk || $sequenceName)) {
            list($pk, $sequenceName) = $this->pkAndSequenceFor($table);
        }

        // If a pk is given, fallback to default sequence name.
        // Don't fetch last insert id for a table without a pk.
        if ($pk && ($sequenceName || $sequenceName = $this->defaultSequenceName($table, $pk))) {
            return $this->_lastInsertId($table, $sequenceName);
        }
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * Parse configuration array into options for PDO constructor.
     *
     * @throws  Horde_Db_Exception
     * @return  array  [dsn, username, password]
     */
    protected function _parseConfig()
    {
        $this->_config['adapter'] = 'pgsql';

        return parent::_parseConfig();
    }

    /**
     * Configures the encoding, verbosity, and schema search path of the connection.
     * This is called by connect() and should not be called manually.
     */
    protected function _configureConnection()
    {
        if (!empty($this->_config['encoding'])) {
            $this->_connection->execute('SET client_encoding TO '.$this->quoteString($this->_config['encoding']));
        }

        if (!empty($this->_config['client_min_messages'])) $this->setClientMinMessages($this->_config['client_min_messages']);
        $this->setSchemaSearchPath(!empty($this->_config['schema_search_path']) || !empty($this->_config['schema_order']));
    }

    /**
     * @TODO
     */
    protected function _selectRaw($sql, $arg1=null, $arg2=null)
    {
        $result = $this->execute($sql, $arg1, $arg2);
        if (!$result) return array();

        $moneyFields = array();
        for ($i = 0, $i_max = $result->columnCount(); $i < $i_max; $i++) {
            $f = $result->getColumnMeta($i);
            if (!empty($f['pgsql:oid']) && $f['pgsql:oid'] == Horde_Db_Adapter_Postgresql_Column::MONEY_COLUMN_TYPE_OID) {
                $moneyFields[] = $i;
                $moneyFields[] = $f['name'];
            }
        }

        foreach ($result as $row) {
            // If this is a money type column and there are any currency
            // symbols, then strip them off. Indeed it would be prettier to do
            // this in Horde_Db_Adapter_Postgres_Column::stringToDecimal but
            // would break form input fields that call valueBeforeTypeCast.
            foreach ($moneyFields as $f) {
                // Because money output is formatted according to the locale, there are two
                // cases to consider (note the decimal separators):
                //  (1) $12,345,678.12
                //  (2) $12.345.678,12
                if (preg_match('/^-?\D+[\d,]+\.\d{2}$/', $row[$f])) { // #1
                    $row[$f] = preg_replace('/[^-\d\.]/', '', $row[$f]) . "\n";
                } elseif (preg_match('/^-?\D+[\d\.]+,\d{2}$/', $row[$f])) { // #2
                    $row[$f] = str_replace(',', '.', preg_replace('/[^-\d,]/', '', $row[$f])) . "\n";
                }
            }
            $rows[] = $row;
        }

        $result->closeCursor();
        return $rows;
    }

    /**
     * Returns the current ID of a table's sequence.
     */
    protected function _lastInsertId($table, $sequenceName)
    {
        return (int)$this->selectValue('SELECT currval('.$this->quoteSequenceName($sequenceName).')');
    }
}

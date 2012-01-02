<?php
/**
 * Class for MySQL-specific managing of database schemes and handling of SQL
 * dialects and quoting.
 *
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Mysql_Schema extends Horde_Db_Adapter_Base_Schema
{
    /*##########################################################################
    # Object factories
    ##########################################################################*/

    /**
     * Factory for Column objects.
     *
     * @param string $name     The column's name, such as "supplier_id" in
     *                         "supplier_id int(11)".
     * @param string $default  The type-casted default value, such as "new" in
     *                         "sales_stage varchar(20) default 'new'".
     * @param string $sqlType  Used to extract the column's type, length and
     *                         signed status, if necessary. For example
     *                         "varchar" and "60" in "company_name varchar(60)"
     *                         or "unsigned => true" in "int(10) UNSIGNED".
     * @param boolean $null    Whether this column allows NULL values.
     *
     * @return Horde_Db_Adapter_Mysql_Column  A column object.
     */
    public function makeColumn($name, $default, $sqlType = null, $null = true)
    {
        return new Horde_Db_Adapter_Mysql_Column($name, $default, $sqlType, $null);
    }


    /*##########################################################################
    # Quoting
    ##########################################################################*/

    /**
     * Returns a quoted form of the column name.
     *
     * @param string $name  A column name.
     *
     * @return string  The quoted column name.
     */
    public function quoteColumnName($name)
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    /**
     * Returns a quoted form of the table name.
     *
     * Defaults to column name quoting.
     *
     * @param string $name  A table name.
     *
     * @return string  The quoted table name.
     */
    public function quoteTableName($name)
    {
        return str_replace('.', '`.`', $this->quoteColumnName($name));
    }

    /**
     * Returns a quoted boolean true.
     *
     * @return string  The quoted boolean true.
     */
    public function quoteTrue()
    {
        return '1';
    }

    /**
     * Returns a quoted boolean false.
     *
     * @return string  The quoted boolean false.
     */
    public function quoteFalse()
    {
        return '0';
    }


    /*##########################################################################
    # Schema Statements
    ##########################################################################*/

    /**
     * Returns a hash of mappings from the abstract data types to the native
     * database types.
     *
     * See TableDefinition::column() for details on the recognized abstract
     * data types.
     *
     * @see TableDefinition::column()
     *
     * @return array  A database type map.
     */
    public function nativeDatabaseTypes()
    {
        return array(
            'autoincrementKey' => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'string'           => array('name' => 'varchar',    'limit' => 255),
            'text'             => array('name' => 'text',       'limit' => null),
            'mediumtext'       => array('name' => 'mediumtext', 'limit' => null),
            'longtext'         => array('name' => 'longtext',   'limit' => null),
            'integer'          => array('name' => 'int',        'limit' => 11),
            'float'            => array('name' => 'float',      'limit' => null),
            'decimal'          => array('name' => 'decimal',    'limit' => null),
            'datetime'         => array('name' => 'datetime',   'limit' => null),
            'timestamp'        => array('name' => 'datetime',   'limit' => null),
            'time'             => array('name' => 'time',       'limit' => null),
            'date'             => array('name' => 'date',       'limit' => null),
            'binary'           => array('name' => 'blob',       'limit' => null),
            'boolean'          => array('name' => 'tinyint',    'limit' => 1),
        );
    }

    /**
     * Returns a list of all tables of the current database.
     *
     * @return array  A table list.
     */
    public function tables()
    {
        return $this->selectValues('SHOW TABLES');
    }

    /**
     * Returns a table's primary key.
     *
     * @param string $tableName  A table name.
     * @param string $name       (can be removed?)
     *
     * @return Horde_Db_Adapter_Base_Index  The primary key index object.
     */
    public function primaryKey($tableName, $name = null)
    {
        // Share the column cache with the columns() method
        $rows = @unserialize($this->_cache->get("tables/columns/$tableName"));

        if (!$rows) {
            $rows = $this->selectAll(
                'SHOW FIELDS FROM ' . $this->quoteTableName($tableName),
                $name);

            $this->_cache->set("tables/columns/$tableName", serialize($rows));
        }

        $pk = $this->makeIndex($tableName, 'PRIMARY', true, true, array());
        foreach ($rows as $row) {
            if ($row['Key'] == 'PRI') {
                $pk->columns[] = $row['Field'];
            }
        }

        return $pk;
    }

    /**
     * Returns a list of tables indexes.
     *
     * @param string $tableName  A table name.
     * @param string $name       (can be removed?)
     *
     * @return array  A list of Horde_Db_Adapter_Base_Index objects.
     */
    public function indexes($tableName, $name=null)
    {
        $indexes = @unserialize($this->_cache->get("tables/indexes/$tableName"));

        if (!$indexes) {
            $indexes = array();
            $currentIndex = null;
            foreach ($this->select('SHOW KEYS FROM ' . $this->quoteTableName($tableName)) as $row) {
                if ($currentIndex != $row['Key_name']) {
                    if ($row['Key_name'] == 'PRIMARY') {
                        continue;
                    }
                    $currentIndex = $row['Key_name'];
                    $indexes[] = $this->makeIndex(
                        $tableName, $row['Key_name'], false, $row['Non_unique'] == '0', array());
                }
                $indexes[count($indexes) - 1]->columns[] = $row['Column_name'];
            }

            $this->_cache->set("tables/indexes/$tableName", serialize($indexes));
        }

        return $indexes;
    }

    /**
     * Returns a list of table columns.
     *
     * @param string $tableName  A table name.
     * @param string $name       (can be removed?)
     *
     * @return array  A list of Horde_Db_Adapter_Base_Column objects.
     */
    public function columns($tableName, $name=null)
    {
        $rows = @unserialize($this->_cache->get("tables/columns/$tableName"));

        if (!$rows) {
            $rows = $this->selectAll('SHOW FIELDS FROM ' . $this->quoteTableName($tableName), $name);

            $this->_cache->set("tables/columns/$tableName", serialize($rows));
        }

        // Create columns from rows.
        $columns = array();
        foreach ($rows as $row) {
            $columns[$row['Field']] = $this->makeColumn(
                $row['Field'], $row['Default'], $row['Type'], $row['Null'] == 'YES');
        }

        return $columns;
    }

    /**
     * Finishes and executes table creation.
     *
     * @param string|Horde_Db_Adapter_Base_TableDefinition $name
     *        A table name or object.
     * @param array $options
     *        A list of options. See createTable().
     */
    public function endTable($name, $options = array())
    {
        if ($name instanceof Horde_Db_Adapter_Base_TableDefinition) {
            $options = array_merge($name->getOptions(), $options);
        }
        if (isset($options['options'])) {
            $opts = $options['options'];
        } else {
            if (empty($options['charset'])) {
                $options['charset'] = $this->getCharset();
            }
            $opts = 'ENGINE=InnoDB DEFAULT CHARSET=' . $options['charset'];
        }
        return parent::endTable($name, array_merge(array('options' => $opts), $options));
    }

    /**
     * Renames a table.
     *
     * @param string $name     A table name.
     * @param string $newName  The new table name.
     */
    public function renameTable($name, $newName)
    {
        $this->_clearTableCache($name);
        $sql = sprintf('ALTER TABLE %s RENAME %s',
                       $this->quoteTableName($name),
                       $this->quoteTableName($newName));
        return $this->execute($sql);
    }

    /**
     * Changes an existing column's definition.
     *
     * @param string $tableName   A table name.
     * @param string $columnName  A column name.
     * @param string $type        A data type.
     * @param array $options      Column options. See
     *                            Horde_Db_Adapter_Base_TableDefinition#column()
     *                            for details.
     */
    public function changeColumn($tableName, $columnName, $type,
                                 $options = array())
    {
        $this->_clearTableCache($tableName);

        $quotedTableName = $this->quoteTableName($tableName);
        $quotedColumnName = $this->quoteColumnName($columnName);

        $options = array_merge(
            array('limit'     => null,
                  'precision' => null,
                  'scale'     => null,
                  'unsigned'  => null),
            $options);

        $sql = sprintf('SHOW COLUMNS FROM %s LIKE %s',
                       $quotedTableName,
                       $this->quoteString($columnName));
        $row = $this->selectOne($sql);
        if (!array_key_exists('default', $options)) {
            $options['default'] = $row['Default'];
            $options['column'] = $this->makeColumn($columnName,
                                                   $row['Default'],
                                                   $row['Type'],
                                                   $row['Null'] == 'YES');
        }

        $typeSql = $this->typeToSql($type, $options['limit'],
                                    $options['precision'], $options['scale'],
                                    $options['unsigned']);
        $dropPk = ($type == 'autoincrementKey' && $row['Key'] == 'PRI')
            ? 'DROP PRIMARY KEY,'
            : '';

        $sql = sprintf('ALTER TABLE %s %s CHANGE %s %s %s',
                       $quotedTableName,
                       $dropPk,
                       $quotedColumnName,
                       $quotedColumnName,
                       $typeSql);
        if ($type != 'autoincrementKey') {
            $sql = $this->addColumnOptions($sql, $options);
        }

        $this->execute($sql);
    }

    /**
     * Sets a new default value for a column.
     *
     * If you want to set the default value to NULL, you are out of luck. You
     * need to execute the apppropriate SQL statement yourself.
     *
     * @param string $tableName   A table name.
     * @param string $columnName  A column name.
     * @param mixed $default      The new default value.
     */
    public function changeColumnDefault($tableName, $columnName, $default)
    {
        $this->_clearTableCache($tableName);

        $quotedTableName = $this->quoteTableName($tableName);
        $quotedColumnName = $this->quoteColumnName($columnName);

        $sql = sprintf('SHOW COLUMNS FROM %s LIKE %s',
                       $quotedTableName,
                       $this->quoteString($columnName));
        $res = $this->selectOne($sql);
        $column = $this->makeColumn($columnName, $res['Default'], $res['Type'], $res['Null'] == 'YES');

        $default = $this->quote($default, $column);
        $sql = sprintf('ALTER TABLE %s CHANGE %s %s %s DEFAULT %s',
                       $quotedTableName,
                       $quotedColumnName,
                       $quotedColumnName,
                       $res['Type'],
                       $default);
        return $this->execute($sql);
    }

    /**
     * Renames a column.
     *
     * @param string $tableName      A table name.
     * @param string $columnName     A column name.
     * @param string $newColumnName  The new column name.
     */
    public function renameColumn($tableName, $columnName, $newColumnName)
    {
        $this->_clearTableCache($tableName);

        $quotedTableName = $this->quoteTableName($tableName);
        $quotedColumnName = $this->quoteColumnName($columnName);

        $sql = sprintf('SHOW COLUMNS FROM %s LIKE %s',
                       $quotedTableName,
                       $this->quoteString($columnName));
        $res = $this->selectOne($sql);
        $currentType = $res['Type'];

        $sql = sprintf('ALTER TABLE %s CHANGE %s %s %s',
                       $quotedTableName,
                       $quotedColumnName,
                       $this->quoteColumnName($newColumnName),
                       $currentType);

        return $this->execute($sql);
    }

    /**
     * Removes a primary key from a table.
     *
     * @since Horde_Db 1.1.0
     *
     * @param string $tableName  A table name.
     *
     * @throws Horde_Db_Exception
     */
    public function removePrimaryKey($tableName)
    {
        $this->_clearTableCache($tableName);
        $sql = sprintf('ALTER TABLE %s DROP PRIMARY KEY',
                       $this->quoteTableName($tableName));
        return $this->execute($sql);
    }

    /**
     * Builds the name for an index.
     *
     * Cuts the index name to the maximum length of 64 characters limited by
     * MySQL.
     *
     * @param string $tableName      A table name.
     * @param string|array $options  Either a column name or index options:
     *                               - column: (string|array) column name(s).
     *                               - name: (string) the index name to fall
     *                                 back to if no column names specified.
     */
    public function indexName($tableName, $options=array())
    {
        $indexName = parent::indexName($tableName, $options);
        if (strlen($indexName) > 64) {
            $indexName = substr($indexName, 0, 64);
        }
        return $indexName;
    }

    /**
     * Creates a database.
     *
     * @param string $name    A database name.
     * @param array $options  Database options.
     */
    public function createDatabase($name, $options = array())
    {
        return $this->execute("CREATE DATABASE `$name`");
    }

    /**
     * Drops a database.
     *
     * @param string $name  A database name.
     */
    public function dropDatabase($name)
    {
        return $this->execute("DROP DATABASE IF EXISTS `$name`");
    }

    /**
     * Returns the name of the currently selected database.
     *
     * @return string  The database name.
     */
    public function currentDatabase()
    {
        return $this->selectValue('SELECT DATABASE() AS db');
    }

    /**
     * Generates the SQL definition for a column type.
     *
     * @param string $type        A column type.
     * @param integer $limit      Maximum column length (non decimal type only)
     * @param integer $precision  The number precision (decimal type only).
     * @param integer $scale      The number scaling (decimal columns only).
     * @param boolean $unsigned   Whether the column is an unsigned number
     *                            (non decimal columns only).
     *
     * @return string  The SQL definition. If $type is not one of the
     *                 internally supported types, $type is returned unchanged.
     */
    public function typeToSql($type, $limit = null, $precision = null,
                              $scale = null, $unsigned = null)
    {
        // If there is no explicit limit, adjust $nativeLimit for unsigned
        // integers.
        if ($type == 'integer' && !empty($unsigned) && empty($limit)) {
            $natives = $this->nativeDatabaseTypes();
            $native = isset($natives[$type]) ? $natives[$type] : null;
            if (empty($native)) {
                return $type;
            }

            $nativeLimit = is_array($native) ? $native['limit'] : null;
            if (is_integer($nativeLimit)) {
                $limit = $nativeLimit - 1;
            }
        }

        $sql = parent::typeToSql($type, $limit, $precision, $scale, $unsigned);

        if (!empty($unsigned)) {
            $sql .= ' UNSIGNED';
        }

        return $sql;
    }

    /**
     * Adds default/null options to column SQL definitions.
     *
     * @param string $sql     Existing SQL definition for a column.
     * @param array $options  Column options:
     *                        - null: (boolean) Whether to allow NULL values.
     *                        - default: (mixed) Default column value.
     *                        - autoincrement: (boolean) Whether the column is
     *                          an autoincrement column. Driver depedendent.
     *                        - after: (string) Insert column after this one.
     *                          MySQL specific.
     *
     * @return string  The manipulated SQL definition.
     */
    public function addColumnOptions($sql, $options)
    {
        $sql = parent::addColumnOptions($sql, $options);
        if (isset($options['after'])) {
            $sql .= ' AFTER ' . $this->quoteColumnName($options['after']);
        }
        if (!empty($options['autoincrement'])) {
            $sql .= ' AUTO_INCREMENT';
        }
        return $sql;
    }

    /**
     * Returns an expression using the specified operator.
     *
     * @param string $lhs    The column or expression to test.
     * @param string $op     The operator.
     * @param string $rhs    The comparison value.
     * @param boolean $bind  If true, the method returns the query and a list
     *                       of values suitable for binding as an array.
     * @param array $params  Any additional parameters for the operator.
     *
     * @return string|array  The SQL test fragment, or an array containing the
     *                       query and a list of values if $bind is true.
     */
    public function buildClause($lhs, $op, $rhs, $bind = false,
                                $params = array())
    {
        switch ($op) {
        case '~':
            if ($bind) {
                return array($lhs . ' REGEXP ?', array($rhs));
            } else {
                return $lhs . ' REGEXP ' . $rhs;
            }
        }
        return parent::buildClause($lhs, $op, $rhs, $bind, $params);
    }


    /*##########################################################################
    # MySQL specific methods
    ##########################################################################*/

    /**
     * Returns the character set of query results.
     *
     * @return string  The result's charset.
     */
    public function getCharset()
    {
        return $this->showVariable('character_set_results');
    }

    /**
     * Sets the client and result charset.
     *
     * @param string $charset  The character set to use for client queries and
     *                         results.
     */
    public function setCharset($charset)
    {
        $charset = $this->_mysqlCharsetName($charset);
        $this->execute('SET NAMES ' . $this->quoteString($charset));
    }

    /**
     * Returns the MySQL name of a character set.
     *
     * @param string $charset  A charset name.
     *
     * @return string  MySQL-normalized charset.
     */
    public function _mysqlCharsetName($charset)
    {
        $charset = preg_replace(array('/[^a-z0-9]/', '/iso8859(\d)/'),
                                array('', 'latin$1'),
                                Horde_String::lower($charset));
        $validCharsets = $this->selectValues('SHOW CHARACTER SET');
        if (!in_array($charset, $validCharsets)) {
            throw new Horde_Db_Exception($charset . ' is not supported by MySQL (' . implode(', ', $validCharsets) . ')');
        }

        return $charset;
    }

    /**
     * Returns the database collation strategy.
     *
     * @return string  Database collation.
     */
    public function getCollation()
    {
        return $this->showVariable('collation_database');
    }

    /**
     * Returns a database variable.
     *
     * Convenience wrapper around "SHOW VARIABLES LIKE 'name'".
     *
     * @param string $name  A variable name.
     *
     * @return string  The variable value.
     * @throws Horde_Db_Exception
     */
    public function showVariable($name)
    {
        $value = $this->selectOne('SHOW VARIABLES LIKE ' . $this->quoteString($name));
        if ($value['Variable_name'] == $name) {
            return $value['Value'];
        } else {
            throw new Horde_Db_Exception($name . ' is not a recognized variable');
        }
    }

    /**
     */
    public function caseSensitiveEqualityOperator()
    {
        return '= BINARY';
    }

    /**
     */
    public function limitedUpdateConditions($whereSql, $quotedTableName,
                                            $quotedPrimaryKey)
    {
        return $whereSql;
    }
}

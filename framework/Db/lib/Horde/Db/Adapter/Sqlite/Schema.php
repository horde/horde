<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd
 * @package    Db
 * @subpackage Adapter
 */

/**
 * Class for SQLite-specific managing of database schemes and handling of SQL
 * dialects and quoting.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2007 Maintainable Software, LLC
 * @copyright  2008-2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd
 * @package    Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Sqlite_Schema extends Horde_Db_Adapter_Base_Schema
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
     * @return Horde_Db_Adapter_Base_Column  A column object.
     */
    public function makeColumn($name, $default, $sqlType = null, $null = true)
    {
        return new Horde_Db_Adapter_Sqlite_Column($name, $default, $sqlType, $null);
    }


    /*##########################################################################
    # Quoting
    ##########################################################################*/

    /**
     * Returns a quoted binary value.
     *
     * @param mixed  A binary value.
     *
     * @return string  The quoted binary value.
     */
    public function quoteBinary($value)
    {
        return "'" . str_replace(array("'", '%', "\0"), array("''", '%25', '%00'), $value) . "'";
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
            'autoincrementKey' => $this->_defaultPrimaryKeyType(),
            'string'     => array('name' => 'varchar',  'limit' => 255),
            'text'       => array('name' => 'text',     'limit' => null),
            'mediumtext' => array('name' => 'text',     'limit' => null),
            'longtext'   => array('name' => 'text',     'limit' => null),
            'integer'    => array('name' => 'int',      'limit' => null),
            'float'      => array('name' => 'float',    'limit' => null),
            'decimal'    => array('name' => 'decimal',  'limit' => null),
            'datetime'   => array('name' => 'datetime', 'limit' => null),
            'timestamp'  => array('name' => 'datetime', 'limit' => null),
            'time'       => array('name' => 'time',     'limit' => null),
            'date'       => array('name' => 'date',     'limit' => null),
            'binary'     => array('name' => 'blob',     'limit' => null),
            'boolean'    => array('name' => 'boolean',  'limit' => null),
        );
    }

    /**
     * Returns a list of all tables of the current database.
     *
     * @return array  A table list.
     */
    public function tables()
    {
        return $this->selectValues("SELECT name FROM sqlite_master WHERE type = 'table' UNION ALL SELECT name FROM sqlite_temp_master WHERE type = 'table' AND name != 'sqlite_sequence' ORDER BY name");
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
        // Share the columns cache with the columns() method
        $rows = @unserialize($this->cacheRead("tables/columns/$tableName"));

        if (!$rows) {
            $rows = $this->selectAll('PRAGMA table_info(' . $this->quoteTableName($tableName) . ')', $name);

            $this->cacheWrite("tables/columns/$tableName", serialize($rows));
        }

        $pk = $this->makeIndex($tableName, 'PRIMARY', true, true, array());
        foreach ($rows as $row) {
            if ($row['pk']) {
                $pk->columns[] = $row['name'];
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
    public function indexes($tableName, $name = null)
    {
        $indexes = @unserialize($this->cacheRead("tables/indexes/$tableName"));

        if (!$indexes) {
            $indexes = array();
            foreach ($this->select('PRAGMA index_list(' . $this->quoteTableName($tableName) . ')') as $row) {
                if (strpos($row['name'], 'sqlite_') !== false) {
                    // ignore internal sqlite_* index tables
                    continue;
                }
                $index = $this->makeIndex(
                    $tableName, $row['name'], false, (bool)$row['unique'], array());
                foreach ($this->select('PRAGMA index_info(' . $this->quoteColumnName($index->name) . ')') as $field) {
                    $index->columns[] = $field['name'];
                }

                $indexes[] = $index;
            }

            $this->cacheWrite("tables/indexes/$tableName", serialize($indexes));
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
    public function columns($tableName, $name = null)
    {
        $rows = @unserialize($this->cacheRead("tables/columns/$tableName"));

        if (!$rows) {
            $rows = $this->selectAll('PRAGMA table_info(' . $this->quoteTableName($tableName) . ')', $name);
            $this->cacheWrite("tables/columns/$tableName", serialize($rows));
        }

        // create columns from rows
        $columns = array();
        foreach ($rows as $row) {
            $columns[$row['name']] = $this->makeColumn(
                $row['name'], $row['dflt_value'], $row['type'], !(bool)$row['notnull']);
        }

        return $columns;
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
        $sql = sprintf('ALTER TABLE %s RENAME TO %s',
                       $this->quoteTableName($name),
                       $this->quoteTableName($newName));
        return $this->execute($sql);
    }

    /**
     * Adds a new column to a table.
     *
     * @param string $tableName   A table name.
     * @param string $columnName  A column name.
     * @param string $type        A data type.
     * @param array $options      Column options. See
     *                            Horde_Db_Adapter_Base_TableDefinition#column()
     *                            for details.
     */
    public function addColumn($tableName, $columnName, $type, $options = array())
    {
        if ($this->transactionStarted()) {
            throw new Horde_Db_Exception('Cannot add columns to a SQLite database while inside a transaction');
        }

        if ($type == 'autoincrementKey') {
            $this->_alterTable(
                $tableName,
                array(),
                function ($definition) use ($columnName, $type, $options) {
                    $definition->column($columnName, $type, $options);
                }
            );
        } else {
            parent::addColumn($tableName, $columnName, $type, $options);
        }

        // See last paragraph on http://www.sqlite.org/lang_altertable.html
        $this->execute('VACUUM');
    }

    /**
     * Removes a column from a table.
     *
     * @param string $tableName   A table name.
     * @param string $columnName  A column name.
     */
    public function removeColumn($tableName, $columnName)
    {
        $this->_clearTableCache($tableName);

        return $this->_alterTable(
            $tableName,
            array(),
            function ($definition) use ($columnName) {
                unset($definition[$columnName]);
            }
        );
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
    public function changeColumn($tableName, $columnName, $type, $options = array())
    {
        $this->_clearTableCache($tableName);

        $defs = array(
            function ($definition) use ($columnName, $type) {
                $definition[$columnName]->setType($type);
            },
            function ($definition) use ($type) {
                if ($type == 'autoincrementKey') {
                    $definition->primaryKey(false);
                }
            }
        );
        if (isset($options['limit'])) {
            $defs[] = function ($definition) use ($columnName, $options) {
                $definition[$columnName]->setLimit($options['limit']);
            };
        }
        if (isset($options['null'])) {
            $defs[] = function ($definition) use ($columnName, $options) {
                $definition[$columnName]->setNull((bool)$options['null']);
            };
        }
        if (isset($options['precision'])) {
            $defs[] = function ($definition) use ($columnName, $options) {
                $definition[$columnName]->setPrecision($options['precision']);
            };
        }
        if (isset($options['scale'])) {
            $defs[] = function ($definition) use ($columnName, $options) {
                $definition[$columnName]->setScale($options['scale']);
            };
        }

        if (array_key_exists('default', $options)) {
            $defs[] = function ($definition) use ($columnName, $options) {
                $definition[$columnName]->setDefault($options['default']);
            };
        }

        return $this->_alterTable(
            $tableName,
            array(),
            function ($definition) use ($defs) {
                foreach ($defs as $callback) {
                    $callback($definition);
                }
            }
        );
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

        return $this->_alterTable(
            $tableName,
            array(),
            function ($definition) use ($columnName, $default) {
                $definition[$columnName]->setDefault($default);
            }
        );
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

        return $this->_alterTable(
            $tableName,
            array('rename' => array($columnName => $newColumnName)));
    }

    /**
     * Adds a primary key to a table.
     *
     * @param string $tableName         A table name.
     * @param string|array $columnName  One or more column names.
     *
     * @throws Horde_Db_Exception
     */
    public function addPrimaryKey($tableName, $columns)
    {
        $this->_clearTableCache($tableName);
        $columns = (array)$columns;
        $callback = function ($definition) use ($columns) {
            $definition->primaryKey($columns);
        };
        $this->_alterTable($tableName, array(), $callback);
    }

    /**
     * Removes a primary key from a table.
     *
     * @param string $tableName  A table name.
     *
     * @throws Horde_Db_Exception
     */
    public function removePrimaryKey($tableName)
    {
        $this->_clearTableCache($tableName);
        $callback = function ($definition) {
            $definition->primaryKey(false);
        };
        $this->_alterTable($tableName, array(), $callback);
    }

    /**
     * Removes an index from a table.
     *
     * See parent class for examples.
     *
     * @param string $tableName      A table name.
     * @param string|array $options  Either a column name or index options:
     *                               - name: (string) the index name.
     *                               - column: (string|array) column name(s).
     */
    public function removeIndex($tableName, $options=array())
    {
        $this->_clearTableCache($tableName);

        $index = $this->indexName($tableName, $options);
        $sql = 'DROP INDEX ' . $this->quoteColumnName($index);
        return $this->execute($sql);
    }

    /**
     * Creates a database.
     *
     * @param string $name    A database name.
     * @param array $options  Database options.
     */
    public function createDatabase($name, $options = array())
    {
        return new PDO('sqlite:' . $name);
    }

    /**
     * Drops a database.
     *
     * @param string $name  A database name.
     */
    public function dropDatabase($name)
    {
        if (!@file_exists($name)) {
            throw new Horde_Db_Exception('database does not exist');
        }

        if (!@unlink($name)) {
            throw new Horde_Db_Exception('could not remove the database file');
        }
    }

    /**
     * Returns the name of the currently selected database.
     *
     * @return string  The database name.
     */
    public function currentDatabase()
    {
        return $this->_config['dbname'];
    }

    /**
     * Generates a modified date for SELECT queries.
     *
     * @param string $reference  The reference date - this is a column
     *                           referenced in the SELECT.
     * @param string $operator   Add or subtract time? (+/-)
     * @param integer $amount    The shift amount (number of days if $interval
     *                           is DAY, etc).
     * @param string $interval   The interval (SECOND, MINUTE, HOUR, DAY,
     *                           MONTH, YEAR).
     *
     * @return string  The generated INTERVAL clause.
     */
    public function modifyDate($reference, $operator, $amount, $interval)
    {
        if (!is_int($amount)) {
            throw new InvalidArgumentException('$amount parameter must be an integer');
        }
        switch ($interval) {
        case 'YEAR':
            $interval = 'years';
            break;
        case 'MONTH':
            $interval = 'months';
            break;
        case 'DAY':
            $interval = 'days';
            break;
        case 'HOUR':
            $interval = 'hours';
            break;
        case 'MINUTE':
            $interval = 'minutes';
            break;
        case 'SECOND':
            $interval = 'seconds';
            break;
        default:
            break;
        }

        return 'datetime(' . $reference . ', \'' . $operator . $amount . ' '
            . $interval . '\')';
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * Returns a column type definition to be use for primary keys.
     *
     * @return string  Primary key type definition.
     */
    protected function _defaultPrimaryKeyType()
    {
        if ($this->supportsAutoIncrement()) {
            return 'INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL';
        } else {
            return 'INTEGER PRIMARY KEY NOT NULL';
        }
    }

    /**
     * Alters a table.
     *
     * This is done by creating a temporary copy, applying changes and callback
     * methods, and copying the table back.
     *
     * @param string $tableName   A table name.
     * @param array $options      Any options to apply when creating the
     *                            temporary table. Supports a 'rename' key for
     *                            the new table name, additionally to the
     *                            options in createTable().
     * @param function $callback  A callback function that can manipulate the
     *                            Horde_Db_Adapter_Base_TableDefinition object
     *                            available in $definition. See _copyTable().
     */
    protected function _alterTable($tableName, $options = array(), $callback = null)
    {
        $this->beginDbTransaction();

        $alteredTableName = 'altered_' . $tableName;
        $this->_moveTable($tableName,
                          $alteredTableName,
                          array_merge($options, array('temporary' => true)));
        $this->_moveTable($alteredTableName,
                          $tableName,
                          array(),
                          $callback);

        $this->commitDbTransaction();
    }

    /**
     * Moves a table.
     *
     * This is done by creating a temporary copy, applying changes and callback
     * methods, and dropping the original table.
     *
     * @param string $from        The name of the source table.
     * @param string $to          The name of the target table.
     * @param array $options      Any options to apply when creating the
     *                            temporary table. Supports a 'rename' key for
     *                            the new table name, additionally to the
     *                            options in createTable().
     * @param function $callback  A callback function that can manipulate the
     *                            Horde_Db_Adapter_Base_TableDefinition object
     *                            available in $definition. See _copyTable().
     */
    protected function _moveTable($from, $to, $options = array(),
                                  $callback = null)
    {
        $this->_copyTable($from, $to, $options, $callback);
        $this->dropTable($from);
    }

    /**
     * Copies a table.
     *
     * Also applies changes and callback methods before creating the new table.
     *
     * @param string $from        The name of the source table.
     * @param string $to          The name of the target table.
     * @param array $options      Any options to apply when creating the
     *                            temporary table. Supports a 'rename' key for
     *                            the new table name, additionally to the
     *                            options in createTable().
     * @param function $callback  A callback function that can manipulate the
     *                            Horde_Db_Adapter_Base_TableDefinition object
     *                            available in $definition.
     */
    protected function _copyTable($from, $to, $options = array(),
                                  $callback = null)
    {
        $fromColumns = $this->columns($from);
        $pk = $this->primaryKey($from);
        if ($pk && count($pk->columns) == 1) {
            /* A primary key is not necessarily what matches the pseudo type
             * "autoincrementKey". We need to parse the table definition to
             * find out if the column is AUTOINCREMENT too. */
            $tableDefinition = $this->selectValue('SELECT sql FROM sqlite_master WHERE name = ? UNION ALL SELECT sql FROM sqlite_temp_master WHERE name = ?',
                                                  array($from, $from));
            if (strpos($tableDefinition, $this->quoteColumnName($pk->columns[0]) . ' INTEGER PRIMARY KEY AUTOINCREMENT')) {
                $pkColumn = $pk->columns[0];
            } else {
                $pkColumn = null;
            }
        } else {
            $pkColumn = null;
        }
        $options = array_merge($options, array('autoincrementKey' => false));

        $copyPk = true;
        $definition = $this->createTable($to, $options);
        foreach ($fromColumns as $column) {
            $columnName = isset($options['rename'][$column->getName()])
                ? $options['rename'][$column->getName()]
                : $column->getName();
            $columnType = $column->getName() == $pkColumn
                ? 'autoincrementKey'
                : $column->getType();
            $columnOptions = array('limit' => $column->getLimit());

            if ($columnType == 'autoincrementKey') {
                $copyPk = false;
            } else {
                $columnOptions['default'] = $column->getDefault();
                $columnOptions['null'] = $column->isNull();
            }

            $definition->column($columnName, $columnType, $columnOptions);
        }

        if ($pkColumn && count($pk->columns) && $copyPk) {
            $definition->primaryKey($pk->columns);
        }

        if (is_callable($callback)) {
            call_user_func($callback, $definition);
        }

        $definition->end();

        $this->_copyTableIndexes(
            $from,
            $to,
            isset($options['rename']) ? $options['rename'] : array());
        $this->_copyTableContents(
            $from,
            $to,
            array_map(
                function($c) { return $c->getName(); },
                iterator_to_array($definition)
            ),
            isset($options['rename']) ? $options['rename'] : array());
    }

    /**
     * Copies indexes from one table to another.
     *
     * @param string $from   The name of the source table.
     * @param string $to     The name of the target table.
     * @param array $rename  A hash of columns to rename during the copy, with
     *                       original names as keys and the new names as values.
     */
    protected function _copyTableIndexes($from, $to, $rename = array())
    {
        $toColumnNames = array();
        foreach ($this->columns($to) as $c) {
            $toColumnNames[$c->getName()] = true;
        }

        foreach ($this->indexes($from) as $index) {
            $name = $index->getName();
            if ($to == 'altered_' . $from) {
                $name = 'temp_' . $name;
            } elseif ($from == 'altered_' . $to) {
                $name = substr($name, 5);
            }

            $columns = array();
            foreach ($index->columns as $c) {
                if (isset($rename[$c])) {
                    $c = $rename[$c];
                }
                if (isset($toColumnNames[$c])) {
                    $columns[] = $c;
                }
            }

            if (!empty($columns)) {
                // Index name can't be the same
                $opts = array('name' => str_replace('_' . $from . '_', '_' . $to . '_', $name));
                if ($index->unique) {
                    $opts['unique'] = true;
                }
                $this->addIndex($to, $columns, $opts);
            }
        }
    }

    /**
     * Copies the content of one table to another.
     *
     * @param string $from   The name of the source table.
     * @param string $to     The name of the target table.
     * @param array $columns  A list of columns to copy.
     * @param array $rename   A hash of columns to rename during the copy, with
     *                        original names as keys and the new names as
     *                        values.
     */
    protected function _copyTableContents($from, $to, $columns,
                                          $rename = array())
    {
        $columnMappings = array_combine($columns, $columns);

        foreach ($rename as $renameFrom => $renameTo) {
            $columnMappings[$renameTo] = $renameFrom;
        }

        $fromColumns = array();
        foreach ($this->columns($from) as $col) {
            $fromColumns[] = $col->getName();
        }

        $tmpColumns = array();
        foreach ($columns as $col) {
            if (in_array($columnMappings[$col], $fromColumns)) {
                $tmpColumns[] = $col;
            }
        }
        $columns = $tmpColumns;

        $fromColumns = array();
        foreach ($columns as $col) {
            $fromColumns[] = $columnMappings[$col];
        }

        $quotedTo = $this->quoteTableName($to);
        $quotedToColumns = implode(', ', array_map(array($this, 'quoteColumnName'), $columns));

        $quotedFrom = $this->quoteTableName($from);
        $quotedFromColumns = implode(', ', array_map(array($this, 'quoteColumnName'), $fromColumns));

        $sql = sprintf('INSERT INTO %s (%s) SELECT %s FROM %s',
                       $quotedTo,
                       $quotedToColumns,
                       $quotedFromColumns,
                       $quotedFrom);
        $this->execute($sql);
    }
}

<?php
/**
 * Base class for managing database schemes and handling database-specific SQL
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
abstract class Horde_Db_Adapter_Base_Schema
{
    /**
     * A Horde_Db_Adapter instance.
     *
     * @var Horde_Db_Adapter_Base
     */
    protected $_adapter = null;

    /**
     * List of public methods supported by the attached adapter.
     *
     * Method names are in the keys.
     *
     * @var array
     */
    protected $_adapterMethods = array();


    /*##########################################################################
    # Construct/Destruct
    ##########################################################################*/

    /**
     * Constructor.
     *
     * @param Horde_Db_Adapter_Base $adapter  A Horde_Db_Adapter instance.
     */
    public function __construct(Horde_Db_Adapter $adapter)
    {
        $this->setAdapter($adapter);
    }

    /**
     * Setter for a Horde_Db_Adapter instance.
     *
     * @param Horde_Db_Adapter $adapter  A Horde_Db_Adapter instance.
     */
    public function setAdapter(Horde_Db_Adapter $adapter)
    {
        $this->_adapter = $adapter;
        $this->_adapterMethods = array_flip(get_class_methods($adapter));
    }


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
        return new Horde_Db_Adapter_Base_Column($name, $default, $sqlType, $null);
    }

    /**
     * Factory for ColumnDefinition objects.
     *
     * @return Horde_Db_Adapter_Base_ColumnDefinition  A column definition
     *                                                 object.
     */
    public function makeColumnDefinition(
        $base, $name, $type, $limit = null, $precision = null, $scale = null,
        $unsigned = null, $default = null, $null = null, $autoincrement = null)
    {
        return new Horde_Db_Adapter_Base_ColumnDefinition(
            $base, $name, $type, $limit, $precision, $scale, $unsigned,
            $default, $null, $autoincrement);
    }

    /**
     * Factory for Index objects.
     *
     * @param string  $table    The table the index is on.
     * @param string  $name     The index's name.
     * @param boolean $primary  Is this a primary key?
     * @param boolean $unique   Is this a unique index?
     * @param array   $columns  The columns this index covers.
     *
     * @return Horde_Db_Adapter_Base_Index  An index object.
     */
    public function makeIndex($table, $name, $primary, $unique, $columns)
    {
        return new Horde_Db_Adapter_Base_Index($table, $name, $primary, $unique, $columns);
    }

    /**
     * Factory for Table objects.
     *
     * @return Horde_Db_Adapter_Base_Table  A table object.
     */
    public function makeTable($name, $primaryKey, $columns, $indexes)
    {
        return new Horde_Db_Adapter_Base_Table($name, $primaryKey, $columns, $indexes);
    }

    /**
     * Factory for TableDefinition objects.
     *
     * @return Horde_Db_Adapter_Base_TableDefinition  A table definition object.
     */
    public function makeTableDefinition($name, $base, $options = array())
    {
        return new Horde_Db_Adapter_Base_TableDefinition($name, $base, $options);
    }


    /*##########################################################################
    # Object composition
    ##########################################################################*/

    /**
     * Delegates calls to the adapter object.
     *
     * @param string $method  A method name.
     * @param array  $args    Method parameters.
     *
     * @return mixed  The method call result.
     * @throws BadMethodCallException if method doesn't exist in the adapter.
     */
    public function __call($method, $args)
    {
        if (isset($this->_adapterMethods[$method])) {
            return call_user_func_array(array($this->_adapter, $method), $args);
        }

        throw new BadMethodCallException('Call to undeclared method "' . $method . '"');
    }

    /**
     * Delegates access to $_cache and $_logger to the adapter object.
     *
     * @param string $key  Property name. Only '_cache' and '_logger' are
     *                     supported.
     *
     * @return object  The request property object.
     */
    public function __get($key)
    {
        if ($key == '_cache' || $key == '_logger') {
            $getter = 'get' . ucfirst(substr($key, 1));
            return $this->_adapter->$getter();
        }
    }


    /*##########################################################################
    # Quoting
    ##########################################################################*/

    /**
     * Quotes the column value to help prevent SQL injection attacks.
     *
     * This method makes educated guesses on the scalar type based on the
     * passed value. Make sure to correctly cast the value and/or pass the
     * $column parameter to get the best results.
     *
     * @param mixed $value    The scalar value to quote, a Horde_Db_Value,
     *                        Horde_Date, or DateTime instance, or an object
     *                        implementing quotedId().
     * @param object $column  An object implementing getType().
     *
     * @return string  The correctly quoted value.
     */
    public function quote($value, $column = null)
    {
        if (is_object($value) && is_callable(array($value, 'quotedId'))) {
            return $value->quotedId();
        }

        if ($value instanceof Horde_Db_Value) {
            return $value->quote($this->_adapter);
        }

        $type = isset($column) ? $column->getType() : null;

        if (is_null($value)) {
            return 'NULL';
        } elseif ($value === true) {
            return $type == 'integer' ? '1' : $this->quoteTrue();
        } elseif ($value === false) {
            return $type == 'integer' ? '0' : $this->quoteFalse();
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        } elseif (is_int($value)) {
            return $value;
        } elseif ($value instanceof DateTime || $value instanceof Horde_Date) {
            return $this->_adapter->quoteString($type == 'integer'
                                                ? $value->format('U')
                                                : $value->format('Y-m-d H:i:s'));
        } elseif ($type == 'integer') {
            return (int)$value;
        } elseif ($type == 'float') {
            return sprintf('%F', $value);
        } else {
            return $this->_adapter->quoteString($value);
        }
    }

    /**
     * Quotes a string, escaping any ' (single quote) and \ (backslash)
     * characters.
     *
     * @param string $string  A string to escape.
     *
     * @return string  The escaped and quoted string.
     */
    public function quoteString($string)
    {
        return "'" . str_replace(array('\\', '\''), array('\\\\', '\\\''), $string) . "'";
    }

    /**
     * Returns a quoted form of the column name.
     *
     * @param string $name  A column name.
     *
     * @return string  The quoted column name.
     */
    abstract public function quoteColumnName($name);

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
        return $this->quoteColumnName($name);
    }

    /**
     * Returns a quoted boolean true.
     *
     * @return string  The quoted boolean true.
     */
    public function quoteTrue()
    {
        return "'t'";
    }

    /**
     * Returns a quoted boolean false.
     *
     * @return string  The quoted boolean false.
     */
    public function quoteFalse()
    {
        return "'f'";
    }

    /**
     * Returns a quoted date value.
     *
     * @param mixed  A date value that can be casted to string.
     *
     * @return string  The quoted date value.
     */
    public function quoteDate($value)
    {
        return $this->quoteString((string)$value);
    }

    /**
     * Returns a quoted binary value.
     *
     * @param mixed  A binary value.
     *
     * @return string  The quoted binary value.
     */
    public function quoteBinary($value)
    {
        return $this->quoteString($value);
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
        return array();
    }

    /**
     * Returns the maximum length a table alias can have.
     *
     * @return integer  The maximum table alias length.
     */
    public function tableAliasLength()
    {
        return 255;
    }

    /**
     * Converts a table name into a suitable table alias.
     *
     * @param string $tableName  A table name.
     *
     * @return string  A possible alias name for the table.
     */
    public function tableAliasFor($tableName)
    {
        $alias = substr($tableName, 0, $this->tableAliasLength());
        return str_replace('.', '_', $alias);
    }

    /**
     * Returns a list of all tables of the current database.
     *
     * @return array  A table list.
     */
    abstract public function tables();

    /**
     * Returns a Horde_Db_Adapter_Base_Table object for a table.
     *
     * @param string $tableName  A table name.
     * @param string $name       (can be removed?)
     *
     * @return Horde_Db_Adapter_Base_Table  A table object.
     */
    public function table($tableName, $name = null)
    {
        return $this->makeTable(
            $tableName,
            $this->primaryKey($tableName),
            $this->columns($tableName, $name),
            $this->indexes($tableName, $name)
        );
    }

    /**
     * Returns a table's primary key.
     *
     * @param string $tableName  A table name.
     * @param string $name       (can be removed?)
     *
     * @return Horde_Db_Adapter_Base_Index  The primary key index object.
     */
    abstract public function primaryKey($tableName, $name = null);

    /**
     * Returns a list of tables indexes.
     *
     * @param string $tableName  A table name.
     * @param string $name       (can be removed?)
     *
     * @return array  A list of Horde_Db_Adapter_Base_Index objects.
     */
    abstract public function indexes($tableName, $name = null);

    /**
     * Returns a list of table columns.
     *
     * @param string $tableName  A table name.
     * @param string $name       (can be removed?)
     *
     * @return array  A list of Horde_Db_Adapter_Base_Column objects.
     */
    abstract public function columns($tableName, $name = null);

    /**
     * Creates a new table.
     *
     * The $options hash can include the following keys:
     * - autoincrementKey (string|array):
     *   The name of the autoincrementing primary key, if one is to be added
     *   automatically. Defaults to "id".
     * - options (array):
     *   Any extra options you want appended to the table definition.
     * - temporary (boolean):
     *   Make a temporary table.
     * - force (boolean):
     *   Set to true or false to drop the table before creating it.
     *   Defaults to false.
     *
     * Examples:
     * <code>
     * // Add a backend specific option to the generated SQL (MySQL)
     * $schema->createTable('suppliers', array('options' => 'ENGINE=InnoDB DEFAULT CHARSET=utf8')));
     * </code>
     * generates:
     * <pre>
     *  CREATE TABLE suppliers (
     *    id int(10) UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY
     *  ) ENGINE=InnoDB DEFAULT CHARSET=utf8
     * </pre>
     *
     * <code>
     * // Rename the primary key column
     * $table = $schema->createTable('objects', array('autoincrementKey' => 'guid'));
     * $table->column('name', 'string', array('limit' => 80));
     * $table->end();
     * </code>
     * generates:
     * <pre>
     *  CREATE TABLE objects (
     *    guid int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
     *    name varchar(80)
     *  )
     * </pre>
     *
     * <code>
     * // Do not add a primary key column, use fluent interface, use type
     * // method.
     * $schema->createTable('categories_suppliers', array('autoincrementKey' => false))
     *     ->column('category_id', 'integer')
     *     ->integer('supplier_id')
     *     ->end();
     * </code>
     * generates:
     * <pre>
     *  CREATE TABLE categories_suppliers (
     *    category_id int(11),
     *    supplier_id int(11)
     *  )
     * </pre>
     *
     * See also Horde_Db_Adapter_Base_TableDefinition::column() for details on
     * how to create columns.
     *
     * @param string $name    A table name.
     * @param array $options  A list of table options, see the method
     *                        description.
     *
     * @return Horde_Db_Adapter_Base_TableDefinition  The definition of the
     *                                                created table.
     */
    public function createTable($name, $options = array())
    {
        $tableDefinition = $this->makeTableDefinition($name, $this, $options);

        if (isset($options['autoincrementKey'])) {
            if ($options['autoincrementKey'] === true ||
                $options['autoincrementKey'] === 'true' ||
                $options['autoincrementKey'] === 't' ||
                $options['autoincrementKey'] === 1 ||
                $options['autoincrementKey'] === '1') {
                $pk = 'id';
            } elseif ($options['autoincrementKey'] === false ||
                      $options['autoincrementKey'] === 'false' ||
                      $options['autoincrementKey'] === 'f' ||
                      $options['autoincrementKey'] === 0 ||
                      $options['autoincrementKey'] === '0') {
                $pk = false;
            } else {
                $pk = $options['autoincrementKey'];
            }
        } else {
            $pk = 'id';
        }

        if ($pk != false) {
            $tableDefinition->primaryKey($pk);
        }

        return $tableDefinition;
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
            $tableDefinition = $name;
            $options = array_merge($tableDefinition->getOptions(), $options);
        } else {
            $tableDefinition = $this->createTable($name, $options);
        }

        // Drop previous table.
        if (isset($options['force'])) {
            $this->dropTable($tableDefinition->getName(), $options);
        }

        $temp = !empty($options['temporary']) ? 'TEMPORARY'         : null;
        $opts = !empty($options['options'])   ? $options['options'] : null;
        $sql  = sprintf("CREATE %s TABLE %s (\n%s\n) %s",
                        $temp,
                        $this->quoteTableName($tableDefinition->getName()),
                        $tableDefinition->toSql(),
                        $opts);

        return $this->execute($sql);
    }

    /**
     * Renames a table.
     *
     * @param string $name     A table name.
     * @param string $newName  The new table name.
     */
    abstract public function renameTable($name, $newName);

    /**
     * Drops a table from the database.
     *
     * @param string $name  A table name.
     */
    public function dropTable($name)
    {
        $this->_clearTableCache($name);
        return $this->execute('DROP TABLE ' . $this->quoteTableName($name));
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
    public function addColumn($tableName, $columnName, $type,
                              $options = array())
    {
        $this->_clearTableCache($tableName);

        $options = array_merge(
            array('limit'     => null,
                  'precision' => null,
                  'scale'     => null,
                  'unsigned'  => null),
            $options);

        $sql = sprintf('ALTER TABLE %s ADD %s %s',
                       $this->quoteTableName($tableName),
                       $this->quoteColumnName($columnName),
                       $this->typeToSql($type,
                                        $options['limit'],
                                        $options['precision'],
                                        $options['scale'],
                                        $options['unsigned']));
        $sql = $this->addColumnOptions($sql, $options);

        return $this->execute($sql);
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
        $sql = sprintf('ALTER TABLE %s DROP %s',
                       $this->quoteTableName($tableName),
                       $this->quoteColumnName($columnName));
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
    abstract public function changeColumn($tableName, $columnName, $type, $options = array());

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
    abstract public function changeColumnDefault($tableName, $columnName, $default);

    /**
     * Renames a column.
     *
     * @param string $tableName      A table name.
     * @param string $columnName     A column name.
     * @param string $newColumnName  The new column name.
     */
    abstract public function renameColumn($tableName, $columnName, $newColumnName);

    /**
     * Adds a primary key to a table.
     *
     * @since Horde_Db 1.1.0
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
        $sql = sprintf('ALTER TABLE %s ADD PRIMARY KEY (%s)',
                       $this->quoteTableName($tableName),
                       implode(', ', $columns));
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
    abstract public function removePrimaryKey($tableName);

    /**
     * Adds a new index to a table.
     *
     * The index will be named after the table and the first column names,
     * unless you pass 'name' as an option.
     *
     * When creating an index on multiple columns, the first column is used as
     * a name for the index. For example, when you specify an index on two
     * columns 'first' and 'last', the DBMS creates an index for both columns
     * as well as an index for the first colum 'first'. Using just the first
     * name for this index makes sense, because you will never have to create a
     * singular index with this name.
     *
     * Examples:
     *
     * Creating a simple index
     * <code>
     * $schema->addIndex('suppliers', 'name');
     * </code>
     * generates
     * <code>
     * CREATE INDEX suppliers_name_index ON suppliers(name)
     * </code>
     *
     * Creating a unique index
     * <code>
     * $schema->addIndex('accounts',
     *                   array('branch_id', 'party_id'),
     *                   array('unique' => true));
     * </code>
     * generates
     * <code>
     * CREATE UNIQUE INDEX accounts_branch_id_index ON accounts(branch_id, party_id)
     * </code>
     *
     * Creating a named index
     * <code>
     * $schema->addIndex('accounts',
     *                   array('branch_id', 'party_id'),
     *                   array('unique' => true, 'name' => 'by_branch_party'));
     * </code>
     * generates
     * <code>
     * CREATE UNIQUE INDEX by_branch_party ON accounts(branch_id, party_id)
     * </code>
     *
     * @param string $tableName         A table name.
     * @param string|array $columnName  One or more column names.
     * @param array $options            Index options:
     *                                  - name: (string) the index name.
     *                                  - unique: (boolean) create a unique
     *                                            index?
     */
    public function addIndex($tableName, $columnName, $options = array())
    {
        $this->_clearTableCache($tableName);

        $columnNames = (array)$columnName;
        $indexName = empty($options['name'])
            ? $this->indexName($tableName, array('column' => $columnNames))
            : $options['name'];
        foreach ($columnNames as &$colName) {
            $colName = $this->quoteColumnName($colName);
        }

        $sql = sprintf('CREATE %s INDEX %s ON %s (%s)',
                       empty($options['unique']) ? null : 'UNIQUE',
                       $this->quoteColumnName($indexName),
                       $this->quoteTableName($tableName),
                       implode(', ', $columnNames));

        return $this->execute($sql);
    }

    /**
     * Removes an index from a table.
     *
     * Examples:
     *
     * Remove the suppliers_name_index in the suppliers table:
     * <code>
     * $schema->removeIndex('suppliers', 'name');
     * </code>
     *
     * Remove the index named accounts_branch_id in the accounts table:
     * <code>
     * $schema->removeIndex('accounts', array('column' => 'branch_id'));
     * </code>
     *
     * Remove the index named by_branch_party in the accounts table:
     * <code>
     * $schema->removeIndex('accounts', array('name' => 'by_branch_party'));
     * </code>
     *
     * You can remove an index on multiple columns by specifying the first
     * column:
     * <code>
     * $schema->addIndex('accounts', array('username', 'password'))
     * $schema->removeIndex('accounts', 'username');
     * </code>
     *
     * @param string $tableName      A table name.
     * @param string|array $options  Either a column name or index options:
     *                               - name: (string) the index name.
     *                               - column: (string|array) column name(s).
     */
    public function removeIndex($tableName, $options = array())
    {
        $this->_clearTableCache($tableName);

        $index = $this->indexName($tableName, $options);
        $sql = sprintf('DROP INDEX %s ON %s',
                       $this->quoteColumnName($index),
                       $this->quoteTableName($tableName));

        return $this->execute($sql);
    }

    /**
     * Builds the name for an index.
     *
     * @param string $tableName      A table name.
     * @param string|array $options  Either a column name or index options:
     *                               - column: (string|array) column name(s).
     *                               - name: (string) the index name to fall
     *                                 back to if no column names specified.
     */
    public function indexName($tableName, $options = array())
    {
        if (!is_array($options)) {
            $options = array('column' => $options);
        }
        if (isset($options['column'])) {
            $columns = (array)$options['column'];
            return "index_{$tableName}_on_" . implode('_and_', $columns);
        }
        if (isset($options['name'])) {
            return $options['name'];
        }
        throw new Horde_Db_Exception('You must specify the index name');
    }

    /**
     * Recreates, i.e. drops then creates a database.
     *
     * @param string $name  A database name.
     */
    public function recreateDatabase($name)
    {
        $this->dropDatabase($name);
        return $this->createDatabase($name);
    }

    /**
     * Creates a database.
     *
     * @param string $name    A database name.
     * @param array $options  Database options.
     */
    abstract public function createDatabase($name, $options = array());

    /**
     * Drops a database.
     *
     * @param string $name  A database name.
     */
    abstract public function dropDatabase($name);

    /**
     * Returns the name of the currently selected database.
     *
     * @return string  The database name.
     */
    abstract public function currentDatabase();

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
        $natives = $this->nativeDatabaseTypes();
        $native = isset($natives[$type]) ? $natives[$type] : null;
        if (empty($native)) {
            return $type;
        }

        $sql = is_array($native) ? $native['name'] : $native;
        if ($type == 'decimal') {
            $nativePrec  = isset($native['precision']) ? $native['precision'] : null;
            $nativeScale = isset($native['scale'])     ? $native['scale']     : null;

            $precision = !empty($precision) ? $precision : $nativePrec;
            $scale     = !empty($scale)     ? $scale     : $nativeScale;
            if ($precision) {
                $sql .= $scale ? "($precision, $scale)" : "($precision)";
            }
        } else {
            $nativeLimit = is_array($native) ? $native['limit'] : null;

            // If there is no explicit limit, adjust $nativeLimit for unsigned
            // integers.
            if (!empty($unsigned) && empty($limit) && is_integer($nativeLimit)) {
                $nativeLimit--;
            }

            if ($limit = !empty($limit) ? $limit : $nativeLimit) {
                $sql .= "($limit)";
            }
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
     *
     * @return string  The manipulated SQL definition.
     */
    public function addColumnOptions($sql, $options)
    {
        /* 'autoincrement' is not handled here - it varies too much between
         * DBs. Do autoincrement-specific handling in the driver. */

        if (isset($options['null']) && $options['null'] === false) {
            $sql .= ' NOT NULL';
        }

        if (isset($options['default'])) {
            $default = $options['default'];
            $column  = isset($options['column']) ? $options['column'] : null;
            $sql .= ' DEFAULT ' . $this->quote($default, $column);
        }

        return $sql;
    }

    /**
     * Generates a DISTINCT clause for SELECT queries.
     *
     * <code>
     * $connection->distinct('posts.id', 'posts.created_at DESC')
     * </code>
     *
     * @param string $columns  A column list.
     * @param string $orderBy  An ORDER clause.
     *
     * @return string  The generated DISTINCT clause.
     */
    public function distinct($columns, $orderBy = null)
    {
        return 'DISTINCT ' . $columns;
    }

    /**
     * Adds an ORDER BY clause to an existing query.
     *
     * @param string $sql     An SQL query to manipulate.
     * @param array $options  Options:
     *                        - order: Order column an direction.
     *
     * @return string  The manipulated SQL query.
     */
    public function addOrderByForAssocLimiting($sql, $options)
    {
        return $sql . 'ORDER BY ' . $options['order'];
    }

    /**
     * Generates an INTERVAL clause for SELECT queries.
     *
     * @deprecated since version 1.2.0. This function does not work with SQLite
     * as a backend so you should avoid using it. Use "modifyDate()" instead.
     *
     * @param string $interval   The interval.
     * @param string $precision  The precision.
     *
     * @return string  The generated INTERVAL clause.
     */
    public function interval($interval, $precision)
    {
        return 'INTERVAL ' . $precision . ' ' . $interval;
    }

    /**
     * Generates a modified date for SELECT queries.
     *
     * @since Horde_Db 1.2.0
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
        return sprintf('%s %s INTERVAL \'%s\' %s',
                       $reference,
                       $operator,
                       $amount,
                       $interval);
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
        $lhs = $this->_escapePrepare($lhs);
        switch ($op) {
        case '|':
        case '&':
            if ($bind) {
                return array($lhs . ' ' . $op . ' ?',
                             array((int)$rhs));
            }
            return $lhs . ' ' . $op . ' ' . (int)$rhs;

        case '~':
            if ($bind) {
                return array($lhs . ' ' . $op . ' ?', array($rhs));
            }
            return $lhs . ' ' . $op . ' ' . $rhs;

        case 'IN':
            if ($bind) {
                if (is_array($rhs)) {
                    return array($lhs . ' IN (?' . str_repeat(', ?', count($rhs) - 1) . ')', $rhs);
                }
                /* We need to bind each member of the IN clause separately to
                 * ensure proper quoting. */
                if (substr($rhs, 0, 1) == '(') {
                    $rhs = substr($rhs, 1);
                }
                if (substr($rhs, -1) == ')') {
                    $rhs = substr($rhs, 0, -1);
                }

                $ids = preg_split('/\s*,\s*/', $rhs);

                return array($lhs . ' IN (?' . str_repeat(', ?', count($ids) - 1) . ')', $ids);
            }
            if (is_array($rhs)) {
                return $lhs . ' IN ' . implode(', ', $rhs);
            }
            return $lhs . ' IN ' . $rhs;

        case 'LIKE':
            $query = 'LOWER(%s) LIKE LOWER(%s)';
            if ($bind) {
                if (empty($params['begin'])) {
                    return array(sprintf($query, $lhs, '?'),
                                 array('%' . $rhs . '%'));
                }
                return array(sprintf('(' . $query . ' OR ' . $query . ')',
                                     $lhs, '?', $lhs, '?'),
                             array($rhs . '%', '% ' . $rhs . '%'));
            }
            if (empty($params['begin'])) {
                return sprintf($query,
                               $lhs,
                               $this->_escapePrepare($this->quote('%' . $rhs . '%')));
            }
            return sprintf('(' . $query . ' OR ' . $query . ')',
                           $lhs,
                           $this->_escapePrepare($this->quote($rhs . '%')),
                           $lhs,
                           $this->_escapePrepare($this->quote('% ' . $rhs . '%')));

        default:
            if ($bind) {
                return array($lhs . ' ' . $this->_escapePrepare($op) . ' ?', array($rhs));
            }
            return $lhs . ' ' . $this->_escapePrepare($op . ' ' . $this->quote($rhs));
        }
    }

    /**
     * Escapes all characters in a string that are placeholders for
     * prepare/execute methods.
     *
     * @param string $query  A string to escape.
     *
     * @return string  The correctly escaped string.
     */
    protected function _escapePrepare($query)
    {
        return preg_replace('/[?!&]/', '\\\\$0', $query);
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * Clears the cache for tables when altering them.
     *
     * @param string $tableName  A table name.
     */
    protected function _clearTableCache($tableName)
    {
        $this->_cache->set('tables/columns/' . $tableName, '');
        $this->_cache->set('tables/indexes/' . $tableName, '');
    }
}

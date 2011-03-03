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
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */
abstract class Horde_Db_Adapter_Base_Schema
{
    /**
     * @var Horde_Db_Adapter_Base
     */
    protected $_adapter = null;

    /**
     * @var array
     */
    protected $_adapterMethods = array();


    /*##########################################################################
    # Construct/Destruct
    ##########################################################################*/

    /**
     * @param Horde_Db_Adapter_Base $adapter
     * @param array $config
     */
    public function __construct(Horde_Db_Adapter $adapter, $config = array())
    {
        $this->setAdapter($adapter);
    }

    /**
     * @param Horde_Db_Adapter $adapter
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
     * Factory for Column objects
     */
    public function makeColumn($name, $default, $sqlType = null, $null = true)
    {
        return new Horde_Db_Adapter_Base_Column($name, $default, $sqlType, $null);
    }

    /**
     * Factory for ColumnDefinition objects
     */
    public function makeColumnDefinition($base, $name, $type, $limit = null,
        $precision = null, $scale = null, $unsigned = null,
        $default = null, $null = null, $autoincrement = null)
    {
        return new Horde_Db_Adapter_Base_ColumnDefinition($base, $name, $type, $limit, $precision, $scale, $unsigned, $default, $null, $autoincrement);
    }

    /**
     * Factory for Index objects
     */
    public function makeIndex($table, $name, $primary, $unique, $columns)
    {
        return new Horde_Db_Adapter_Base_Index($table, $name, $primary, $unique, $columns);
    }

    /**
     * Factory for Table objects
     */
    public function makeTable($name, $primaryKey, $columns, $indexes)
    {
        return new Horde_Db_Adapter_Base_Table($name, $primaryKey, $columns, $indexes);
    }

    /**
     * Factory for TableDefinition objects
     */
    public function makeTableDefinition($name, $base, $options = array())
    {
        return new Horde_Db_Adapter_Base_TableDefinition($name, $base, $options);
    }


    /*##########################################################################
    # Object composition
    ##########################################################################*/

    /**
     * Delegate calls to the adapter object.
     *
     * @param  string  $method
     * @param  array   $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (isset($this->_adapterMethods[$method])) {
            return call_user_func_array(array($this->_adapter, $method), $args);
        }

        throw new BadMethodCallException('Call to undeclared method "'.$method.'"');
    }

    /**
     * Delegate access to $_cache and $_logger to the adapter object.
     *
     * @param  string  $key
     *
     * @return mixed
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
     * Quotes the column value to help prevent
     * {SQL injection attacks}[http://en.wikipedia.org/wiki/SQL_injection].
     *
     * @param   string  $value
     * @param   string  $column
     * @return  string
     */
    public function quote($value, $column = null)
    {
        if (is_object($value) && is_callable(array($value, 'quotedId'))) {
            return $value->quotedId();
        }

        $type = isset($column) ? $column->getType() : null;

        if (is_null($value)) {
            return 'NULL';
        } elseif ($value === true) {
            return $type == 'integer' ? '1' : $this->quoteTrue();
        } elseif ($value === false) {
            return $type == 'integer' ? '0' : $this->quoteFalse();
        } elseif (is_int($value) || is_float($value)) {
            return $value;
        } elseif ($value instanceof DateTime || $value instanceof Horde_Date) {
            return $this->_adapter->quoteString($type == 'integer'
                                                ? $value->format('U')
                                                : $value->format('Y-m-d H:i:s'));
        } elseif ($type == 'integer') {
            return (int)$value;
        } elseif ($type == 'float') {
            return (float)$value;
        } else {
            /*@TODO
          when String, ActiveSupport::Multibyte::Chars
            value = value.to_s
            if column && column.type == :binary && column.class.respond_to?(:string_to_binary)
              "#{quoted_string_prefix}'#{quote_string(column.class.string_to_binary(value))}'" # ' (for ruby-mode)
            elsif column && [:integer, :float].include?(column.type)
              value = column.type == :integer ? value.to_i : value.to_f
              value.to_s
            else
              "#{quoted_string_prefix}'#{quote_string(value)}'" # ' (for ruby-mode)
            end
            */
            return $this->_adapter->quoteString($value);
        }
    }

    /**
     * Quotes a string, escaping any ' (single quote) and \ (backslash)
     * characters..
     *
     * @param   string  $string
     * @return  string
     */
    public function quoteString($string)
    {
        return "'" . str_replace(array('\\', '\''), array('\\\\', '\\\''), $string) . "'";
    }

    /**
     * Returns a quoted form of the column name. This is highly adapter
     * specific.
     *
     * @param   string  $name
     * @return  string
     */
    abstract public function quoteColumnName($name);

    /**
     * Returns a quoted form of the table name. Defaults to column name quoting.
     *
     * @param   string  $name
     * @return  string
     */
    public function quoteTableName($name)
    {
        return $this->quoteColumnName($name);
    }

    /**
     * @return  string
     */
    public function quoteTrue()
    {
        return "'t'";
    }

    /**
     * @return  string
     */
    public function quoteFalse()
    {
        return "'f'";
    }

    /**
     * @return  string
     */
    public function quoteDate($value)
    {
        return $this->_adapter->quoteString((string)$value);
    }

    /**
     * @return  string
     */
    public function quotedStringPrefix()
    {
        return '';
    }


    /*##########################################################################
    # Schema Statements
    ##########################################################################*/

    /**
     * Returns a Hash of mappings from the abstract data types to the native
     * database types.  See TableDefinition#column for details on the recognized
     * abstract data types.
     *
     * @return  array
     */
    public function nativeDatabaseTypes()
    {
        return array();
    }

    /**
     * This is the maximum length a table alias can be
     *
     * @return  int
     */
    public function tableAliasLength()
    {
        return 255;
    }

    /**
     * Truncates a table alias according to the limits of the current adapter.
     *
     * @param   string  $tableName
     * @return  string
     */
    public function tableAliasFor($tableName)
    {
        $alias = substr($tableName, 0, $this->tableAliasLength());
        return str_replace('.', '_', $alias);
    }

    /**
     * @param   string  $name
     * @return  array
     */
    abstract public function tables($name = null);

    /**
     * Get a Horde_Db_Adapter_Base_Table object for the table.
     *
     * @param  string  $tableName
     * @param  string  $name
     *
     * @return Horde_Db_Adapter_Base_Table
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
     * Return a table's primary key
     */
    abstract public function primaryKey($tableName, $name = null);

    /**
     * Returns an array of indexes for the given table.
     *
     * @param   string  $tableName
     * @param   string  $name
     * @return  array
     */
    abstract public function indexes($tableName, $name = null);

    /**
     * Returns an array of Horde_Db_Adapter_Base_Column objects for the
     * table specified by +table_name+.  See the concrete implementation for
     * details on the expected parameter values.
     *
     * @param   string  $tableName
     * @param   string  $name
     * @return  array
     */
    abstract public function columns($tableName, $name = null);

    /**
     * Creates a new table.
     *
     * The $options hash can include the following keys:
     * - primaryKey (string|array):
     *   The name of the primary key, if one is to be added automatically.
     *   Defaults to "id".
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
     *    id int(11) DEFAULT NULL auto_increment PRIMARY KEY
     *  ) ENGINE=InnoDB DEFAULT CHARSET=utf8
     * </pre>
     *
     * <code>
     * // Rename the primary key column
     * $table = $schema->createTable('objects', array('primaryKey' => 'guid'));
     * $table->column('name', 'string', array('limit' => 80));
     * $table->end();
     * </code>
     * generates:
     * <pre>
     *  CREATE TABLE objects (
     *    guid int(11) DEFAULT NULL auto_increment PRIMARY KEY,
     *    name varchar(80)
     *  )
     * </pre>
     *
     * <code>
     * // Do not add a primary key column
     * $table = $schema->createTable('categories_suppliers', array('primaryKey' => false));
     * $table->column('category_id', 'integer');
     * $table->column('supplier_id', 'integer');
     * $table->end();
     * </code>
     * generates:
     * <pre>
     *  CREATE TABLE categories_suppliers (
     *    category_id int,
     *    supplier_id int
     *  )
     * </pre>
     *
     * See also Horde_Db_Adapter_Base_TableDefinition::column() for details on
     * how to create columns.
     *
     * @param   string  $name
     * @param   array   $options
     */
    public function createTable($name, $options = array())
    {
        $tableDefinition = $this->makeTableDefinition($name, $this, $options);

        if (isset($options['primaryKey'])) {
            if ($options['primaryKey'] === false) {
                $pk = false;
            } else {
                switch ($options['primaryKey']) {
                case 'true':
                case 't':
                case 1:
                case '1':
                    $pk = 'id';
                    break;

                case 'false':
                case 'f':
                case 0:
                case '0':
                    $pk = false;

                default:
                    $pk = $options['primaryKey'];
                }
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
     * Execute table creation
     *
     * @param   string  $name
     * @param   array   $options
     */
    public function endTable($name, $options=array())
    {
        if ($name instanceof Horde_Db_Adapter_Base_TableDefinition) {
            $tableDefinition = $name;
            $options = array_merge($tableDefinition->getOptions(), $options);
        } else {
            $tableDefinition = $this->createTable($name, $options);
        }

        // drop previous
        if (isset($options['force'])) {
            $this->dropTable($tableDefinition->getName(), $options);
        }

        $temp = !empty($options['temporary']) ? 'TEMPORARY'           : null;
        $opts = !empty($options['options'])   ? $options['options']   : null;

        $sql  = sprintf("CREATE %s TABLE %s (\n%s\n) %s",
                        $temp,
                        $this->quoteTableName($tableDefinition->getName()),
                        $tableDefinition->toSql(),
                        $opts);
        return $this->execute($sql);
    }

    /**
     * Renames a table.
     * ===== Example
     *  rename_table('octopuses', 'octopi')
     *
     * @param   string  $name
     * @param   string  $newName
     */
    abstract public function renameTable($name, $newName);

    /**
     * Drops a table from the database.
     *
     * @param   string  $name
     */
    public function dropTable($name)
    {
        $this->_clearTableCache($name);
        return $this->execute('DROP TABLE ' . $this->quoteTableName($name));
    }

    /**
     * Adds a new column to the named table.
     * See TableDefinition#column for details of the options you can use.
     *
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $type
     * @param   array   $options
     */
    public function addColumn($tableName, $columnName, $type, $options=array())
    {
        $this->_clearTableCache($tableName);

        $limit     = isset($options['limit'])     ? $options['limit']     : null;
        $precision = isset($options['precision']) ? $options['precision'] : null;
        $scale     = isset($options['scale'])     ? $options['scale']     : null;
        $unsigned  = isset($options['unsigned'])  ? $options['unsigned']  : null;

        $sql = sprintf('ALTER TABLE %s ADD %s %s',
                       $this->quoteTableName($tableName),
                       $this->quoteColumnName($columnName),
                       $this->typeToSql($type, $limit, $precision, $scale, $unsigned));
        $sql = $this->addColumnOptions($sql, $options);
        return $this->execute($sql);
    }

    /**
     * Removes the column from the table definition.
     * ===== Examples
     *  remove_column(:suppliers, :qualification)
     *
     * @param   string  $tableName
     * @param   string  $columnName
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
     * Changes the column's definition according to the new options.
     * See TableDefinition#column for details of the options you can use.
     * ===== Examples
     *  change_column(:suppliers, :name, :string, :limit => 80)
     *  change_column(:accounts, :description, :text)
     *
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $type
     * @param   array   $options
     */
    abstract public function changeColumn($tableName, $columnName, $type, $options = array());

    /**
     * Sets a new default value for a column.  If you want to set the default
     * value to +NULL+, you are out of luck.  You need to
     * DatabaseStatements#execute the apppropriate SQL statement yourself.
     * ===== Examples
     *  change_column_default(:suppliers, :qualification, 'new')
     *  change_column_default(:accounts, :authorized, 1)
     *
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $default
     */
    abstract public function changeColumnDefault($tableName, $columnName, $default);

    /**
     * Renames a column.
     * ===== Example
     *  rename_column(:suppliers, :description, :name)
     *
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $newColumnName
     */
    abstract public function renameColumn($tableName, $columnName, $newColumnName);

    /**
     * Adds a new index to the table.  +column_name+ can be a single Symbol, or
     * an Array of Symbols.
     *
     * The index will be named after the table and the first column names,
     * unless you pass +:name+ as an option.
     *
     * When creating an index on multiple columns, the first column is used as a name
     * for the index. For example, when you specify an index on two columns
     * [+:first+, +:last+], the DBMS creates an index for both columns as well as an
     * index for the first colum +:first+. Using just the first name for this index
     * makes sense, because you will never have to create a singular index with this
     * name.
     *
     * ===== Examples
     * ====== Creating a simple index
     *  add_index(:suppliers, :name)
     * generates
     *  CREATE INDEX suppliers_name_index ON suppliers(name)
     *
     * ====== Creating a unique index
     *  add_index(:accounts, [:branch_id, :party_id], :unique => true)
     * generates
     *  CREATE UNIQUE INDEX accounts_branch_id_index ON accounts(branch_id, party_id)
     *
     * ====== Creating a named index
     *  add_index(:accounts, [:branch_id, :party_id], :unique => true, :name => 'by_branch_party')
     * generates
     *  CREATE UNIQUE INDEX by_branch_party ON accounts(branch_id, party_id)
     *
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   array   $options
     */
    public function addIndex($tableName, $columnName, $options=array())
    {
        $this->_clearTableCache($tableName);

        $columnNames = (array)($columnName);
        $indexName = $this->indexName($tableName, array('column' => $columnNames));

        $indexType = !empty($options['unique']) ? 'UNIQUE'         : null;
        $indexName = !empty($options['name'])   ? $options['name'] : $indexName;

        foreach ($columnNames as $colName) {
            $quotedCols[] = $this->quoteColumnName($colName);
        }
        $quotedColumnNames = implode(', ', $quotedCols);
        $sql = sprintf('CREATE %s INDEX %s ON %s (%s)',
                       $indexType,
                       $this->quoteColumnName($indexName),
                       $this->quoteTableName($tableName),
                       $quotedColumnNames);
        return $this->execute($sql);
    }

    /**
     * Remove the given index from the table.
     *
     * Remove the suppliers_name_index in the suppliers table (legacy support, use the second or third forms).
     *   remove_index :suppliers, :name
     * Remove the index named accounts_branch_id in the accounts table.
     *   remove_index :accounts, :column => :branch_id
     * Remove the index named by_branch_party in the accounts table.
     *   remove_index :accounts, :name => :by_branch_party
     *
     * You can remove an index on multiple columns by specifying the first column.
     *   add_index :accounts, [:username, :password]
     *   remove_index :accounts, :username
     *
     * @param   string  $tableName
     * @param   array   $options
     */
    public function removeIndex($tableName, $options=array())
    {
        $this->_clearTableCache($tableName);

        $index = $this->indexName($tableName, $options);
        $sql = sprintf('DROP INDEX %s ON %s',
                       $this->quoteColumnName($index),
                       $this->quoteTableName($tableName));
        return $this->execute($sql);
    }

    /**
     * Get the name of the index
     *
     * @param   string  $tableName
     * @param   array   $options
     */
    public function indexName($tableName, $options=array())
    {
        if (!is_array($options)) {
            $options = array('column' => $options);
        }

        if (isset($options['column'])) {
            $columns = (array)$options['column'];
            return "index_{$tableName}_on_".implode('_and_', $columns);

        } elseif (isset($options['name'])) {
            return $options['name'];

        } else {
            throw new Horde_Db_Exception('You must specify the index name');
        }
    }

    /**
     * Recreate the given db
     *
     * @param   string  $name
     */
    public function recreateDatabase($name)
    {
        $this->dropDatabase($name);
        return $this->createDatabase($name);
    }

    /**
     * Create the given db
     *
     * @param   string  $name
     */
    abstract public function createDatabase($name);

    /**
     * Drop the given db
     *
     * @param   string  $name
     */
    abstract public function dropDatabase($name);

    /**
     * Get the name of the current db
     *
     * @return  string
     */
    abstract public function currentDatabase();

    /**
     * The sql for this column type
     *
     * @param   string  $type
     * @param   string  $limit
     */
    public function typeToSql($type, $limit = null, $precision = null, $scale = null, $unsigned = null)
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
     * Add default/null options to column sql
     *
     * @param   string  $sql
     * @param   array   $options
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
            $column  = isset($options['column'])  ? $options['column']  : null;
            $sql .= ' DEFAULT ' . $this->quote($default, $column);
        }

        return $sql;
    }

    /**
     * SELECT DISTINCT clause for a given set of columns and a given
     * ORDER BY clause. Both PostgreSQL and Oracle override this for
     * custom DISTINCT syntax.
     *
     * $connection->distinct("posts.id", "posts.created_at desc")
     *
     * @param   string  $columns
     * @param   string  $orderBy
     */
    public function distinct($columns, $orderBy=null)
    {
        return 'DISTINCT ' . $columns;
    }

    /**
     * ORDER BY clause for the passed order option.
     * PostgreSQL overrides this due to its stricter standards compliance.
     *
     * @param   string  $sql
     * @param   array   $options
     * @return  string
     */
    public function addOrderByForAssocLimiting($sql, $options)
    {
        return $sql . 'ORDER BY ' . $options['order'];
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * We need to clear cache for tables when altering them at all
     */
    protected function _clearTableCache($tableName)
    {
        $this->_cache->set('tables/columns/' . $tableName, '');
        $this->_cache->set('tables/indexes/' . $tableName, '');
    }
}

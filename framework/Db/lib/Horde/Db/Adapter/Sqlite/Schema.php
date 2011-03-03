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
class Horde_Db_Adapter_Sqlite_Schema extends Horde_Db_Adapter_Base_Schema
{
    /*##########################################################################
    # Object factories
    ##########################################################################*/

    /**
     * Factory for Column objects
     */
    public function makeColumn($name, $default, $sqlType = null, $null = true)
    {
        return new Horde_Db_Adapter_Sqlite_Column($name, $default, $sqlType, $null);
    }


    /*##########################################################################
    # Quoting
    ##########################################################################*/

    /**
     * @return  string
     */
    public function quoteColumnName($name)
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    /**
     * @return  string
     */
    public function quoteTrue()
    {
        return '1';
    }

    /**
     * @return  string
     */
    public function quoteFalse()
    {
        return '0';
    }


    /*##########################################################################
    # Schema Statements
    ##########################################################################*/

    /**
     * The db column types for this adapter
     *
     * @return  array
     */
    public function nativeDatabaseTypes()
    {
        return array(
            'primaryKey' => $this->_defaultPrimaryKeyType(),
            'string'     => array('name' => 'varchar',  'limit' => 255),
            'text'       => array('name' => 'text',     'limit' => null),
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
     * Create the given db
     *
     * @param   string  $name
     */
    public function createDatabase($name)
    {
        return new PDO('sqlite:' . $name);
    }

    /**
     * Drop the given db
     *
     * @param   string  $name
     */
    public function dropDatabase($name)
    {
        if (! @file_exists($name)) {
            throw new Horde_Db_Exception('database does not exist');
        }

        if (! @unlink($name)) {
            throw new Horde_Db_Exception('could not remove the database file');
        }
    }

    /**
     * Get the name of the current db
     *
     * @return  string
     */
    public function currentDatabase()
    {
        return $this->_config['dbname'];
    }

    /**
     * List of tables for the db
     *
     * @param   string  $name
     */
    public function tables($name = null)
    {
        return $this->selectValues("SELECT name FROM sqlite_master WHERE type = 'table' UNION ALL SELECT name FROM sqlite_temp_master WHERE type = 'table' AND name != 'sqlite_sequence' ORDER BY name");
    }

    /**
     * Return a table's primary key
     */
    public function primaryKey($tableName, $name = null)
    {
        // Share the columns cache with the columns() method
        $rows = @unserialize($this->_cache->get("tables/columns/$tableName"));

        if (!$rows) {
            $rows = $this->selectAll('PRAGMA table_info(' . $this->quoteTableName($tableName) . ')', $name);

            $this->_cache->set("tables/columns/$tableName", serialize($rows));
        }

        $pk = $this->makeIndex($tableName, 'PRIMARY', true, true, array());
        foreach ($rows as $row) {
            if ($row['pk'] == 1) {
                $pk->columns[] = $row['name'];
            }
        }

        return $pk;
    }

    /**
     * List of indexes for the given table
     *
     * @param   string  $tableName
     * @param   string  $name
     */
    public function indexes($tableName, $name = null)
    {
        $indexes = @unserialize($this->_cache->get("tables/indexes/$tableName"));

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

            $this->_cache->set("tables/indexes/$tableName", serialize($indexes));
        }

        return $indexes;
    }

    /**
     * @param   string  $tableName
     * @param   string  $name
     */
    public function columns($tableName, $name = null)
    {
        $rows = @unserialize($this->_cache->get("tables/columns/$tableName"));

        if (!$rows) {
            $rows = $this->selectAll('PRAGMA table_info(' . $this->quoteTableName($tableName) . ')', $name);
            $this->_cache->set("tables/columns/$tableName", serialize($rows));
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
     * @param   string  $name
     * @param   string  $newName
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
        /* Ignore ':autoincrement' - it is handled automatically by SQLite
         * for any 'INTEGER PRIMARY KEY' column. */
        if ($this->transactionStarted()) {
            throw new Horde_Db_Exception('Cannot add columns to a SQLite database while inside a transaction');
        }

        parent::addColumn($tableName, $columnName, $type, $options);

        // See last paragraph on http://www.sqlite.org/lang_altertable.html
        $this->execute('VACUUM');
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

        return $this->_alterTable($tableName, array(),
            create_function('$definition', 'unset($definition["' . $columnName . '"]);'));
    }

    /**
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $type
     * @param   array   $options
     */
    public function changeColumn($tableName, $columnName, $type, $options=array())
    {
        $this->_clearTableCache($tableName);

        $defs = array(sprintf('$definition["%s"]->setType("%s");', $columnName, $type));
        if (isset($options['limit'])) {
            $defs[] = sprintf('$definition["%s"]->setLimit("%s");', $columnName, $options['limit']);
        }
        if (isset($options['null'])) {
            $defs[] = sprintf('$definition["%s"]->setNull("%s");', $columnName, $options['null']);
        }
        if (isset($options['precision'])) {
            $defs[] = sprintf('$definition["%s"]->setPrecision("%s");', $columnName, $options['precision']);
        }
        if (isset($options['scale'])) {
            $defs[] = sprintf('$definition["%s"]->setScale("%s");', $columnName, $options['scale']);
        }

        if (array_key_exists('default', $options)) {
            if ($options['default'] === true) {
                $default = 'true';
            } elseif ($options['default'] === false) {
                $default = 'false';
            } elseif ($options['default'] === null) {
                $default = 'null';
            } else {
                $default = '"' . $options['default'] . '"';
            }
            $defs[] = sprintf('$definition["%s"]->setDefault(%s);', $columnName, $default);
        }

        return $this->_alterTable($tableName, array(),
            create_function('$definition', implode("\n", $defs)));
    }

    /**
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $default
     */
    public function changeColumnDefault($tableName, $columnName, $default)
    {
        $this->_clearTableCache($tableName);

        $default = is_null($default) ? 'null' : '"' . $default . '"';
        return $this->_alterTable($tableName, array(),
                                  create_function('$definition', sprintf('$definition["%s"]->setDefault(%s);', $columnName, $default)));
    }

    /**
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $newColumnName
     */
    public function renameColumn($tableName, $columnName, $newColumnName)
    {
        $this->_clearTableCache($tableName);

        return $this->_alterTable($tableName, array('rename' => array($columnName => $newColumnName)));
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
        $sql = 'DROP INDEX ' . $this->quoteColumnName($index);
        return $this->execute($sql);
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    protected function _defaultPrimaryKeyType()
    {
        if ($this->supportsAutoIncrement()) {
            return 'INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL';
        } else {
            return 'INTEGER PRIMARY KEY NOT NULL';
        }
    }

    protected function _alterTable($tableName, $options = array(), $callback = null)
    {
        $this->beginDbTransaction();

        $alteredTableName = 'altered_' . $tableName;
        $this->_moveTable($tableName, $alteredTableName, array_merge($options, array('temporary' => true)));
        $this->_moveTable($alteredTableName, $tableName, array(), $callback);

        $this->commitDbTransaction();

        return true;
    }

    protected function _moveTable($from, $to, $options = array(), $callback = null)
    {
        $this->_copyTable($from, $to, $options, $callback);
        $this->dropTable($from);
    }

    protected function _copyTable($from, $to, $options = array(), $callback = null)
    {
        $fromColumns = $this->columns($from);
        $options = array_merge($options, array('primaryKey' => false));

        $definition = $this->createTable($to, $options);
        foreach ($fromColumns as $column) {
            $columnName = isset($options['rename'][$column->getName()])
                ? $options['rename'][$column->getName()]
                : $column->getName();

            $definition->column($columnName, $column->getType(), array(
                'limit' => $column->getLimit(),
                'default' => $column->getDefault(),
                'null' => $column->isNull()));
        }

        $primaryKey = $this->primaryKey($from);
        if ($primaryKey) {
            $definition->primaryKey($primaryKey);
        }

        if (is_callable($callback)) {
            call_user_func($callback, $definition);
        }

        $definition->end();

        $this->_copyTableIndexes($from, $to, isset($options['rename']) ? $options['rename'] : array());
        $this->_copyTableContents($from, $to,
            array_map(create_function('$c', 'return $c->getName();'), iterator_to_array($definition)),
            isset($options['rename']) ? $options['rename'] : array());
    }

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

    protected function _copyTableContents($from, $to, $columns, $rename = array())
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

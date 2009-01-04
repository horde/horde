<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008 The Horde Project (http://www.horde.org/)
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
class Horde_Db_Adapter_Sqlite_Schema extends Horde_Db_Adapter_Abstract_Schema
{
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
     * Dump entire schema structure or specific table
     *
     * @param   string  $table
     * @return  string
     */
    public function structureDump($table=null)
    {
        if ($table) {
            return $this->selectValue('SELECT sql FROM (
                SELECT * FROM sqlite_master UNION ALL
                SELECT * FROM sqlite_temp_master) WHERE type != \'meta\' AND name = ' . $this->quote($table));
        } else {
            $dump = $this->selectValues('SELECT sql FROM (
                SELECT * FROM sqlite_master UNION ALL
                SELECT * FROM sqlite_temp_master) WHERE type != \'meta\' AND name != \'sqlite_sequence\'');
            return implode("\n\n", $dump);
        }
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
                $index = (object)array('table'   => $tableName,
                                       'name'    => $row[1],
                                       'unique'  => (bool)$row[2],
                                       'columns' => array());
                foreach ($this->select('PRAGMA index_info(' . $this->quoteColumnName($index->name) . ')') as $field) {
                    $index->columns[] = $field[2];
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
            $columns[] = $this->componentFactory('Column', array(
                $row[1], $row[4], $row[2], !(bool)$row[3]));
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

        return $this->execute('ALTER TABLE ' . $this->quoteTableName($name) . ' RENAME TO ' . $this->quoteTableName($newName));
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
        throw new Horde_Db_Exception('Sqlite#removeColumn is not supported');
        /*@TODO
        column_names.flatten.each do |column_name|
          alter_table(table_name) do |definition|
            definition.columns.delete(definition[column_name])
          end
        end
        */
    }

    /**
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $type
     * @param   array   $options
     */
    public function changeColumn($tableName, $columnName, $type, $options=array())
    {
        throw new Horde_Db_Exception('Not supported');
        /*@TODO
        alter_table(table_name) do |definition|
          include_default = options_include_default?(options)
          definition[column_name].instance_eval do
            self.type    = type
            self.limit   = options[:limit] if options.include?(:limit)
            self.default = options[:default] if include_default
            self.null    = options[:null] if options.include?(:null)
          end
        end
        */
    }

    /**
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $default
     */
    public function changeColumnDefault($tableName, $columnName, $default)
    {
        throw new Horde_Db_Exception('Not supported');
        /*@TODO
        alter_table(table_name) do |definition|
          definition[column_name].default = default
        end
        */
    }

    /**
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $newColumnName
     */
    public function renameColumn($tableName, $columnName, $newColumnName)
    {
        throw new Horde_Db_Exception('Not supported');
        /*@TODO
        alter_table(table_name, :rename => {column_name.to_s => new_column_name.to_s})
        */
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
        $sql = 'DROP INDEX '.$this->quoteColumnName($index);
        return $this->execute($sql);
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    protected function _defaultPrimaryKeyType()
    {
        if ($this->supportsAutoIncrement())
            return 'INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL';
        else
            return 'INTEGER PRIMARY KEY NOT NULL';
    }

    /*@TODO
        def alter_table(table_name, options = {}) #:nodoc:
          altered_table_name = "altered_#{table_name}"
          caller = lambda {|definition| yield definition if block_given?}

          transaction do
            move_table(table_name, altered_table_name,
              options.merge(:temporary => true))
            move_table(altered_table_name, table_name, &caller)
          end
        end

        def move_table(from, to, options = {}, &block) #:nodoc:
          copy_table(from, to, options, &block)
          drop_table(from)
        end

        def copy_table(from, to, options = {}) #:nodoc:
          options = options.merge(:id => !columns(from).detect{|c| c.name == 'id'}.nil?)
          create_table(to, options) do |definition|
            @definition = definition
            columns(from).each do |column|
              column_name = options[:rename] ?
                (options[:rename][column.name] ||
                 options[:rename][column.name.to_sym] ||
                 column.name) : column.name

              @definition.column(column_name, column.type,
                :limit => column.limit, :default => column.default,
                :null => column.null)
            end
            @definition.primary_key(primary_key(from)) if primary_key(from)
            yield @definition if block_given?
          end

          copy_table_indexes(from, to, options[:rename] || {})
          copy_table_contents(from, to,
            @definition.columns.map {|column| column.name},
            options[:rename] || {})
        end

        def copy_table_indexes(from, to, rename = {}) #:nodoc:
          indexes(from).each do |index|
            name = index.name
            if to == "altered_#{from}"
              name = "temp_#{name}"
            elsif from == "altered_#{to}"
              name = name[5..-1]
            end

            to_column_names = columns(to).map(&:name)
            columns = index.columns.map {|c| rename[c] || c }.select do |column|
              to_column_names.include?(column)
            end

            unless columns.empty?
              # index name can't be the same
              opts = { :name => name.gsub(/_(#{from})_/, "_#{to}_") }
              opts[:unique] = true if index.unique
              add_index(to, columns, opts)
            end
          end
        end

        def copy_table_contents(from, to, columns, rename = {}) #:nodoc:
          column_mappings = Hash[*columns.map {|name| [name, name]}.flatten]
          rename.inject(column_mappings) {|map, a| map[a.last] = a.first; map}
          from_columns = columns(from).collect {|col| col.name}
          columns = columns.find_all{|col| from_columns.include?(column_mappings[col])}
          quoted_columns = columns.map { |col| quote_column_name(col) } * ','

          quoted_to = quote_table_name(to)
          @connection.execute "SELECT * FROM #{quote_table_name(from)}" do |row|
            sql = "INSERT INTO #{quoted_to} (#{quoted_columns}) VALUES ("
            sql << columns.map {|col| quote row[column_mappings[col]]} * ', '
            sql << ')'
            @connection.execute sql
          end
        end
    */

}

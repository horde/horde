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
class Horde_Db_Adapter_Mysql_Schema extends Horde_Db_Adapter_Abstract_Schema
{
    /*##########################################################################
    # Quoting
    ##########################################################################*/

    /**
     * @return  string
     */
    public function quoteColumnName($name)
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    /**
     * @return  string
     */
    public function quoteTableName($name)
    {
        return str_replace('.', '`.`', $this->quoteColumnName($name));
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
            'primaryKey' => 'int(11) DEFAULT NULL auto_increment PRIMARY KEY',
            'string'     => array('name' => 'varchar',  'limit' => 255),
            'text'       => array('name' => 'text',     'limit' => null),
            'integer'    => array('name' => 'int',      'limit' => 11),
            'float'      => array('name' => 'float',    'limit' => null),
            'decimal'    => array('name' => 'decimal',  'limit' => null),
            'datetime'   => array('name' => 'datetime', 'limit' => null),
            'timestamp'  => array('name' => 'datetime', 'limit' => null),
            'time'       => array('name' => 'time',     'limit' => null),
            'date'       => array('name' => 'date',     'limit' => null),
            'binary'     => array('name' => 'blob',     'limit' => null),
            'boolean'    => array('name' => 'tinyint',  'limit' => 1),
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
        foreach ($this->selectAll('SHOW TABLES') as $row) {
            if ($table && $table != current($row)) { continue; }
            $dump = $this->selectOne('SHOW CREATE TABLE ' . $this->quoteTableName(current($row)));
            $creates[] = $dump['Create Table'] . ';';
        }
        return isset($creates) ? implode("\n\n", $creates) : null;
    }

    /**
     * Create the given db
     *
     * @param   string  $name
     */
    public function createDatabase($name)
    {
        return $this->execute("CREATE DATABASE `$name`");
    }

    /**
     * Drop the given db
     *
     * @param   string  $name
     */
    public function dropDatabase($name)
    {
        return $this->execute("DROP DATABASE IF EXISTS `$name`");
    }

    /**
     * Get the name of the current db
     *
     * @return  string
     */
    public function currentDatabase()
    {
        return $this->selectValue('SELECT DATABASE() AS db');
    }

    /**
     * Returns the database character set
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->showVariable('character_set_database');
    }

    /**
     * Returns the database collation strategy
     *
     * @return string
     */
    public function getCollation()
    {
        return $this->showVariable('collation_database');
    }

    /**
     * List of tables for the db
     *
     * @param   string  $name
     */
    public function tables($name=null)
    {
        return $this->selectValues('SHOW TABLES');
    }

    /**
     * List of indexes for the given table
     *
     * @param   string  $tableName
     * @param   string  $name
     */
    public function indexes($tableName, $name=null)
    {
        $indexes = array();
        $currentIndex = null;
        foreach ($this->select('SHOW KEYS FROM ' . $this->quoteTableName($tableName)) as $row) {
            if ($currentIndex != $row[2]) {
                if ($row[2] == 'PRIMARY') continue;
                $currentIndex = $row[2];
                $indexes[] = (object)array('table'   => $row[0],
                                           'name'    => $row[2],
                                           'unique'  => $row[1] == '0',
                                           'columns' => array());
            }
            $indexes[sizeof($indexes)-1]->columns[] = $row[4];
        }
        return $indexes;
    }

    /**
     * @param   string  $tableName
     * @param   string  $name
     */
    public function columns($tableName, $name=null)
    {
        // check cache
        $rows = @unserialize($this->_cache->get("tables/$tableName"));

        // query to build rows
        if (!$rows) {
            $rows = $this->selectAll('SHOW FIELDS FROM ' . $this->quoteTableName($tableName), $name);

            // write cache
            $this->_cache->set("tables/$tableName", serialize($rows));
        }

        // create columns from rows
        $columns = array();
        foreach ($rows as $row) {
            $columns[] = new Horde_Db_Adapter_Mysql_Column(
                $row[0], $row[4], $row[1], $row[2] == 'YES');
        }
        return $columns;
    }

    /**
     * Override createTable to return a Mysql Table Definition
     * param    string  $name
     * param    array   $options
     */
    public function createTable($name, $options=array())
    {
        $pk = isset($options['primaryKey']) && $options['primaryKey'] === false ? false : 'id';
        $tableDefinition =
            new Horde_Db_Adapter_Mysql_TableDefinition($name, $this, $options);
        if ($pk != false) {
            $tableDefinition->primaryKey($pk);
        }
        return $tableDefinition;
    }

    /**
     * @param   string  $name
     * @param   array   $options
     */
    public function endTable($name, $options=array())
    {
        $inno = array('options' => 'ENGINE=InnoDB');
        return parent::endTable($name, array_merge($inno, $options));
    }

    /**
     * @param   string  $name
     * @param   string  $newName
     */
    public function renameTable($name, $newName)
    {
        $this->_clearTableCache($name);

        return $this->execute('RENAME TABLE '.$this->quoteTableName($name).' TO '.$this->quoteTableName($newName));
    }

    /**
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $default
     */
    public function changeColumnDefault($tableName, $columnName, $default)
    {
        $this->_clearTableCache($tableName);

        $quotedTableName = $this->quoteTableName($tableName);
        $quotedColumnName = $this->quoteColumnName($columnName);

        $sql = "SHOW COLUMNS FROM $quotedTableName LIKE ".$this->quoteString($columnName);
        $res = $this->selectOne($sql);
        $currentType = $res['Type'];

        $default = $this->quote($default);
        $sql = "ALTER TABLE $quotedTableName CHANGE $quotedColumnName $quotedColumnName
                $currentType DEFAULT $default";
        return $this->execute($sql);
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

        $quotedTableName = $this->quoteTableName($tableName);
        $quotedColumnName = $this->quoteColumnName($columnName);

        if (!array_key_exists('default', $options)) {
            $row = $this->selectOne("SHOW COLUMNS FROM $quotedTableName LIKE ".$this->quoteString($columnName));
            $options['default'] = $row['Default'];
        }

        $limit     = !empty($options['limit'])     ? $options['limit']     : null;
        $precision = !empty($options['precision']) ? $options['precision'] : null;
        $scale     = !empty($options['scale'])     ? $options['scale']     : null;

        $typeSql = $this->typeToSql($type, $limit, $precision, $scale);

        $sql = "ALTER TABLE $quotedTableName CHANGE $quotedColumnName $quotedColumnName $typeSql";
        $sql = $this->addColumnOptions($sql, $options);
        $this->execute($sql);
    }

    /**
     * @param   string  $tableName
     * @param   string  $columnName
     * @param   string  $newColumnName
     */
    public function renameColumn($tableName, $columnName, $newColumnName)
    {
        $this->_clearTableCache($tableName);

        $quotedTableName = $this->quoteTableName($tableName);
        $quotedColumnName = $this->quoteColumnName($columnName);

        $sql = "SHOW COLUMNS FROM $quotedTableName LIKE ".$this->quoteString($columnName);
        $res = $this->selectOne($sql);
        $currentType = $res["Type"];

        $sql = "ALTER TABLE $quotedTableName CHANGE ".
                $quotedColumnName.' '.
                $this->quoteColumnName($newColumnName)." ".
                $currentType;
        return $this->execute($sql);
    }

    /**
     * SHOW VARIABLES LIKE 'name'
     *
     * @param   string  $name
     * @return  string
     */
    public function showVariable($name)
    {
        return $this->selectValue('SHOW VARIABLES LIKE '.$this->quoteString($name));
    }

    /**
     * Add AFTER option
     *
     * @param   string  $sql
     * @param   array   $options
     * @return  string
     */
    public function addColumnOptions($sql, $options)
    {
        $sql = parent::addColumnOptions($sql, $options);
        if (isset($options['after'])) {
            $sql .= " AFTER ".$this->quoteColumnName($options['after']);
        }
        return $sql;
    }

}

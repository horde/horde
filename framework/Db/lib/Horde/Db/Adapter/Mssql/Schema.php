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
class Horde_Db_Adapter_Mssql_Schema extends Horde_Db_Adapter_Base_Schema
{
    /*##########################################################################
    # Quoting
    ##########################################################################*/

    /**
     * @return  string
     */
    public function quoteColumnName($name)
    {
        return '[' . str_replace(']', ']]', $name) . ']';
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
            /* TODO, just put in nchar for unicode strings */
            'primaryKey' => 'int(11) DEFAULT NULL IDENTITY PRIMARY KEY',
            'string'     => array('name' => 'nchar',  'limit' => 255),
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
     */
    public function tables($name = null)
    {
        return 'SELECT name FROM sysobjects WHERE type = \'U\' ORDER BY name';
    }

    /**
     */
    protected function _limit($query, &$sql, &$bindParams)
    {
        if ($query->limit) {
            $orderby = stristr($sql, 'ORDER BY');
            if ($orderby !== false) {
                $sort = (stripos($orderby, 'DESC') !== false) ? 'DESC' : 'ASC';
                $order = str_ireplace('ORDER BY', '', $orderby);
                $order = trim(preg_replace('/ASC|DESC/i', '', $order));
            }

            $sql = preg_replace('/^SELECT /i', 'SELECT TOP ' . ($query->limit + $query->limitOffset) . ' ', $sql);

            $sql = 'SELECT * FROM (SELECT TOP ' . $query->limit . ' * FROM (' . $sql . ') AS inner_tbl';
            if ($orderby !== false) {
                $sql .= ' ORDER BY ' . $order . ' ';
                $sql .= (stripos($sort, 'ASC') !== false) ? 'DESC' : 'ASC';
            }
            $sql .= ') AS outer_tbl';
            if ($orderby !== false) {
                $sql .= ' ORDER BY ' . $order . ' ' . $sort;
            }
        }
    }

    /**
     * Get a description of the database table that $model is going to
     * reflect.
     */
    public function loadModel($model)
    {
        $tblinfo = $this->select('EXEC sp_columns @table_name = ' . $this->dml->quoteColumnName($model->table));
        while ($col = $tblinfo->fetch()) {
            if (strpos($col['type_name'], ' ') !== false) {
                list($type, $identity) = explode(' ', $col['type_name']);
            } else {
                $type = $col['type_name'];
                $identity = '';
            }

            $model->addField($col['column_name'], array('type' => $type,
                                                        'null' => !(bool)$col['is_nullable'] == 'NO',
                                                        'default' => $col['column_def']));
            if (strtolower($identity) == 'identity') {
                $model->key = $col['column_name'];
            }
        }
    }

    /**
     */
    protected function _lastInsertId($sequence)
    {
        return $this->selectOne('SELECT @@IDENTITY');
    }

    /**
     * Changes the column of a table.
     */
    public function changeColumn($tableName, $columnName, $type,
                                 $options = array())
    {
        parent::changeColumn($tableName, $columnName, $type, $options);

        if (!empty($options['autoincrement'])) {
            /* Can't alter column in table - need to create, remove old
             * column, and rename:
             * http://www.eggheadcafe.com/software/aspnet/30132263/alter-table-add-identity.aspx */
            // TODO: Better temp name?
            $this->addColumn($tableName, $columnName . '_temp', $type, $options);
            $this->removeColumn($tableName, $columnName);
            $this->renameColumn($tableName, $columnName . '_temp', $columnName);
        }
    }

    /**
     * Add additional column options.
     *
     * @param   string  $sql
     * @param   array   $options
     * @return  string
     */
    public function addColumnOptions($sql, $options)
    {
        $sql = parent::addColumnOptions($sql, $options);
        if (!empty($options['autoincrement'])) {
            $sql .= ' IDENTITY';
        }
        return $sql;
    }


}

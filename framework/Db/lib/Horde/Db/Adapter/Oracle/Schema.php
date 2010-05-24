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
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Oracle_Schema extends Horde_Db_Adapter_Base_Schema
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

    /**
     * Adds a new column to the named table.
     * See TableDefinition#column for details of the options you can use.
     *
     * @throws Horde_Db_Exception
     */
    public function addColumn($tableName, $columnName, $type,
                              $options = array())
    {
        parent::addColumn($tableName, $columnName, $type, $options);

        if (!empty($options['autoincrement'])) {
            $this->_autoSequenceColumn($tableName, $columnName);
        }
    }

    /**
     * Changes the column of a table.
     *
     * @throws Horde_Db_Exception
     */
    public function changeColumn($tableName, $columnName, $type,
                                 $options = array())
    {
        parent::changeColumn($tableName, $columnName, $type, $options);

        if (!empty($options['autoincrement'])) {
            $this->_autoSequenceColumn($tableName, $columnName);
        }
    }

    /**
     * Add auto-sequencing to a column.
     *
     * @throws Horde_Db_Exception
     */
    protected function _autoSequenceColumn($tableName, $columnName)
    {
        $seq_name = $tableName . '_' . $columnName . '_seq';
        $trigger_name = $tableName . '_' . $columnName . '_trigger';

        $this->beginDbTransaction();
        $this->executeWrite('CREATE SEQUENCE ' . $seq_name);
        $this->executeWrite(
            'CREATE TRIGGER ' . $trigger_name . ' ' .
            'BEFORE INSERT ON ' . $this->quoteTableName($tableName) . ' ' .
            'FOR EACH ROW '
            'BEGIN '
            'SELECT ' . $seq_name . '.nextval INTO :new.' . $columnName . ' FROM dual; END'
        );
        $this->commitDbTransaction();
    }

}

<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Oracle_TableDefinition extends Horde_Db_Adapter_Base_TableDefinition
{
    protected $_createTrigger = false;

    /**
     * Adds a new column to the table definition.
     *
     * @param string $type    Column type, one of:
     *                        autoincrementKey, string, text, integer, float,
     *                        datetime, timestamp, time, date, binary, boolean.
     * @param array $options  Column options:
     *                        - limit: (integer) Maximum column length (string,
     *                          text, binary or integer columns only)
     *                        - default: (mixed) The column's default value.
     *                          You cannot explicitly set the default value to
     *                          NULL. Simply leave off this option if you want
     *                          a NULL default value.
     *                        - null: (boolean) Whether NULL values are allowed
     *                          in the column.
     *                        - precision: (integer) The number precision
     *                          (float columns only).
     *                        - scale: (integer) The number scaling (float
     *                          columns only).
     *                        - unsigned: (boolean) Whether the column is an
     *                          unsigned number (integer columns only).
     *                        - autoincrement: (boolean) Whether the column is
     *                          an autoincrement column. Restrictions are
     *                          RDMS specific.
     *
     * @return Horde_Db_Adapter_Base_TableDefinition  This object.
     */
    public function column($name, $type, $options = array())
    {
        parent::column($name, $type, $options);

        if ($type == 'autoincrementKey') {
            $this->_createTrigger = $name;
        }

        return $this;
    }

    /**
     * Wrap up table creation block & create the table
     */
    public function end()
    {
        parent::end();
        if ($this->_createTrigger) {
            $id = $this->_name . '_' . $this->_createTrigger;
            $this->_base->execute(sprintf(
                'CREATE SEQUENCE %s_seq',
                $id
            ));
            $this->_base->execute(sprintf(
                'CREATE OR REPLACE TRIGGER %s_trig BEFORE INSERT ON %s FOR EACH ROW BEGIN SELECT %s_seq.NEXTVAL INTO :NEW.%s FROM dual; END;',
                $id,
                $this->_name,
                $id,
                $this->_createTrigger
            ));
        }
    }
}

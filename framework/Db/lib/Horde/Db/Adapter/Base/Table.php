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
class Horde_Db_Adapter_Base_Table implements ArrayAccess, IteratorAggregate
{
    protected $_name;
    protected $_primaryKey;
    protected $_columns;
    protected $_indexes;


    /*##########################################################################
    # Construct/Destruct
    ##########################################################################*/

    /**
     * Construct
     *
     * @param   string  $name     The table's name, such as <tt>supplier_id</tt> in <tt>supplier_id int(11)</tt>.
     */
    public function __construct($name, $primaryKey, $columns, $indexes)
    {
        $this->_name       = $name;
        $this->_primaryKey = $primaryKey;
        $this->_columns    = $columns;
        $this->_indexes    = $indexes;
    }


    /*##########################################################################
    # Accessor
    ##########################################################################*/

    /**
     * @return  string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return  mixed
     */
    public function getPrimaryKey()
    {
        return $this->_primaryKey;
    }

    /**
     * @return  array
     */
    public function getColumns()
    {
        return $this->_columns;
    }

    /**
     * @return  Horde_Db_Adapter_Base_Column
     */
    public function getColumn($column)
    {
        return isset($this->_columns[$column]) ? $this->_columns[$column] : null;
    }

    /**
     * @return  array
     */
    public function getColumnNames()
    {
        $names = array();
        foreach ($this->_columns as $column) {
            $names[] = $column->getName();
        }
        return $names;
    }

    /**
     * @return  array
     */
    public function getIndexes()
    {
        return $this->_indexes;
    }

    /**
     * @return  array
     */
    public function getIndexNames()
    {
        $names = array();
        foreach ($this->_indexes as $index) {
            $names[] = $index->getName();
        }
        return $names;
    }


    /*##########################################################################
    # Object composition
    ##########################################################################*/

    public function __get($key)
    {
        return $this->getColumn($key);
    }

    public function __isset($key)
    {
        return isset($this->_columns[$key]);
    }


    /*##########################################################################
    # ArrayAccess
    ##########################################################################*/

    /**
     * ArrayAccess: Check if the given offset exists
     *
     * @param   int     $offset
     * @return  boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->_columns[$column]);
    }

    /**
     * ArrayAccess: Return the value for the given offset.
     *
     * @param   int     $offset
     * @return  object  {@link {@Horde_Db_Adapter_Base_ColumnDefinition}
     */
    public function offsetGet($offset)
    {
        return $this->getColumn($offset);
    }

    /**
     * ArrayAccess: Set value for given offset
     *
     * @param   int     $offset
     * @param   mixed   $value
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * ArrayAccess: remove element
     *
     * @param   int     $offset
     */
    public function offsetUnset($offset)
    {
    }


    /*##########################################################################
    # IteratorAggregate
    ##########################################################################*/

    public function getIterator()
    {
        return new ArrayIterator($this->_columns);
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

}

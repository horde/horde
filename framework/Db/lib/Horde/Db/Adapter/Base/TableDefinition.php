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
class Horde_Db_Adapter_Base_TableDefinition implements ArrayAccess, IteratorAggregate
{
    protected $_name    = null;
    protected $_base    = null;
    protected $_options = null;
    protected $_columns = null;
    protected $_primaryKey = null;

    protected $_columntypes = array('string', 'text', 'integer', 'float',
        'datetime', 'timestamp', 'time', 'date', 'binary', 'boolean');

    /**
     * Class Constructor
     *
     * @param  string  $name
     * @param  Horde_Db_Adapter_Base_Schema  $base
     * @param  array   $options
     */
    public function __construct($name, $base, $options=array())
    {
        $this->_name    = $name;
        $this->_base    = $base;
        $this->_options = $options;
        $this->_columns = array();
    }

    /**
     * @return  string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return  array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * @param   string  $name
     */
    public function primaryKey($name)
    {
        if (is_scalar($name) && $name !== false) {
            $natives = $this->_native();
            $this->column($name, $natives['primaryKey']);
        }

        $this->_primaryKey = $name;
    }

    /**
     * Instantiates a new column for the table.
     * The +type+ parameter must be one of the following values:
     * <tt>:primary_key</tt>, <tt>:string</tt>, <tt>:text</tt>,
     * <tt>:integer</tt>, <tt>:float</tt>, <tt>:datetime</tt>,
     * <tt>:timestamp</tt>, <tt>:time</tt>, <tt>:date</tt>,
     * <tt>:binary</tt>, <tt>:boolean</tt>.
     *
     * Available options are (none of these exists by default):
     * * <tt>:limit</tt>:
     *   Requests a maximum column length (<tt>:string</tt>, <tt>:text</tt>,
     *   <tt>:binary</tt> or <tt>:integer</tt> columns only)
     * * <tt>:default</tt>:
     *   The column's default value.  You cannot explicitly set the default
     *   value to +NULL+.  Simply leave off this option if you want a +NULL+
     *   default value.
     * * <tt>:null</tt>:
     *   Allows or disallows +NULL+ values in the column.  This option could
     *   have been named <tt>:null_allowed</tt>.
     * * <tt>:precision</tt>
     *   TODO
     * * <tt>:scale</tt>
     *   TODO
     * * <tt>:unsigned</tt>
     *   TODO
     * * <tt>:autoincrement</tt>
     *   TODO
     *
     * This method returns <tt>self</tt>.
     *
     * ===== Examples
     *  # Assuming def is an instance of TableDefinition
     *  def.column(:granted, :boolean)
     *    #=> granted BOOLEAN
     *
     *  def.column(:picture, :binary, :limit => 2.megabytes)
     *    #=> picture BLOB(2097152)
     *
     *  def.column(:sales_stage, :string, :limit => 20, :default => 'new', :null => false)
     *    #=> sales_stage VARCHAR(20) DEFAULT 'new' NOT NULL
     *
     * @return  TableDefinition
     */
    public function column($name, $type, $options = array())
    {
        $column = $this->_base->makeColumnDefinition($this->_base, $name, $type);

        $column->setLimit(isset($options['limit'])         ? $options['limit']     : null);
        $column->setPrecision(isset($options['precision']) ? $options['precision'] : null);
        $column->setScale(isset($options['scale'])         ? $options['scale']     : null);
        $column->setUnsigned(isset($options['unsigned'])   ? $options['unsigned']  : null);
        $column->setDefault(isset($options['default'])     ? $options['default']   : null);
        $column->setNull(isset($options['null'])           ? $options['null']      : null);
        $column->setAutoIncrement(isset($options['autoincrement']) ? $options['autoincrement'] : null);

        $this[$name] ? $this[$name] = $column : $this->_columns[] = $column;
        return $this;
    }

    /**
     * Adds created_at and updated_at columns to the table.
     */
    public function timestamps()
    {
        return $this->column('created_at', 'datetime')
                    ->column('updated_at', 'datetime');
    }

    /**
     * Add one or several references to foreign keys
     *
     * This method returns <tt>self</tt>.
     */
    public function belongsTo($columns)
    {
        if (!is_array($columns)) { $columns = array($columns); }
        foreach ($columns as $col) {
            $this->column($col . '_id', 'integer');
        }

        return $this;
    }

    /**
     * Alias for the belongsTo() method
     *
     * This method returns <tt>self</tt>.
     */
    public function references($columns)
    {
        return $this->belongsTo($columns);
    }

    /**
     * Use __call to provide shorthand column creation ($this->integer(), etc.)
     */
    public function __call($method, $arguments)
    {
        if (!in_array($method, $this->_columntypes)) {
            throw new BadMethodCallException('Call to undeclared method "'.$method.'"');
        } elseif (count($arguments) > 0 && count($arguments) < 3) {
            return $this->column($arguments[0], $method,
                                 isset($arguments[1]) ? $arguments[1] : array());
        } else {
            throw new BadMethodCallException('Method "'.$method.'" takes two arguments');
        }
    }

    /**
     * Wrap up table creation block & create the table
     */
    public function end()
    {
        return $this->_base->endTable($this);
    }

    /**
     * Returns a String whose contents are the column definitions
     * concatenated together.  This string can then be pre and appended to
     * to generate the final SQL to create the table.
     *
     * @return  string
     */
    public function toSql()
    {
        $cols = array();
        foreach ($this->_columns as $col) {
            $cols[] = $col->toSql();
        }
        $sql = '  ' . implode(", \n  ", $cols);

        // Specify composite primary keys as well
        if (is_array($this->_primaryKey)) {
            $pk = array();
            foreach ($this->_primaryKey as $pkColumn) {
                $pk[] = $this->_base->quoteColumnName($pkColumn);
            }
            $sql .= ", \n  PRIMARY KEY(" . implode(', ', $pk) . ')';
        }

        return $sql;
    }

    public function __toString()
    {
        return $this->toSql();
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
        foreach ($this->_columns as $column) {
            if ($column->getName() == $offset) return true;
        }
        return false;
    }

    /**
     * ArrayAccess: Return the value for the given offset.
     *
     * @param   int     $offset
     * @return  object  {@link {@Horde_Db_Adapter_Base_ColumnDefinition}
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        foreach ($this->_columns as $column) {
            if ($column->getName() == $offset) {
                return $column;
            }
        }
    }

    /**
     * ArrayAccess: Set value for given offset
     *
     * @param   int     $offset
     * @param   mixed   $value
     */
    public function offsetSet($offset, $value)
    {
        foreach ($this->_columns as $key=>$column) {
            if ($column->getName() == $offset) {
                $this->_columns[$key] = $value;
            }
        }
    }

    /**
     * ArrayAccess: remove element
     *
     * @param   int     $offset
     */
    public function offsetUnset($offset)
    {
        foreach ($this->_columns as $key=>$column) {
            if ($column->getName() == $offset) {
                unset($this->_columns[$key]);
            }
        }
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

    /**
     * Get the types
     */
    protected function _native()
    {
        return $this->_base->nativeDatabaseTypes();
    }

}

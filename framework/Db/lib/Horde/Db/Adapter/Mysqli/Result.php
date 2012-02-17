<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Mysqli_Result implements Iterator
{
    /**
     * @var Horde_Db_Adapter
     */
    protected $_adapter;

    /**
     * @var string
     */
    protected $_sql;

    /**
     * @var mixed
     */
    protected $_arg1;

    /**
     * @var string
     */
    protected $_arg2;

    /**
     * Result resource
     * @var mysqli_result
     */
    protected $_result;

    /**
     * Current row
     * @var array
     */
    protected $_current;

    /**
     * Current offset
     * @var integer
     */
    protected $_index;

    /**
     * Are we at the end of the result?
     * @var boolean
     */
    protected $_eof;

    /**
     * Which kind of keys to use for results.
     */
    protected $_fetchMode = MYSQLI_ASSOC;

    /**
     * Constructor
     *
     * @param   Horde_Db_Adapter $adapter
     * @param   string  $sql
     * @param   mixed   $arg1  Either an array of bound parameters or a query name.
     * @param   string  $arg2  If $arg1 contains bound parameters, the query name.
     */
    public function __construct($adapter, $sql, $arg1 = null, $arg2 = null)
    {
        $this->_adapter = $adapter;
        $this->_sql = $sql;
        $this->_arg1 = $arg1;
        $this->_arg2 = $arg2;
    }

    /**
     * Destructor - release any resources.
     */
    public function __destruct()
    {
        if ($this->_result) {
            unset($this->_result);
        }
    }

    /**
     * Implementation of the rewind() method for iterator.
     */
    public function rewind()
    {
        if ($this->_result) {
            unset($this->_result);
        }
        $this->_current = null;
        $this->_index = null;
        $this->_eof = true;
        $this->_result = $this->_adapter->execute($this->_sql, $this->_arg1, $this->_arg2);

        $this->next();
    }

    /**
     * Implementation of the current() method for iterator.
     *
     * @return mixed The current row, or null if no rows.
     */
    public function current()
    {
        if (is_null($this->_result)) {
            $this->rewind();
        }
        return $this->_current;
    }

    /**
     * Implementation of the key() method for iterator.
     *
     * @return mixed The current row number (starts at 0), or NULL if no rows
     */
    public function key()
    {
        if (is_null($this->_result)) {
            $this->rewind();
        }
        return $this->_index;
    }

    /**
     * Implementation of the next() method.
     *
     * @return array|null The next row in the resultset or null if there are no
     * more results.
     */
    public function next()
    {
        if (is_null($this->_result)) {
            $this->rewind();
        }

        if ($this->_result) {
            $row = $this->_result->fetch_array($this->_fetchMode);
            if (!$row) {
                $this->_eof = true;
            } else {
                $this->_eof = false;

                if (is_null($this->_index)) {
                    $this->_index = 0;
                } else {
                    ++$this->_index;
                }

                $this->_current = $row;
            }
        }

        return $this->_current;
    }

    /**
     * Returns the current row and advances the recordset one row.
     *
     * @param integer $fetchmode  The default fetch mode for this result. One
     *                            of the Horde_Db::FETCH_* constants.
     */
    public function fetch($fetchmode = Horde_Db::FETCH_ASSOC)
    {
        if (!$this->valid()) {
            return null;
        }
        $this->setFetchMode($fetchmode);
        $row = $this->current();
        $this->next();
        return $row;
    }

    /**
     * Implementation of the valid() method for iterator
     *
     * @return boolean Whether the iteration is valid
     */
    public function valid()
    {
        if (is_null($this->_result)) {
            $this->rewind();
        }
        return !$this->_eof;
    }

    /**
     * Sets the default fetch mode for this result.
     *
     * @param integer $fetchmode  One of the Horde_Db::FETCH_* constants.
     */
    public function setFetchMode($fetchmode)
    {
        $map = array(Horde_Db::FETCH_ASSOC => MYSQL_ASSOC,
                     Horde_Db::FETCH_NUM   => MYSQL_NUM,
                     Horde_Db::FETCH_BOTH  => MYSQL_BOTH);
        $this->_fetchMode = $map[$fetchmode];
    }

    /**
     * Returns the number of columns in the result set
     *
     * @return integer  Number of columns.
     */
    public function columnCount()
    {
        if (is_null($this->_result)) {
            $this->rewind();
        }
        return $this->_result->field_count;
    }
}

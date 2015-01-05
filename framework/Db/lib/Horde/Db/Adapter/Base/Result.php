<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */

/**
 * This class represents the result set of a SELECT query.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */
abstract class Horde_Db_Adapter_Base_Result implements Iterator
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
     * Result resource.
     *
     * @var resource
     */
    protected $_result;

    /**
     * Current row.
     *
     * @var array
     */
    protected $_current;

    /**
     * Current offset.
     *
     * @var integer
     */
    protected $_index;

    /**
     * Are we at the end of the result?
     *
     * @var boolean
     */
    protected $_eof;

    /**
     * Which kind of keys to use for results.
     */
    protected $_fetchMode = Horde_Db::FETCH_ASSOC;

    /**
     * Constructor.
     *
     * @param Horde_Db_Adapter $adapter  A driver instance.
     * @param string $sql                A SQL query.
     * @param mixed $arg1                Either an array of bound parameters or
     *                                   a query name.
     * @param string $arg2               If $arg1 contains bound parameters,
     *                                   the query name.
     */
    public function __construct($adapter, $sql, $arg1 = null, $arg2 = null)
    {
        $this->_adapter = $adapter;
        $this->_sql = $sql;
        $this->_arg1 = $arg1;
        $this->_arg2 = $arg2;
    }

    /**
     * Destructor.
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
        $this->_result = $this->_adapter->execute(
            $this->_sql, $this->_arg1, $this->_arg2
        );

        $this->next();
    }

    /**
     * Implementation of the current() method for Iterator.
     *
     * @return array  The current row, or null if no rows.
     */
    public function current()
    {
        if (is_null($this->_result)) {
            $this->rewind();
        }
        return $this->_current;
    }

    /**
     * Implementation of the key() method for Iterator.
     *
     * @return mixed  The current row number (starts at 0), or null if no rows.
     */
    public function key()
    {
        if (is_null($this->_result)) {
            $this->rewind();
        }
        return $this->_index;
    }

    /**
     * Implementation of the next() method for Iterator.
     *
     * @return array|null  The next row in the resultset or null if there are
     *                     no more results.
     */
    public function next()
    {
        if (is_null($this->_result)) {
            $this->rewind();
        }

        if ($this->_result) {
            $row = $this->_fetchArray();
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
     * Implementation of the valid() method for Iterator.
     *
     * @return boolean  Whether the iteration is valid.
     */
    public function valid()
    {
        if (is_null($this->_result)) {
            $this->rewind();
        }
        return !$this->_eof;
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
     * Sets the default fetch mode for this result.
     *
     * @param integer $fetchmode  One of the Horde_Db::FETCH_* constants.
     */
    public function setFetchMode($fetchmode)
    {
        $this->_fetchMode = $fetchmode;
    }

    /**
     * Returns the number of columns in the result set.
     *
     * @return integer  Number of columns.
     */
    public function columnCount()
    {
        if (is_null($this->_result)) {
            $this->rewind();
        }
        return $this->_columnCount();
    }

    /**
     * Returns a row from a resultset.
     *
     * @return array|boolean  The next row in the resultset or false if there
     *                        are no more results.
     */
    abstract protected function _fetchArray();

    /**
     * Returns the number of columns in the result set.
     *
     * @return integer  Number of columns.
     */
    abstract protected function _columnCount();
}

<?php
/**
 * @category Horde
 * @package  Rdo
 */

/**
 * Iterator for collections of Rdo objects.
 *
 * @TODO implement ArrayAccess as well?
 *
 * @category Horde
 * @package  Rdo
 */
class Horde_Rdo_List implements ArrayAccess, Iterator, Countable
{
    /**
     * Rdo Query
     * @var mixed
     */
    protected $_query;

    /**
     * Rdo Mapper
     * @var Horde_Rdo_Mapper
     */
    protected $_mapper;

    /**
     * SQL query to run
     * @var string
     */
    protected $_sql;

    /**
     * Bind parameters
     * @var array
     */
    protected $_bindParams = array();

    /**
     * Result resource
     * @var Iterator
     */
    protected $_result;

    /**
     * Current object
     * @var Horde_Rdo_Base
     */
    protected $_current;

    /**
     * Current list offset.
     * @var integer
     */
    protected $_index;

    /**
     * Are we at the end of the list?
     * @var boolean
     */
    protected $_eof;

    /**
     * The number of objects in the list.
     * @var integer
     */
    protected $_count;

    /**
     * Constructor.
     *
     * @param mixed $query The query to run when results are
     * requested. Can be a Horde_Rdo_Query object, a literal SQL
     * query, or a tuple containing an SQL string and an array of bind
     * parameters to use.
     * @param Horde_Rdo_Mapper $mapper Mapper to create objects for this list from.
     */
    public function __construct($query, $mapper = null)
    {
        if ($query instanceof Horde_Rdo_Query) {
            // Make sure we have a Mapper object, which can be passed
            // implicitly by being set on the Query.
            if (!$mapper) {
                if (!$query->mapper) {
                    throw new Horde_Rdo_Exception('Mapper must be set on the Query object or explicitly passed.');
                }
                $mapper = $query->mapper;
            }

            // Convert the query into a SQL statement and an array of
            // bind parameters.
            list($this->_sql, $this->_bindParams) = $query->getQuery();
        } elseif (is_string($query)) {
            // Straight SQL query, empty bind parameters array.
            $this->_sql = $query;
            $this->_bindParams = array();
        } else {
            // $query is already an array with SQL and bind parameters.
            list($this->_sql, $this->_bindParams) = $query;
        }

        if (!$mapper) {
            throw new Horde_Rdo_Exception('Mapper must be provided either explicitly or in a Query object');
        }

        $this->_query = $query;
        $this->_mapper = $mapper;
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
        $this->_result = $this->_mapper->adapter->select($this->_sql, $this->_bindParams);

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
     * @return Horde_Rdo_Base|null The next Rdo object in the set or
     * null if no more results.
     */
    public function next()
    {
        if (is_null($this->_result)) {
            $this->rewind();
        }

        if ($this->_result) {
            $row = $this->_result->fetch();
            if (!$row) {
                $this->_eof = true;
            } else {
                $this->_eof = false;

                if (is_null($this->_index)) {
                    $this->_index = 0;
                } else {
                    ++$this->_index;
                }

                $this->_current = $this->_mapper->map($row);
            }
        }

        return $this->_current;
    }

    /**
     * Implementation of the offsetExists() method for ArrayAccess
     * This method is executed when using isset() or empty() on Horde_Rdo_List objects
     * @param integer $offset  The offset to check.
     *
     * @return boolean  Whether or not an offset exists.
     */
    public function offsetExists($offset)
    {
        $query = Horde_Rdo_Query::create($this->_query);
        $query->limit(1, $offset);
        return $this->_mapper->exists($query);
    }

    /**
     * Implementation of the offsetGet() method for ArrayAccess
     * This method is executed when using isset() or empty() on Horde_Rdo_List objects
     * @param integer $offset  The offset to retrieve.
     *
     * @return Horde_Rdo_Base  An entity object at the offset position or null
     */
    public function offsetGet($offset)
    {
        $query = Horde_Rdo_Query::create($this->_query);
        $query->limit(1, $offset);
        return $this->_mapper->find($query)->current();
    }

    /**
     * Not implemented.
     *
     * Stub of the offsetSet() method for ArrayAccess
     * This method is executed when adding an item to the Horde_Rdo_List
     * @param Horde_Rdo_Base $item  The item to add to the list.
     * @param integer $offset  The offset to add or change.
     * @param Horde_Rdo_Base $offset  The item to add to the list.
     * 
     * @return Horde_Rdo_Base  An entity object at the offset position or null
     */
    public function offsetSet($offset, $item)
    {
        new Horde_Rdo_Exception('You cannot add objects to a result set');
    }

    /**
     * Not implemented.
     *
     * Stub of the offsetUnset() method for ArrayAccess
     * This method is executed when calling unset on a Horde_Rdo_List index
     * @param Horde_Rdo_Base $item  The item to add to the list.
     * @param integer $offset  The offset to unset.
     *
     * @return Horde_Rdo_Base  An entity object at the offset position or null
     */
    public function offsetUnset($offset)
    {
        new Horde_Rdo_Exception('You cannot remove objects from a result set');
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
     * Implementation of count() for Countable
     *
     * @return integer Number of elements in the list
     */
    public function count()
    {
        if (is_null($this->_count)) {
            $this->_count = $this->_mapper->count($this->_query);
        }
        return $this->_count;
    }

}

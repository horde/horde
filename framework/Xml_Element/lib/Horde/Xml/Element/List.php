<?php
/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Xml_Element
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Xml_Element
 */
abstract class Horde_Xml_Element_List extends Horde_Xml_Element implements Countable, Iterator
{
    /**
     * Cache of list items.
     * @var array
     */
    protected $_listItems;

    /**
     * Current index on the collection of list items for the Iterator
     * implementation.
     * @var integer
     */
    protected $_listItemIndex = 0;

    /**
     * The classname for individual list items.
     * @var string
     */
    protected $_listItemClassName = 'Horde_Xml_Element';

    /**
     * Ensure that $_listItems is populated by calling the concrete implementation's
     * _buildItemsCache() method.
     */
    public function __wakeup()
    {
        parent::__wakeup();

        $this->_listItems = $this->_buildListItemCache();
    }

    /**
     * Called by __wakeup to cache list items. Must be implemented in the
     * extending class to return the array of list items.
     * @return array
     */
    abstract protected function _buildListItemCache();

    /**
     * Get the number of items in this list.
     *
     * @return integer Item count.
     */
    public function count()
    {
        return count($this->_listItems);
    }

    /**
     * Required by the Iterator interface.
     *
     * @internal
     */
    public function rewind()
    {
        $this->_listItemIndex = 0;
    }

    /**
     * Required by the Iterator interface.
     *
     * @internal
     *
     * @return mixed The current row, or null if no rows.
     */
    public function current()
    {
        return new $this->_listItemClassName(
            $this->_listItems[$this->_listItemIndex]);
    }

    /**
     * Required by the Iterator interface.
     *
     * @internal
     *
     * @return mixed The current row number (starts at 0), or null if no rows
     */
    public function key()
    {
        return $this->_listItemIndex;
    }

    /**
     * Required by the Iterator interface.
     *
     * @internal
     *
     * @return mixed The next row, or null if no more rows.
     */
    public function next()
    {
        ++$this->_listItemIndex;
    }

    /**
     * Required by the Iterator interface.
     *
     * @internal
     *
     * @return boolean Whether the iteration is valid
     */
    public function valid()
    {
        return (0 <= $this->_listItemIndex && $this->_listItemIndex < count($this->_listItems));
    }

}

<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2015-2016 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Abstract iterator filter for the Storage object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015-2016 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
abstract class Ingo_Storage_FilterIterator
extends FilterIterator
{
    /**
     * Filter list.
     *
     * @var array
     */
    protected $_filters = array();

    /**
     * Create a filter iterator.
     *
     * @param Ingo_Storage $storage  A storage object.
     * @param array $filter          A list of rules (classnames) to filter.
     *
     * @return Ingo_Storage_FilterIterator  Filtered iterator.
     */
    public static function create(
        Ingo_Storage $storage, array $filter = array()
    )
    {
        $iterator =  new static($storage->getIterator());
        $iterator->setFilter($filter);

        return $iterator;
    }

    /**
     * Sets the filters list.
     *
     * @param array $filter  Filters list.
     */
    public function setFilter(array $filter = array())
    {
        $this->_filters = $filter;
    }

}

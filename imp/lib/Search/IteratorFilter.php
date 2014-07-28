<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Iterator filter for the IMP_Search object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Search_IteratorFilter extends FilterIterator
{
    /* Bitmask filters for iterator. */
    const FILTER = 1;
    const QUERY = 2;
    const VFOLDER = 4;
    const ALL = 8;
    const DISABLED = 16;

    /**
     * Filtering mask.
     *
     * @var integer
     */
    protected $_mask = 0;

    /**
     * Create the iterator and set the filter mask.
     *
     * @param integer $mask  See setFilter().
     *
     * @return IMP_Search_IteratorFilter  Iterator.
     */
    static public function create($mask = 0)
    {
        global $injector;

        $iterator = new self(
            $injector->getInstance('IMP_Search')->getIterator()
        );
        $iterator->setFilter($mask);

        return $iterator;
    }

    /**
     * Set the iterator filter and reset the internal pointer.
     *
     * @param integer $mask  A mask with the following possible elements:
     *   - self::DISABLED: List even if disabled.
     *   - self::FILTER: List filters.
     *   - self::QUERY: List search queries.
     *   - self::VFOLDER: List virtual folders.
     *   - self::ALL: List filter, search queries, and virtual folders.
     */
    public function setFilter($mask = 0)
    {
        $this->_mask = intval($mask);
        if ($this->_mask & self::ALL) {
            $this->_mask |= self::FILTER | self::QUERY | self::VFOLDER;
        }
        $this->rewind();
    }

    /* FilterIterator method. */

    /**
     */
    public function accept()
    {
        $ob = $this->current();

        if ($ob->enabled || ($this->_mask & self::DISABLED)) {
            if (($this->_mask & self::FILTER) &&
                ($ob instanceof IMP_Search_Filter)) {
                return true;
            }

            if (($this->_mask & self::VFOLDER) &&
                ($ob instanceof IMP_Search_Vfolder)) {
                return true;
            }

            if (($this->_mask & self::QUERY) &&
                !($ob instanceof IMP_Search_Filter) &&
                !($ob instanceof IMP_Search_Vfolder)) {
                return true;
            }
        }

        return false;
    }

}

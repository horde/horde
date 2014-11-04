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
 * Iterator filter for IMP_Ftree.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_IteratorFilter implements Iterator
{
    /**
     * Filter mask constants.
     *   - CHILDREN: Don't include child elements.
     *   - CONTAINERS: Don't include container elements.
     *   - EXPANDED: Don't include unexpanded mailboxes.
     *   - NONIMAP: Don't include non-IMAP elements.
     *   - POLLED: Don't include non-polled elements.
     *   - REMOTE: Don't include remote accounts.
     *   - SPECIALMBOXES: Don't include special mailboxes.
     *   - UNSUB: Don't include unsubscribed elements.
     *   - VFOLDER: Don't include Virtual Folders.
     */
    const CHILDREN = 1;
    const CONTAINERS = 2;
    const EXPANDED = 4;
    const NONIMAP = 8;
    const POLLED = 16;
    const REMOTE = 32;
    const SPECIALMBOXES = 64;
    const UNSUB = 128;
    const VFOLDER = 256;

    /**
     * Master iterator object.
     *
     * @var Iterator
     */
    public $iterator;

    /**
     * A list of mailboxes to filter out.
     *
     * @var array
     */
    public $mboxes = array();

    /**
     * Filtered iterator used for actual iteration.
     *
     * @var Iterator
     */
    private $_filter;

    /**
     * Filter mask.
     *
     * @var integer
     */
    protected $_mask;

    /**
     * Constructor.
     *
     * @param Iterator $i  Master iterator object.
     */
    public function __construct($i = null)
    {
        $this->iterator = $i;

        $this->_filter = new EmptyIterator();
        $this->_mask = self::UNSUB;
    }

    /**
     * Add filter masks.
     *
     * @param mixed $mask  Filter masks to add.
     */
    public function add($mask)
    {
        foreach ((is_array($mask) ? $mask : array($mask)) as $val) {
            $this->_mask |= $val;
        }
    }

    /**
     * Remove filter masks.
     *
     * @param mixed $mask  Filter masks to remove.
     */
    public function remove($mask)
    {
        foreach ((is_array($mask) ? $mask : array($mask)) as $val) {
            $this->_mask &= ~$val;
        }
    }

    /* Iterator methods. */

    /**
     */
    public function current()
    {
        return $this->_filter->current();
    }

    /**
     */
    public function key()
    {
        return $this->_filter->key();
    }

    /**
     */
    public function next()
    {
        $this->_filter->next();
    }

    /**
     */
    public function rewind()
    {
        if (!isset($this->iterator)) {
            throw new InvalidArgumentException('Missing iterator.');
        }

        $i = $this->iterator;
        if (!($i instanceof RecursiveIterator)) {
            $i = $i->getIterator();
        }

        /* Need to add RecursiveIteratorFilters first. */
        $filters = array(
            self::CHILDREN => 'IMP_Ftree_IteratorFilter_Children',
            self::EXPANDED => 'IMP_Ftree_IteratorFilter_Expanded',
            self::REMOTE => 'IMP_Ftree_IteratorFilter_Remote'
        );

        foreach ($filters as $key => $val) {
            if ($this->_mask & $key) {
                $i = new $val($i);
            }
        }

        $i = new RecursiveIteratorIterator(
            $i,
            RecursiveIteratorIterator::SELF_FIRST
        );

        /* Now we can add regular FilterIterators. */
        $filters = array(
            self::CONTAINERS => 'IMP_Ftree_IteratorFilter_Containers',
            self::NONIMAP => 'IMP_Ftree_IteratorFilter_Nonimap',
            self::POLLED => 'IMP_Ftree_IteratorFilter_Polled',
            self::SPECIALMBOXES => 'IMP_Ftree_IteratorFilter_Special',
            self::UNSUB => 'IMP_Ftree_IteratorFilter_Subscribed',
            self::VFOLDER => 'IMP_Ftree_IteratorFilter_Vfolder'
        );

        foreach ($filters as $key => $val) {
            if ($this->_mask & $key) {
                $i = new $val($i);
            }
        }

        /* Mailbox filter is handled separately. */
        if (!empty($this->mboxes)) {
            $i = new IMP_Ftree_IteratorFilter_Mailboxes($i);
            $i->mboxes = $this->mboxes;
        }

        $i->rewind();

        $this->_filter = $i;
    }

    /**
     */
    public function valid()
    {
        return $this->_filter->valid();
    }

}

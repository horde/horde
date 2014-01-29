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
 * Iterator filter for the IMP_Ftree object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_IteratorFilter extends RecursiveFilterIterator
{
    /* The list filtering constants. */
    const NO_CHILDREN = 1;
    const NO_NONIMAP = 2;
    const NO_REMOTE = 4;
    const NO_SPECIALMBOXES = 8;
    const NO_UNEXPANDED = 16;
    const NO_UNPOLLED = 32;
    const NO_VFOLDER = 64;
    const INVISIBLE = 128;
    const UNSUB = 256;
    const UNSUB_PREF = 512;

    /**
     * Cached data.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Filter mask.
     *
     * @var integer
     */
    protected $_mask = 0;

    /**
     * Creates an Iterator object.
     *
     * @param integer $mask           Any mask to apply to the filter.
     * @param IMP_Ftree_Element $elt  Base element.
     *
     * @return Iterator  Filter iterator.
     */
    static public function create($mask = 0, $elt = null)
    {
        $ob = new self(new IMP_Ftree_Iterator($elt, self::showUnsub($mask)));
        $ob->setFilter($mask);
        return $ob->getIterator();
    }

    /**
     * Show unsubscribed mailboxes based on the mask?
     *
     * @param integer $mask  Mask to apply to the filter.
     *
     * @return boolean  True if showing unsubscribed mailboxes.
     */
    static public function showUnsub($mask = 0)
    {
        global $prefs;

        return (($mask & self::UNSUB) ||
                (($mask & self::UNSUB_PREF) && !$prefs->getValue('subscribe')));
    }

    /**
     * Set the iterator filter and reset the internal pointer.
     *
     * @param integer $mask  A mask with the following possible elements:
     * <pre>
     *   - self::NO_CHILDREN: Don't include child elements.
     *   - self::NO_NONIMAP: Don't include non-IMAP elements.
     *   - self::NO_REMOTE: Don't include remote accounts.
     *   - self::NO_SPECIALMBOXES: Don't include special mailboxes.
     *   - self::NO_UNEXPANDED: Don't include unexpanded mailboxes.
     *   - self::NO_UNPOLLED: Don't include unpolled mailboxes.
     *   - self::NO_VFOLDER: Don't include Virtual Folders.
     *   - self::INVISIBLE: Include invisible elements.
     *   - self::UNSUB: Include unsubscribed elements.
     *   - self::UNSUB_PREF: Include unsubscribed elements based on current
     *                       subscribe preference value.
     * </pre>
     */
    public function setFilter($mask = 0)
    {
        if (self::showUnsub($mask)) {
            $mask |= self::UNSUB;
        }
        $mask &= ~self::UNSUB_PREF;

        $this->_mask = $mask;
        reset($this);
    }

    /**
     * Return the iterator needed to traverse tree.
     *
     * @return RecursiveIteratorIterator  Iterator.
     */
    public function getIterator()
    {
        return new RecursiveIteratorIterator(
            $this,
            RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /* RecursiveFilterIterator methods. */

    /**
     */
    public function accept()
    {
        $elt = $this->current();

        if (($this->_mask & self::NO_NONIMAP) && $elt->nonimap) {
            return false;
        }

        if ($elt->vfolder) {
            return !($this->_mask & self::NO_VFOLDER);
        }

        if ($elt->invisible && !($this->_mask & self::INVISIBLE)) {
            return false;
        }

        if ($elt->container) {
            if ($elt->remote) {
                if ($this->_mask & self::NO_REMOTE) {
                    return false;
                }
            } elseif (!$this->getInnerIterator()->hasChildren() ||
                      !($prefetch = iterator_to_array($this->getChildren()->getIterator(), false))) {
                return false;
            } else {
                $this->_cache[$this->key()] = $prefetch;
            }
            return true;
        }

        if ($elt->mbox_ob->special) {
            if ($this->_mask & self::NO_SPECIALMBOXES) {
                return false;
            }
        } elseif (($this->_mask & self::NO_UNPOLLED) && !$elt->polled) {
            return false;
        }

        if (!($this->_mask & self::UNSUB) && !$elt->subscribed) {
            return false;
        }

        return true;
    }

    /**
     */
    public function getChildren()
    {
        $key = $this->key();

        if (isset($this->_cache[$key])) {
            $filter = new IMP_Ftree_IteratorFilter_Prefetch(
                new IMP_Ftree_Iterator($this->_cache[$key])
            );
            unset($this->_cache[$key]);
        } else {
            $filter = new self($this->getInnerIterator()->getChildren());
            $filter->setFilter($this->_mask);
        }

        return $filter;
    }

    /**
     */
    public function hasChildren()
    {
        /* Check for the existence of children in the first place. Use this
         * recursive hasChildren() call since it will cache the results. */
        if (!$this->getInnerIterator()->hasChildren()) {
            return false;
        }

        /* If expanded is requested, we assume it overrides child filter. */
        if (($this->_mask & self::NO_UNEXPANDED) && !$this->current()->open) {
            return false;
        }

        /* Explicitly don't return child elements. */
        if ($this->_mask & self::NO_CHILDREN) {
            return false;
        }

        return true;
    }

    /* RecursiveIterator methods. */

    /**
     */
    public function rewind()
    {
        parent::rewind();
        $this->_cache = array();
    }

}

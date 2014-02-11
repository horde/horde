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
 * Manage the mailbox poll list.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_Prefs_Poll extends IMP_Ftree_Prefs
{
    /**
     * Tree object.
     *
     * @var IMP_Ftree
     */
    private $_ftree;

    /**
     * Constructor.
     *
     * @param IMP_Ftree $ftree  Tree object.
     */
    public function __construct(IMP_Ftree $ftree)
    {
        global $prefs;

        $this->_ftree = $ftree;

        if ($prefs->getValue('nav_poll_all')) {
            $this->_data = $this->_locked = true;
        } else {
            /* We ALWAYS poll the INBOX. */
            $this->_data = array('INBOX' => 1);

            /* Add the list of polled mailboxes from the prefs. */
            if ($nav_poll = @unserialize($prefs->getValue('nav_poll'))) {
                $this->_data += $nav_poll;
            }

            $this->_locked = $prefs->isLocked('nav_poll');
        }
    }

    /**
     */
    public function shutdown()
    {
        $GLOBALS['prefs']->setValue('nav_poll', serialize($this->_data));
    }

    /**
     * Returns the list of mailboxes to poll.
     *
     * @param boolean $sort  Sort the directory list?
     *
     * @return array  The list of mailboxes to poll (IMP_Mailbox objects).
     */
    public function getPollList($sort = false)
    {
        $plist = array();

        $mask = IMP_Ftree_IteratorFilter::NO_NONIMAP;
        if ($this->_data !== true) {
            $mask |= IMP_Ftree_IteratorFilter::NO_UNPOLLED;
        }

        $iterator = new IMP_Ftree_IteratorFilter_Nocontainers(
            IMP_Ftree_IteratorFilter::create($mask)
        );
        $plist = array_map('strval', iterator_to_array($iterator, false));

        if ($sort) {
            $this->_ftree->sortList($plist, $this->_ftree[IMP_Ftree::BASE_ELT]);
        }

        return IMP_Mailbox::get($plist);
    }

    /**
     * Add elements to the poll list.
     *
     * @param mixed $id  The element name or a list of element names to add.
     */
    public function addPollList($id)
    {
        if ($this->locked) {
            return;
        }

        foreach ((is_array($id) ? $id : array($id)) as $val) {
            if (($elt = $this->_ftree[$val]) &&
                !$elt->polled &&
                !$elt->nonimap &&
                !$elt->container) {
                if (!$elt->subscribed) {
                    $elt->subscribed = true;
                }
                $this[$elt] = true;
                $elt->polled = true;
            }
        }
    }

    /**
     * Remove elements from the poll list.
     *
     * @param mixed $id  The element name or a list of element names to
     *                   remove.
     */
    public function removePollList($id)
    {
        if (!$this->locked) {
            foreach ((is_array($id) ? $id : array($id)) as $val) {
                if ($elt = $this->_ftree[$val]) {
                    if (!$elt->inbox) {
                        unset($this[$val]);
                        $elt->polled = false;
                    }
                } elseif (!IMP_Mailbox::get($val)->inbox) {
                    unset($this[$val]);
                }
            }
        }
    }

    /**
     * Prune non-existent mailboxes from poll list.
     */
    public function prunePollList()
    {
        if (!$this->locked) {
            foreach (IMP_Mailbox::get($this->_data) as $val) {
                if (!$val->mbox_ob->exists) {
                    $this->removePollList($val);
                }
            }
        }
    }

    /**
     */
    public function offsetGet($offset)
    {
        return ($this->_data === true)
            ? (($elt = $this->_ftree[$offset]) && !$elt->nonimap && !$elt->container)
            : parent::offsetGet($offset);
    }

}

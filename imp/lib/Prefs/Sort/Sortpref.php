<?php
/**
 * This class provides the object representation for the sort preference for
 * a mailbox.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 *
 * @property IMP_Mailbox $mbox  Mailbox for these preferences.
 * @property integer $sortby  The sortby value.
 * @property boolean $sortby_default  Is the sortby value the default?
 * @property boolean $sortby_locked  Is the sortby value locked?
 * @property integer $sortdir  The sortdir value.
 * @property boolean $sortdir_default  Is the sortdir value the default?
 * @property boolean $sortdir_locked  Is the sortdir value locked?
 */
class IMP_Prefs_Sort_Sortpref
{
    /**
     * Mailbox object.
     *
     * @var IMP_Mailbox
     */
    protected $_mbox;

    /**
     * The sortby value.
     *
     * @var array
     */
    protected $_sortby;

    /**
     * The sortdir value.
     *
     * @var array
     */
    protected $_sortdir;

    /**
     * Constructor.
     *
     * @param string $mbox      Mailbox.
     * @param integer $sortby   Sortby value.
     * @param integer $sortdir  Sortdir value.
     */
    public function __construct($mbox, $sortby = null, $sortdir = null)
    {
        $this->_mbox = IMP_Mailbox::get($mbox);
        $this->_sortby = $sortby;
        $this->_sortdir = $sortdir;
    }

    /**
     */
    public function __get($name)
    {
        global $prefs;

        switch ($name) {
        case 'mbox':
            return $this->_mbox;

        case 'sortby':
            if (is_null($this->_sortby)) {
                return ($by = $prefs->getValue('sortby'))
                    ? $by
                    /* Sanity check: make sure we have a sort value. */
                    : Horde_Imap_Client::SORT_ARRIVAL;
            }
            return $this->_sortby;

        case 'sortby_default':
            return is_null($this->_sortby);

        case 'sortby_locked':
            return $this->_mbox->search
                /* For now, only allow sorting in search mailboxes guaranteed
                 * to consist of a single mailbox. */
                ? !$this->_mbox->systemquery
                : $prefs->isLocked(IMP_Prefs_Sort::SORTPREF);

        case 'sortdir':
            return is_null($this->_sortdir)
                ? $prefs->getValue('sortdir')
                : $this->_sortdir;

        case 'sortdir_default':
            return is_null($this->_sortdir);

        case 'sortdir_locked':
            return $this->_mbox->search
                /* Search results can always/easily be reversed. */
                ? false
                : $prefs->isLocked(IMP_Prefs_Sort::SORTPREF);
        }
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'sortby':
            $this->_sortby = $value;
            break;

        case 'sortdir':
            $this->_sortdir = $value;
            break;
        }
    }

    /**
     * Converts sortby value given current mailbox attributes.
     */
    public function convertSortby()
    {
        if ($this->_mbox->access_sort) {
            switch ($this->sortby) {
            case Horde_Imap_Client::SORT_THREAD:
                if (!$this->_mbox->access_sortthread) {
                    $this->_sortby = Horde_Imap_Client::SORT_SUBJECT;
                }
                break;

             case Horde_Imap_Client::SORT_FROM:
                 /* If the preference is to sort by From Address, when we are
                  * in the Drafts or Sent mailboxes, sort by To Address. */
                 if ($this->_mbox->special_outgoing) {
                     $this->_sortby = Horde_Imap_Client::SORT_TO;
                 }
                 break;

              case Horde_Imap_Client::SORT_TO:
                  if (!$this->_mbox->special_outgoing) {
                      $this->_sortby = Horde_Imap_Client::SORT_FROM;
                  }
                  break;
              }
        } else {
            $this->_sortby = Horde_Imap_Client::SORT_SEQUENCE;
        }
    }

    /**
     * Returns the array representation of this object.
     */
    public function toArray()
    {
        $ret = array();

        if (!is_null($this->_sortby)) {
            $ret['b'] = $this->_sortby;
        }
        if (!is_null($this->_sortdir)) {
            $ret['d'] = $this->_sortdir;
        }

        return empty($ret)
            ? null
            : $ret;
    }

}

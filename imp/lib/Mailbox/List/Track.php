<?php
/**
 * This class contains code related to generating and handling a mailbox
 * message list.  This class will keep track of the current index within
 * a mailbox.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mailbox_List_Track extends IMP_Mailbox_List
{
    /**
     * Check the IMAP cache ID?
     *
     * @var boolean
     */
    public $checkcache = true;

    /**
     * The IMAP cache ID of the mailbox.
     *
     * @var string
     */
    protected $_cacheid = null;

    /**
     * The location in the sorted array we are at.
     *
     * @var integer
     */
    protected $_index = null;

    /**
     * The list of additional variables to serialize.
     *
     * @var array
     */
    protected $_slist = array('_cacheid', '_index');

    /**
     * Returns the current message array index. If the array index has
     * run off the end of the message array, will return the last index.
     *
     * @return integer  The message array index.
     */
    public function getMessageIndex()
    {
        return $this->isValidIndex()
            ? ($this->_index + 1)
            : 1;
    }

    /**
     * Checks to see if the current index is valid.
     *
     * @return boolean  True if index is valid, false if not.
     */
    public function isValidIndex()
    {
        return !is_null($this->_index);
    }

    /**
     * Returns IMAP mbox/UID information on a message.
     *
     * @param integer $offset  The offset from the current message.
     *
     * @return array  Array with the following entries:
     *   - mailbox: (IMP_Mailbox) The mailbox.
     *   - uid: (integer) The message UID.
     */
    public function getIMAPIndex($offset = 0)
    {
        $index = $this->_index + $offset;

        return isset($this->_sorted[$index])
            ? array(
                  'mailbox' => isset($this->_sortedMbox[$index]) ? IMP_Mailbox::get($this->_sortedMbox[$index]) : $this->_mailbox,
                  'uid' => $this->_sorted[$index]
              )
            : array();
    }

    /**
     * Using the preferences and the current mailbox, determines the messages
     * to view on the current page.
     *
     * @param integer $page   The page number currently being displayed.
     * @param integer $start  The starting message number.
     *
     * @return array  An array with the following fields:
     *   anymsg: (boolean) Are there any messages at all in mailbox? E.g. If
     *           'msgcount' is 0, there may still be hidden deleted messages.
     *   begin: (integer) The beginning message sequence number of the page.
     *   end: (integer) The ending message sequence number of the page.
     *   index: (integer) The index of the starting message.
     *   msgcount: (integer) The number of viewable messages in the current
     *             mailbox.
     *   page: (integer) The current page number.
     *   pagecount: (integer) The number of pages in this mailbox.
     */
    public function buildMailboxPage($page = 0, $start = 0)
    {
        $this->_buildMailbox();

        $ret = array('msgcount' => count($this->_sorted));

        $page_size = max($GLOBALS['prefs']->getValue('max_msgs'), 1);

        if ($ret['msgcount'] > $page_size) {
            $ret['pagecount'] = ceil($ret['msgcount'] / $page_size);

            /* Determine which page to display. */
            if (empty($page) || strcspn($page, '0123456789')) {
                if (!empty($start)) {
                    /* Messages set this when returning to a mailbox. */
                    $page = ceil($start / $page_size);
                } else {
                    /* Search for the last visited page first. */
                    if ($GLOBALS['session']->exists('imp', 'mbox_page/' . $this->_mailbox)) {
                        $page = $GLOBALS['session']->get('imp', 'mbox_page/' . $this->_mailbox);
                    } elseif ($this->_mailbox->search) {
                        $page = 1;
                    } else {
                        $page = ceil($this->mailboxStart($ret['msgcount']) / $page_size);
                    }
                }
            }

            /* Make sure we're not past the end or before the beginning, and
               that we have an integer value. */
            $ret['page'] = intval($page);
            if ($ret['page'] > $ret['pagecount']) {
                $ret['page'] = $ret['pagecount'];
            } elseif ($ret['page'] < 1) {
                $ret['page'] = 1;
            }

            $ret['begin'] = (($ret['page'] - 1) * $page_size) + 1;
            $ret['end'] = $ret['begin'] + $page_size - 1;
            if ($ret['end'] > $ret['msgcount']) {
                $ret['end'] = $ret['msgcount'];
            }
        } else {
            $ret['begin'] = 1;
            $ret['end'] = $ret['msgcount'];
            $ret['page'] = 1;
            $ret['pagecount'] = 1;
        }

        $ret['index'] = $this->_mailbox->search
            ? $ret['begin'] - 1
            : $this->_index;

        /* If there are no viewable messages, check for deleted messages in
           the mailbox. */
        $ret['anymsg'] = true;
        if (!$ret['msgcount'] && !$this->_mailbox->search) {
            try {
                $status = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->status($this->_mailbox, Horde_Imap_Client::STATUS_MESSAGES);
                $ret['anymsg'] = (bool)$status['messages'];
            } catch (IMP_Imap_Exception $e) {
                $ret['anymsg'] = false;
            }
        }

        /* Store the page value now. */
        $GLOBALS['session']->set('imp', 'mbox_page/' . $this->_mailbox, $ret['page']);

        return $ret;
    }

    /**
     * Updates the message array index.
     *
     * @param mixed $data  If an integer, the number of messages to increase
     *                     array index by. If an indices object, sets array
     *                     index to the index value. If null, rebuilds the
     *                     internal index.
     */
    public function setIndex($data)
    {
        if ($data instanceof IMP_Indices) {
            list($mailbox, $uid) = $data->getSingle();
            $this->_index = $this->getArrayIndex($uid, $mailbox);
            if (is_null($this->_index)) {
                $this->_rebuild(true);
                $this->_index = $this->getArrayIndex($uid, $mailbox);
            }
        } elseif (is_null($data)) {
            $this->_index = null;
            $this->_rebuild(true);
        } else {
            $index = $this->_index += $data;
            if (isset($this->_sorted[$this->_index])) {
                $this->_rebuild();
            } else {
                $this->_rebuild(true);
                $this->_index = isset($this->_sorted[$index])
                    ? $index
                    : null;
            }
        }
    }

    /**
     */
    protected function _buildMailbox()
    {
        $cacheid = $this->_mailbox->cacheid;

        /* Check cache ID: will catch changes to the mailbox after coming out
         * of message view mode. */
        if (!$this->isBuilt() ||
            ($this->checkcache && ($this->_cacheid != $cacheid))) {
            $this->_sorted = null;
            $this->_cacheid = $cacheid;
            parent::_buildMailbox();
        }
    }

    /**
     */
    protected function _rebuild($force = false)
    {
        if ($force ||
            (!is_null($this->_index) && !$this->getIMAPIndex(1))) {
            parent::rebuild();
        }
    }

    /**
     */
    public function removeMsgs($indices)
    {
        if (parent::removeMsgs($indices)) {
            /* Update the current array index to its new position in the
             * message array. */
            $this->setIndex(0);

            return true;
        }

        return false;
    }

}

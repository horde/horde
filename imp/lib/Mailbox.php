<?php
/**
 * The IMP_Mailbox:: class contains all code related to handling a mailbox
 * message list.
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Mailbox
{
    /**
     * The mailbox to work with.
     *
     * @var string
     */
    protected $_mailbox;

    /**
     * The location in the sorted array we are at.
     *
     * @var integer
     */
    protected $_arrayIndex = null;

    /**
     * The location of the last message we were at.
     *
     * @var integer
     */
    protected $_lastArrayIndex = null;

    /**
     * The array of sorted indices.
     *
     * @var array
     */
    protected $_sorted = null;

    /**
     * The array of information about the sorted indices list.
     * Entries:
     *  'm' = Mailbox (if not exist, then use current mailbox)
     *
     * @var array
     */
    protected $_sortedInfo = array();

    /**
     * Is this a search malbox?
     *
     * @var boolean
     */
    protected $_searchmbox;

    /**
     * The Horde_Imap_Client_Thread object for the mailbox.
     *
     * @var Horde_Imap_Client_Thread
     */
    protected $_threadob = null;

    /**
     * Attempts to return a reference to a concrete IMP_Mailbox instance.
     * It will only create a new instance if no IMP_Mailbox instance with
     * the same parameters currently exists.
     *
     * This method must be invoked as:
     *   $var = &IMP_Mailbox::singleton($mailbox[, $index]);
     *
     * @param string $mailbox  See IMP_Mailbox constructor.
     * @param integer $index   See IMP_Mailbox constructor.
     *
     * @return mixed  The created concrete IMP_Mailbox instance, or false
     *                on error.
     */
    static public function &singleton($mailbox, $index = null)
    {
        static $instances = array();

        if (!isset($instances[$mailbox])) {
            $instances[$mailbox] = new IMP_Mailbox($mailbox, $index);
        } elseif (!is_null($index)) {
            $instances[$mailbox]->setIndex($index);
        }

        return $instances[$mailbox];
    }

    /**
     * Constructor.
     *
     * @param string $mailbox  The mailbox to work with.
     * @param integer $index   The index of the current message.
     */
    function __construct($mailbox, $index = null)
    {
        $this->_mailbox = $mailbox;
        $this->_searchmbox = $GLOBALS['imp_search']->isSearchMbox($mailbox);

        if (!is_null($index)) {
            $this->setIndex($index);
        }
    }

    /**
     * The mailbox this object works with.
     *
     * @return string  A mailbox name.
     */
    public function getMailboxName()
    {
        return $this->_mailbox;
    }

    /**
     * Build the array of message information.
     *
     * @param array $msgnum   An array of message sequence numbers.
     * @param mixed $preview  Include preview information?  If empty, add no
     *                        preview information. If 1, uses value from
     *                        prefs.  If 2, forces addition of preview info.
     * @param array $headers  A list of non-standard (non-envelope) headers to
     *                        return.
     *
     * @return array  An array with the following keys:
     * <pre>
     * 'overview' - (array) The overview information.
     * 'uids' - (array) The array of UIDs.
     * </pre>
     */
    public function getMailboxArray($msgnum, $preview = false,
                                    $headers = array())
    {
        $this->_buildMailbox();

        $mboxes = $overview = $uids = array();

        /* Build the list of mailboxes and messages. */
        foreach ($msgnum as $i) {
            /* Make sure that the index is actually in the slice of messages
               we're looking at. If we're hiding deleted messages, for
               example, there may be gaps here. */
            if (isset($this->_sorted[$i - 1])) {
                $mboxname = ($this->_searchmbox) ? $this->_sortedInfo[$i - 1]['m'] : $this->_mailbox;
                if (!isset($mboxes[$mboxname])) {
                    $mboxes[$mboxname] = array();
                }
                // $mboxes - KEY: UID, VALUE: sequence number
                $mboxes[$mboxname][$this->_sorted[$i - 1]] = $i;
            }
        }

        $fetch_criteria = array(
            Horde_Imap_Client::FETCH_ENVELOPE => true,
            Horde_Imap_Client::FETCH_FLAGS => true,
            Horde_Imap_Client::FETCH_SIZE => true,
            Horde_Imap_Client::FETCH_UID => true,
            Horde_Imap_Client::FETCH_SEQ => true
        );

        if (!empty($headers)) {
            $fetch_criteria[Horde_Imap_Client::FETCH_HEADERS] = array(array('headers' => $headers, 'label' => 'imp', 'parse' => true, 'peek' => true));
        }

        $cacheob = $preview ? $GLOBALS['imp_imap']->ob->getCacheOb() : null;

        /* Retrieve information from each mailbox. */
        foreach ($mboxes as $mbox => $ids) {
            try {
                $fetch_res = $GLOBALS['imp_imap']->ob->fetch($mbox, $fetch_criteria, array('ids' => array_keys($ids)));

                if ($preview) {
                    $preview_info = $tostore = array();
                    if ($cacheob) {
                        try {
                            $preview_info = $cacheob->get($mbox, array_keys($ids), array('IMPpreview', 'IMPpreviewc'));
                        } catch (Horde_Imap_Client_Exception $e) {}
                    }
                }

                reset($fetch_res);
                while (list($k, $v) = each($fetch_res)) {
                    $v['mailbox'] = $mbox;
                    if (isset($v['headers']['imp'])) {
                        $v['headers'] = $v['headers']['imp'];
                    }

                    if ($preview &&
                        (($preview == 2) ||
                         !$GLOBALS['prefs']->getValue('preview_show_unread') ||
                         !in_array('\\seen', $v['flags']))) {
                        if (!isset($preview_info[$k])) {
                            $imp_contents = &IMP_Contents::singleton($uid . IMP_IDX_SEP . $mailbox);
                            if (is_a($imp_contents, 'PEAR_Error')) {
                                $preview_info[$k] = array('IMPpreview' => '', 'IMPpreviewc' => false);
                            } else {
                                $prev = $imp_contents->generatePreview();
                                $preview_info[$k] = array('IMPpreview' => $prev['text'], 'IMPpreviewc' => $prev['cut']);
                                if (!is_null($cacheob)) {
                                    $tostore[$k] = $preview_info[$k];
                                }
                            }
                        }

                        $v['preview'] = $preview_info[$k]['IMPpreview'];
                        $v['previewcut'] = $preview_info[$k]['IMPpreviewc'];
                    }

                    $overview[$ids[$k]] = $v;
                }

                $uids = array_merge($uids, array_keys($fetch_res));

                if (!is_null($cacheob) && !empty($tostore)) {
                    $cacheob->set($mbox, $tostore);
                }
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        /* Sort via the sorted array index. */
        ksort($overview);

        return array('overview' => $overview, 'uids' => $uids);
    }

    /**
     * Builds the sorted list of messages in the mailbox.
     */
    protected function _buildMailbox()
    {
        if (!is_null($this->_sorted)) {
            return;
        }

        $query = null;

        if ($this->_searchmbox) {
            if (IMP::hideDeletedMsgs()) {
                $query = new Horde_Imap_Client_Search_Query();
                $query->flag('\\deleted', false);
            }

            $this->_sorted = $this->_sortedInfo = array();
            foreach ($GLOBALS['imp_search']->runSearch($query, $this->_mailbox) as $val) {
                list($idx, $mbox) = explode(IMP::IDX_SEP, $val);
                $this->_sorted[] = $idx;
                $this->_sortedInfo[] = array('m' => $mbox);
            }
        } else {
            $sortpref = IMP::getSort($this->_mailbox);

            if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
                $threadob = $this->getThreadOb();
                $this->_sorted = $threadob->messageList((bool)$sortpref['dir']);
            } else {
                if (($_SESSION['imp']['protocol'] != 'pop') &&
                    IMP::hideDeletedMsgs()) {
                    $query = new Horde_Imap_Client_Search_Query();
                    $query->flag('\\deleted', false);
                }
                try {
                    $res = $GLOBALS['imp_imap']->ob->search($this->_mailbox, $query, array('sort' => array($sortpref['by']), 'reverse' => (bool)$sortpref['dir']));
                    $this->_sorted = $res['sort'];
                } catch (Horde_Imap_Client_Exception $e) {
                    $this->_sorted = array();
                }
            }
        }
    }

    /**
     * Get the list of new messages in the mailbox (IMAP RECENT flag, with
     * UNDELETED if we're hiding deleted messages).
     *
     * @param boolean $count  Return a count of new messages, rather than
     *                        the entire message list?
     *
     * @return integer  The number of new messages in the mailbox.
     */
    public function newMessages($count = false)
    {
        return $this->_msgFlagSearch('recent', $count);
    }

    /**
     * Get the list of unseen messages in the mailbox (IMAP UNSEEN flag, with
     * UNDELETED if we're hiding deleted messages).
     *
     * @param boolean $count  Return a count of unseen messages, rather than
     *                        the entire message list?
     *
     * @return array  The list of unseen messages.
     */
    public function unseenMessages($count = false)
    {
        return $this->_msgFlagSearch('unseen', $count);
    }

    /**
     * Do a search on a mailbox in the most efficient way available.
     *
     * @param string $type    The search type - either 'recent' or 'unseen'.
     * @param boolean $count  Return a count of unseen messages, rather than
     *                        the entire message list?
     *
     * @return mixed  If $count is true, the number of messages.  If $count is
     *                false, a list of message UIDs.
     */
    protected function _msgFlagSearch($type, $count)
    {
        if ($this->_searchmbox || empty($this->_sorted)) {
            return $count ? 0 : array();
        }

        $criteria = new Horde_Imap_Client_Search_Query();

        if (IMP::hideDeletedMsgs()) {
            $criteria->flag('\\deleted', false);
        } elseif ($count) {
            try {
                $status_res = $GLOBALS['imp_imap']->ob->status($this->_mailbox, $type == 'recent' ? Horde_Imap_Client::STATUS_RECENT : Horde_Imap_Client::STATUS_UNSEEN);
                return $status_res[$type];
            } catch (Horde_Imap_Client_Exception $e) {
                return 0;
            }
        }

        if ($type == 'recent') {
            $criteria->flag('\\recent');
        } else {
            $criteria->flag('\\seen', false);
        }

        $results = $count
            ? array(Horde_Imap_Client::SORT_RESULTS_COUNT)
            : array(Horde_Imap_Client::SORT_RESULTS_MATCH);

        try {
            $res = $GLOBALS['imp_imap']->ob->search($this->_mailbox, $criteria, array('results' => $results));
            return $count ? $res['count'] : $res['match'];
        } catch (Horde_Imap_Client_Exception $e) {
            return $count ? 0 : array();
        }
    }

    /**
     * Returns the current message array index. If the array index has
     * run off the end of the message array, will return the last index.
     *
     * @return integer  The message array index.
     */
    public function getMessageIndex()
    {
        return is_null($this->_arrayIndex)
            ? (is_null($this->_lastArrayIndex) ? 1 : $this->_lastArrayIndex + 1)
            : $this->_arrayIndex + 1;
    }

    /**
     * Returns the current message count of the mailbox.
     *
     * @return integer  The mailbox message count.
     */
    public function getMessageCount()
    {
        if (!$GLOBALS['imp_search']->isVFolder($this->_mailbox)) {
            $this->_buildMailbox();
        }
        return count($this->_sorted);
    }

    /**
     * Checks to see if the current index is valid.
     * This function is only useful if an index was passed to the constructor.
     *
     * @return boolean  True if index is valid, false if not.
     */
    public function isValidIndex()
    {
        $this->_sortIfNeeded();
        return !is_null($this->_arrayIndex);
    }

    /**
     * Returns IMAP mbox/UID information on a message.
     *
     * @param integer $offset  The offset from the current message.
     *
     * @return array  'index'   -- The message index.
     *                'mailbox' -- The mailbox.
     */
    public function getIMAPIndex($offset = 0)
    {
        $index = $this->_arrayIndex + $offset;

        /* If the offset would put us out of array index, return now. */
        if (!isset($this->_sorted[$index])) {
            return array();
        }

        return array(
            'index' => $this->_sorted[$index],
            'mailbox' => ($this->_searchmbox) ? $this->_sortedInfo[$index]['m'] : $this->_mailbox
        );
    }

    /**
     * Using the preferences and the current mailbox, determines the messages
     * to view on the current page.
     *
     * @param integer $page       The page number currently being displayed.
     * @param integer $start      The starting message number.
     * @param integer $page_size  Override the maxmsgs preference and specify
     *                            the page size.
     *
     * @return array  An array with the following fields:
     * <pre>
     * 'anymsg' - (boolean) Are there any messages at all in mailbox? E.g. If
     *            'msgcount' is 0, there may still be hidden deleted messages.
     * 'begin' - (integer) The beginning message sequence number of the page.
     * 'end' - (integer) The ending message sequence number of the page.
     * 'index' - (integer) The index of the starting message.
     * 'msgcount' - (integer) The number of viewable messages in the current
     *              mailbox.
     * 'page' - (integer) The current page number.
     * 'pagecount' - (integer) The number of pages in this mailbox.
     * </pre>
     */
    public function buildMailboxPage($page = 0, $start = 0, $page_size = null)
    {
        $this->_buildMailbox();

        $ret = array('msgcount' => $this->getMessageCount());

        if (is_null($page_size) &&
            ($page_size != $GLOBALS['prefs']->getValue('max_msgs'))) {
            $page_size = 20;
        }

        if ($ret['msgcount'] > $page_size) {
            $ret['pagecount'] = ceil($ret['msgcount'] / $page_size);

            /* Determine which page to display. */
            if (empty($page) || strcspn($page, '0123456789')) {
                if (!empty($start)) {
                    /* Messages set this when returning to a mailbox. */
                    $page = ceil($start / $page_size);
                } else {
                    /* Search for the last visited page first. */
                    if (isset($_SESSION['imp']['cache']['mbox_page'][$this->_mailbox])) {
                        $page = $_SESSION['imp']['cache']['mbox_page'][$this->_mailbox];
                    } else {
                        $startpage = $GLOBALS['prefs']->getValue('mailbox_start');
                        switch ($startpage) {
                        case IMP::MAILBOX_START_FIRSTPAGE:
                            $page = 1;
                            break;

                        case IMP::MAILBOX_START_LASTPAGE:
                            $page = $ret['pagecount'];
                            break;

                        case IMP::MAILBOX_START_FIRSTUNSEEN:
                            // TODO - Use status()

                        case IMP::MAILBOX_START_LASTUNSEEN:
                            $sortpref = IMP::getSort($this->_mailbox);
                            if (!$sortpref['limit'] &&
                                !$this->_searchmbox &&
                                ($query = $this->unseenMessages())) {
                                $sortednew = array_keys(array_intersect($this->_sorted, $query));
                                $first_new = ($startpage == IMP::MAILBOX_START_FIRSTUNSEEN) ?
                                    array_shift($sortednew) :
                                    array_pop($sortednew);
                                $page = ceil(($first_new + 1) / $page_size);
                            }
                            break;
                        }
                    }
                }

                if (empty($page)) {
                    if (!isset($sortpref)) {
                        $sortpref = IMP::getSort($this->_mailbox);
                    }
                    $page = $sortpref['dir'] ? 1 : $ret['pagecount'];
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

            $ret['begin'] = (($page - 1) * $page_size) + 1;
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

        $ret['index'] = ($this->_searchmbox) ? ($ret['begin'] - 1) : $this->_arrayIndex;

        /* If there are no viewable messages, check for deleted messages in
           the mailbox. */
        $ret['anymsg'] = true;
        if (!$ret['msgcount'] && !$this->_searchmbox) {
            try {
                $status = $GLOBALS['imp_imap']->ob->status($this->_mailbox, Horde_Imap_Client::STATUS_MESSAGES);
                $ret['anymsg'] = (bool)$status['messages'];
            } catch (Horde_Imap_Client_Exception $e) {
                $ret['anymsg'] = false;
            }
        }

        /* Store the page value now. */
        $_SESSION['imp']['cache']['mbox_page'][$this->_mailbox] = $ret['page'];

        return $ret;
    }

    /**
     * Updates the message array index.
     *
     * @param integer $data  If $type is 'offset', the number of messages to
     *                       increase array index by.  If type is 'uid',
     *                       sets array index to the value of the given
     *                       message index.
     * @param string $type   Either 'offset' or 'uid'.
     */
    public function setIndex($data, $type = 'uid')
    {
        switch ($type) {
        case 'offset':
            if (!is_null($this->_arrayIndex)) {
                $this->_lastArrayIndex = $this->_arrayIndex;
                $this->_arrayIndex += $data;
                if (empty($this->_sorted[$this->_arrayIndex])) {
                    $this->_arrayIndex = null;
                }
                $this->_sortIfNeeded();
            }
            break;

        case 'uid':
            $this->_arrayIndex = $this->_lastArrayIndex = $this->getArrayIndex($data);
            break;
        }
    }

    /**
     * Get the Horde_Imap_Client_Thread object for the current mailbox.
     *
     * @return Horde_Imap_Client_Thread  The thread object for the current
     *                                   mailbox.
     */
    public function getThreadOb()
    {
        if (is_null($this->_threadob)) {
            try {
                $this->_threadob = $GLOBALS['imp_imap']->ob->thread($this->_mailbox);
            } catch (Horde_Imap_Client_Exception $e) {
                return new Horde_Imap_Client_Thread();
            }
        }

        return $this->_threadob;
    }

    /**
     * Determines if a resort is needed, and, if necessary, performs
     * the resort.
     */
    protected function _sortIfNeeded()
    {
        if (!is_null($this->_arrayIndex) &&
            !$this->_searchmbox &&
            !$this->getIMAPIndex(1)) {
            $this->_build = false;
            $this->_buildMailbox();
        }
    }

    /**
     * Returns the current sorted array without the given messages.
     *
     * @param array $msgs  The indices to remove.
     *
     * @return boolean  True if sorted array was updated without a call to
     *                  _buildMailbox().
     */
    protected function _removeMsgs($msgs)
    {
        if (empty($msgs)) {
            return;
        }

        if (!($msgList = IMP::parseIndicesList($msgs))) {
            $msgList = array($this->_mailbox => $msgs);
        }

        $msgcount = 0;
        $sortcount = count($this->_sorted);

        /* Remove the current entry and recalculate the range. */
        foreach ($msgList as $key => $val) {
            // @todo $arrival is false here
            foreach ($val as $index) {
                $val = $this->getArrayIndex($index, $key);
                if ($arrival !== false && isset($this->_sorted[$val])) {
                    unset($arrival[$this->_sorted[$val]]);
                }
                unset($this->_sorted[$val]);
                if ($this->_searchmbox) {
                    unset($this->_sortedInfo[$val]);
                }
                ++$msgcount;
            }
        }

        $this->_sorted = array_values($this->_sorted);
        if ($this->_searchmbox) {
            $this->_sortedInfo = array_values($this->_sortedInfo);
        }

        /* Update the current array index to its new position in the message
         * array. */
        $this->setIndex(0, 'offset');

        /* If we have a sortlimit, it is possible the sort prefs will have
         * changed after messages are expunged. */
        if (!empty($GLOBALS['conf']['server']['sort_limit']) &&
            ($sortcount > $GLOBALS['conf']['server']['sort_limit']) &&
            (($sortcount - $msgcount) <= $GLOBALS['conf']['server']['sort_limit'])) {
            $this->updateMailbox(self::UPDATE);
            return false;
        }

        return true;
    }

    /**
     * Returns the array index of the given message UID.
     *
     * @param integer $uid   The message UID.
     * @param integer $mbox  The message mailbox (defaults to the current
     *                       mailbox).
     *
     * @return integer  The array index of the location of the message UID in
     *                  the current mailbox.
     */
    public function getArrayIndex($uid, $mbox = null)
    {
        $aindex = null;

        $this->_buildMailbox();

        if ($this->_searchmbox) {
            if (is_null($mbox)) {
                $mbox = $GLOBALS['imp_mbox']['thismailbox'];
            }

            /* Need to compare both mbox name and message UID to obtain the
             * correct array index since there may be duplicate UIDs. */
            foreach (array_keys($this->_sorted, $uid) as $key) {
                if ($this->_sortedInfo[$key]['m'] == $mbox) {
                    return $key;
                }
            }
        } else {
            /* array_search() returns false on no result. We will set an
             * unsuccessful result to NULL. */
            if (($aindex = array_search($uid, $this->_sorted)) === false) {
                $aindex = null;
            }
        }

        return $aindex;
    }

    /**
     * Returns a raw sorted list of the mailbox.
     *
     * @return array  An array with two keys: 's' = sorted UIDS list, 'm' =
     *                sorted mailboxes list.
     */
    public function getSortedList()
    {
        $this->_buildMailbox();

        /* For exterior use, the array needs to begin numbering at 1. */
        $s = $this->_sorted;
        array_unshift($s, 0);
        unset($s[0]);
        $m = $this->_sortedInfo;
        array_unshift($m, 0);
        unset($m[0]);

        return array('s' => $s, 'm' => $m);
    }

    /**
     * Returns a unique identifier for the current mailbox status.
     *
     * @return string  The cache ID string, which will change when the status
     *                 of the mailbox changes.
     */
    public function getCacheID()
    {
        try {
            $sortpref = IMP::getSort($this->_mailbox);
            $ret = $GLOBALS['imp_imap']->ob->status($this->_mailbox, Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_UIDNEXT | Horde_Imap_Client::STATUS_UIDVALIDITY);
            return implode('|', array($ret['messages'], $ret['uidnext'], $ret['uidvalidity'], $sortpref['by'], $sortpref['dir']));
        } catch (Horde_Imap_Client_Exception $e) {
            return '';
        }
    }
}

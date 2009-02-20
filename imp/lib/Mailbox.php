<?php
/**
 * The IMP_Mailbox:: class contains all code related to handling a mailbox
 * message list.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
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
     * Singleton instances
     *
     * @var array
     */
    protected static $_instances = array();

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
     * @param string $mailbox  See IMP_Mailbox constructor.
     * @param integer $index   See IMP_Mailbox constructor.
     *
     * @return mixed  The created concrete IMP_Mailbox instance, or false
     *                on error.
     */
    static public function singleton($mailbox, $index = null)
    {
        if (!isset(self::$_instances[$mailbox])) {
            self::$_instances[$mailbox] = new IMP_Mailbox($mailbox, $index);
        } elseif (!is_null($index)) {
            self::$_instances[$mailbox]->setIndex($index);
        }

        return self::$_instances[$mailbox];
    }

    /**
     * Constructor.
     *
     * @param string $mailbox  The mailbox to work with.
     * @param integer $index   The index of the current message.
     */
    protected function __construct($mailbox, $index = null)
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
     * 'uids' - (array) The array of UIDs. It is in the same format as used
     *          for IMP::parseIndicesList().
     * </pre>
     */
    public function getMailboxArray($msgnum, $preview = false,
                                    $headers = array())
    {
        $this->_buildMailbox();

        $overview = $to_process = $uids = array();

        /* Build the list of mailboxes and messages. */
        foreach ($msgnum as $i) {
            /* Make sure that the index is actually in the slice of messages
               we're looking at. If we're hiding deleted messages, for
               example, there may be gaps here. */
            if (isset($this->_sorted[$i - 1])) {
                $mboxname = ($this->_searchmbox) ? $this->_sortedInfo[$i - 1]['m'] : $this->_mailbox;
                if (!isset($to_process[$mboxname])) {
                    $to_process[$mboxname] = array();
                }
                // $uids - KEY: UID, VALUE: sequence number
                $to_process[$mboxname][$this->_sorted[$i - 1]] = $i;
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

        $cache = $preview ? $GLOBALS['imp_imap']->ob->getCache() : null;

        /* Retrieve information from each mailbox. */
        foreach ($to_process as $mbox => $ids) {
            try {
                $fetch_res = $GLOBALS['imp_imap']->ob->fetch($mbox, $fetch_criteria, array('ids' => array_keys($ids)));

                if ($preview) {
                    $preview_info = $tostore = array();
                    if ($cache) {
                        try {
                            $preview_info = $cache->get($mbox, array_keys($ids), array('IMPpreview', 'IMPpreviewc'));
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
                        (($preview === 2) ||
                         !$GLOBALS['prefs']->getValue('preview_show_unread') ||
                         !in_array('\\seen', $v['flags']))) {
                        if (empty($preview_info[$k])) {
                            $imp_contents = IMP_Contents::singleton($k . IMP::IDX_SEP . $mbox);
                            if (is_a($imp_contents, 'PEAR_Error')) {
                                $preview_info[$k] = array('IMPpreview' => '', 'IMPpreviewc' => false);
                            } else {
                                $prev = $imp_contents->generatePreview();
                                $preview_info[$k] = array('IMPpreview' => $prev['text'], 'IMPpreviewc' => $prev['cut']);
                                if (!is_null($cache)) {
                                    $tostore[$k] = $preview_info[$k];
                                }
                            }
                        }

                        $v['preview'] = $preview_info[$k]['IMPpreview'];
                        $v['previewcut'] = $preview_info[$k]['IMPpreviewc'];
                    }

                    $overview[$ids[$k]] = $v;
                }

                $uids[$mbox] = array_keys($fetch_res);

                if (!is_null($cache) && !empty($tostore)) {
                    $cache->set($mbox, $tostore);
                }
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        /* Sort via the sorted array index. */
        ksort($overview);

        return array('overview' => $overview, 'uids' => $uids);
    }

    /**
     * Returns true if the mailbox data has been built.
     *
     * @return boolean  True if the mailbox has been built.
     */
    public function isBuilt()
    {
        return !is_null($this->_sorted);
    }

    /**
     * Builds the sorted list of messages in the mailbox.
     */
    protected function _buildMailbox()
    {
        if ($this->isBuilt()) {
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
                $this->_threadob = null;
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
     * @param integer $results  A Horde_Imap_Client::SORT_* constant that
     *                          indicates the desired return type.
     *
     * @return mixed  Whatever is requested in $results.
     */
    public function newMessages($results)
    {
        return $this->_msgFlagSearch('recent', $results);
    }

    /**
     * Get the list of unseen messages in the mailbox (IMAP UNSEEN flag, with
     * UNDELETED if we're hiding deleted messages).
     *
     * @param integer $results  A Horde_Imap_Client::SORT_RESULTS_* constant
     *                          that indicates the desired return type.
     *
     * @return mixed  Whatever is requested in $results.
     */
    public function unseenMessages($results)
    {
        return $this->_msgFlagSearch('unseen', $results);
    }

    /**
     * Do a search on a mailbox in the most efficient way available.
     *
     * @param string $type      The search type - either 'recent' or 'unseen'.
     * @param integer $results  A Horde_Imap_Client::SORT_RESULTS_* constant
     *                          that indicates the desired return type.
     *
     * @return mixed  Whatever is requested in $results.
     */
    protected function _msgFlagSearch($type, $results)
    {
        $count = $results == Horde_Imap_Client::SORT_RESULTS_COUNT;

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
            $criteria->flag('\\recent', true);
        } else {
            $criteria->flag('\\seen', false);
        }

        try {
            $res = $GLOBALS['imp_imap']->ob->search($this->_mailbox, $criteria, array('results' => array($results)));
            return $count ? $res['count'] : $res;
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
        return is_null($this->_arrayIndex) ? 1 : $this->_arrayIndex + 1;
    }

    /**
     * Returns the current message count of the mailbox.
     *
     * @return integer  The mailbox message count.
     */
    public function getMessageCount()
    {
        $this->_buildMailbox();
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
        $this->_rebuild();
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

        return isset($this->_sorted[$index])
            ? array(
                  'index' => $this->_sorted[$index],
                  'mailbox' => ($this->_searchmbox) ? $this->_sortedInfo[$index]['m'] : $this->_mailbox
              )
            : array();
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

        $ret = array('msgcount' => count($this->_sorted));

        if (is_null($page_size)) {
            $page_size = $GLOBALS['prefs']->getValue('max_msgs');
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
                    } elseif ($this->_searchmbox) {
                        $page = 1;
                    } else {
                        $page_uid = null;
                        $startpage = $GLOBALS['prefs']->getValue('mailbox_start');

                        switch ($GLOBALS['prefs']->getValue('mailbox_start')) {
                        case IMP::MAILBOX_START_FIRSTPAGE:
                            $page = 1;
                            break;

                        case IMP::MAILBOX_START_LASTPAGE:
                            $page = $ret['pagecount'];
                            break;

                        case IMP::MAILBOX_START_FIRSTUNSEEN:
                            $sortpref = IMP::getSort($this->_mailbox);

                            /* Optimization: if sorting by arrival then first
                             * unseen information is returned via a
                             * SELECT/EXAMINE call. */
                            if ($sortpref['by'] == Horde_Imap_Client::SORT_ARRIVAL) {
                                try {
                                    $res = $GLOBALS['imp_imap']->ob->status($this->_mailbox, Horde_Imap_Client::STATUS_FIRSTUNSEEN);
                                    $page_uid = is_null($res['firstunseen']) ? null : $this->_sorted[$res['firstunseen'] - 1];
                                } catch (Horde_Imap_Client_Exception $e) {}
                            } else {
                                $unseen_msgs = $this->unseenMessages(Horde_Imap_Client::SORT_RESULTS_MIN);
                                $page_uid = $unseen_msgs['min'];
                            }
                            break;

                        case IMP::MAILBOX_START_LASTUNSEEN:
                            $unseen_msgs = $this->unseenMessages(Horde_Imap_Client::SORT_RESULTS_MAX);
                            $page_uid = $unseen_msgs['max'];
                            break;
                        }
                    }
                }

                if (empty($page)) {
                    $page = is_null($page_uid)
                        ? 1
                        : ceil((array_search($page_uid, $this->_sorted) + 1) / $page_size);
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
                $this->_arrayIndex += $data;
                if (empty($this->_sorted[$this->_arrayIndex])) {
                    $this->_arrayIndex = null;
                }
                $this->_rebuild();
            }
            break;

        case 'uid':
            $this->_arrayIndex = $this->getArrayIndex($data);
            if (empty($this->_arrayIndex)) {
                $this->_rebuild(true);
                $this->_arrayIndex = $this->getArrayIndex($data);
            }
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
     * Determines if a rebuild is needed, and, if necessary, performs
     * the rebuild.
     *
     * @param boolean $force  Force a rebuild?
     */
    protected function _rebuild($force = false)
    {
        if ($force ||
            (!is_null($this->_arrayIndex) &&
             !$this->_searchmbox &&
             !$this->getIMAPIndex(1))) {
            $this->_sorted = null;
            $this->_buildMailbox();
        }
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
     * Returns the current sorted array without the given messages.
     *
     * @param mixed $msgs  The list of indices to remove (see
     *                     IMP::parseIndicesList()) or true to remove all
     *                     messages in the mailbox.
     */
    public function removeMsgs($msgs)
    {
        if ($msgs === true) {
            $this->_rebuild(true);
            return;
        }

        if (empty($msgs)) {
            return;
        }

        $msgcount = 0;
        $sortcount = count($this->_sorted);

        /* Remove the current entry and recalculate the range. */
        foreach (IMP::parseIndicesList($msgs) as $key => $val) {
            foreach ($val as $index) {
                $val = $this->getArrayIndex($index, $key);
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

        $this->_threadob = null;

        /* Update the current array index to its new position in the message
         * array. */
        $this->setIndex(0, 'offset');

        /* If we have a sortlimit, it is possible the sort prefs will have
         * changed after messages are expunged. */
        if (!empty($GLOBALS['conf']['server']['sort_limit']) &&
            ($sortcount > $GLOBALS['conf']['server']['sort_limit']) &&
            (($sortcount - $msgcount) <= $GLOBALS['conf']['server']['sort_limit'])) {
            $this->_rebuild(true);
        }
    }

    /**
     * Returns a unique identifier for the current mailbox status.
     *
     * @return string  The cache ID string, which will change when the
     *                 composition of the mailbox changes.
     */
    public function getCacheID()
    {
        if (!$this->_searchmbox) {
            $sortpref = IMP::getSort($this->_mailbox);

            try {
                $status = $GLOBALS['imp_imap']->ob->status($this->_mailbox, Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_UIDNEXT | Horde_Imap_Client::STATUS_UIDVALIDITY);
                return implode('|', array($status['uidvalidity'], $status['uidnext'], $status['messages'], $sortpref['by'], $sortpref['dir']));
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        /* This should generate a sufficiently random #. */
        return time() . mt_rand();
    }

}

<?php
/**
 * The IMP_Mailbox:: class contains all code related to handling a mailbox
 * message list.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
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
     * The mailboxes corresponding to the sorted indices list.
     * If empty, uses $_mailbox.
     *
     * @var array
     */
    protected $_sortedMbox = array();

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
     * Has the internal message list changed?
     *
     * @var boolean
     */
    protected $_changed = false;

    /**
     * Attempts to return a reference to a concrete instance.
     * It will only create a new instance if no instance with the same
     * parameters currently exists.
     *
     * @param string $mailbox  See constructor.
     * @param string $uid      UID string (UID . IMP::IDX_SEP . Mailbox).
     *
     * @return IMP_Mailbox  The created instance.
     */
    static public function singleton($mailbox, $uid = null)
    {
        if (!isset(self::$_instances[$mailbox])) {
            self::$_instances[$mailbox] = new self($mailbox, $uid);
        } elseif (!is_null($uid)) {
            self::$_instances[$mailbox]->setIndex($uid);
        }

        return self::$_instances[$mailbox];
    }

    /**
     * Constructor.
     *
     * @param string $mailbox  The mailbox to work with.
     * @param string $uid      UID string (UID . IMP::IDX_SEP . Mailbox).
     */
    protected function __construct($mailbox, $uid = null)
    {
        $this->_mailbox = $mailbox;
        $this->_searchmbox = $GLOBALS['imp_search']->isSearchMbox($mailbox);

        if (is_null($uid)) {
            unset($_SESSION['imp']['cache']['imp_mailbox'][$mailbox]);
        } else {
            /* Try to rebuild sorted information from the session cache. */
            if (isset($_SESSION['imp']['cache']['imp_mailbox'][$mailbox])) {
                $tmp = json_decode($_SESSION['imp']['cache']['imp_mailbox'][$mailbox]);
                $this->_sorted = $this->_searchmbox ? $tmp->s : $tmp;
                $this->_sortedMbox = $this->_searchmbox ? $tmp->m : array();
            }
            $this->setIndex($uid);
        }

        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Cache mailbox information if viewing in standard (IMP) message mode.
     * Needed to keep navigation consistent when moving through the message
     * list, and to ensure messages aren't marked as missing in search
     * mailboxes (e.g. if search is dependent on unseen flag).
     */
    public function shutdown()
    {
        if ($this->_changed &&
            ($_SESSION['imp']['view'] == 'imp')) {
            /* Casting $_sorted to integers saves a significant amount of
             * space when json_encoding (no need to quote every value). Only
             * can do for IMAP though (since POP3 UIDs are not limited to
             * integers). */
            $sorted = ($_SESSION['imp']['protocol'] == 'pop')
                ? $this->_sorted
                : array_map('intval', $this->_sorted);
            $_SESSION['imp']['cache']['imp_mailbox'][$this->_mailbox] = $this->_searchmbox
                ? json_encode(array('m' => $this->_sortedMbox, 's' => $sorted))
                : json_encode($sorted);
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
     * @param array $options  Additional options:
     * <pre>
     * 'headers' - (boolean) Return info on the non-envelope headers
     *             'Importance', 'List-Post', and 'X-Priority'.
     *             DEFAULT: false (only envelope headers returned)
     * 'preview' - (mixed) Include preview information?  If empty, add no
     *                     preview information. If 1, uses value from prefs.
     *                     If 2, forces addition of preview info.
     *                     DEFAULT: No preview information.
     * 'structure' - (boolean) Get structure information from server.
     *               Contained in the 'strucutre' entry.
     *               DEFAULT: false
     * </pre>
     *
     * @return array  An array with the following keys:
     * <pre>
     * 'overview' - (array) The overview information. Contains the following:
     *              'envelope' - (array) Envelope information returned from
     *                           the IMAP server. See
     *                           Horde_Imap_Client::fetch() for format.
     *              'flags' - (array) The list of IMAP flags returned from
     *                        the server. See Horde_Imap_Client::fetch() for
     *                        the format.
     *              'headers' - (array) Any headers requested in
     *                          $options['headers']. Horde_Mime_Headers objects
     *                          are returned.  See Horde_Imap_Client::fetch()
     *                          for the format.
     *              'mailbox' - (string) The mailbox containing the message.
     *              'preview' - (string) If requested in $options['preview'],
     *                          the preview text.
     *              'previewcut'- (boolean) Has the preview text been cut?
     *              'seq' - (integer) The sequence number of the message.
     *              'size' - (integer) The size of the message in bytes.
     *              'structure'- (array) The structure of the message. Only
     *                           set if $options['structure'] is true. See
     *                           Horde_Imap_Client::fetch() for format.
     *              'uid' - (string) The unique ID of the message.
     *
     * 'uids' - (array) The array of UIDs. It is in the same format as used
     *          for IMP::parseIndicesList().
     * </pre>
     */
    public function getMailboxArray($msgnum, $options = array())
    {
        $this->_buildMailbox();

        $overview = $to_process = $uids = array();

        /* Build the list of mailboxes and messages. */
        foreach ($msgnum as $i) {
            /* Make sure that the index is actually in the slice of messages
               we're looking at. If we're hiding deleted messages, for
               example, there may be gaps here. */
            if (isset($this->_sorted[$i - 1])) {
                $mboxname = ($this->_searchmbox) ? $this->_sortedMbox[$i - 1] : $this->_mailbox;

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

        if (!empty($options['headers'])) {
            $fetch_criteria[Horde_Imap_Client::FETCH_HEADERS] = array(array('cache' => true, 'headers' => array('importance', 'list-post', 'x-priority'), 'label' => 'imp', 'parse' => true, 'peek' => true));
        }

        if (!empty($options['structure'])) {
            $fetch_criteria[Horde_Imap_Client::FETCH_STRUCTURE] = array('parse' => true);
        }

        if (empty($options['preview'])) {
            $cache = null;
            $options['preview'] = 0;
        } else {
            $cache = $GLOBALS['imp_imap']->ob()->getCache();
        }

        /* Retrieve information from each mailbox. */
        foreach ($to_process as $mbox => $ids) {
            try {
                $fetch_res = $GLOBALS['imp_imap']->ob()->fetch($mbox, $fetch_criteria, array('ids' => array_keys($ids)));

                if ($options['preview']) {
                    $preview_info = $tostore = array();
                    if ($cache) {
                        try {
                            $preview_info = $cache->get($mbox, array_keys($ids), array('IMPpreview', 'IMPpreviewc'));
                        } catch (Horde_Imap_Client_Exception $e) {}
                    }
                }

                foreach (array_keys($ids) as $k) {
                    $v = $fetch_res[$k];

                    $v['mailbox'] = $mbox;
                    if (isset($v['headers']['imp'])) {
                        $v['headers'] = $v['headers']['imp'];
                    }

                    if (($options['preview'] === 2) ||
                        (($options['preview'] === 1) &&
                         (!$GLOBALS['prefs']->getValue('preview_show_unread') ||
                          !in_array('\\seen', $v['flags'])))) {
                        if (empty($preview_info[$k])) {
                            try {
                                $imp_contents = IMP_Contents::singleton($k . IMP::IDX_SEP . $mbox);
                                $prev = $imp_contents->generatePreview();
                                $preview_info[$k] = array('IMPpreview' => $prev['text'], 'IMPpreviewc' => $prev['cut']);
                                if (!is_null($cache)) {
                                    $tostore[$k] = $preview_info[$k];
                                }
                            } catch (Horde_Exception $e) {
                                $preview_info[$k] = array('IMPpreview' => '', 'IMPpreviewc' => false);
                            }
                        }

                        $v['preview'] = $preview_info[$k]['IMPpreview'];
                        $v['previewcut'] = $preview_info[$k]['IMPpreviewc'];
                    }

                    $overview[] = $v;
                }

                $uids[$mbox] = array_keys($fetch_res);

                if (!is_null($cache) && !empty($tostore)) {
                    $status = $GLOBALS['imp_imap']->ob()->status($mbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
                    $cache->set($mbox, $tostore, $status['uidvalidity']);
                }
            } catch (Horde_Imap_Client_Exception $e) {}
        }

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

        $this->_changed = true;
        $query = null;

        if ($this->_searchmbox) {
            if (IMP::hideDeletedMsgs($this->_mailbox)) {
                $query = new Horde_Imap_Client_Search_Query();
                $query->flag('\\deleted', false);
            }

            try {
                foreach ($GLOBALS['imp_search']->runSearch($query, $this->_mailbox) as $val) {
                    list($idx, $mbox) = explode(IMP::IDX_SEP, $val);
                    $this->_sorted[] = $idx;
                    $this->_sortedMbox[] = $mbox;
                }
            } catch (Horde_Imap_Client_Exception $e) {
                $this->_sorted = $this->_sortedMbox = array();
                $GLOBALS['notification']->push(_("Mailbox listing failed") . ': ' . $e->getMessage(), 'horde.error');
            }
        } else {
            $sortpref = IMP::getSort($this->_mailbox);

            if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
                $this->_threadob = null;
                $threadob = $this->getThreadOb();
                $this->_sorted = $threadob->messageList((bool)$sortpref['dir']);
            } else {
                if (($_SESSION['imp']['protocol'] != 'pop') &&
                    IMP::hideDeletedMsgs($this->_mailbox)) {
                    $query = new Horde_Imap_Client_Search_Query();
                    $query->flag('\\deleted', false);
                }
                try {
                    $res = $GLOBALS['imp_search']->imapSearch($this->_mailbox, $query, array('sort' => array($sortpref['by']), 'reverse' => (bool)$sortpref['dir']));
                    $this->_sorted = $res['sort'];
                } catch (Horde_Imap_Client_Exception $e) {
                    $this->_sorted = array();
                    $GLOBALS['notification']->push(_("Mailbox listing failed") . ': ' . $e->getMessage(), 'horde.error');
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
     * @param boolean $uid      Return UIDs instead of sequence numbers (for
     *                          $results queries that return message lists).
     *
     * @return mixed  Whatever is requested in $results.
     */
    public function newMessages($results, $uid = false)
    {
        return $this->_msgFlagSearch('recent', $results, $uid);
    }

    /**
     * Get the list of unseen messages in the mailbox (IMAP UNSEEN flag, with
     * UNDELETED if we're hiding deleted messages).
     *
     * @param integer $results  A Horde_Imap_Client::SORT_RESULTS_* constant
     *                          that indicates the desired return type.
     * @param boolean $uid      Return UIDs instead of sequence numbers (for
     *                          $results queries that return message lists).
     *
     * @return mixed  Whatever is requested in $results.
     */
    public function unseenMessages($results, $uid = false)
    {
        return $this->_msgFlagSearch('unseen', $results, $uid);
    }

    /**
     * Do a search on a mailbox in the most efficient way available.
     *
     * @param string $type      The search type - either 'recent' or 'unseen'.
     * @param integer $results  A Horde_Imap_Client::SORT_RESULTS_* constant
     *                          that indicates the desired return type.
     * @param boolean $uid      Return UIDs instead of sequence numbers (for
     *                          $results queries that return message lists).
     *
     * @return mixed  Whatever is requested in $results.
     */
    protected function _msgFlagSearch($type, $results, $uid)
    {
        $count = $results == Horde_Imap_Client::SORT_RESULTS_COUNT;

        if ($this->_searchmbox || empty($this->_sorted)) {
            return $count ? 0 : array();
        }

        $criteria = new Horde_Imap_Client_Search_Query();

        if (IMP::hideDeletedMsgs($this->_mailbox)) {
            $criteria->flag('\\deleted', false);
        } elseif ($count) {
            try {
                $status_res = $GLOBALS['imp_imap']->ob()->status($this->_mailbox, $type == 'recent' ? Horde_Imap_Client::STATUS_RECENT : Horde_Imap_Client::STATUS_UNSEEN);
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
            $res = $GLOBALS['imp_imap']->ob()->search($this->_mailbox, $criteria, array('results' => array($results), 'sequence' => !$uid));
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
     * @param boolean $rebuild  Rebuild mailbox list, if needed.
     *
     * @return boolean  True if index is valid, false if not.
     */
    public function isValidIndex($rebuild = true)
    {
        if ($rebuild) {
            $this->_rebuild();
            $this->setIndex(0, 'offset');
        }
        return !is_null($this->_arrayIndex);
    }

    /**
     * Returns IMAP mbox/UID information on a message.
     *
     * @param integer $offset  The offset from the current message.
     *
     * @return array  Array with the following entries:
     * <pre>
     * 'mailbox' - (string) The mailbox.
     * 'uid' - (integer) The message UID.
     * </pre>
     */
    public function getIMAPIndex($offset = 0)
    {
        $index = $this->_arrayIndex + $offset;

        return isset($this->_sorted[$index])
            ? array(
                  'mailbox' => ($this->_searchmbox ? $this->_sortedMbox[$index] : $this->_mailbox),
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
    public function buildMailboxPage($page = 0, $start = 0, $opts = array())
    {
        $this->_buildMailbox();

        $ret = array('msgcount' => count($this->_sorted));

        $page_size = $GLOBALS['prefs']->getValue('max_msgs');

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

        $ret['index'] = ($this->_searchmbox) ? ($ret['begin'] - 1) : $this->_arrayIndex;

        /* If there are no viewable messages, check for deleted messages in
           the mailbox. */
        $ret['anymsg'] = true;
        if (!$ret['msgcount'] && !$this->_searchmbox) {
            try {
                $status = $GLOBALS['imp_imap']->ob()->status($this->_mailbox, Horde_Imap_Client::STATUS_MESSAGES);
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
     * Determines the sequence number of the first message to display, based
     * on the user's preferences.
     *
     * @param integer $total  The total number of messages in the mailbox.
     *
     * @return integer  The sequence number in the sorted mailbox.
     */
    public function mailboxStart($total)
    {
        if ($this->_searchmbox) {
            return 1;
        }

        switch ($GLOBALS['prefs']->getValue('mailbox_start')) {
        case IMP::MAILBOX_START_FIRSTPAGE:
            return 1;

        case IMP::MAILBOX_START_LASTPAGE:
            return $total;

        case IMP::MAILBOX_START_FIRSTUNSEEN:
            $sortpref = IMP::getSort($this->_mailbox);

            /* Optimization: if sorting by arrival then first unseen
             * information is returned via a SELECT/EXAMINE call. */
            if ($sortpref['by'] == Horde_Imap_Client::SORT_ARRIVAL) {
                try {
                    $res = $GLOBALS['imp_imap']->ob()->status($this->_mailbox, Horde_Imap_Client::STATUS_FIRSTUNSEEN);
                    if (!is_null($res['firstunseen'])) {
                        return $res['firstunseen'];
                    }
                } catch (Horde_Imap_Client_Exception $e) {}

                return 1;
            }

            $unseen_msgs = $this->unseenMessages(Horde_Imap_Client::SORT_RESULTS_MIN, true);
            return empty($unseen_msgs['min'])
                ? 1
                : ($this->getArrayIndex($unseen_msgs['min']) + 1);

        case IMP::MAILBOX_START_LASTUNSEEN:
            $unseen_msgs = $this->unseenMessages(Horde_Imap_Client::SORT_RESULTS_MAX, true);
            return empty($unseen_msgs['max'])
                ? 1
                : ($this->getArrayIndex($unseen_msgs['max']) + 1);
        }
    }

    /**
     * Updates the message array index.
     *
     * @param mixed $data   If $type is 'offset', the number of messages to
     *                      increase array index by.  If type is 'uid', sets
     *                      array index to the value of the given message
     *                      UID string (UID . IMP::IDX_SEP . Mailbox).
     * @param string $type  Either 'offset' or 'uid'.
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
            list($uid, $mailbox) = explode(IMP::IDX_SEP, $data);
            $this->_arrayIndex = $this->getArrayIndex($uid, $mailbox);
            if (empty($this->_arrayIndex)) {
                $this->_rebuild(true);
                $this->_arrayIndex = $this->getArrayIndex($uid, $mailbox);
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
                $this->_threadob = $GLOBALS['imp_imap']->ob()->thread($this->_mailbox, array('criteria' => $_SESSION['imp']['imap']['thread']));
            } catch (Horde_Imap_Client_Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return new Horde_Imap_Client_Thread(array(), 'uid');
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
            (!is_null($this->_arrayIndex) && !$this->getIMAPIndex(1))) {
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
     * @return mixed  The array index of the location of the message UID in
     *                the current mailbox. Returns null if not found.
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
                if ($this->_sortedMbox[$key] == $mbox) {
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
        $m = $this->_sortedMbox;
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
            foreach ($val as $uid) {
                $val = $this->getArrayIndex($uid, $key);
                unset($this->_sorted[$val]);
                if ($this->_searchmbox) {
                    unset($this->_sortedMbox[$val]);
                }
                ++$msgcount;
            }
        }

        $this->_sorted = array_values($this->_sorted);
        $this->_changed = true;
        if ($this->_searchmbox) {
            $this->_sortedMbox = array_values($this->_sortedMbox);
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
                $id = $GLOBALS['imp_imap']->ob()->getCacheId($this->_mailbox);
                return $id . '|' . $sortpref['by'] . '|' . $sortpref['dir'];
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        /* This should generate a sufficiently random #. */
        return time() . mt_rand();
    }

}

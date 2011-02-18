<?php
/**
 * This class contains code related to generating and handling a mailbox
 * message list.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Mailbox_List implements Countable, Serializable
{
    /* Serialized version. */
    const VERSION = 1;

    /**
     * Has the internal message list changed?
     *
     * @var boolean
     */
    public $changed = false;

    /**
     * The mailbox to work with.
     *
     * @var string
     */
    protected $_mailbox;

    /**
     * Is this a search malbox?
     *
     * @var boolean
     */
    protected $_searchmbox;

    /**
     * The list of additional variables to serialize.
     *
     * @var array
     */
    protected $_slist = array();

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
     * The thread object for the mailbox.
     *
     * @var Horde_Imap_Client_Data_Thread
     */
    protected $_threadob = null;

    /**
     * Constructor.
     *
     * @param string $mailbox  The mailbox to work with.
     */
    public function __construct($mailbox)
    {
        $this->_mailbox = $mailbox;
        $this->_searchmbox = $GLOBALS['injector']->getInstance('IMP_Search')->isSearchMbox($mailbox);
    }

    /**
     * Build the array of message information.
     *
     * @param array $msgnum   An array of message sequence numbers.
     * @param array $options  Additional options:
     * <pre>
     * headers - (boolean) Return info on the non-envelope headers
     *           'Importance', 'List-Post', and 'X-Priority'.
     *           DEFAULT: false (only envelope headers returned)
     * preview - (mixed) Include preview information?  If empty, add no
     *                   preview information. If 1, uses value from prefs.
     *                   If 2, forces addition of preview info.
     *                   DEFAULT: No preview information.
     * type - (boolean) Return info on the MIME Content-Type of the base
     *        message part ('Content-Type' header).
     *        DEFAULT: false
     * </pre>
     *
     * @return array  An array with the following keys:
     * <pre>
     * overview - (array) The overview information. Contains the following:
     *     envelope - (Horde_Imap_Client_Data_Envelope) Envelope information
     *                returned from the IMAP server.
     *     flags - (array) The list of IMAP flags returned from the server.
     *     headers - (array) Horde_Mime_Headers objects containing header data
     *               if either $options['headers'] or $options['type'] are
     *               true.
     *     mailbox - (string) The mailbox containing the message.
     *     preview - (string) If requested in $options['preview'], the preview
     *               text.
     *     previewcut - (boolean) Has the preview text been cut?
     *     size - (integer) The size of the message in bytes.
     *     uid - (string) The unique ID of the message.
     * uids - (IMP_Indices) An indices object.
     * </pre>
     */
    public function getMailboxArray($msgnum, $options = array())
    {
        $this->_buildMailbox();

        $headers = $overview = $to_process = $uids = array();

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

        $fetch_query = new Horde_Imap_Client_Fetch_Query();
        $fetch_query->envelope();
        $fetch_query->flags();
        $fetch_query->size();
        $fetch_query->uid();

        if (!empty($options['headers'])) {
            $headers = array_merge($headers, array(
                'importance',
                'list-post',
                'x-priority'
            ));
        }

        if (!empty($options['type'])) {
            $headers[] = 'content-type';
        }

        if (!empty($headers)) {
            $fetch_query->headers('imp', $headers, array(
                'cache' => true,
                'peek' => true
            ));
        }

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        if (empty($options['preview'])) {
            $cache = null;
            $options['preview'] = 0;
        } else {
            $cache = $imp_imap->getCache();
        }

        /* Retrieve information from each mailbox. */
        foreach ($to_process as $mbox => $ids) {
            try {
                $fetch_res = $imp_imap->fetch($mbox, $fetch_query, array(
                    'ids' => new Horde_Imap_Client_Ids(array_keys($ids))
                ));

                if ($options['preview']) {
                    $preview_info = $tostore = array();
                    if ($cache) {
                        try {
                            $preview_info = $cache->get($mbox, array_keys($ids), array('IMPpreview', 'IMPpreviewc'));
                        } catch (Horde_Imap_Client_Exception $e) {}
                    }
                }

                reset($fetch_res);
                while (list($k, $f) = each($fetch_res)) {
                    $v = array(
                        'envelope' => $f->getEnvelope(),
                        'flags' => $f->getFlags(),
                        'headers' => $f->getHeaders('imp', Horde_Imap_Client_Data_Fetch::HEADER_PARSE),
                        'mailbox' => $mbox,
                        'size' => $f->getSize(),
                        'uid' => $f->getUid()
                    );

                    if (($options['preview'] === 2) ||
                        (($options['preview'] === 1) &&
                         (!$GLOBALS['prefs']->getValue('preview_show_unread') ||
                          !in_array('\\seen', $v['flags'])))) {
                        if (empty($preview_info[$k])) {
                            try {
                                $imp_contents = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create(new IMP_Indices($mbox, $k));
                                $prev = $imp_contents->generatePreview();
                                $preview_info[$k] = array('IMPpreview' => $prev['text'], 'IMPpreviewc' => $prev['cut']);
                                if (!is_null($cache)) {
                                    $tostore[$k] = $preview_info[$k];
                                }
                            } catch (Exception $e) {
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
                    $status = $imp_imap->status($mbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
                    $cache->set($mbox, $tostore, $status['uidvalidity']);
                }
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return array(
            'overview' => $overview,
            'uids' => new IMP_Indices($uids)
        );
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

        $this->changed = true;
        $this->_sorted = $this->_sortedMbox = array();
        $query = null;

        if ($this->_searchmbox) {
            if (IMP::hideDeletedMsgs($this->_mailbox)) {
                $query = new Horde_Imap_Client_Search_Query();
                $query->flag('\\deleted', false);
            }

            try {
                foreach ($GLOBALS['injector']->getInstance('IMP_Search')->runSearch($query, $this->_mailbox) as $mbox => $idx) {
                    $this->_sorted[] = $idx;
                    $this->_sortedMbox[] = $mbox;
                }
            } catch (Horde_Imap_Client_Exception $e) {
                $GLOBALS['notification']->push(_("Mailbox listing failed") . ': ' . $e->getMessage(), 'horde.error');
            }
        } else {
            $sortpref = IMP::getSort($this->_mailbox, true);
            if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
                $this->_threadob = null;
                $threadob = $this->getThreadOb();
                $this->_sorted = $threadob->messageList((bool)$sortpref['dir']);
            } else {
                if (($GLOBALS['session']->get('imp', 'protocol') != 'pop') &&
                    IMP::hideDeletedMsgs($this->_mailbox)) {
                    $query = new Horde_Imap_Client_Search_Query();
                    $query->flag('\\deleted', false);
                }
                try {
                    $res = $GLOBALS['injector']->getInstance('IMP_Search')->imapSearch($this->_mailbox, $query, array('sort' => array($sortpref['by'])));
                    if ($sortpref['dir']) {
                        $res['match']->reverse();
                    }
                    $this->_sorted = $res['match']->ids;
                } catch (Horde_Imap_Client_Exception $e) {
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
        $count = ($results == Horde_Imap_Client::SORT_RESULTS_COUNT);

        if ($this->_searchmbox || empty($this->_sorted)) {
            if ($count &&
                $this->_searchmbox &&
                ($type == 'unseen') &&
                $GLOBALS['injector']->getInstance('IMP_Search')->isVinbox($this->_mailbox)) {
                return count($this);
            }

            return $count ? 0 : array();
        }

        $criteria = new Horde_Imap_Client_Search_Query();
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        if (IMP::hideDeletedMsgs($this->_mailbox)) {
            $criteria->flag('\\deleted', false);
        } elseif ($count) {
            try {
                $status_res = $imp_imap->status($this->_mailbox, $type == 'recent' ? Horde_Imap_Client::STATUS_RECENT : Horde_Imap_Client::STATUS_UNSEEN);
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
            $res = $imp_imap->search($this->_mailbox, $criteria, array('results' => array($results), 'sequence' => !$uid));
            return $count ? $res['count'] : $res;
        } catch (Horde_Imap_Client_Exception $e) {
            return $count ? 0 : array();
        }
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
                    if ($GLOBALS['session']->exists('imp', 'mbox_page/' . $this->_mailbox)) {
                        $page = $GLOBALS['session']->get('imp', 'mbox_page/' . $this->_mailbox);
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

        $ret['index'] = $ret['begin'] - 1;

        /* If there are no viewable messages, check for deleted messages in
           the mailbox. */
        $ret['anymsg'] = true;
        if (!$ret['msgcount'] && !$this->_searchmbox) {
            try {
                $status = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->status($this->_mailbox, Horde_Imap_Client::STATUS_MESSAGES);
                $ret['anymsg'] = (bool)$status['messages'];
            } catch (Horde_Imap_Client_Exception $e) {
                $ret['anymsg'] = false;
            }
        }

        /* Store the page value now. */
        $GLOBALS['session']->set('imp', 'mbox_page/' . $this->_mailbox, $ret['page']);

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

            /* Optimization: if sorting by sequence then first unseen
             * information is returned via a SELECT/EXAMINE call. */
            if ($sortpref['by'] == Horde_Imap_Client::SORT_SEQUENCE) {
                try {
                    $res = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->status($this->_mailbox, Horde_Imap_Client::STATUS_FIRSTUNSEEN);
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
     * Get the thread object for the current mailbox.
     *
     * @return Horde_Imap_Client_Data_Thread  The thread object for the
     *                                        current mailbox.
     */
    public function getThreadOb()
    {
        if (is_null($this->_threadob)) {
            try {
                $this->_threadob = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->thread($this->_mailbox, array('criteria' => $GLOBALS['session']->get('imp', 'imap_thread')));
            } catch (Horde_Imap_Client_Exception $e) {
                $GLOBALS['notification']->push($e);
                return new Horde_Imap_Client_Data_Thread(array(), 'uid');
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
        if ($force) {
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
                $mbox = IMP::$thismailbox;
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
     * @param mixed $indices  An IMP_Indices object or true to remove all
     *                        messages in the mailbox.
     *
     * @return boolean  True if the message was removed from the mailbox.
     */
    public function removeMsgs($indices)
    {
        if ($indices === true) {
            $this->_rebuild(true);
            return false;
        }

        if (!count($indices)) {
            return false;
        }

        /* Remove the current entry and recalculate the range. */
        foreach ($indices as $mbox => $uid) {
            $val = $this->getArrayIndex($uid, $mbox);
            unset($this->_sorted[$val]);
            if ($this->_searchmbox) {
                unset($this->_sortedMbox[$val]);
            }
        }

        $this->changed = true;
        $this->_sorted = array_values($this->_sorted);
        if ($this->_searchmbox) {
            $this->_sortedMbox = array_values($this->_sortedMbox);
        }
        $this->_threadob = null;

        return true;
    }

    /**
     * Returns a unique identifier for the current mailbox status.
     *
     * This cache ID is guaranteed to change if messages are added/deleted from
     * the mailbox. Additionally, if CONDSTORE is available on the remote
     * IMAP server, this ID will change if flag information changes.
     *
     * @return string  The cache ID string, which will change when the
     *                 composition of the mailbox changes.
     */
    public function getCacheID()
    {
        if (!$this->_searchmbox) {
            $sortpref = IMP::getSort($this->_mailbox, true);
            try {
                return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getCacheId($this->_mailbox, array($sortpref['by'], $sortpref['dir']));
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return strval(new Horde_Support_Randomid());
    }

    /* Countable methods. */

    /**
     * Returns the current message count of the mailbox.
     *
     * @return integer  The mailbox message count.
     */
    public function count()
    {
        $this->_buildMailbox();
        return count($this->_sorted);
    }

    /* Serializable methods. */

    /**
     * Serialization.
     *
     * @return string  Serialized data.
     */
    public function serialize()
    {
        $data = array(
            'm' => $this->_mailbox,
            's' => $this->_searchmbox,
            'v' => self::VERSION
        );

        if (!is_null($this->_sorted)) {
            $data['so'] = $this->_sorted;
            if (!empty($this->_sortedMbox)) {
                $data['som'] = $this->_sortedMbox;
            }
        }

        foreach ($this->_slist as $val) {
            $data[$val] = $this->$val;
        }

        return json_encode($data);
    }

    /**
     * Unserialization.
     *
     * @param string $data  Serialized data.
     *
     * @throws Exception
     */
    public function unserialize($data)
    {
        $data = json_decode($data, true);
        if (!is_array($data) ||
            !isset($data['v']) ||
            ($data['v'] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_mailbox = $data['m'];
        $this->_searchmbox = $data['s'];

        if (isset($data['so'])) {
            $this->_sorted = $data['so'];
            if (isset($data['som'])) {
                $this->_sortedMbox = $data['som'];
            }
        }

        foreach ($this->_slist as $val) {
            $this->$val = $data[$val];
        }
    }

}

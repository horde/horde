<?php
/**
 * This class contains code related to generating and handling a mailbox
 * message list.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mailbox_List implements ArrayAccess, Countable, Iterator, Serializable
{
    /* Serialized version. */
    const VERSION = 2;

    /**
     * Has the internal message list changed?
     *
     * @var boolean
     */
    public $changed = false;

    /**
     * The mailbox to work with.
     *
     * @var IMP_Mailbox
     */
    protected $_mailbox;

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
     * @param string $mbox  The mailbox to work with.
     */
    public function __construct($mbox)
    {
        $this->_mailbox = IMP_Mailbox::get($mbox);
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
                $mboxname = $this->_mailbox->search
                    ? $this->_sortedMbox[$i - 1]
                    : strval($this->_mailbox);

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
                    'ids' => $imp_imap->getIdsOb(array_keys($ids))
                ));

                if ($options['preview']) {
                    $preview_info = $tostore = array();
                    if ($cache) {
                        try {
                            $preview_info = $cache->get($mbox, array_keys($ids), array('IMPpreview', 'IMPpreviewc'));
                        } catch (IMP_Imap_Exception $e) {}
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
                          !in_array(Horde_Imap_Client::FLAG_SEEN, $v['flags'])))) {
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
            } catch (IMP_Imap_Exception $e) {}
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

        if ($this->_mailbox->search) {
            if ($this->_mailbox->hideDeletedMsgs()) {
                $query = new Horde_Imap_Client_Search_Query();
                $query->flag(Horde_Imap_Client::FLAG_DELETED, false);
            }

            try {
                foreach ($GLOBALS['injector']->getInstance('IMP_Search')->runSearch($query, $this->_mailbox) as $ob) {
                    $this->_sorted = array_merge($this->_sorted, $ob->uids);
                    $this->_sortedMbox = array_merge($this->_sortedMbox, array_fill(0, count($ob->uids), strval($ob->mbox)));
                }
            } catch (IMP_Imap_Exception $e) {
                $e->notify(_("Mailbox listing failed") . ': ' . $e->getMessage());
            }
        } else {
            $sortpref = $this->_mailbox->getSort(true);
            if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
                $this->_threadob = null;
                $threadob = $this->getThreadOb();
                $this->_sorted = $threadob->messageList();
                if ($sortpref['dir']) {
                    $this->_sorted = array_reverse($this->_sorted);
                }
            } else {
                if ($this->_mailbox->hideDeletedMsgs()) {
                    $query = new Horde_Imap_Client_Search_Query();
                    $query->flag(Horde_Imap_Client::FLAG_DELETED, false);
                }
                try {
                    $res = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->search($this->_mailbox, $query, array(
                        'sort' => array($sortpref['by'])
                    ));
                    if ($sortpref['dir']) {
                        $res['match']->reverse();
                    }
                    $this->_sorted = $res['match']->ids;
                } catch (IMP_Imap_Exception $e) {
                    $e->notify(_("Mailbox listing failed") . ': ' . $e->getMessage());
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

        if ($this->_mailbox->search || empty($this->_sorted)) {
            if ($count &&
                ($type == 'unseen') &&
                $this->_mailbox->vinbox) {
                return count($this);
            }

            return $count ? 0 : array();
        }

        $criteria = new Horde_Imap_Client_Search_Query();
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        if ($this->_mailbox->hideDeletedMsgs()) {
            $criteria->flag(Horde_Imap_Client::FLAG_DELETED, false);
        } elseif ($count) {
            try {
                $status_res = $imp_imap->status($this->_mailbox, $type == 'recent' ? Horde_Imap_Client::STATUS_RECENT : Horde_Imap_Client::STATUS_UNSEEN);
                return $status_res[$type];
            } catch (IMP_Imap_Exception $e) {
                return 0;
            }
        }

        if ($type == 'recent') {
            $criteria->flag(Horde_Imap_Client::FLAG_RECENT, true);
        } else {
            $criteria->flag(Horde_Imap_Client::FLAG_SEEN, false);
        }

        try {
            $res = $imp_imap->search($this->_mailbox, $criteria, array(
                'results' => array($results),
                'sequence' => !$uid
            ));
            return $count ? $res['count'] : $res;
        } catch (IMP_Imap_Exception $e) {
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
    public function buildMailboxPage($page = 0, $start = 0, $opts = array())
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

        $ret['index'] = $ret['begin'] - 1;

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
     * Determines the sequence number of the first message to display, based
     * on the user's preferences.
     *
     * @param integer $total  The total number of messages in the mailbox.
     *
     * @return integer  The sequence number in the sorted mailbox.
     */
    public function mailboxStart($total)
    {
        if ($this->_mailbox->search) {
            return 1;
        }

        switch ($GLOBALS['prefs']->getValue('mailbox_start')) {
        case IMP::MAILBOX_START_FIRSTPAGE:
            return 1;

        case IMP::MAILBOX_START_LASTPAGE:
            return $total;

        case IMP::MAILBOX_START_FIRSTUNSEEN:
            if (!$this->_mailbox->access_sort) {
                return 1;
            }

            $sortpref = $this->_mailbox->getSort();

            /* Optimization: if sorting by sequence then first unseen
             * information is returned via a SELECT/EXAMINE call. */
            if ($sortpref['by'] == Horde_Imap_Client::SORT_SEQUENCE) {
                try {
                    $res = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->status($this->_mailbox, Horde_Imap_Client::STATUS_FIRSTUNSEEN | Horde_Imap_Client::STATUS_MESSAGES);
                    if (!is_null($res['firstunseen'])) {
                        return $sortpref['dir']
                            ? ($res['messages'] - $res['firstunseen'] + 1)
                            : $res['firstunseen'];
                    }
                } catch (IMP_Imap_Exception $e) {}

                return 1;
            }

            $unseen_msgs = $this->unseenMessages(Horde_Imap_Client::SORT_RESULTS_MIN, true);
            return empty($unseen_msgs['min'])
                ? 1
                : ($this->getArrayIndex($unseen_msgs['min']) + 1);

        case IMP::MAILBOX_START_LASTUNSEEN:
            if (!$this->_mailbox->access_sort) {
                return 1;
            }

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
            } catch (IMP_Imap_Exception $e) {
                $e->notify();
                return new Horde_Imap_Client_Data_Thread(array(), 'uid');
            }
        }

        return $this->_threadob;
    }

    /**
     * Rebuilds the mailbox.
     */
    public function rebuild()
    {
        $this->_sorted = null;
        $this->_buildMailbox();
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

        if ($this->_mailbox->search) {
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
     * Generate an IMP_Indices object out of the contents of this mailbox.
     *
     * @return IMP_Indices  An indices object.
     */
    public function getIndicesOb()
    {
        $this->_buildMailbox();
        $ob = new IMP_Indices();

        if ($this->_mailbox->search) {
            reset($this->_sorted);
            while (list($k, $v) = each($this->_sorted)) {
                $ob->add($this->_sortedMbox[$k], $v);
            }
        } else {
            $ob->add($this->_mailbox, $this->_sorted);
        }

        return $ob;
    }

    /**
     * Removes messages from the mailbox.
     *
     * @param mixed $indices  An IMP_Indices object or true to remove all
     *                        messages in the mailbox.
     *
     * @return boolean  True if the message was removed from the mailbox.
     */
    public function removeMsgs($indices)
    {
        if ($indices === true) {
            $this->rebuild();
            return false;
        }

        if (!count($indices)) {
            return false;
        }

        /* Remove the current entry and recalculate the range. */
        foreach ($indices as $ob) {
            foreach ($ob->uids as $uid) {
                $val = $this->getArrayIndex($uid, $ob->mbox);
                unset($this->_sorted[$val]);
                if ($this->_mailbox->search) {
                    unset($this->_sortedMbox[$val]);
                }
            }
        }

        $this->changed = true;
        $this->_sorted = array_values($this->_sorted);
        if ($this->_mailbox->search) {
            $this->_sortedMbox = array_values($this->_sortedMbox);
        }
        $this->_threadob = null;

        return true;
    }

    /* ArrayAccess methods. */

    /**
     * @param integer $offset  Sequence number of message.
     */
    public function offsetExists($offset)
    {
        return isset($this->_sorted[$offset - 1]);
    }

    /**
     * @param integer $offset  Sequence number of message.
     *
     * @return array  Two-element array:
     *   - m: (IMP_Mailbox) Mailbox of message.
     *   - u: (string) UID of message.
     */
    public function offsetGet($offset)
    {
        return isset($this->_sorted[$offset - 1])
            ? array(
                  'm' => (empty($this->_sortedMbox) ? $this->_mailbox : IMP_Mailbox::get($this->_sortedMbox[$offset - 1])),
                  'u' => $this->_sorted[$offset - 1]
              )
            : null;
    }

    /**
     * @throws BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Not supported');
    }

    /**
     * @throws BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Not supported');
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

    /* Iterator methods. */

    /**
     * @return array  Two-element array:
     *   - m: (IMP_Mailbox) Mailbox of message.
     *   - u: (string) UID of message.
     */
    public function current()
    {
        $key = key($this->_sorted);
        return array(
            'm' => (empty($this->_sortedMbox) ? $this->_mailbox : IMP_Mailbox::get($this->_sortedMbox[$key])),
            'u' => $this->_sorted[$key]
        );
    }

    /**
     * @return integer  Sequence number of message.
     */
    public function key()
    {
        return (key($this->_sorted) + 1);
    }

    /**
     */
    public function next()
    {
        next($this->_sorted);
    }

    /**
     */
    public function rewind()
    {
        reset($this->_sorted);
    }

    /**
     */
    public function valid()
    {
        return (key($this->_sorted) !== null);
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

        return serialize($data);
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
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data['v']) ||
            ($data['v'] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_mailbox = $data['m'];

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

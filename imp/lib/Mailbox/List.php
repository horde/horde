<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class contains code related to generating and handling a mailbox
 * message list.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mailbox_List
implements ArrayAccess, Countable, Iterator, Serializable
{
    /* The UID count at which the sorted list will undergo UID compression
     * when being serialized. */
    const SERIALIZE_LIMIT = 500;

    /**
     * The list of headers used by this class.
     *
     * @var array
     */
    public static $headersUsed = array(
        'content-type',
        'importance',
        'list-post',
        'x-priority'
    );

    /**
     * Has the internal message list changed?
     *
     * @var boolean
     */
    public $changed = false;

    /**
     * Max assigned browser-UID.
     *
     * @var integer
     */
    protected $_buidmax = 0;

    /**
     * Mapping of browser-UIDs to UIDs.
     *
     * @var array
     */
    protected $_buids = array();

    /**
     * The IMAP cache ID of the mailbox.
     *
     * @var string
     */
    protected $_cacheid = null;

    /**
     * The mailbox to work with.
     *
     * @var IMP_Mailbox
     */
    protected $_mailbox;

    /**
     * The array of sorted indices.
     *
     * @var array
     */
    protected $_sorted = null;

    /**
     * The thread object representation(s) for the mailbox.
     *
     * @var array
     */
    protected $_thread = array();

    /**
     * The thread tree UI cached data.
     *
     * @var array
     */
    protected $_threadui = array();

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
     * @param array $msgnum   An array of index numbers.
     *
     * @return array  An array with the following keys:
     * <pre>
     *   - overview: (array) The overview information. Contains the following:
     *     - envelope: (Horde_Imap_Client_Data_Envelope) Envelope information
     *                 returned from the IMAP server.
     *     - flags: (array) The list of IMAP flags returned from the server.
     *     - headers: (array) Horde_Mime_Headers objects containing header
     *                data for non-envelope headers.
     *     - idx: (integer) Array index of this message.
     *     - mailbox: (string) The mailbox containing the message.
     *     - size: (integer) The size of the message in bytes.
     *     - uid: (string) The unique ID of the message.
     *   - uids: (IMP_Indices) An indices object.
     * </pre>
     */
    public function getMailboxArray($msgnum)
    {
        $this->_buildMailbox();

        $overview = $to_process = $uids = array();

        /* Build the list of mailboxes and messages. */
        foreach ($msgnum as $i) {
            /* Make sure that the index is actually in the slice of messages
               we're looking at. If we're hiding deleted messages, for
               example, there may be gaps here. */
            if (isset($this->_sorted[$i - 1])) {
                $to_process[strval($this->_getMbox($i - 1))][$i] = $this->_sorted[$i - 1];
            }
        }

        $fetch_query = new Horde_Imap_Client_Fetch_Query();
        $fetch_query->envelope();
        $fetch_query->flags();
        $fetch_query->size();
        $fetch_query->uid();

        $fetch_query->headers(
            'imp',
            array_merge(
                self::$headersUsed,
                IMP_Contents_Message::$headersUsed
            ),
            array(
                'cache' => true,
                'peek' => true
            )
        );

        /* Retrieve information from each mailbox. */
        foreach ($to_process as $mbox => $ids) {
            try {
                $imp_imap = IMP_Mailbox::get($mbox)->imp_imap;
                if ($imp_imap->config->atc_structure) {
                    $query = clone $fetch_query;
                    $query->structure();
                } else {
                    $query = $fetch_query;
                }

                $fetch_res = $imp_imap->fetch($mbox, $query, array(
                    'ids' => $imp_imap->getIdsOb($ids)
                ));

                $mbox_ids = array();

                foreach ($ids as $k => $v) {
                    if (!isset($fetch_res[$v])) {
                        continue;
                    }

                    $f = $fetch_res[$v];
                    $uid = $f->getUid();
                    $v = array(
                        'envelope' => $f->getEnvelope(),
                        'flags' => $f->getFlags(),
                        'headers' => $f->getHeaders('imp', Horde_Imap_Client_Data_Fetch::HEADER_PARSE),
                        'idx' => $k,
                        'mailbox' => $mbox,
                        'size' => $f->getSize(),
                        'structure' => $f->getStructure(),
                        'uid' => $uid
                    );

                    $overview[] = $v;
                    $mbox_ids[] = $uid;
                }

                $uids[$mbox] = $mbox_ids;
            } catch (IMP_Imap_Exception $e) {}
        }

        return array(
            'overview' => $overview,
            'uids' => new IMP_Indices($uids)
        );
    }

    /**
     * Builds the sorted list of messages in the mailbox.
     */
    protected function _buildMailbox()
    {
        $cacheid = $this->_mailbox->cacheid;

        if (!is_null($this->_sorted) && ($this->_cacheid == $cacheid)) {
            return;
        }

        $this->changed = true;
        $this->_cacheid = $cacheid;
        $this->_sorted = array();

        $query_ob = $this->_buildMailboxQuery();
        $sortpref = $this->_mailbox->getSort(true);
        $thread_sort = ($sortpref->sortby == Horde_Imap_Client::SORT_THREAD);

        if ($this->_mailbox->access_search &&
            $this->_mailbox->hideDeletedMsgs()) {
            $delete_query = new Horde_Imap_Client_Search_Query();
            $delete_query->flag(Horde_Imap_Client::FLAG_DELETED, false);

            if (is_null($query_ob)) {
                $query_ob = array(strval($this->_mailbox) => $delete_query);
            } else {
                foreach ($query_ob as $val) {
                    $val->andSearch($delete_query);
                }
            }
        }

        if (is_null($query_ob)) {
            $query_ob = array(strval($this->_mailbox) => null);
        }

        if ($thread_sort) {
            $this->_thread = $this->_threadui = array();
        }

        foreach ($query_ob as $mbox => $val) {
            if ($thread_sort) {
                $this->_getThread($mbox, $val ? array('search' => $val) : array());
                $sorted = $this->_thread[$mbox]->messageList()->ids;
                if ($sortpref->sortdir) {
                    $sorted = array_reverse($sorted);
                }
            } else {
                $res = IMP_Mailbox::get($mbox)->imp_imap->search($mbox, $val, array(
                    'sort' => array($sortpref->sortby)
                ));
                if ($sortpref->sortdir) {
                    $res['match']->reverse();
                }
                $sorted = $res['match']->ids;
            }

            $this->_sorted = array_merge($this->_sorted, $sorted);
            $this->_buildMailboxProcess($mbox, $sorted);
        }
    }

    /**
     */
    protected function _buildMailboxQuery()
    {
        return null;
    }

    /**
     */
    protected function _buildMailboxProcess($mbox, $sorted)
    {
    }

    /**
     * Get the list of unseen messages in the mailbox (IMAP UNSEEN flag, with
     * UNDELETED if we're hiding deleted messages).
     *
     * @param integer $results  A Horde_Imap_Client::SEARCH_RESULTS_* constant
     *                          that indicates the desired return type.
     * @param array $opts       Additional options:
     *   - sort: (array) List of sort criteria to use.
     *   - uids: (boolean) Return UIDs instead of sequence numbers (for
     *           $results queries that return message lists).
     *           DEFAULT: false
     *
     * @return mixed  Whatever is requested in $results.
     */
    public function unseenMessages($results, array $opts = array())
    {
        $count = ($results == Horde_Imap_Client::SEARCH_RESULTS_COUNT);

        if (empty($this->_sorted)) {
            return $count ? 0 : array();
        }

        $criteria = new Horde_Imap_Client_Search_Query();
        $imp_imap = $this->_mailbox->imp_imap;

        if ($this->_mailbox->hideDeletedMsgs()) {
            $criteria->flag(Horde_Imap_Client::FLAG_DELETED, false);
        } elseif ($count) {
            try {
                $status_res = $imp_imap->status($this->_mailbox, Horde_Imap_Client::STATUS_UNSEEN);
                return $status_res['unseen'];
            } catch (IMP_Imap_Exception $e) {
                return 0;
            }
        }

        $criteria->flag(Horde_Imap_Client::FLAG_SEEN, false);

        try {
            $res = $imp_imap->search($this->_mailbox, $criteria, array(
                'results' => array($results),
                'sequence' => empty($opts['uids']),
                'sort' => empty($opts['sort']) ? null : $opts['sort']
            ));
            return $count ? $res['count'] : $res;
        } catch (IMP_Imap_Exception $e) {
            return $count ? 0 : array();
        }
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
            if ($sortpref->sortby == Horde_Imap_Client::SORT_SEQUENCE) {
                try {
                    $res = $this->_mailbox->imp_imap->status($this->_mailbox, Horde_Imap_Client::STATUS_FIRSTUNSEEN | Horde_Imap_Client::STATUS_MESSAGES);
                    if (!is_null($res['firstunseen'])) {
                        return $sortpref->sortdir
                            ? ($res['messages'] - $res['firstunseen'] + 1)
                            : $res['firstunseen'];
                    }
                } catch (IMP_Imap_Exception $e) {}

                return 1;
            }

            $unseen_msgs = $this->unseenMessages(Horde_Imap_Client::SEARCH_RESULTS_MIN, array(
                'sort' => array(Horde_Imap_Client::SORT_DATE),
                'uids' => true
            ));
            return empty($unseen_msgs['min'])
                ? 1
                : ($this->getArrayIndex($unseen_msgs['min']) + 1);

        case IMP::MAILBOX_START_LASTUNSEEN:
            if (!$this->_mailbox->access_sort) {
                return 1;
            }

            $unseen_msgs = $this->unseenMessages(Horde_Imap_Client::SEARCH_RESULTS_MAX, array(
                'sort' => array(Horde_Imap_Client::SORT_DATE),
                'uids' => true
            ));
            return empty($unseen_msgs['max'])
                ? 1
                : ($this->getArrayIndex($unseen_msgs['max']) + 1);
        }
    }

    /**
     * Rebuilds/resets the mailbox list.
     *
     * @param boolean $reset  If true, resets the list instead of rebuilding.
     */
    public function rebuild($reset = false)
    {
        $this->_cacheid = $this->_sorted = null;

        if ($reset) {
            $this->_buidmax = 0;
            $this->_buids = array();
            $this->changed = true;
        } else {
            $this->_buildMailbox();
        }
    }

    /**
     * Returns the array index of the given message UID.
     *
     * @param integer $uid  The message UID.
     * @param string $mbox  The message mailbox (defaults to the current
     *                      mailbox).
     *
     * @return mixed  The array index of the location of the message UID in
     *                the current mailbox. Returns null if not found.
     */
    public function getArrayIndex($uid, $mbox = null)
    {
        $this->_buildMailbox();

        /* array_search() returns false on no result. We will set an
         * unsuccessful result to NULL. */
        return (($aindex = array_search($uid, $this->_sorted)) === false)
            ? null
            : $aindex;
    }

    /**
     * Generate an IMP_Indices object out of the contents of this mailbox.
     *
     * @return IMP_Indices  An indices object.
     */
    public function getIndicesOb()
    {
        $this->_buildMailbox();

        return new IMP_Indices($this->_mailbox, $this->_sorted);
    }

    /**
     * Returns the list of UIDs for an entire thread given one message in
     * that thread.
     *
     * @param integer $uid  The message UID.
     * @param string $mbox  The message mailbox (defaults to the current
     *                      mailbox).
     *
     * @return IMP_Indices  An indices object.
     */
    public function getFullThread($uid, $mbox = null)
    {
        if (is_null($mbox)) {
            $mbox = $this->_mailbox;
        }

        return new IMP_Indices($mbox, array_keys($this->_getThread($mbox)->getThread($uid)));
    }

    /**
     * Returns a thread object for a message.
     *
     * @param integer $offset  Sequence number of message.
     *
     * @return IMP_Mailbox_List_Thread  The thread object.
     */
    public function getThreadOb($offset)
    {
        $entry = $this[$offset];
        $mbox = strval($entry['m']);
        $uid = $entry['u'];

        if (!isset($this->_threadui[$mbox][$uid])) {
            $thread_level = array();
            $t_ob = $this->_getThread($mbox);

            foreach ($t_ob->getThread($uid) as $key => $val) {
                if (is_null($val->base) ||
                    ($val->last && ($val->base == $key))) {
                    $this->_threadui[$mbox][$key] = '';
                    continue;
                }

                if ($val->last) {
                    $join = IMP_Mailbox_List_Thread::JOINBOTTOM;
                } else {
                    $join = (!$val->level && ($val->base == $key))
                        ? IMP_Mailbox_List_Thread::JOINBOTTOM_DOWN
                        : IMP_Mailbox_List_Thread::JOIN;
                }

                $thread_level[$val->level] = $val->last;
                $line = '';

                for ($i = 0; $i < $val->level; ++$i) {
                    if (isset($thread_level[$i])) {
                        $line .= (isset($thread_level[$i]) && !$thread_level[$i])
                            ? IMP_Mailbox_List_Thread::LINE
                            : IMP_Mailbox_List_Thread::BLANK;
                    }
                }

                $this->_threadui[$mbox][$key] = $line . $join;
            }
        }

        return new IMP_Mailbox_List_Thread($this->_threadui[$mbox][$uid]);
    }

    /**
     * Returns the thread object for a mailbox.
     *
     * @param string $mbox  The mailbox.
     * @param array $extra  Extra options to pass to IMAP thread() command.
     *
     * @return Horde_Imap_Client_Data_Thread  Thread object.
     */
    protected function _getThread($mbox, array $extra = array())
    {
        if (!isset($this->_thread[strval($mbox)])) {
            $imp_imap = IMP_Mailbox::get($mbox)->imp_imap;

            try {
                $thread = $imp_imap->thread($mbox, array_merge($extra, array(
                    'criteria' => $imp_imap->thread_algo
                )));
            } catch (Horde_Imap_Client_Exception $e) {
                $thread = new Horde_Imap_Client_Data_Thread(array(), 'uid');
            }

            $this->_thread[strval($mbox)] = $thread;
        }

        return $this->_thread[strval($mbox)];
    }

    /**
     * Get the mailbox for a sequence ID.
     *
     * @param integer $id  Sequence ID.
     *
     * @return IMP_Mailbox  The mailbox.
     */
    protected function _getMbox($id)
    {
        return $this->_mailbox;
    }

    /* Pseudo-UID related methods. */

    /**
     * Create a browser-UID from a mail UID.
     *
     * @param string $mbox  The mailbox.
     * @param integer $uid  UID.
     *
     * @return integer  Browser-UID.
     */
    public function getBuid($mbox, $uid)
    {
        return $uid;
    }

    /**
     * Resolve a mail UID from a browser-UID.
     *
     * @param integer $buid  Browser-UID.
     *
     * @return array  Two-element array:
     *   - m: (IMP_Mailbox) Mailbox of message.
     *   - u: (string) UID of message.
     */
    public function resolveBuid($buid)
    {
        return array(
            'm' => $this->_mailbox,
            'u' => intval($buid)
        );
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
        if (!isset($this->_sorted[$offset - 1])) {
            return null;
        }

        $ret = array(
            'm' => $this->_getMbox($offset - 1),
            'u' => $this->_sorted[$offset - 1]
        );

        return $ret;
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
        return $this[key($this->_sorted) + 1];
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
        return $GLOBALS['injector']->getInstance('Horde_Pack')->pack(
            $this->_serialize(),
            array(
                'compression' => false,
                'phpob' => true
            )
        );
    }

    /**
     */
    protected function _serialize()
    {
        $data = array(
            'm' => $this->_mailbox
        );

        if ($this->_buidmax) {
            $data['bm'] = $this->_buidmax;
            if (!empty($this->_buids)) {
                $data['b'] = $this->_buids;
            }
        }

        if (!is_null($this->_cacheid)) {
            $data['c'] = $this->_cacheid;
        }

        if (!is_null($this->_sorted)) {
            /* Store UIDs in sequence string to save on storage space. */
            if (count($this->_sorted) > self::SERIALIZE_LIMIT) {
                $ids = $this->_mailbox->imp_imap->getIdsOb();
                /* Optimization: we know there are no duplicates in sorted. */
                $ids->duplicates = true;
                $ids->add($this->_sorted);
                $data['so'] = $ids;
            } else {
                $data['so'] = $this->_sorted;
            }
        }

        return $data;
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
        $this->_unserialize(
            $GLOBALS['injector']->getInstance('Horde_Pack')->unpack($data)
        );
    }

    /**
     */
    protected function _unserialize($data)
    {
        $this->_mailbox = $data['m'];

        if (isset($data['bm'])) {
            $this->_buidmax = $data['bm'];
            if (isset($data['b'])) {
                $this->_buids = $data['b'];
            }
        }

        if (isset($data['c'])) {
            $this->_cacheid = $data['c'];
        }

        if (isset($data['so'])) {
            $this->_sorted = is_object($data['so'])
                ? $data['so']->ids
                : $data['so'];
        }
    }

}

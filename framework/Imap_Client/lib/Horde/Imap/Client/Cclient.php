<?php
/**
 * Horde_Imap_Client_Cclient provides an interface to an IMAP server using the
 * PHP imap (c-client) module.
 *
 * PHP IMAP module: http://www.php.net/imap
 *
 * Optional Parameters:
 *   retries - (integer) Connection retries.
 *             DEFAULT: 3
 *   timeout - (array) Timeout value (in seconds) for various actions. Unlinke
 *             the base Horde_Imap_Client class, this driver supports an
 *             array of timeout entries as follows:
 *               'open', 'read', 'write', 'close'
 *             If timeout is a string, the same timeout will be used for all
 *             values.
 *             DEFAULT: C-client default values
 *   validate_cert - (boolean)  If using tls or ssl connections, validate the
 *                   certificate?
 *                   DEFAULT: Don't validate
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Horde_Imap_Client
 */
class Horde_Imap_Client_Cclient extends Horde_Imap_Client_Base
{
    /**
     * The Horde_Imap_Client_Socket object needed to obtain server info.
     *
     * @var Horde_Imap_Client_Socket
     */
    protected $_socket;

    /**
     * The IMAP resource stream.
     *
     * @var resource
     */
    protected $_stream = null;

    /**
     * The IMAP c-client connection string.
     *
     * @var string
     */
    protected $_cstring;

    /**
     * The service to connect to via c-client
     *
     * @var string
     */
    protected $_service = 'imap';

    /**
     * The IMAP flags supported in this driver.
     *
     * @var array
     */
    protected $_supportedFlags = array(
        'seen', 'answered', 'flagged', 'deleted', 'recent', 'draft'
    );

    /**
     * The c-client code -> MIME type conversion table.
     *
     * @var array
     */
    protected $_mimeTypes = array(
        TYPETEXT => 'text',
        TYPEMULTIPART => 'multipart',
        TYPEMESSAGE => 'message',
        TYPEAPPLICATION => 'application',
        TYPEAUDIO => 'audio',
        TYPEIMAGE => 'image',
        TYPEVIDEO => 'video',
        TYPEMODEL => 'model',
        TYPEOTHER => 'other'
    );

    /**
     * The c-client code -> MIME encodings conversion table.
     *
     * @var array
     */
    protected $_mimeEncodings = array(
        ENC7BIT => '7bit',
        ENC8BIT => '8bit',
        ENCBINARY => 'binary',
        ENCBASE64 => 'base64',
        ENCQUOTEDPRINTABLE => 'quoted-printable',
        ENCOTHER => 'unknown'
    );

    /**
     * Constructs a new Horde_Imap_Client_Cclient object.
     *
     * @param array $params  A hash containing configuration parameters.
     */
    public function __construct($params)
    {
        if (!isset($params['retries'])) {
            $params['retries'] = 3;
        }
        parent::__construct($params);
    }

    /**
     * Do cleanup prior to serialization and provide a list of variables
     * to serialize.
     */
    public function __sleep()
    {
        $this->logout();
        parent::__sleep();
        return array_diff(array_keys(get_class_vars(__CLASS__)), array('encryptKey'));
    }

    /**
     * Get CAPABILITY info from the IMAP server.
     *
     * @return array  The capability array.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _capability()
    {
        $cap = $this->_getSocket()->capability();

        /* No need to support these extensions here - the wrapping required
         * to make this work is probably just as resource intensive as what
         * we are trying to avoid. */
        unset($cap['CONDSTORE'], $cap['QRESYNC']);

        return $cap;
    }

    /**
     * Send a NOOP command.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _noop()
    {
        // Already guaranteed to be logged in here.

        $old_error = error_reporting(0);
        $res = imap_ping($this->_stream);
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Received error from IMAP server when sending a NOOP command: ' . imap_last_error());
        }
    }

    /**
     * Get the NAMESPACE information from the IMAP server.
     *
     * @return array  An array of namespace information.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getNamespaces()
    {
        return $this->_getSocket()->getNamespaces();
    }

    /**
     * Return a list of alerts that MUST be presented to the user.
     *
     * @return array  An array of alert messages.
     */
    public function alerts()
    {
        // TODO: check for [ALERT]?
        return imap_alerts();
    }

    /**
     * Login to the IMAP server.
     *
     * @return boolean  Return true if global login tasks should be run.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _login()
    {
        $i = -1;
        $res = false;

        if (!empty($this->_params['secure']) && !extension_loaded('openssl')) {
            throw new Horde_Imap_Client_Exception('Secure connections require the PHP openssl extension: http://php.net/openssl.');
        }

        $mask = ($this->_service == 'pop3') ? 0 : OP_HALFOPEN;

        $old_error = error_reporting(0);
        if (version_compare(PHP_VERSION, '5.2.1') != -1) {
            $res = imap_open($this->_connString(), $this->_params['username'], $this->_params['password'], $mask, $this->_params['retries']);
        } else {
            while (($res === false) &&
                   !strstr(strtolower(imap_last_error()), 'login failure') &&
                   (++$i < $this->_params['retries'])) {
                if ($i != 0) {
                    sleep(1);
                }
                $res = imap_open($this->_connString(), $this->_params['username'], $this->_params['password'], $mask);
            }
        }
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Could not authenticate to IMAP server: ' . imap_last_error());
        }

        $this->_stream = $res;
        $this->_isSecure = !empty($this->_params['secure']);

        $this->setLanguage();

        if (!empty($this->_params['timeout'])) {
            $timeout = array(
                'open' => IMAP_OPENTIMEOUT,
                'read' => IMAP_READTIMEOUT,
                'write' => IMAP_WRITETIMEOUT,
                'close' => IMAP_CLOSETIMEOUT
            );

            foreach ($timeout as $key => $val) {
                if (isset($this->_params['timeout'][$key])) {
                    imap_timeout($val, $this->_params['timeout'][$key]);
                }
            }
        }

        return true;
    }

    /**
     * Log out of the IMAP session.
     */
    protected function _logout()
    {
        if (!is_null($this->_stream)) {
            imap_close($this->_stream);
            $this->_stream = null;
            if (isset($this->_socket)) {
                $this->_socket->logout();
            }
        }
    }

    /**
     * Send ID information to the IMAP server (RFC 2971).
     *
     * @param array $info  The information to send to the server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _sendID($info)
    {
        $this->_getSocket()->sendID($info);
    }

    /**
     * Return ID information from the IMAP server (RFC 2971).
     *
     * @return array  An array of information returned, with the keys as the
     *                'field' and the values as the 'value'.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getID()
    {
        return $this->_getSocket()->getID();
    }

    /**
     * Sets the preferred language for server response messages (RFC 5255).
     *
     * @param array $info  The preferred list of languages.
     *
     * @return string  The language accepted by the server, or null if the
     *                 default language is used.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _setLanguage($langs)
    {
        return $this->_getSocket()->setLanguage($langs);
    }

    /**
     * Gets the preferred language for server response messages (RFC 5255).
     *
     * @param array $list  If true, return the list of available languages.
     *
     * @return mixed  If $list is true, the list of languages available on the
     *                server (may be empty). If false, the language used by
     *                the server, or null if the default language is used.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getLanguage($list)
    {
        return $this->_getSocket()->getLanguage($list);
    }

    /**
     * Open a mailbox.
     *
     * @param string $mailbox  The mailbox to open (UTF7-IMAP).
     * @param integer $mode    The access mode.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _openMailbox($mailbox, $mode)
    {
        $this->login();
        $flag = ($mode == Horde_Imap_Client::OPEN_READONLY) ? OP_READONLY : 0;

        $old_error = error_reporting(0);
        if (version_compare(PHP_VERSION, '5.2.1') != -1) {
            $res = imap_reopen($this->_stream, $this->_connString($mailbox), $flag, $this->_params['retries']);
        } else {
            $res = imap_reopen($this->_stream, $this->_connString($mailbox), $flag);
        }
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Could not open mailbox "' . $mailbox . '": ' . imap_last_error());
        }
    }

    /**
     * Create a mailbox.
     *
     * @param string $mailbox  The mailbox to create (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _createMailbox($mailbox)
    {
        $this->login();

        $old_error = error_reporting(0);
        $res = imap_createmailbox($this->_stream, $this->_connString($mailbox));
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Could not create mailbox "' . $mailbox . '": ' . imap_last_error());
        }
    }

    /**
     * Delete a mailbox.
     *
     * @param string $mailbox  The mailbox to delete (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _deleteMailbox($mailbox)
    {
        $this->login();

        $old_error = error_reporting(0);
        $res = imap_deletemailbox($this->_stream, $this->_connString($mailbox));
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Could not delete mailbox "' . $mailbox . '": ' . imap_last_error());
        }
    }

    /**
     * Rename a mailbox.
     *
     * @param string $old     The old mailbox name (UTF7-IMAP).
     * @param string $new     The new mailbox name (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _renameMailbox($old, $new)
    {
        $this->login();

        $old_error = error_reporting(0);
        $res = imap_renamemailbox($this->_stream, $this->_connString($old), $this->_connString($new));
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Could not rename mailbox "' . $old . '": ' . imap_last_error());
        }
    }

    /**
     * Manage subscription status for a mailbox.
     *
     * @param string $mailbox     The mailbox to [un]subscribe to (UTF7-IMAP).
     * @param boolean $subscribe  True to subscribe, false to unsubscribe.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _subscribeMailbox($mailbox, $subscribe)
    {
        $this->login();

        $old_error = error_reporting(0);
        if ($subscribe) {
            $res = imap_subscribe($this->_stream, $this->_connString($mailbox));
        } else {
            $res = imap_unsubscribe($this->_stream, $this->_connString($mailbox));
        }
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Could not ' . ($subscribe ? 'subscribe' : 'unsubscribe') . ' to mailbox "' . $mailbox . '": ' . imap_last_error());
        }
    }

    /**
     * Obtain a list of mailboxes matching a pattern.
     *
     * @param string $pattern  The mailbox search pattern.
     * @param integer $mode    Which mailboxes to return.
     * @param array $options   Additional options.
     * <pre>
     * For the 'attributes' option, this driver will return only these
     * attributes:
     *   '\noinferiors', '\noselect', '\marked', '\unmarked', '\referral',
     *   '\haschildren', '\hasnochildren'
     * </pre>
     *
     * @return array  See Horde_Imap_Client_Base::listMailboxes().
     * @throws Horde_Imap_Client_Exception
     */
    protected function _listMailboxes($pattern, $mode, $options)
    {
        $this->login();

        switch ($mode) {
        case Horde_Imap_Client::MBOX_ALL:
            if (!empty($options['flat'])) {
                $mboxes = $this->_getMailboxList($pattern, $mode);
                return (empty($options['utf8'])) ? $mboxes : array_map(array('Horde_Imap_Client_Utf7imap', 'Utf7ImapToUtf8'), $mboxes);
            }
            $check = false;
            break;

        case Horde_Imap_Client::MBOX_SUBSCRIBED:
        case Horde_Imap_Client::MBOX_UNSUBSCRIBED:
            $sub = $this->_getMailboxList($pattern, Horde_Imap_Client::MBOX_SUBSCRIBED);
            if (!empty($options['flat'])) {
                if (!empty($options['utf8'])) {
                    $sub = array_map(array('Horde_Imap_Client_Utf7imap', 'Utf7ImapToUtf8'), $sub);
                }
                if ($mode == Horde_Imap_Client::MBOX_SUBSCRIBED) {
                    return $sub;
                }

                $mboxes = $this->_getMailboxList($pattern, Horde_Imap_Client::MBOX_ALL);
                if (!empty($options['utf8'])) {
                    $sub = array_map(array('Horde_Imap_Client_Utf7imap', 'Utf7ImapToUtf8'), $sub);
                }
                return array_values(array_diff($mboxes, $sub));
            }
            $sub = array_flip($sub);
            $check = true;
        }

        $attr = array(
            LATT_NOINFERIORS => '\\noinferiors',
            LATT_NOSELECT => '\\noselect',
            LATT_MARKED => '\\marked',
            LATT_UNMARKED => '\\unmarked',
            LATT_REFERRAL => '\\referral',
            LATT_HASCHILDREN => '\\haschildren',
            LATT_HASNOCHILDREN => '\\hasnochildren'
        );

        $old_error = error_reporting(0);
        $res = imap_getmailboxes($this->_stream, $this->_connString(), $pattern);
        error_reporting($old_error);

        $mboxes = array();
        while (list(,$val) = each($res)) {
            $mbox = substr($val->name, strpos($val->name, '}') + 1);

            if ($check &&
                ((($mode == Horde_Imap_Client::MBOX_UNSUBSCRIBED) &&
                  isset($sub[$mbox])) ||
                 (($mode == Horde_Imap_Client::MBOX_SUBSCRIBED) &&
                  !isset($sub[$mbox])))) {
                continue;
            }

            if (!empty($options['utf8'])) {
                $mbox = Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($mbox);
            }

            $tmp = array('mailbox' => $mbox);
            if (!empty($options['attributes'])) {
                $tmp['attributes'] = array();
                foreach ($attr as $k => $a) {
                    if ($val->attributes & $k) {
                        $tmp['attributes'][] = $a;
                    }
                }
            }
            if (!empty($options['delimiter'])) {
                $tmp['delimiter'] = $val->delimiter;
            }
            $mboxes[$mbox] = $tmp;
        }

        return $mboxes;
    }

    /**
     * Obtain a list of mailboxes matching a pattern.
     *
     * @param string $pattern  The mailbox search pattern.
     * @param integer $mode    Which mailboxes to return.  Either
     *                         Horde_Imap_Client::MBOX_SUBSCRIBED or
     *                         Horde_Imap_Client::MBOX_ALL.
     *
     * @return array  A list of mailboxes in UTF7-IMAP format.
     */
    protected function _getMailboxList($pattern, $mode)
    {
        $mboxes = array();

        $old_error = error_reporting(0);
        if ($mode != Horde_Imap_Client::MBOX_ALL) {
            $res = imap_list($this->_stream, $this->_connString(), $pattern);
        } else {
            $res = imap_lsub($this->_stream, $this->_connString(), $pattern);
        }
        error_reporting($old_error);

        if (is_array($res)) {
            while (list(,$val) = each($res)) {
                $mboxes[] = substr($val, strpos($val, '}') + 1);
            }
        }

        return $mboxes;
    }

    /**
     * Obtain status information for a mailbox.
     *
     * @param string $mailbox  The mailbox to query (UTF7-IMAP).
     * @param string $flags    A bitmask of information requested from the
     *                         server.
     *
     * @return array  See Horde_Imap_Client_Base::status().
     * @throws Horde_Imap_Client_Exception
     */
    protected function _status($mailbox, $flags)
    {
        $this->login();

        /* If FLAGS/PERMFLAGS/HIGHESTMODSEQ/UIDNOTSTICKY are needed, we must
         * use the Socket driver. */
        if (($flags & Horde_Imap_Client::STATUS_FLAGS) ||
            ($flags & Horde_Imap_Client::STATUS_PERMFLAGS) ||
            ($flags & Horde_Imap_Client::STATUS_HIGHESTMODSEQ) ||
            ($flags & Horde_Imap_Client::STATUS_UIDNOTSTICKY)) {
            return $this->_getSocket()->status($mailbox, $flags);
        }

        $items = array(
            Horde_Imap_Client::STATUS_MESSAGES => SA_MESSAGES,
            Horde_Imap_Client::STATUS_RECENT => SA_RECENT,
            Horde_Imap_Client::STATUS_UIDNEXT => SA_UIDNEXT,
            Horde_Imap_Client::STATUS_UIDVALIDITY => SA_UIDVALIDITY,
            Horde_Imap_Client::STATUS_UNSEEN => SA_UNSEEN
        );

        $c_flag = 0;
        $res = null;

        foreach ($items as $key => $val) {
            if ($key & $flags) {
                $c_flag |= $val;
            }
        }

        if (!empty($c_flag)) {
            $res = imap_status($this->_stream, $this->_connString($mailbox), $c_flag);
            if (!is_object($res)) {
                $res = null;
            }
        }

        if ($flags & Horde_Imap_Client::STATUS_FIRSTUNSEEN) {
            $search_query = new Horde_Imap_Client_Search_Query();
            $search_query->flag('\\unseen', false);
            $search = $this->search($mailbox, $search_query, array('results' => array(Horde_Imap_Client::SORT_RESULTS_MIN), 'sequence' => true));

            if (is_null($res)) {
                return array('firstunseen' => $search['min']);
            }
            $res->firstunseen = reset($search);
        }

        if (is_null($res)) {
            return array();
        } else {
            unset($res->flags);
            return (array)$res;
        }
    }

    /**
     * Append message(s) to a mailbox.
     *
     * @param string $mailbox  The mailbox to append the message(s) to
     *                         (UTF7-IMAP).
     * @param array $data      The message data.
     * @param array $options   Additional options.
     *
     * @return mixed  Returns true.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _append($mailbox, $data, $options)
    {
        $this->login();

        /* This driver does not support flags other than those defined in the
         * IMAP4 spec, and does not support 'internaldate'. If either of these
         * conditions exist, use the Socket driver instead. */
        while (list(,$val) = each($data)) {
            if (isset($val['internaldate']) ||
                (!empty($val['flags']) &&
                 $this->_nonSupportedFlags($val['flags']))) {
                return $this->_getSocket()->append($mailbox, $data);
            }
        }

        while (list(,$val) = each($data)) {
            $old_error = error_reporting(0);
            $text = is_resource($val['data']) ? stream_get_contents($val['data']) : $val['data'];
            $res = imap_append($this->_stream, $this->_connString($mailbox), $this->utils->removeBareNewlines($text), empty($val['flags']) ? null : implode(' ', $val['flags']));
            error_reporting($old_error);

            if ($res === false) {
                if (!empty($options['create'])) {
                    $this->createMailbox($mailbox);
                    unset($options['create']);
                    return $this->_append($mailbox, $data, $options);
                }
                throw new Horde_Imap_Client_Exception('Could not append message to IMAP server: ' . imap_last_error());
            }
        }

        return true;
    }

    /**
     * Request a checkpoint of the currently selected mailbox.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _check()
    {
        // Already guaranteed to be logged in here.

        $old_error = error_reporting(0);
        $res = imap_check($this->_stream);
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Received error from IMAP server when sending a CHECK command: ' . imap_last_error());
        }
    }

    /**
     * Close the connection to the currently selected mailbox, optionally
     * expunging all deleted messages (RFC 3501 [6.4.2]).
     *
     * @param array $options  Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _close($options)
    {
        if (!empty($options['expunge'])) {
            $this->expunge($this->_selected);
        }
        $this->openMailbox($this->_selected, Horde_Imap_Client::OPEN_READONLY);
    }

    /**
     * Expunge deleted messages from the given mailbox.
     *
     * @param array $options  Additional options.
     *
     * @return array  If 'list' option is true, returns the list of
     *                expunged messages.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _expunge($options)
    {
        // Already guaranteed to be logged in here.

        $msg_list = !empty($options['list']);

        if (!empty($options['ids']) || $msg_list) {
            $search_query = new Horde_Imap_Client_Search_Query();
            $search_query->flag('\\deleted');
            $ids = $this->search($this->_selected, $search_query, array('sequence' => $use_seq));
        }

        if (empty($options['ids'])) {
            $old_error = error_reporting(0);
            imap_expunge($this->_stream);
            error_reporting($old_error);
            return $msg_list ? $ids['match'] : null;
        }

        $use_seq = !empty($options['sequence']);

        // Need to temporarily unflag all messages marked as deleted but not
        // a part of requested UIDs to delete.
        if (!empty($ids['match'])) {
            $unflag = array_diff($ids['match'], $options['ids']);
            if (!empty($unflag)) {
                $this->store($this->_selected, array('ids' => $unflag, 'remove' => array('\\deleted'), 'sequence' => $use_seq));
            }

            /* If we are using a cache, we need to get the list of
             * messages that will be expunged. */
            if ($this->_initCache($this->_selected)) {
                if ($use_seq) {
                    $res = $this->search($this->_selected, $search_query);
                    $expunged = $res['match'];
                } else {
                    $expunged = array_intersect($ids['match'], $options['ids']);
                }

                if (!empty($expunged)) {
                    $this->_cache->deleteMsgs($this->_selected, $expunged);
                }
            }
        }

        $old_error = error_reporting(0);
        imap_expunge($this->_stream);
        error_reporting($old_error);

        if (!empty($unflag)) {
            $this->store($this->_selected, array('add' => array('\\deleted'), 'ids' => $unflag, 'sequence' => $use_seq));
        }

        return $msg_list ? $ids['match'] : null;
    }

    /**
     * Search a mailbox.
     *
     * @param object $query   The search string.
     * @param array $options  Additional options. The '_query' key contains
     *                        the value of $query->build().
     *
     * @return array  An array of UIDs (default) or an array of message
     *                sequence numbers (if 'sequence' is true).
     * @throws Horde_Imap_Client_Exception
     */
    protected function _search($query, $options)
    {
        // Already guaranteed to be logged in here.

        /* If more than 1 sort criteria given, or if SORT_REVERSE is given
         * as a sort criteria, or search query uses IMAP4 criteria, use the
         * Socket client instead. */
        if ($options['_query']['imap4'] ||
            (!empty($options['sort']) &&
             ((count($options['sort']) > 1) ||
             in_array(Horde_Imap_Client::SORT_REVERSE, $options['sort'])))) {
            return $this->_getSocket()->search($this->_selected, $query, $options);
        }

        $old_error = error_reporting(0);
        if (empty($options['sort'])) {
            $res = imap_search($this->_stream, $options['_query']['query'], empty($options['sequence']) ? SE_UID : 0, $options['_query']['charset']);
        } else {
            $sort_criteria = array(
                Horde_Imap_Client::SORT_ARRIVAL => SORTARRIVAL,
                Horde_Imap_Client::SORT_CC => SORTCC,
                Horde_Imap_Client::SORT_DATE => SORTDATE,
                Horde_Imap_Client::SORT_FROM => SORTFROM,
                Horde_Imap_Client::SORT_SIZE => SORTSIZE,
                Horde_Imap_Client::SORT_SUBJECT => SORTSUBJECT,
                Horde_Imap_Client::SORT_TO => SORTTO
            );

            $res = imap_sort($this->_stream, $sort_criteria[reset($options['sort'])], 0, empty($options['sequence']) ? SE_UID : 0, $options['_query']['query'], $options['_query']['charset']);
        }
        $res = ($res === false) ? array() : $res;
        error_reporting($old_error);

        $ret = array();
        foreach ($options['results'] as $val) {
            switch ($val) {
            case Horde_Imap_Client::SORT_RESULTS_COUNT:
                $ret['count'] = count($res);
                break;

            case Horde_Imap_Client::SORT_RESULTS_MATCH:
                $ret[empty($options['sort']) ? 'match' : 'sort'] = $res;
                break;

            case Horde_Imap_Client::SORT_RESULTS_MAX:
                $ret['max'] = empty($res) ? null : max($res);
                break;

            case Horde_Imap_Client::SORT_RESULTS_MIN:
                $ret['min'] = empty($res) ? null : min($res);
                break;
            }
        }

        return $ret;
    }

    /**
     * Set the comparator to use for searching/sorting (RFC 5255).
     *
     * @param string $comparator  The comparator string (see RFC 4790 [3.1] -
     *                            "collation-id" - for format). The reserved
     *                            string 'default' can be used to select
     *                            the default comparator.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _setComparator($comparator)
    {
        return $this->_getSocket()->setComparator($comparator);
    }

    /**
     * Get the comparator used for searching/sorting (RFC 5255).
     *
     * @return mixed  Null if the default comparator is being used, or an
     *                array of comparator information (see RFC 5255 [4.8]).
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getComparator()
    {
        return $this->_getSocket()->getComparator();
    }

    /**
     * Thread sort a given list of messages (RFC 5256).
     *
     * @param array $options  Additional options.
     *
     * @return array  See Horde_Imap_Client_Base::_thread().
     * @throws Horde_Imap_Client_Exception
     */
    protected function _thread($options)
    {
        // Already guaranteed to be logged in here

        /* This driver only supports Horde_Imap_Client::THREAD_REFERENCES
         * and does not support defining search criteria. */
        if (!empty($options['search']) ||
            (!empty($options['criteria']) &&
             $options['criteria'] != Horde_Imap_Client::THREAD_REFERENCES)) {
            return $this->_getSocket()->thread($this->_selected, $options);
        }

        $use_seq = !empty($options['sequence']);

        $old_error = error_reporting(0);
        $ob = imap_thread($this->_stream, $use_seq ? 0 : SE_UID);
        error_reporting($old_error);

        if (empty($ob)) {
            return array();
        }

        $container = $container_base = $last_index = $thread_base = $thread_base_idx = $uid = null;
        $lookup = $ret = array();
        $i = $last_i = $level = 0;

        reset($ob);
        while (list($key, $val) = each($ob)) {
            $pos = strpos($key, '.');
            $index = substr($key, 0, $pos);
            $type = substr($key, $pos + 1);

            switch ($type) {
            case 'num':
                if ($val === 0) {
                    $container = $index;
                } else {
                    ++$i;
                    if (is_null($container) && empty($level)) {
                        $thread_base = $val;
                        $thread_base_idx = $index;
                    }
                    $lookup[$index] = $use_seq ? $index : $val;
                    $ret[$val] = array();
                }
                break;

            case 'next':
                if (!is_null($container) && ($container === $index)) {
                    $container_base = $val;
                } else {
                    $ret[$lookup[$index]]['b'] = (!is_null($container))
                        ? $lookup[$container_base]
                        : ((!empty($level) || ($val != 0)) ? $lookup[$thread_base_idx] : null);
                    ++$i;
                    ++$level;
                }
                break;

            case 'branch':
                if ($container === $index) {
                    $container = $container_base = null;
                } else {
                    if ($level--) {
                        $ret[$lookup[$index]]['l'] = $level + 1;
                    }
                    if (!is_null($container) && empty($level)) {
                        $ret[$lookup[$index]]['s'] = true;
                    }
                    if ($index === $thread_base_idx) {
                        $index = null;

                    } elseif (!empty($level) &&
                              !is_null($last_index) &&
                              isset($ret[$last_index])) {
                        if (!($last_i == ($i - 1))) {
                            $ret[$lookup[$last_index]]['s'] = true;
                        }
                    }
                }
                $last_index = $index;
                $last_i = $i++;
                break;
            }
        }

        return $ret;
    }

    /**
     * Fetch message data.
     *
     * @param array $criteria  The fetch criteria.
     * @param array $options   Additional options.
     *
     * @return array  See self::fetch().
     * @throws Horde_Imap_Client_Exception
     */
    protected function _fetch($criteria, $options)
    {
        // Already guaranteed to be logged in here

        $err = false;
        $hdrinfo = $overview = null;

        $old_error = error_reporting(0);

        // These options are not supported by this driver.
        if (!empty($options['changedsince']) ||
            (reset($options['ids']) == Horde_Imap_Client::USE_SEARCHRES)) {
            return $this->_getSocket()->fetch($this->_selected, $criteria, $options);
        }

        if (empty($options['ids'])) {
            $seq = '1:*';
            $options['ids'] = range(1, imap_num_msg($this->_stream));
        } else {
            $seq = $this->utils->toSequenceString($options['ids']);
        }

        $ret = array_combine($options['ids'], array_fill(0, count($options['ids']), array()));

        foreach ($criteria as $type => $c_val) {
            if (!is_array($c_val)) {
                $c_val = array();
            }

            switch ($type) {
            case Horde_Imap_Client::FETCH_STRUCTURE:
                // 'noext' has no effect in this driver
                foreach ($options['ids'] as $id) {
                    $structure = imap_fetchstructure($this->_stream, $id, empty($options['sequence']) ? FT_UID : 0);
                    if (!$structure) {
                        $err = true;
                        break 2;
                    }
                    $structure = $this->_parseStructure($structure);
                    $ret[$id]['structure'] = empty($c_val['parse']) ? $structure : Horde_Mime_Part::parseStructure($structure);
                }
                break;

            case Horde_Imap_Client::FETCH_FULLMSG:
                foreach ($options['ids'] as $id) {
                    $tmp = imap_fetchheader($this->_stream, $id, (empty($options['sequence']) ? FT_UID : 0) | FT_PREFETCHTEXT) .
                           imap_body($this->_stream, $id, (empty($options['sequence']) ? FT_UID : 0) | (empty($c_val['peek']) ? 0 : FT_PEEK));
                    if (isset($c_val['start']) && !empty($c_val['length'])) {
                        $ret[$id]['fullmsg'] = substr($tmp, $c_val['start'], $c_val['length']);
                    } else {
                        $ret[$id]['fullmsg'] = $tmp;
                    }

                    if (!empty($c_val['stream'])) {
                        $ptr = fopen('php://temp', 'r+');
                        fwrite($ptr, $tmp);
                        $ret[$id]['fullmsg'] = $ptr;
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_HEADERTEXT:
            case Horde_Imap_Client::FETCH_BODYPART:
                foreach ($c_val as $val) {
                    if ($type == Horde_Imap_Client::FETCH_HEADERTEXT) {
                        $label = 'headertext';
                        /* imap_fetchbody() can return header parts for a
                         * given MIME part by appending '.0' (or 0 for the
                         * main header) */
                        if (empty($val['id'])) {
                            $val['id'] = 0;
                            $body_key = 0;
                        } else {
                            $body_key = $val['id'] . '.0';
                        }
                    } else {
                        $label = 'bodypart';
                        if (empty($val['id'])) {
                            throw new Horde_Imap_Client_Exception('Need a MIME ID when retrieving a MIME body part.');
                        }
                        $body_key = $val['id'];
                    }

                    foreach ($options['ids'] as $id) {
                        if (!isset($ret[$id][$label])) {
                            $ret[$id][$label] = array();
                        }
                        $tmp = imap_fetchbody($this->_stream, $id, $header_key, empty($options['sequence']) ? FT_UID : 0);

                        if (isset($val['start']) && !empty($val['length'])) {
                            $tmp = substr($tmp, $val['start'], $val['length']);
                        }

                        if ($type == Horde_Imap_Client::FETCH_BODYPART) {
                            if (!empty($val['parse'])) {
                                $tmp = Horde_Mime_Headers::parseHeaders($tmp);
                            } elseif (!empty($val['stream'])) {
                                $ptr = fopen('php://temp', 'r+');
                                fwrite($ptr, $tmp);
                                $tmp = $ptr;
                            }
                        }

                        $ret[$id][$label][$val['id']] = $tmp;
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_BODYTEXT:
                foreach ($c_val as $val) {
                    // This is the base body.  This is easily obtained via
                    // imap_body().
                    $use_imapbody = empty($val['id']);

                    foreach ($options['ids'] as $id) {
                        if (!isset($ret[$id]['bodytext'])) {
                            $ret[$id]['bodytext'] = array();
                        }
                        if ($use_imapbody) {
                            $tmp = imap_body($this->_stream, $id, (empty($options['sequence']) ? FT_UID : 0) | (empty($val['peek']) ? 0 : FT_PEEK));
                            if (isset($val['start']) && !empty($val['length'])) {
                                $ret[$id]['bodytext'][0] = substr($tmp, $val['start'], $val['length']);
                            } else {
                                $ret[$id]['bodytext'][0] = $tmp;
                            }
                        } else {
                            /* OY! There is no way to download just the body
                             * of the message/rfc822 part.  The best we can do
                             * is download the header of the part, determine
                             * the length, and then remove that info from the
                             * beginning of the imap_fetchbody() data. */
                            $hdr_len = strlen(imap_fetchbody($this->_stream, $id, $val['id'] . '.0', (empty($options['sequence']) ? FT_UID : 0)));
                            $tmp = substr(imap_fetchbody($this->_stream, $id, $val['id'], (empty($options['sequence']) ? FT_UID : 0)), $hdr_len);
                            if (isset($val['start']) && !empty($val['length'])) {
                                $ret[$id]['bodytext'][$val['id']] = substr($tmp, $val['start'], $val['length']);
                            } else {
                                $ret[$id]['bodytext'][$val['id']] = $tmp;
                            }
                        }

                        if (!empty($val['stream'])) {
                            $ptr = fopen('php://temp', 'r+');
                            fwrite($ptr, $ret[$id]['bodytext'][$val['id']);
                            $ret[$id]['bodytext'][$val['id']] = $ptr;
                        }
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_MIMEHEADER:
            case Horde_Imap_Client::FETCH_HEADERS:
            case Horde_Imap_Client::FETCH_MODSEQ:
                // Can't do it. Nope. Nada. Without heavy duty parsing of the
                // full imap_body() object, it is impossible to retrieve the
                // MIME headers for each individual part. Ship it off to
                // the Socket driver. Adios.
                // This goes for header field searches also.
                // MODSEQ isn't available in c-client either.
                switch ($type) {
                case Horde_Imap_Client::FETCH_MIMEHEADER:
                    $label = 'mimeheader';
                    break;

                case Horde_Imap_Client::FETCH_HEADERS:
                    $label = 'headers';
                    break;

                case Horde_Imap_Client::FETCH_MODSEQ:
                    $label = 'modseq';
                    break;
                }
                $tmp = $this->_getSocket()->fetch($this->_selected, array($type => $c_val), $options);
                foreach ($tmp as $id => $id_data) {
                    if (!isset($ret[$id][$label])) {
                        $ret[$id][$label] = array();
                    }
                    $ret[$id][$label] = array_merge($ret[$id][$label], $id_data[$label]);
                }
                break;

            case Horde_Imap_Client::FETCH_ENVELOPE:
                if (is_null($hdrinfo)) {
                    $hdrinfo = array();
                    foreach ($options['ids'] as $id) {
                        $hdrinfo[$id] = imap_headerinfo($this->_stream, empty($options['sequence']) ? imap_msgno($this->_stream, $id) : $id);
                        if (!$hdrinfo[$id]) {
                            $err = true;
                            break 2;
                        }
                    }
                }

                $env_data = array(
                    'date', 'subject', 'from', 'sender', 'reply_to', 'to',
                    'cc', 'bcc', 'in_reply_to', 'message_id'
                );

                foreach ($options['ids'] as $id) {
                    $hptr = &$hdrinfo[$id];
                    $ret[$id]['envelope'] = array();
                    $ptr = &$ret[$id]['envelope'];

                    foreach ($env_data as $e_val) {
                        $label = strtr($e_val, '_', '-');
                        if (isset($hptr->$e_val)) {
                            if (is_array($hptr->$e_val)) {
                                $tmp = array();
                                foreach ($hptr->$e_val as $a_val) {
                                    $tmp[] = (array)$a_val;
                                }
                                $ptr[$label] = $tmp;
                            } else {
                                $ptr[$label] = $hptr->$e_val;
                            }
                        } else {
                            $ptr[$label] = null;
                        }
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_FLAGS:
                if (is_null($overview)) {
                    $overview = imap_fetch_overview($this->_stream, $seq, empty($options['sequence']) ? FT_UID : 0);
                    if (!$overview) {
                        $err = true;
                        break 2;
                    }
                }

                foreach ($options['ids'] as $id) {
                    $tmp = array();
                    foreach ($this->_supportedFlags as $f_val) {
                        if ($overview[$id]->$f_val) {
                            $tmp[] = '\\' . $f_val;
                        }
                    }
                    $ret[$id]['flags'] = $tmp;
                }
                break;

            case Horde_Imap_Client::FETCH_DATE:
                if (is_null($hdrinfo)) {
                    $hdrinfo = array();
                    foreach ($options['ids'] as $id) {
                        $hdrinfo[$id] = imap_headerinfo($this->_stream, empty($options['sequence']) ? imap_msgno($this->_stream, $id) : $id);
                        if (!$hdrinfo[$id]) {
                            $err = true;
                            break 2;
                        }
                    }
                }

                foreach ($options['ids'] as $id) {
                    $ret[$id]['date'] = new Horde_Imap_Client_DateTime($hdrinfo[$id]->MailDate);
                }
                break;

            case Horde_Imap_Client::FETCH_SIZE:
                if (!is_null($hdrinfo)) {
                    foreach ($options['ids'] as $id) {
                        $ret[$id]['size'] = $hdrinfo[$id]->Size;
                    }
                } else {
                    if (is_null($overview)) {
                        $overview = imap_fetch_overview($this->_stream, $seq, empty($options['sequence']) ? FT_UID : 0);
                        if (!$overview) {
                            $err = true;
                            break;
                        }
                    }
                    foreach ($options['ids'] as $id) {
                        $ret[$id]['size'] = $overview[$id]->size;
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_UID:
                if (empty($options['sequence'])) {
                    foreach ($options['ids'] as $id) {
                        $ret[$id]['uid'] = $id;
                    }
                } else {
                    if (is_null($overview)) {
                        $overview = imap_fetch_overview($this->_stream, $seq, empty($options['sequence']) ? FT_UID : 0);
                        if (!$overview) {
                            $err = true;
                            break;
                        }
                    }
                    foreach ($options['ids'] as $id) {
                        $ret[$id]['uid'] = $overview[$id]->uid;
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_SEQ:
                if (!empty($options['sequence'])) {
                    foreach ($options['ids'] as $id) {
                        $ret[$id]['seq'] = $id;
                    }
                } else {
                    if (is_null($overview)) {
                        $overview = imap_fetch_overview($this->_stream, $seq, empty($options['sequence']) ? FT_UID : 0);
                        if (!$overview) {
                            $err = true;
                            break;
                        }
                    }
                    foreach ($options['ids'] as $id) {
                        $ret[$id]['uid'] = $overview[$id]->msgno;
                    }
                }
            }
        }
        error_reporting($old_error);

        if ($err) {
            throw new Horde_Imap_Client_Exception('Error when fetching messages: ' . imap_last_error());
        }

        return $ret;
    }

    /**
     * Parse the output from imap_fetchstructure() in the format that
     * this class returns structure data in.
     *
     * @param object $data  Data from imap_fetchstructure().
     *
     * @return array  See self::fetch() for structure return format.
     */
    protected function _parseStructure($data)
    {
        // Required entries
        $ret = array(
            'type' => $this->_mimeTypes[$data->type],
            'subtype' => $data->ifsubtype ? strtolower($data->subtype) : 'x-unknown'
        );

        // Optional for multipart-parts, required for all others
        if ($data->ifparameters) {
            $ret['parameters'] = array();
            foreach ($data->parameters as $val) {
                $ret['parameters'][$val->attribute] = $val->value;
            }
        }

        // Optional entries. 'location' and 'language' not supported
        if ($data->ifdisposition) {
            $ret['disposition'] = $data->disposition;
            if ($data->ifdparameters) {
                $ret['dparameters'] = array();
                foreach ($data->dparameters as $val) {
                    $ret['dparameters'][$val->attribute] = $val->value;
                }
            }
        }

        if ($ret['type'] == 'multipart') {
            // multipart/* specific entries
            $ret['parts'] = array();
            foreach ($data->parts as $val) {
                $ret['parts'][] = $this->_parseStructure($val);
            }
        } else {
            // Required options
            $ret['id'] = $data->ifid ? $data->id : null;
            $ret['description'] = $data->ifdescription ? $data->description : null;
            $ret['encoding'] = $this->_mimeEncodings[$data->encoding];
            $ret['size'] = $data->bytes;

            // Part specific options
            if (($ret['type'] == 'message') && ($ret['subtype'] == 'rfc822')) {
                // @todo - Doesn't seem to be an easy way to obtain the
                // envelope information for this part.
                $ret['envelope'] = array();
                $ret['structure'] = $this->_parseStructure(reset($data->parts));
                $ret['lines'] = $data->lines;
            } elseif ($ret['type'] == 'text') {
                $ret['lines'] = $data->lines;
            }

            // No support for 'md5' option
        }

        return $ret;
    }

    /**
     * Store message flag data.
     *
     * @param array $options  Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _store($options)
    {
        // Already guaranteed to be logged in here

        /* This driver does not support flags other than those defined in the
         * IMAP4 spec. If other flags exist, need to use the Socket driver
         * instead. */
        foreach (array('add', 'remove') as $val) {
            if (!empty($options[$val]) &&
                $this->_nonSupportedFlags($options[$val])) {
                return $this->_getSocket()->store($this->_selected, $options);
            }
        }

        // This driver does not support the 'unchangedsince' or 'replace'
        // options, nor does it support using stored searches.
        if (!empty($options['unchangedsince']) ||
            !empty($options['replace']) ||
            (reset($options['ids']) == Horde_Imap_Client::USE_SEARCHRES)) {
            // Requires Socket driver.
            return $this->_getSocket()->store($this->_selected, $options);
        }

        $seq = empty($options['ids'])
            ? '1:*'
            : $this->utils->toSequenceString($options['ids']);

        $old_error = error_reporting(0);

        if (!empty($options['add'])) {
            $res = imap_setflag_full($this->_stream, $seq, implode(' ', $options['add']), empty($options['sequence']) ? ST_UID : 0);
        }

        if (($res === true) && !empty($options['remove'])) {
            $res = imap_clearflag_full($this->_stream, $seq, implode(' ', $options['remove']), empty($options['sequence']) ? ST_UID : 0);
        }

        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Error when flagging messages: ' . imap_last_error());
        }

        return array();
    }

    /**
     * Copy messages to another mailbox.
     *
     * @param string $dest    The destination mailbox (UTF7-IMAP).
     * @param array $options  Additional options.
     *
     * @return boolean  True on success (this driver does not support
     *                  returning the UIDs).
     * @throws Horde_Imap_Client_Exception
     */
    protected function _copy($dest, $options)
    {
        // Already guaranteed to be logged in here

        $opts = 0;
        if (empty($options['sequence'])) {
            $opts |= CP_UID;
        }
        if (!empty($options['move'])) {
            $opts |= CP_MOVE;
        }

        if (reset($options['ids']) == Horde_Imap_Client::USE_SEARCHRES) {
            // Requires Socket driver.
            return $this->_getSocket()->copy($this->_selected, $options);
        }

        $seq = empty($options['ids'])
            ? '1:*'
            : $this->utils->toSequenceString($options['ids']);

        $old_error = error_reporting(0);
        $res = imap_mail_copy($this->_stream, $seq, $this->_connString($dest), $opts);
        error_reporting($old_error);

        if ($res === false) {
            if (!empty($options['create'])) {
                $this->createMailbox($dest);
                unset($options['create']);
                return $this->copy($dest, $options);
            }
            throw new Horde_Imap_Client_Exception('Error when copying/moving messages: ' . imap_last_error());
        }

        return true;
    }

    /**
     * Set quota limits.
     *
     * @param string $root    The quota root (UTF7-IMAP).
     * @param array $options  Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _setQuota($root, $options)
    {
        // This driver only supports setting the 'STORAGE' quota.
        if (isset($options['messages'])) {
            $this->_getSocket()->setQuota($root, $options);
            return;
        }

        $this->login();

        $old_error = error_reporting(0);
        $res = imap_set_quota($this->_stream, $root, $options['storage']);
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Error when setting quota: ' . imap_last_error());
        }
    }

    /**
     * Get quota limits.
     *
     * @param string $root  The quota root (UTF7-IMAP).
     *
     * @return mixed  An array with these possible keys: 'messages' and
     *                'storage'; each key holds an array with 2 values:
     *                'limit' and 'usage'.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getQuota($root)
    {
        $this->login();

        $old_error = error_reporting(0);
        $res = imap_get_quota($this->_stream, $root);
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Error when retrieving quota: ' . imap_last_error());
        }
    }

    /**
     * Get quota limits for a mailbox.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     *
     * @return mixed  An array with the keys being the quota roots. Each key
     *                holds an array with two possible keys: 'messages' and
     *                'storage'; each of these keys holds an array with 2
     *                values: 'limit' and 'usage'.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getQuotaRoot($mailbox)
    {
        $this->login();

        $old_error = error_reporting(0);
        $res = imap_get_quotaroot($this->_stream, $mailbox);
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Error when retrieving quotaroot: ' . imap_last_error());
        }

        return array($mailbox => $ret);
    }

    /**
     * Set ACL rights for a given mailbox/identifier.
     *
     * @param string $mailbox     A mailbox (UTF7-IMAP).
     * @param string $identifier  The identifier to alter (UTF7-IMAP).
     * @param array $options      Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _setACL($mailbox, $identifier, $options)
    {
        $this->login();

        if (empty($options['rights']) && !empty($options['remove'])) {
            $acl = $this->listACLRights($mailbox, $identifier);
            if (empty($acl['rights'])) {
                return;
            }
            $options['rights'] = $acl['rights'];
            $options['remove'] = true;
        }

        if (empty($options['rights'])) {
            return;
        }

        $old_error = error_reporting(0);
        $res = imap_setacl($this->_stream, $mailbox, $identifier, (empty($options['remove']) ? '+' : '-') . $implode('', $options['rights']));
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Error when setting ACL: ' . imap_last_error());
        }
    }

    /**
     * Get ACL rights for a given mailbox.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     *
     * @return array  An array with identifiers as the keys and an array of
     *                rights as the values.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getACL($mailbox)
    {
        $this->login();

        $acl = array();

        $old_error = error_reporting(0);
        $res = imap_getacl($this->_stream, $mailbox);
        error_reporting($old_error);

        if ($res === false) {
            throw new Horde_Imap_Client_Exception('Error when retrieving ACLs: ' . imap_last_error());
        }

        foreach ($res as $id => $rights) {
            $acl[$id] = array();
            for ($i = 0, $iMax = strlen($rights); $i < $iMax; ++$i) {
                $acl[$id][] = $rights[$i];
            }
        }

        return $acl;
    }

    /**
     * Get ACL rights for a given mailbox/identifier.
     *
     * @param string $mailbox     A mailbox (UTF7-IMAP).
     * @param string $identifier  The identifier (UTF-8).
     *
     * @return array  An array of rights (keys: 'required' and 'optional').
     * @throws Horde_Imap_Client_Exception
     */
    protected function _listACLRights($mailbox, $identifier)
    {
        $acl = $this->getACL($mailbox);
        // @todo - Does this return 'optional' information?
        return isset($acl[$identifier]) ? $acl[$identifier] : array();
    }

    /**
     * Get the ACL rights for the current user for a given mailbox.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     *
     * @return array  An array of rights.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getMyACLRights($mailbox)
    {
        // No support in c-client for MYRIGHTS - need to call Socket driver
        return $this->_getSocket()->getMyACLRights($mailbox);
    }

    /* Internal functions */

    /**
     * Create a Horde_Imap_Client_Socket instance pre-filled with this client's
     * parameters.
     *
     * @return Horde_Imap_Client_Socket  The socket instance.
     */
    protected function _getSocket()
    {
        if (!isset($this->_socket)) {
            $this->_socket = Horde_Imap_Client::factory('Socket', $this->_params);
        }
        return $this->_socket;
    }

    /**
     * Generate the c-client connection string.
     *
     * @param string $mailbox  The mailbox to add to the connection string.
     *
     * @return string  The connection string.
     */
    protected function _connString($mailbox = '')
    {
        if (isset($this->_cstring)) {
            return $this->_cstring . $mailbox;
        }

        $conn = '{' . $this->_params['hostspec'] . ':' . $this->_params['port'] . '/service=' . $this->_service;

        switch ($this->_params['secure']) {
        case 'ssl':
            $conn .= '/ssl';
            if (empty($this->_params['validate_cert'])) {
                $conn .= '/novalidate-cert';
            }
            break;

        case 'tls':
            $conn .= '/tls';
            if (empty($this->_params['validate_cert'])) {
                $conn .= '/novalidate-cert';
            }
            break;

        default:
            $conn .= '/notls';
            break;
        }
        $this->_cstring = $conn . '}';

        return $this->_cstring . $mailbox;
    }

    /**
     * Checks a flag list for non-supported c-client flags.
     *
     * @param array $flags  The list of flags.
     *
     * @return boolean  True if there is a non-supported flag in $flags.
     */
    protected function _nonSupportedFlags($flags)
    {
        // This driver does not support flags other than 'Seen', 'Answered',
        // 'Flagged', 'Deleted', 'Recent', and 'Draft'.
        foreach (array_map('strtolower', $flags) as $val) {
            if (!in_array($val, $this->_supportedFlags)) {
                return true;
            }
        }

        return false;
    }

}

<?php
/**
 * Horde_Imap_Client_Cclient provides an interface to an IMAP server using the
 * PHP imap (c-client) module.
 *
 * PHP IMAP module: http://www.php.net/imap
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
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
     * @param array $params  Additional optional parameters for this driver:
     * <pre>
     * retries - (integer) Connection retries.
     *           DEFAULT: 3
     * timeout - (array) Timeout value (in seconds) for various actions.
     *           Unlike the base class, this driver supports an array of
     *           timeout entries as follows:
     *             'open', 'read', 'write', 'close'
     *           If timeout is an integer/string, the same timeout will be
     *           used for all values.
     *           DEFAULT: c-client default values
     * validate_cert - (boolean)  If using tls or ssl connections, validate the
     *                 certificate?
     *                 DEFAULT: false (don't validate)
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['retries'])) {
            $params['retries'] = 3;
        }

        $this->_storeVars[] = '_cstring';
        $this->_storeVars[] = '_service';

        parent::__construct($params);
    }

    /**
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
     */
    protected function _noop()
    {
        // Already guaranteed to be logged in here.

        if (@imap_ping($this->_stream) === false) {
            $this->_exception('Received error from IMAP server when sending a NOOP command: ' . imap_last_error());
        }
    }

    /**
     */
    protected function _getNamespaces()
    {
        return $this->_getSocket()->getNamespaces();
    }

    /**
     */
    public function alerts()
    {
        // TODO: check for [ALERT]?
        return imap_alerts();
    }

    /**
     */
    protected function _login()
    {
        $i = -1;
        $res = false;

        if (!empty($this->_params['secure']) && !extension_loaded('openssl')) {
            $this->_exception('Secure connections require the PHP openssl extension: http://php.net/openssl.');
        }

        $mask = ($this->_service == 'pop3') ? 0 : OP_HALFOPEN;

        if (version_compare(PHP_VERSION, '5.2.1') != -1) {
            $res = @imap_open($this->_connString(), $this->_params['username'], $this->getParam('password'), $mask, $this->_params['retries']);
        } else {
            while (($res === false) &&
                   !strstr(strtolower(imap_last_error()), 'login failure') &&
                   (++$i < $this->_params['retries'])) {
                if ($i != 0) {
                    sleep(1);
                }
                $res = @imap_open($this->_connString(), $this->_params['username'], $this->getParam('password'), $mask);
            }
        }

        if ($res === false) {
            $this->_exception('Could not authenticate to IMAP server: ' . imap_last_error());
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
     */
    protected function _sendID($info)
    {
        $this->_getSocket()->sendID($info);
    }

    /**
     */
    protected function _getID()
    {
        return $this->_getSocket()->getID();
    }

    /**
     */
    protected function _setLanguage($langs)
    {
        return $this->_getSocket()->setLanguage($langs);
    }

    /**
     */
    protected function _getLanguage($list)
    {
        return $this->_getSocket()->getLanguage($list);
    }

    /**
     */
    protected function _openMailbox($mailbox, $mode)
    {
        $this->login();
        $flag = ($mode == Horde_Imap_Client::OPEN_READONLY) ? OP_READONLY : 0;

        $res = (version_compare(PHP_VERSION, '5.2.1') != -1)
            ? @imap_reopen($this->_stream, $this->_connString($mailbox), $flag, $this->_params['retries'])
            : @imap_reopen($this->_stream, $this->_connString($mailbox), $flag);

        if ($res === false) {
            $this->_exception('Could not open mailbox "' . $mailbox . '": ' . imap_last_error());
        }
    }

    /**
     */
    protected function _createMailbox($mailbox, $opts)
    {
        if (isset($opts['special_use'])) {
            $this->_getSocket()->createMailbox($mailbox, $opts);
            return;
        }

        $this->login();

        if (@imap_createmailbox($this->_stream, $this->_connString($mailbox)) === false) {
            $this->_exception('Could not create mailbox "' . $mailbox . '": ' . imap_last_error());
        }
    }

    /**
     */
    protected function _deleteMailbox($mailbox)
    {
        $this->login();

        if (@imap_deletemailbox($this->_stream, $this->_connString($mailbox) === false)) {
            $this->_exception('Could not delete mailbox "' . $mailbox . '": ' . imap_last_error());
        }
    }

    /**
     */
    protected function _renameMailbox($old, $new)
    {
        $this->login();

        if (@imap_renamemailbox($this->_stream, $this->_connString($old), $this->_connString($new)) === false) {
            $this->_exception('Could not rename mailbox "' . $old . '": ' . imap_last_error());
        }
    }

    /**
     */
    protected function _subscribeMailbox($mailbox, $subscribe)
    {
        $this->login();

        $res = $subscribe
            ? @imap_subscribe($this->_stream, $this->_connString($mailbox))
            : @imap_unsubscribe($this->_stream, $this->_connString($mailbox));

        if ($res === false) {
            $this->_exception('Could not ' . ($subscribe ? 'subscribe' : 'unsubscribe') . ' to mailbox "' . $mailbox . '": ' . imap_last_error());
        }
    }

    /**
     * For the 'attributes' option, this driver will return only these
     * attributes:
     * <pre>
     *   '\noinferiors', '\noselect', '\marked', '\unmarked', '\referral',
     *   '\haschildren', '\hasnochildren'
     * </pre>
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
        case Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS:
            $sub = $this->_getMailboxList($pattern, Horde_Imap_Client::MBOX_SUBSCRIBED);
            if ($mode == Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS) {
                $mboxes = $this->_getMailboxList($pattern, Horde_Imap_Client::MBOX_ALL);
                $sub = array_intersect($sub, $mboxes);
            }

            if (!empty($options['flat'])) {
                if (!empty($options['utf8'])) {
                    $sub = array_map(array('Horde_Imap_Client_Utf7imap', 'Utf7ImapToUtf8'), $sub);
                }

                if (($mode == Horde_Imap_Client::MBOX_SUBSCRIBED) ||
                    ($mode == Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS)) {
                    return array_values($sub);
                }

                $mboxes = $this->_getMailboxList($pattern, Horde_Imap_Client::MBOX_ALL);

                if (!empty($options['utf8'])) {
                    $mboxes = array_map(array('Horde_Imap_Client_Utf7imap', 'Utf7ImapToUtf8'), $mboxes);
                }
                return array_values(array_diff($mboxes, $sub));
            }
            $sub = array_flip($sub);
            $check = true;
            break;
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

        $res = @imap_getmailboxes($this->_stream, $this->_connString(), $pattern);

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
     */
    protected function _getMailboxList($pattern, $mode)
    {
        if (is_array($pattern)) {
            $res = array();
            foreach ($pattern as $val) {
                if (strlen($val)) {
                    $res = array_merge($res, $this->_getMailboxList($val, $mode));
                }
            }
            return array_unique($res);
        }

        $mboxes = array();

        $res = ($mode != Horde_Imap_Client::MBOX_ALL)
            ? @imap_list($this->_stream, $this->_connString(), $pattern)
            : @imap_lsub($this->_stream, $this->_connString(), $pattern);

        if (is_array($res)) {
            while (list(,$val) = each($res)) {
                $mboxes[] = substr($val, strpos($val, '}') + 1);
            }
        }

        return $mboxes;
    }

    /**
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
     * @return boolean  True.
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
                return $this->_getSocket()->append($mailbox, $data, $options);
            }
        }

        while (list(,$val) = each($data)) {
            $text = is_resource($val['data'])
                ? stream_get_contents($val['data'])
                : $val['data'];

            $res = @imap_append($this->_stream, $this->_connString($mailbox), $this->utils->removeBareNewlines($text), empty($val['flags']) ? null : implode(' ', $val['flags']));

            if ($res === false) {
                if (!empty($options['create'])) {
                    $this->createMailbox($mailbox);
                    unset($options['create']);
                    return $this->_append($mailbox, $data, $options);
                }
                $this->_exception('Could not append message to IMAP server: ' . imap_last_error());
            }
        }

        return true;
    }

    /**
     */
    protected function _check()
    {
        // Already guaranteed to be logged in here.

        if (@imap_check($this->_stream) === false) {
            $this->_exception('Received error from IMAP server when sending a CHECK command: ' . imap_last_error());
        }
    }

    /**
     */
    protected function _close($options)
    {
        if (!empty($options['expunge'])) {
            $this->expunge($this->_selected);
        }
        $this->openMailbox($this->_selected, Horde_Imap_Client::OPEN_READONLY);
    }

    /**
     */
    protected function _expunge($options)
    {
        // Already guaranteed to be logged in here.

        $msg_list = !empty($options['list']);
        $unflag = false;

        if (!$options['ids']->all || $msg_list) {
            $search_query = new Horde_Imap_Client_Search_Query();
            $search_query->flag('\\deleted');
            $ids = $this->search($this->_selected, $search_query, array('sequence' => $options['ids']->sequence));

            // Need to temporarily unflag all messages marked as deleted but not
            // a part of requested UIDs to delete.
            if (!$options['ids']->all && count($ids['match'])) {
                $unflag = array_diff($ids['match']->ids, $options['ids']->ids);
                if (!empty($unflag)) {
                    $unflag = new Horde_Imap_Client_Ids($unflag, $options['ids']->sequence);
                    $this->store($this->_selected, array(
                        'ids' => $unflag,
                        'remove' => array('\\deleted')
                    ));
                }

                /* If we are using a cache, we need to get the list of
                 * messages that will be expunged. */
                if ($this->_initCache($this->_selected)) {
                    if ($options['ids']->sequence) {
                        $res = $this->search($this->_selected, $search_query);
                        $expunged = $res['match'];
                    } else {
                        $expunged = array_intersect($ids['match']->ids, $options['ids']->ids);
                    }

                    if (!empty($expunged)) {
                        $this->cache->deleteMsgs($this->_selected, $expunged);
                    }
                }
            }
        }

        @imap_expunge($this->_stream);

        if (!empty($unflag)) {
            $this->store($this->_selected, array(
                'add' => array('\\deleted'),
                'ids' => $unflag
            ));
        }

        return $msg_list
            ? $ids['match']
            : null;
    }

    /**
     */
    protected function _search($query, $options)
    {
        // Already guaranteed to be logged in here.

        /* If more than 1 sort criteria given, or if SORT_REVERSE,
         * SORT_DISPLAYFROM, or SORT_DISPLAYTO is given as a sort criteria, or
         * search query uses IMAP4 criteria, use the Socket client instead. */
        if ($options['_query']['imap4'] ||
            (!empty($options['sort']) &&
             ((count($options['sort']) > 1) ||
              in_array(Horde_Imap_Client::SORT_REVERSE, $options['sort']) ||
              in_array(Horde_Imap_Client::SORT_DISPLAYFROM, $options['sort']) ||
              in_array(Horde_Imap_Client::SORT_DISPLAYTO, $options['sort'])))) {
            return $this->_getSocket()->search($this->_selected, $query, $options);
        }

        $sort = empty($options['sort'])
            ? null
            : reset($options['sort']);

        if (!$sort || ($sort == Horde_Imap_Client::SORT_SEQUENCE)) {
            $res = @imap_search($this->_stream, $options['_query']['query'], empty($options['sequence']) ? SE_UID : 0, $options['_query']['charset']);
            if ($sort && ($res !== false)) {
                sort($res, SORT_NUMERIC);
            }
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

            $res = @imap_sort($this->_stream, $sort_criteria[$sort], 0, empty($options['sequence']) ? SE_UID : 0, $options['_query']['query'], $options['_query']['charset']);
        }
        $res = ($res === false) ? array() : $res;

        $ret = array();
        foreach ($options['results'] as $val) {
            switch ($val) {
            case Horde_Imap_Client::SORT_RESULTS_COUNT:
                $ret['count'] = count($res);
                break;

            case Horde_Imap_Client::SORT_RESULTS_MATCH:
                $ret['match'] = new Horde_Imap_Client_Ids($res, !empty($options['sequence']));
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
     */
    protected function _setComparator($comparator)
    {
        return $this->_getSocket()->setComparator($comparator);
    }

    /**
     */
    protected function _getComparator()
    {
        return $this->_getSocket()->getComparator();
    }

    /**
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

        $ob = @imap_thread($this->_stream, $use_seq ? 0 : SE_UID);

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
     */
    protected function _fetch($query, $results, $options)
    {
        // Already guaranteed to be logged in here

        $hdrinfo = array();
        $overview = null;

        // These options are not supported by this driver.
        if (!empty($options['changedsince'])) {
            $options['fetch_res'] = $results;
            return $this->_getSocket()->fetch($this->_selected, $query, $options);
        }

        $seq = $options['ids']->all
            ? '1:*'
            : strval($options['ids']);
        $uid_mask = $options['ids']->sequence
            ? 0
            : FT_UID;

        foreach ($query as $type => $c_val) {
            switch ($type) {
            case Horde_Imap_Client::FETCH_STRUCTURE:
                foreach (array_keys($results) as $id) {
                    $structure = @imap_fetchstructure($this->_stream, $id, $uid_mask);
                    if ($structure) {
                        $ob = $this->_parseStructure($structure);
                        $ob->buildMimeIds();
                        $results[$id]->setStructure($ob);
                    } else {
                        $this->_exception('FETCH error: ' . imap_last_error(), 0, true);
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_FULLMSG:
                foreach (array_keys($results) as $id) {
                    $tmp = @imap_fetchheader($this->_stream, $id, $uid_mask | FT_PREFETCHTEXT) .
                           @imap_body($this->_stream, $id, $uid_mask | (empty($c_val['peek']) ? 0 : FT_PEEK));
                    $results[$id]->setFullmsg($this->_processString($tmp, $c_val));
                }
                break;

            case Horde_Imap_Client::FETCH_HEADERTEXT:
                foreach ($c_val as $key => $val) {
                    foreach (array_keys($results) as $id) {
                        $results[$id]->setHeaderText(
                            $key,
                            /* imap_fetchbody() can return header parts for a
                             * given MIME part by appending '.0' (or 0 for the
                             * main header) */
                            $this->_processString(@imap_fetchbody($this->_stream, $id, $body_key . ($body_key != 0 ? '.0' : ''), $uid_mask, $val))
                        );
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_BODYPART:
                foreach ($c_val as $key => $val) {
                    foreach (array_keys($results) as $id) {
                        $results[$id]->setBodyPart(
                            $key,
                            $this->_processString(@imap_fetchbody($this->_stream, $id, $key, $uid_mask, $val))
                        );
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_BODYTEXT:
                foreach ($c_val as $key => $val) {
                    foreach (array_keys($results) as $id) {
                        if ($key == 0) {
                            $tmp = @imap_body($this->_stream, $id, $uid_mask | (empty($val['peek']) ? 0 : FT_PEEK));
                        } else {
                            /* OY! There is no way to download just the body
                             * of the message/rfc822 part.  The best we can do
                             * is download the header of the part, determine
                             * the length, and then remove that info from the
                             * beginning of the imap_fetchbody() data. */
                            $hdr_len = strlen(@imap_fetchbody($this->_stream, $id, $key . '.0', $uid_mask));
                            $tmp = substr(@imap_fetchbody($this->_stream, $id, $key, $uid_mask), $hdr_len);
                        }

                        $results[$id]->setBodyText(
                            $key,
                            $this->_processString($tmp, $val)
                        );
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
                $tmp_query = new Horde_Imap_Client_Fetch_Query();

                switch ($type) {
                case Horde_Imap_Client::FETCH_MIMEHEADER:
                    foreach ($c_val as $key => $val) {
                        $tmp_query->mimeHeader($key, $val);
                    }
                    break;

                case Horde_Imap_Client::FETCH_HEADERS:
                    foreach ($c_val as $key => $val) {
                        $tmp_query->headers($key, $val['headers'], $val);
                    }
                    break;

                case Horde_Imap_Client::FETCH_MODSEQ:
                    $tmp_query->modseq();
                    break;
                }

                $results = $this->_getSocket()->fetch($this->_selected, $tmp_query, array_merge($options, array('fetch_res' => $results)));
                break;

            case Horde_Imap_Client::FETCH_ENVELOPE:
                $env_data = array(
                    'date', 'subject', 'from', 'sender', 'reply_to', 'to',
                    'cc', 'bcc', 'in_reply_to', 'message_id'
                );

                foreach (array_keys($results) as $id) {
                    if (!isset($hdrinfo[$id])) {
                        $hdrinfo[$id] = @imap_headerinfo($this->_stream, $options['ids']->sequence ? $id : @imap_msgno($this->_stream, $id));
                        if (!$hdrinfo[$id]) {
                            $this->_exception('FETCH error: ' . imap_last_error(), 0, true);
                            continue;
                        }
                    }

                    $env_data = array();
                    $hptr = &$hdrinfo[$id];

                    foreach ($env_data as $e_val) {
                        if (isset($hptr->$e_val)) {
                            $env_data[$label] = $hptr->$e_val;
                        }
                    }

                    $results[$id]->setEnvelope($env_data);
                }
                break;

            case Horde_Imap_Client::FETCH_FLAGS:
                if (is_null($overview)) {
                    $overview = @imap_fetch_overview($this->_stream, $seq, $uid_mask);
                    if (!$overview) {
                        $this->_exception('FETCH error: ' . imap_last_error(), 0, true);
                        break;
                    }
                }

                foreach (array_keys($results) as $id) {
                    $tmp = array();
                    foreach ($this->_supportedFlags as $f_val) {
                        if ($overview[$id]->$f_val) {
                            $tmp[] = '\\' . $f_val;
                        }
                    }
                    $results[$id]->setFlags($tmp);
                }
                break;

            case Horde_Imap_Client::FETCH_IMAPDATE:
                foreach (array_keys($results) as $id) {
                    if (!isset($hdrinfo[$id])) {
                        $hdrinfo[$id] = @imap_headerinfo($this->_stream, $options['ids']->sequence ? $id : @imap_msgno($this->_stream, $id));
                        if (!$hdrinfo[$id]) {
                            $this->_exception('FETCH error: ' . imap_last_error(), 0, true);
                            continue;
                        }
                    }

                    $results[$id]->setImapDate($hdrinfo[$id]->MailDate);
                }
                break;

            case Horde_Imap_Client::FETCH_SIZE:
                foreach (array_keys($results) as $id) {
                    if (isset($hdrinfo[$id])) {
                        $results[$id]->setSize($hdrinfo[$id]->Size);
                    } else {
                        if (is_null($overview)) {
                            $overview = @imap_fetch_overview($this->_stream, $seq, $uid_mask);
                            if (!$overview) {
                                $this->_exception('FETCH error: ' . imap_last_error(), 0, true);
                                break;
                            }
                        }

                        $results[$id]->setSize($overview[$id]->size);
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_UID:
                foreach (array_keys($results) as $id) {
                    if ($options['ids']->sequence) {
                        if (is_null($overview)) {
                            $overview = @imap_fetch_overview($this->_stream, $seq, $uid_mask);
                            if (!$overview) {
                                $this->_exception('FETCH error: ' . imap_last_error(), 0, true);
                                break;
                            }
                        }

                        $results[$id]->setUid($overview[$id]->uid);
                    } else {
                        $results[$id]->setUid($id);
                    }
                }
                break;

            case Horde_Imap_Client_Getch_Query::SEQ:
                foreach (array_keys($results) as $id) {
                    if ($options['ids']->sequence) {
                        $results[$id]->setSeq($id);
                    } else {
                        if (is_null($overview)) {
                            $overview = @imap_fetch_overview($this->_stream, $seq, $uid_mask);
                            if (!$overview) {
                                $this->_exception('FETCH error: ' . imap_last_error(), 0, true);
                                break;
                            }
                        }

                        $results[$id]->setSeq($overview[$id]->msgno);
                    }
                }
                break;
            }
        }

        return $results;
    }

    /**
     * Process a string response based on criteria options.
     *
     * @param string $str  The original string.
     * @param array $opts  The criteria options.
     *
     * @return string  The requested string.
     */
    protected function _processString($str, $opts)
    {
        if (!empty($opts['length'])) {
            return substr($str, empty($opts['start']) ? 0 : $opts['start'], $opts['length']);
        } elseif (!empty($opts['start'])) {
            return substr($str, $opts['start']);
        }

        return $str;
    }

    /**
     * Parse the output from imap_fetchstructure() into a MIME Part object.
     *
     * @param object $data  Data from imap_fetchstructure().
     *
     * @return Horde_Mime_Part  A MIME Part object.
     */
    protected function _parseStructure($data)
    {
        $ob = new Horde_Mime_Part();

        $ob->setType($this->_mimeTypes[$data->type] . '/' . ($data->ifsubtype ? strtolower($data->subtype) : Horde_Mime_Part::UNKNOWN));

        // Optional for multipart-parts, required for all others
        if ($data->ifparameters) {
            $params = array();
            foreach ($data->parameters as $val) {
                $params[$val->attribute] = $val->value;
            }

            $params = Horde_Mime::decodeParam('content-type', $params, 'UTF-8');
            foreach ($params['params'] as $key => $val) {
                $ob->setContentTypeParameter($key, $val);
            }
        }

        // Optional entries. 'location' and 'language' not supported
        if ($data->ifdisposition) {
            $ob->setDisposition($data->disposition);
            if ($data->ifdparameters) {
                $dparams = array();
                foreach ($data->dparameters as $val) {
                    $dparams[$val->attribute] = $val->value;
                }

                $dparams = Horde_Mime::decodeParam('content-disposition', $dparams, 'UTF-8');
                foreach ($dparams['params'] as $key => $val) {
                    $ob->setDispositionParameter($key, $val);
                }
            }
        }

        if ($ob->getPrimaryType() == 'multipart') {
            // multipart/* specific entries
            foreach ($data->parts as $val) {
                $ob->addPart($this->_parseStructure($val));
            }
        } else {
            // Required options
            if ($data->ifid) {
                $ob->setContentId($data->id);
            }
            if ($data->ifdescription) {
                $ob->setDescription(Horde_Mime::decode($data->description, 'UTF-8'));
            }

            $ob->setTransferEncoding($this->_mimeEncodings[$data->encoding]);
            $ob->setBytes($data->bytes);

            if ($ob->getType() == 'message/rfc822') {
                $ob->addPart($this->_parseStructure(reset($data->parts)));
            }
        }

        return $ob;
    }

    /**
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
        // options.
        if (!empty($options['unchangedsince']) ||
            !empty($options['replace'])) {
            // Requires Socket driver.
            return $this->_getSocket()->store($this->_selected, $options);
        }

        $seq = $options['ids']->all
            ? '1:*'
            : strval($options['ids']);

        if (!empty($options['add'])) {
            $res = @imap_setflag_full($this->_stream, $seq, implode(' ', $options['add']), $options['ids']->sequence ? 0 : ST_UID);
        }

        if (($res === true) && !empty($options['remove'])) {
            $res = @imap_clearflag_full($this->_stream, $seq, implode(' ', $options['remove']), $options['ids']->sequence ? 0 : ST_UID);
        }

        if ($res === false) {
            $this->_exception('Error when flagging messages: ' . imap_last_error());
        }

        return new Horde_Imap_Client_Ids();
    }

    /**
     * @return boolean  True (this driver does not support returning UIDs).
     */
    protected function _copy($dest, $options)
    {
        // Already guaranteed to be logged in here

        $opts = 0;
        if ($options['ids']->sequence) {
            $opts |= CP_UID;
        }
        if (!empty($options['move'])) {
            $opts |= CP_MOVE;
        }

        $seq = $options['ids']->all
            ? '1:*'
            : strval($options['ids']);

        $res = @imap_mail_copy($this->_stream, $seq, $this->_connString($dest), $opts);

        if ($res === false) {
            if (!empty($options['create'])) {
                $this->createMailbox($dest);
                unset($options['create']);
                return $this->copy($dest, $options);
            }
            $this->_exception('Error when copying/moving messages: ' . imap_last_error());
        }

        return true;
    }

    /**
     */
    protected function _setQuota($root, $options)
    {
        // This driver only supports setting the 'STORAGE' quota.
        if (isset($options['messages'])) {
            $this->_getSocket()->setQuota($root, $options);
            return;
        }

        $this->login();

        $res = @imap_set_quota($this->_stream, $root, $options['storage']);

        if ($res === false) {
            $this->_exception('Error when setting quota: ' . imap_last_error());
        }
    }

    /**
     */
    protected function _getQuota($root)
    {
        $this->login();

        if (@imap_get_quota($this->_stream, $root) === false) {
            $this->_exception('Error when retrieving quota: ' . imap_last_error());
        }
    }

    /**
     */
    protected function _getQuotaRoot($mailbox)
    {
        $this->login();

        $res = imap_get_quotaroot($this->_stream, $mailbox);

        if ($res === false) {
            $this->_exception('Error when retrieving quotaroot: ' . imap_last_error());
        }

        return array($mailbox => $res);
    }

    /**
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

        $res = @imap_setacl($this->_stream, $mailbox, $identifier, (empty($options['remove']) ? '+' : '-') . $implode('', $options['rights']));

        if ($res === false) {
            $this->_exception('Error when setting ACL: ' . imap_last_error());
        }
    }

    /**
     */
    protected function _getACL($mailbox)
    {
        $this->login();

        $acl = array();
        $res = @imap_getacl($this->_stream, $mailbox);

        if ($res === false) {
            $this->_exception('Error when retrieving ACLs: ' . imap_last_error());
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
     */
    protected function _listACLRights($mailbox, $identifier)
    {
        $retval = array('optional' => array(), 'required' => array());
        $acl = $this->getACL($mailbox);
        if (isset($acl[$identifier])) {
            $retval['optional'] = $acl[$identifier];
        }
        return $retval;
    }

    /**
     */
    protected function _getMyACLRights($mailbox)
    {
        // No support in c-client for MYRIGHTS - need to call Socket driver
        return $this->_getSocket()->getMyACLRights($mailbox);
    }

    /**
     */
    protected function _getMetadata($mailbox, $entries, $options)
    {
        return $this->_getSocket()->getMetadata($mailbox, $entries, $options);
    }

    /**
     */
    protected function _setMetadata($mailbox, $data)
    {
        $this->_getSocket()->setMetadata($mailbox, $data);
    }

    /* Internal functions */

    /**
     * Create a Horde_Imap_Client_Socket instance pre-filled with this
     * client's configuration parameters.
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

<?php
/**
 * Horde_Imap_Client_Cclient_Pop3 provides an interface to a POP3 server (RFC
 * 1939) via the PHP imap (c-client) module.  This driver is an abstraction
 * layer allowing POP3 commands to be used based on its IMAP equivalents.
 *
 * PHP IMAP module: http://www.php.net/imap
 *
 * No additional paramaters from those defined in Horde_Imap_Client_Cclient.
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
class Horde_Imap_Client_Cclient_Pop3 extends Horde_Imap_Client_Cclient
{
    /**
     * Constructs a new Horde_Imap_Client_Cclient object.
     *
     * @param array $params  A hash containing configuration parameters.
     */
    public function __construct($params)
    {
        $this->_service = 'pop3';
        if (!isset($params['port'])) {
            $params['port'] = ($params['secure'] == 'ssl') ? 995 : 110;
        }
        parent::__construct($params);
    }

    /**
     * Get CAPABILITY info from the IMAP server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _capability()
    {
        throw new Horde_Imap_Client_Exception('IMAP CAPABILITY command not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Get the NAMESPACE information from the IMAP server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getNamespaces()
    {
        throw new Horde_Imap_Client_Exception('IMAP namespaces not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
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
        throw new Horde_Imap_Client_Exception('IMAP ID command not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Return ID information from the IMAP server (RFC 2971).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getID()
    {
        throw new Horde_Imap_Client_Exception('IMAP ID command not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
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
        throw new Horde_Imap_Client_Exception('IMAP LANGUAGE extension not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Gets the preferred language for server response messages (RFC 5255).
     *
     * @param array $list  If true, return the list of available languages.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getLanguage($list)
    {
        throw new Horde_Imap_Client_Exception('IMAP LANGUAGE extension not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
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
        if (strcasecmp($mailbox, 'INBOX') !== 0) {
            throw new Horde_Imap_Client_Exception('Mailboxes other than INBOX not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
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
        throw new Horde_Imap_Client_Exception('Creating mailboxes not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
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
        throw new Horde_Imap_Client_Exception('Deleting mailboxes not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
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
        throw new Horde_Imap_Client_Exception('Renaming mailboxes not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
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
        throw new Horde_Imap_Client_Exception('Mailboxes other than INBOX not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Unsubscribe to a mailbox.
     *
     * @param string $mailbox  The mailbox to unsubscribe to (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _unsubscribeMailbox($mailbox)
    {
        throw new Horde_Imap_Client_Exception('Mailboxes other than INBOX not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Obtain a list of mailboxes matching a pattern.
     *
     * @param string $pattern  The mailbox search pattern.
     * @param integer $mode    Which mailboxes to return.
     * @param array $options   Additional options.
     *
     * @return array  See Horde_Imap_Client_Base::listMailboxes().
     * @throws Horde_Imap_Client_Exception
     */
    protected function _listMailboxes($pattern, $mode, $options)
    {
        $tmp = array('mailbox' => 'INBOX');

        if (!empty($options['attributes'])) {
            $tmp['attributes'] = array();
        }
        if (!empty($options['delimiter'])) {
            $tmp['delimiter'] = '';
        }

        return array('INBOX' => $tmp);
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
        if (strcasecmp($mailbox, 'INBOX') !== 0) {
            throw new Horde_Imap_Client_Exception('Mailboxes other than INBOX not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
        }

        // This driver only supports the base flags given by c-client.
        if (($flags & Horde_Imap_Client::STATUS_FIRSTUNSEEN) ||
            ($flags & Horde_Imap_Client::STATUS_FLAGS) ||
            ($flags & Horde_Imap_Client::STATUS_PERMFLAGS)) {
            throw new Horde_Imap_Client_Exception('Improper status request on POP3 server.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
        }

        return parent::_status($mailbox, $flags);
    }

    /**
     * Search a mailbox.
     *
     * @param object $query   The search string.
     * @param array $options  Additional options.
     *
     * @return array  An array of UIDs (default) or an array of message
     *                sequence numbers (if 'sequence' is true).
     * @throws Horde_Imap_Client_Exception
     */
    protected function _search($query, $options)
    {
        // POP 3 supports c-client search criteria only.
        $search_query = $query->build();

        /* If more than 1 sort criteria given, or if SORT_REVERSE is given
         * as a sort criteria, or search query uses IMAP4 criteria, use the
         * Socket client instead. */
        if ($search_query['imap4'] ||
            (!empty($options['sort']) &&
             ((count($options['sort']) > 1) ||
             in_array(Horde_Imap_Client::SORT_REVERSE, $options['sort'])))) {
            throw new Horde_Imap_Client_Exception('Unsupported search criteria on POP3 server.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
        }

        return parent::_search($query, $options);
    }

   /**
     * Set the comparator to use for searching/sorting (RFC 5255).
     *
     * @param string $comparator  The comparator string (see RFC 4790 [3.1] -
     *                            "collation-id" - for format). The reserved
     *                            string 'default' can be used to select
     *                            the default comparator.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _setComparator($comparator)
    {
        throw new Horde_Imap_Client_Exception('Search comparators not supported on POP3 server.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Get the comparator used for searching/sorting (RFC 5255).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getComparator()
    {
        throw new Horde_Imap_Client_Exception('Search comparators not supported on POP3 server.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Thread sort a given list of messages (RFC 5256).
     *
     * @param array $options  Additional options.
     *
     * @return array  See Horde_Imap_Client_Base::thread().
     * @throws Horde_Imap_Client_Exception
     */
    protected function _thread($options)
    {
        /* This driver only supports Horde_Imap_Client::THREAD_REFERENCES
         * and does not support defining search criteria. */
        if (!empty($options['search']) ||
            (!empty($options['criteria']) &&
             $options['criteria'] != Horde_Imap_Client::THREAD_REFERENCES)) {
            throw new Horde_Imap_Client_Exception('Unsupported threading criteria on POP3 server.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
        }

        return parent::_thread($options);
    }

    /**
     * Append a message to the mailbox.
     *
     * @param array $mailbox   The mailboxes to append the messages to
     *                         (UTF7-IMAP).
     * @param array $data      The message data.
     * @param array $options   Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _append($mailbox, $data, $options)
    {
        throw new Horde_Imap_Client_Exception('Appending messages not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
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
        // No support for FETCH_MIMEHEADER or FETCH_HEADERS
        $nosupport = array(Horde_Imap_Client::FETCH_MIMEHEADER, Horde_Imap_Client::FETCH_HEADERS);

        reset($criteria);
        while (list($val,) = each($criteria)) {
            if (in_array($val, $nosupport)) {
                throw new Horde_Imap_Client_Exception('Fetch criteria provided not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
            }
        }

        return parent::_fetch($criteria, $options);
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
        throw new Horde_Imap_Client_Exception('Flagging messages not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Copy messages to another mailbox.
     *
     * @param string $dest    The destination mailbox (UTF7-IMAP).
     * @param array $options  Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _copy($dest, $options)
    {
        throw new Horde_Imap_Client_Exception('Copying messages not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
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
        throw new Horde_Imap_Client_Exception('IMAP quotas not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Get quota limits.
     *
     * @param string $root  The quota root (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getQuota($root)
    {
        throw new Horde_Imap_Client_Exception('IMAP quotas not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Get quota limits for a mailbox.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getQuotaRoot($mailbox)
    {
        throw new Horde_Imap_Client_Exception('IMAP quotas not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
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
        throw new Horde_Imap_Client_Exception('IMAP ACLs not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Get ACL rights for a given mailbox.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getACL($mailbox)
    {
        throw new Horde_Imap_Client_Exception('IMAP ACLs not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Get ACL rights for a given mailbox/identifier.
     *
     * @param string $mailbox     A mailbox (UTF7-IMAP).
     * @param string $identifier  The identifier (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _listACLRights($mailbox, $identifier)
    {
        throw new Horde_Imap_Client_Exception('IMAP ACLs not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

    /**
     * Get the ACL rights for the current user for a given mailbox.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getMyACLRights($mailbox)
    {
        throw new Horde_Imap_Client_Exception('IMAP ACLs not supported on POP3 servers.', Horde_Imap_Client_Exception::POP3_NOTSUPPORTED);
    }

}

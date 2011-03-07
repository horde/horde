<?php
/**
 * Horde_Imap_Client_Cclient_Pop3 provides an interface to a POP3 server (RFC
 * 1939) via the PHP imap (c-client) module.  This driver is an abstraction
 * layer allowing POP3 commands to be used based on its IMAP equivalents.
 *
 * Caching is not supported in this driver.
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
class Horde_Imap_Client_Cclient_Pop3 extends Horde_Imap_Client_Cclient
{
    /**
     */
    public function __construct($params)
    {
        if (empty($params['port'])) {
            $params['port'] = ($params['secure'] == 'ssl') ? 995 : 110;
        }

        parent::__construct($params);

        $this->_setInit('service', 'pop3');

        // Disable caching.
        $this->_params['cache'] = array('fields' => array());
    }

    /**
     */
    public function unserialize($data)
    {
        parent::unserialize($data);

        // Disable caching.
        $this->_params['cache'] = array('fields' => array());
    }

    /**
     */
    protected function _capability()
    {
        $this->_exception('CAPABILITY command not supported in this POP3 driver.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _getNamespaces()
    {
        $this->_exception('IMAP namespaces not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _sendID($info)
    {
        $this->_exception('IMAP ID command not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _getID()
    {
        $this->_exception('IMAP ID command not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _setLanguage($langs)
    {
        $this->_exception('IMAP LANGUAGE extension not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _getLanguage($list)
    {
        $this->_exception('IMAP LANGUAGE extension not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _openMailbox($mailbox, $mode)
    {
        if (strcasecmp($mailbox, 'INBOX') !== 0) {
            $this->_exception('Mailboxes other than INBOX not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
        }
    }

    /**
     */
    protected function _createMailbox($mailbox, $opts)
    {
        $this->_exception('Creating mailboxes not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _deleteMailbox($mailbox)
    {
        $this->_exception('Deleting mailboxes not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _renameMailbox($old, $new)
    {
        $this->_exception('Renaming mailboxes not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _subscribeMailbox($mailbox, $subscribe)
    {
        $this->_exception('Mailboxes other than INBOX not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
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
     */
    protected function _status($mailbox, $flags)
    {
        $this->openMailbox($mailbox);

        // This driver only supports the base flags given by c-client.
        if (($flags & Horde_Imap_Client::STATUS_FIRSTUNSEEN) ||
            ($flags & Horde_Imap_Client::STATUS_FLAGS) ||
            ($flags & Horde_Imap_Client::STATUS_PERMFLAGS) ||
            ($flags & Horde_Imap_Client::STATUS_HIGHESTMODSEQ) ||
            ($flags & Horde_Imap_Client::STATUS_UIDNOTSTICKY)) {
            $this->_exception('Improper status request on POP3 server.', 'POP3_NOTSUPPORTED');
        }

        return parent::_status($mailbox, $flags);
    }

    /**
     */
    protected function _search($query, $options)
    {
        // POP 3 supports c-client search criteria only.
        $search_query = $query->build();

        /* If more than 1 sort criteria given, or if SORT_REVERSE,
         * SORT_DISPLAYFROM, or SORT_DISPLAYTO is given as a sort criteria,
         * or search query uses IMAP4 criteria, fail. */
        if ($search_query['imap4'] ||
            (!empty($options['sort']) &&
             ((count($options['sort']) > 1) ||
              in_array(Horde_Imap_Client::SORT_REVERSE, $options['sort']) ||
              in_array(Horde_Imap_Client::SORT_DISPLAYFROM, $options['sort']) ||
              in_array(Horde_Imap_Client::SORT_DISPLAYTO, $options['sort'])))) {
            $this->_exception('Unsupported search criteria on POP3 server.', 'POP3_NOTSUPPORTED');
        }

        return parent::_search($query, $options);
    }

    /**
     */
    protected function _setComparator($comparator)
    {
        $this->_exception('Search comparators not supported on POP3 server.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _getComparator()
    {
        $this->_exception('Search comparators not supported on POP3 server.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _thread($options)
    {
        /* This driver only supports Horde_Imap_Client::THREAD_REFERENCES
         * and does not support defining search criteria. */
        if (!empty($options['search']) ||
            (!empty($options['criteria']) &&
             $options['criteria'] != Horde_Imap_Client::THREAD_REFERENCES)) {
            $this->_exception('Unsupported threading criteria on POP3 server.', 'POP3_NOTSUPPORTED');
        }

        return parent::_thread($options);
    }

    /**
     */
    protected function _append($mailbox, $data, $options)
    {
        $this->_exception('Appending messages not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _fetch($query, $results, $options)
    {
        if ($query->contains(Horde_Imap_Client::FETCH_MIMEHEADER) ||
            $query->contains(Horde_Imap_Client::FETCH_HEADERS)) {
            $this->_exception('Fetch criteria provided not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
        }

        return parent::_fetch($query, $results, $options);
    }

    /**
     */
    protected function _store($options)
    {
        /* Only support deleting/undeleting messages. */
        if (isset($options['replace'])) {
            if (count(array_intersect($options['replace'], array(Horde_Imap_Client::FLAG_DELETED)))) {
                $options['add'] = array(Horde_Imap_Client::FLAG_DELETED);
            } else {
                $options['remove'] = array(Horde_Imap_Client::FLAG_DELETED);
            }
            unset($options['replace']);
        } else {
            if (!empty($options['add']) &&
                count(array_intersect($options['add'], array(Horde_Imap_Client::FLAG_DELETED)))) {
                $options['add'] = array(Horde_Imap_Client::FLAG_DELETED);
            }

            if (!empty($options['remove']) &&
                !count(array_intersect($options['remove'], array(Horde_Imap_Client::FLAG_DELETED)))) {
                $options['add'] = array();
                $options['remove'] = array(Horde_Imap_Client::FLAG_DELETED);
            }
        }

        return parent::_store($options);
    }

    /**
     */
    protected function _copy($dest, $options)
    {
        $this->_exception('Copying messages not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _setQuota($root, $options)
    {
        $this->_exception('IMAP quotas not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _getQuota($root)
    {
        $this->_exception('IMAP quotas not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _getQuotaRoot($mailbox)
    {
        $this->_exception('IMAP quotas not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _setACL($mailbox, $identifier, $options)
    {
        $this->_exception('IMAP ACLs not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _getACL($mailbox)
    {
        $this->_exception('IMAP ACLs not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _listACLRights($mailbox, $identifier)
    {
        $this->_exception('IMAP ACLs not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
    protected function _getMyACLRights($mailbox)
    {
        $this->_exception('IMAP ACLs not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     */
     protected function _getMetadata($mailbox, $entries, $options)
     {
        $this->_exception('IMAP metadata not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
     }

    /**
     */
    protected function _setMetadata($mailbox, $data)
    {
        $this->_exception('IMAP metadata not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

}

<?php
/**
 * A mock IMAP driver for unit testing.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */

/**
 * The mock driver class.
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */
class Horde_Imap_Client_Mock extends Horde_Imap_Client_Base
{
    /**
     * Message flags.
     */
    const FLAG_NONE    = 0;
    const FLAG_DELETED = 1;

    /**
     * The simulated IMAP storage.
     *
     * @var array
     */
    static public $storage = array();

    /**
     * Id of the current user
     *
     * @var string
     */
    private $_user;

    /**
     * The data of the mailbox currently opened
     *
     * @var array
     */
    private $_mbox = null;

    /**
     * The name of the mailbox currently opened
     *
     * @var array
     */
    private $_mboxname = null;

    /**
     * Constructs a new Horde_Imap_Client object.
     *
     * @param array $params A hash containing configuration parameters.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        $this->_user = $params['username'];

        if (!empty($this->params['persistent'])) {
            register_shutdown_function(array($this, 'shutdown'));

            if (empty(self::$storage) && file_exists($this->params['persistent'])
                && $data = @unserialize(file_get_contents($this->params['persistent']))) {
                self::$storage = $data;
            }
        }

        if (!is_array(self::$storage)) {
            /* Simulate an empty IMAP server */
            self::$storage = array();
        }

        try {
            $this->_getMailbox('INBOX');
        } catch (Horde_Imap_Client_Exception $e) {
            $this->createMailbox('INBOX');
        }
    }

    /**
     * Store the simulated IMAP store in a file.
     *
     * @return NULL
     */
    protected function shutdown()
    {
        $storage = fopen($this->_params['persistent'], 'a');
        $data    = @serialize(self::$storage);
        fwrite($storage, $data);
        fflush($storage);
        fclose($storage);
    }

    /**
     * Clean the simulated IMAP store.
     *
     * @return NULL
     */
    static public function clean()
    {
        self::$storage = array();
    }

    /**
     * Parse the given folder name into a structure that contains the user name.
     *
     * @param string $folder The folder name.
     *
     * @return string The corrected user name.
     *
     * @todo This type of mapping only works for cyrus imap with a specific
     *       configuration.
     */
    function _parseFolder($folder)
    {
        if (substr($folder, 0, 5) == 'INBOX') {
            $user = split('@', $this->_user);
            return 'user/' . $user[0] . substr($folder, 5);
        }
        return $folder;
    }

    /**
     * Get CAPABILITY information from the IMAP server.
     *
     * @return array  The capability array.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _capability()
    {
        $capabilities = array(
            'ACL' => true,
            'METADATA' => true,
        );
        return $capabilities;
    }

    /**
     * Send a NOOP command.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _noop()
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Get the NAMESPACE information from the IMAP server.
     *
     * @return array  An array of namespace information.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getNamespaces()
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Return a list of alerts that MUST be presented to the user (RFC 3501
     * [7.1]).
     *
     * @return array  An array of alert messages.
     */
    public function alerts()
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Login to the IMAP server.
     *
     * @return boolean  Return true if global login tasks should be run.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _login()
    {
        /**
         * We already stored the username on class construction so we have
         * nothing to do here.
         */
        return true;
    }

    /**
     * Logout from the IMAP server (see RFC 3501 [6.1.3]).
     */
    protected function _logout()
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Send ID information to the IMAP server (RFC 2971).
     *
     * @param array $info The information to send to the server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _sendID($info)
    {
        throw new Horde_Imap_Client_Exception('not implemented');
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
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Sets the preferred language for server response messages (RFC 5255).
     *
     * @param array $langs The preferred list of languages.
     *
     * @return string  The language accepted by the server, or null if the
     *                 default language is used.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _setLanguage($langs)
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Gets the preferred language for server response messages (RFC 5255).
     *
     * @param array $list If true, return the list of available languages.
     *
     * @return mixed  If $list is true, the list of languages available on the
     *                server (may be empty). If false, the language used by
     *                the server, or null if the default language is used.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getLanguage($list)
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Check if a mailbox exists.
     *
     * @param string $mailbox The mailbox to open (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    private function _getMailbox($mailbox)
    {
        $folder = $this->_parseFolder($mailbox);
        if (!isset(self::$storage[$folder])) {
            throw new Horde_Imap_Client_Exception(sprintf("IMAP folder %s does not exist!",
                                                          $folder));
        }
        return $folder;
    }

    /**
     * Open a mailbox.
     *
     * @param string  $mailbox The mailbox to open (UTF7-IMAP).
     * @param integer $mode    The access mode.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _openMailbox($mailbox, $mode)
    {
        $folder          = $this->_getMailbox($mailbox);
        $this->_mbox     = &self::$storage[$folder];
        $this->_mboxname = $folder;
        return true;
    }

    /**
     * Create a mailbox.
     *
     * @param string $mailbox The mailbox to create (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _createMailbox($mailbox)
    {
        $mailbox = $this->_parseFolder($mailbox);
        if (isset(self::$storage[$mailbox])) {
            throw new Horde_Imap_Client_Exception(sprintf("IMAP folder %s already exists!",
                                                          $mailbox));
        }
        self::$storage[$mailbox] = array(
            'status' => array(
                'uidvalidity' => time(),
                'uidnext' => 1),
            'mails' => array(),
            'permissions' => array(),
            'annotations' => array(),
        );
        return true;
    }

    /**
     * Delete a mailbox.
     *
     * @param string $mailbox The mailbox to delete (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _deleteMailbox($mailbox)
    {
        $folder = $this->_parseFolder($mailbox);
        if (!isset(self::$storage[$folder])) {
            throw new Horde_Imap_Client_Exception(sprintf("IMAP folder %s does not exist!",
                                                          $folder));
        }
        unset(self::$storage[$folder]);
        return true;
    }

    /**
     * Rename a mailbox.
     *
     * @param string $old The old mailbox name (UTF7-IMAP).
     * @param string $new The new mailbox name (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _renameMailbox($old, $new)
    {
        $old = $this->_parseFolder($old);
        $new = $this->_parseFolder($new);

        if (!isset(self::$storage[$old])) {
            throw new Horde_Imap_Client_Exception(sprintf("IMAP folder %s does not exist!",
                                                          $old));
        }
        if (isset(self::$storage[$new])) {
            throw new Horde_Imap_Client_Exception(sprintf("IMAP folder %s already exists!",
                                                          $new));
        }
        self::$storage[$new] = self::$storage[$old];
        unset(self::$storage[$old]);
        return true;
    }

    /**
     * Manage subscription status for a mailbox.
     *
     * @param string  $mailbox   The mailbox to [un]subscribe to (UTF7-IMAP).
     * @param boolean $subscribe True to subscribe, false to unsubscribe.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _subscribeMailbox($mailbox, $subscribe)
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Obtain a list of mailboxes matching a pattern.
     *
     * @param string  $pattern The mailbox search pattern (UTF7-IMAP).
     * @param integer $mode    Which mailboxes to return.
     * @param array   $options Additional options.
     *
     * @return array  See self::listMailboxes().
     * @throws Horde_Imap_Client_Exception
     */
    protected function _listMailboxes($pattern, $mode, $options)
    {
        $mboxes  = array_keys(self::$storage);
        $user    = split('@', $this->_user);
        $pattern = '#^user/' . $user[0] . '#';
        $result  = array();
        foreach ($mboxes as $mbox) {
            if (preg_match($pattern, $mbox)) {
                $result[] = preg_replace($pattern, 'INBOX', $mbox);
            } elseif (!empty(self::$storage[$mbox]['permissions'][$this->_user])
                      && strpos(self::$storage[$mbox]['permissions'][$this->_user], 'l') !== false) {
                $result[] = $mbox;
            }
        }
        return $result;
    }

    /**
     * Obtain status information for a mailbox.
     *
     * @param string $mailbox The mailbox to query (UTF7-IMAP).
     * @param string $flags   A bitmask of information requested from the
     *                        server.
     *
     * @return array  See self::status().
     * @throws Horde_Imap_Client_Exception
     */
    protected function _status($mailbox, $flags)
    {
        $this->openMailbox($mailbox);
        return $this->_mbox['status'];
    }

    /**
     * Append message(s) to a mailbox.
     *
     * @param string $mailbox The mailbox to append the message(s) to
     *                        (UTF7-IMAP).
     * @param array  $data    The message data.
     * @param array  $options Additional options.
     *
     * @return mixed  An array of the UIDs of the appended messages (if server
     *                supports UIDPLUS extension) or true.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _append($mailbox, $data, $options)
    {
        foreach ($data as $element) {
            $split = strpos($element['data'], "\r\n\r\n");
            $mail  = array('header' => substr($element['data'], 0, $split + 2),
                           'body' => substr($element['data'], $split + 3));
            $this->_appendMessage($mailbox, $mail);
        }
    }

    /**
     * Appends a message to the current folder.
     *
     * @param string $mailbox The mailbox to append the message(s) to
     *                        (UTF7-IMAP).
     * @param array  $msg     The message to append.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    private function _appendMessage($mailbox, $msg)
    {
        $this->openMailbox($mailbox);
        $mail           = array();
        $mail['flags']  = self::FLAG_NONE;
        $mail['header'] = $msg['header'];
        $mail['body']   = $msg['body'];

        $this->_mbox['mails'][$this->_mbox['status']['uidnext']] = $mail;
        $this->_mbox['status']['uidnext']++;
        return true;
    }

    /**
     * Request a checkpoint of the currently selected mailbox.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _check()
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Close the connection to the currently selected mailbox, optionally
     * expunging all deleted messages (RFC 3501 [6.4.2]).
     *
     * @param array $options Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _close($options)
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Expunge all deleted messages from the given mailbox.
     *
     * @param array $options Additional options.
     *
     * @return array  If 'list' option is true, returns the list of
     *                expunged messages.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _expunge($options)
    {
        $remaining = array();
        foreach ($this->_mbox['mails'] as $uid => $mail) {
            if (!($mail['flags'] & self::FLAG_DELETED)) {
                $remaining[$uid] = $mail;
            }
        }
        $this->_mbox['mails'] = $remaining;
        return true;
    }

    /**
     * Search a mailbox.
     *
     * @param object $query   The search query.
     * @param array  $options Additional options. The '_query' key contains
     *                        the value of $query->build(). 'reverse' should
     *                        be ignored (handled in search()).
     *
     * @return array  An array of UIDs (default) or an array of message
     *                sequence numbers (if 'sequence' is true).
     * @throws Horde_Imap_Client_Exception
     */
    protected function _search($query, $options)
    {
        $uids = array();

        $querystring = $options['_query']['query'];
        $cmds        = explode(' ', $querystring);

        foreach ($cmds as $cmd) {
            switch ($cmd) {
            case 'UNDELETED':
                foreach ($this->_mbox['mails'] as $uid => $mail) {
                    if (!($mail['flags'] & self::FLAG_DELETED)) {
                        $uids[] = $uid;
                    }
                }
                break;
            default:
                throw new Horde_Imap_Client_Exception(sprintf('Search command %s not implemented!',
                                                                $cmd));
            }
        }
        return array('match' => $uids, 'count' => count($uids));
    }

    /**
     * Set the comparator to use for searching/sorting (RFC 5255).
     *
     * @param string $comparator The comparator string (see RFC 4790 [3.1] -
     *                           "collation-id" - for format). The reserved
     *                           string 'default' can be used to select
     *                           the default comparator.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _setComparator($comparator)
    {
        throw new Horde_Imap_Client_Exception('not implemented');
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
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Thread sort a given list of messages (RFC 5256).
     *
     * @param array $options Additional options.
     *
     * @return array  An array with the following values, one per message,
     *                with the key being either the UID (default) or the
     *                message sequence number (if 'sequence' is true). Values
     *                of each entry:
     * <pre>
     * 'b' (base) - (integer) [OPTIONAL] The ID of the base message. Is not
     *              set, this is the only message in the thread.
     *              DEFAULT: Only message in thread
     * 'l' (level) - (integer) [OPTIONAL] The thread level of this
     *               message (1 = base).
     *               DEFAULT: 0
     * 's' (subthread) - (boolean) [OPTIONAL] Are there more messages in this
     *                   subthread?
     *                   DEFAULT: No
     * </pre>
     * @throws Horde_Imap_Client_Exception
     */
    protected function _thread($options)
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Fetch message data.
     *
     * @param array $criteria The fetch criteria. Function must not handle
     *                        'parse' param to FETCH_HEADERTEXT.
     * @param array $options  Additional options.
     *
     * @return array  See self::fetch().
     * @throws Horde_Imap_Client_Exception
     */
    protected function _fetch($criteria, $options)
    {
        $fetch  = array();
        $result = array();

        reset($criteria);
        while (list($type, $c_val) = each($criteria)) {
            if (!is_array($c_val)) {
                $c_val = array();
            }

            $uid = $options['ids'][0];

            switch ($type) {
            case Horde_Imap_Client::FETCH_HEADERTEXT:
                if (!isset($this->_mbox['mails'][$uid])) {
                    throw new Horde_Imap_Client_Exception(sprintf("No IMAP message %s!", $uid));
                }
                $result['headertext'][$uid] = $this->_mbox['mails'][$uid]['header'];
                break;
            case Horde_Imap_Client::FETCH_BODYTEXT:
                if (!isset($this->_mbox['mails'][$uid])) {
                    throw new Horde_Imap_Client_Exception(sprintf("No IMAP message %s!", $uid));
                }
                $result['bodytext'][$uid] =  $this->_mbox['mails'][$uid]['body'];
                break;

            case Horde_Imap_Client::FETCH_STRUCTURE:
            case Horde_Imap_Client::FETCH_FULLMSG:
            case Horde_Imap_Client::FETCH_MIMEHEADER:
            case Horde_Imap_Client::FETCH_BODYPART:
            case Horde_Imap_Client::FETCH_HEADERS:
            case Horde_Imap_Client::FETCH_BODYPARTSIZE:
            case Horde_Imap_Client::FETCH_ENVELOPE:
            case Horde_Imap_Client::FETCH_FLAGS:
            case Horde_Imap_Client::FETCH_DATE:
            case Horde_Imap_Client::FETCH_SIZE:
            case Horde_Imap_Client::FETCH_UID:
            case Horde_Imap_Client::FETCH_SEQ:
            case Horde_Imap_Client::FETCH_MODSEQ:
                throw new Horde_Imap_Client_Exception('Not supported!');
            }
        }
        return $result;
    }

    /**
     * Store message flag data.
     *
     * @param array $options Additional options.
     *
     * @return array  See self::store().
     * @throws Horde_Imap_Client_Exception
     */
    protected function _store($options)
    {

        foreach ($options['ids'] as $uid) {

            if (!isset($this->_mbox['mails'][$uid])) {
                throw new Horde_Imap_Client_Exception(sprintf("No IMAP message %s!", $uid));
            }
            foreach ($options['add'] as $flag) {
                $flag = strtoupper($flag);
                switch ($flag) {
                case '\\DELETED':
                    $this->_mbox['mails'][$uid]['flags'] |= self::FLAG_DELETED;
                    break;
                default:
                    throw new Horde_Imap_Client_Exception(sprintf('Flag %s not implemented!',
                                                                  $flag));
                }
            }
        }
        return true;
    }

    /**
     * Copy messages to another mailbox.
     *
     * @param string $dest    The destination mailbox (UTF7-IMAP).
     * @param array  $options Additional options.
     *
     * @return mixed  An array mapping old UIDs (keys) to new UIDs (values) on
     *                success (if the IMAP server and/or driver support the
     *                UIDPLUS extension) or true.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _copy($dest, $options)
    {
        $new_folder = $this->_parseFolder($dest);

        foreach ($options['ids'] as $uid) {
            if (!isset($this->_mbox['mails'][$uid])) {
                throw new Horde_Imap_Client_Exception(sprintf("No IMAP message %s!", $uid));
            }
            $mail = $this->_mbox['mails'][$uid];
            if (!empty($options['move'])) {
                unset($this->_mbox['mails'][$uid]);
            }
            $this->_appendMessage($new_folder, $mail);
        }
        return true;
    }

    /**
     * Set quota limits.
     *
     * @param string $root    The quota root (UTF7-IMAP).
     * @param array  $options Additional options.
     *
     * @return boolean  True on success.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _setQuota($root, $options)
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Get quota limits.
     *
     * @param string $root The quota root (UTF7-IMAP).
     *
     * @return mixed  An array with these possible keys: 'messages' and
     *                'storage'; each key holds an array with 2 values:
     *                'limit' and 'usage'.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getQuota($root)
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Get quota limits for a mailbox.
     *
     * @param string $mailbox A mailbox (UTF7-IMAP).
     *
     * @return mixed  An array with the keys being the quota roots. Each key
     *                holds an array with two possible keys: 'messages' and
     *                'storage'; each of these keys holds an array with 2
     *                values: 'limit' and 'usage'.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getQuotaRoot($mailbox)
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Get ACL rights for a given mailbox.
     *
     * @param string $mailbox A mailbox (UTF7-IMAP).
     *
     * @return array  An array with identifiers as the keys and an array of
     *                rights as the values.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getACL($mailbox)
    {
        $folder = $this->_getMailbox($mailbox);
        $acl    = '';
        if (isset(self::$storage[$folder]['permissions'])) {
            $acl = self::$storage[$folder]['permissions'];
        }
        return $acl;
    }

    /**
     * Set ACL rights for a given mailbox/identifier.
     *
     * @param string $mailbox    A mailbox (UTF7-IMAP).
     * @param string $identifier The identifier to alter (UTF7-IMAP).
     * @param array  $options    Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _setACL($mailbox, $identifier, $options)
    {
        $folder = $this->_getMailbox($mailbox);
        if (empty($options['rights']) && !empty($options['remove'])) {
            unset(self::$storage[$folder]['permissions'][$identifier]);
        } else {
            self::$storage[$folder]['permissions'][$identifier] = $options['rights'];
        }
    }

    /**
     * Get ACL rights for a given mailbox/identifier.
     *
     * @param string $mailbox    A mailbox (UTF7-IMAP).
     * @param string $identifier The identifier to alter (UTF7-IMAP).
     *
     * @return array  An array of rights (keys: 'required' and 'optional').
     * @throws Horde_Imap_Client_Exception
     */
    protected function _listACLRights($mailbox, $identifier)
    {
        throw new Horde_Imap_Client_Exception('not implemented');
    }

    /**
     * Get the ACL rights for the current user for a given mailbox.
     *
     * @param string $mailbox A mailbox (UTF7-IMAP).
     *
     * @return array  An array of rights.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getMyACLRights($mailbox)
    {
        $folder = $this->_getMailbox($mailbox);
        $acl    = '';
        if (isset(self::$storage[$folder]['permissions'][$this->_user])) {
            $acl = self::$storage[$folder]['permissions'][$this->_user];
        }
        return $acl;
    }

    /**
     * Get metadata for a given mailbox.
     *
     * @param string $mailbox A mailbox (UTF7-IMAP).
     * @param array  $entries The entries to fetch.
     * @param array  $options Additional options.
     *
     * @return array  An array with identifiers as the keys and the
     *                metadata as the values.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getMetadata($mailbox, $entries, $options)
    {
        $folder   = $this->_getMailbox($mailbox);
        $metadata = array();
        foreach ($entries as $entry) {
            $result = false;
            if (isset(self::$storage[$folder]['annotations'])) {
                $ref  = &self::$storage[$folder]['annotations'];
                $path = split('/', $entry);
                foreach ($path as $element) {
                    if (!isset($ref[$element])) {
                        $result = false;
                        break;
                    } else {
                        $ref    = &$ref[$element];
                        $result = true;
                    }
                }
                if ($result && isset($ref['/'])) {
                    $result = $ref['/'];
                }
            }
            $metadata[$entry] = $result;
        }
        return $metadata;
    }

    /**
     * Set metadata for a given mailbox/identifier.
     *
     * @param string $mailbox A mailbox (UTF7-IMAP).
     * @param array  $data    A set of data values. The metadata values
     *                        corresponding to the keys of the array will
     *                        be set to the values in the array.
     * @param array  $options Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _setMetadata($mailbox, $data, $options)
    {
        $folder = $this->_getMailbox($mailbox);
        foreach ($data as $key => $value) {
            $path = split('/', $key);
            $ref  = &self::$storage[$folder]['annotations'];
            foreach ($path as $element) {
                if (!isset($ref[$element])) {
                    $ref[$element] = array();

                    $ref = &$ref[$element];
                }
            }
            $ref['/'] = $value;
        }
        return true;
    }
}
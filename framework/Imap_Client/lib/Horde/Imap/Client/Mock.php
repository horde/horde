<?php
/**
 * A mock IMAP driver for unit testing.
 *
 * PHP version 5
 *
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 * @package  Imap_Client
 */

/**
 * The mock driver class.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 * @package  Imap_Client
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
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        $this->_user = $params['username'];

        if (!empty($this->params['persistent'])) {
            register_shutdown_function(array($this, 'shutdown'));

            if (empty(self::$storage) &&
                file_exists($this->params['persistent']) &&
                ($data = @unserialize(file_get_contents($this->params['persistent'])))) {
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
     */
    static public function clean()
    {
        self::$storage = array();
    }

    /**
     * Parse the given folder name into a structure that contains the user
     * name.
     *
     * @param string $folder  The folder name.
     *
     * @return string  The corrected user name.
     *
     * @todo This type of mapping only works for cyrus imap with a specific
     *       configuration.
     */
    protected function _parseFolder($folder)
    {
        if (substr($folder, 0, 5) == 'INBOX') {
            $user = explode('@', $this->_user);
            return 'user/' . $user[0] . substr($folder, 5);
        }
        return $folder;
    }

    /**
     */
    protected function _capability()
    {
        return array(
            'ACL' => true,
            'METADATA' => true,
        );
    }

    /**
     */
    protected function _noop()
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _getNamespaces()
    {
        $this->_exception('not implemented');
    }

    /**
     */
    public function alerts()
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _login()
    {
        /* We already stored the username on class construction so we have
         * nothing to do here. */
        return true;
    }

    /**
     */
    protected function _logout()
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _sendID($info)
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _getID()
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _setLanguage($langs)
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _getLanguage($list)
    {
        $this->_exception('not implemented');
    }

    /**
     * Check if a mailbox exists.
     *
     * @param string $mailbox  The mailbox to open (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    private function _getMailbox($mailbox)
    {
        $folder = $this->_parseFolder($mailbox);
        if (!isset(self::$storage[$folder])) {
            $this->_exception(sprintf("IMAP folder %s does not exist!", $folder));
        }
        return $folder;
    }

    /**
     */
    protected function _openMailbox($mailbox, $mode)
    {
        $folder          = $this->_getMailbox($mailbox);
        $this->_mbox     = &self::$storage[$folder];
        $this->_mboxname = $folder;

        return true;
    }

    /**
     */
    protected function _createMailbox($mailbox, $opts)
    {
        $mailbox = $this->_parseFolder($mailbox);
        if (isset(self::$storage[$mailbox])) {
            $this->_exception(sprintf("IMAP folder %s already exists!", $mailbox));
        }
        self::$storage[$mailbox] = array(
            'status' => array(
                'uidvalidity' => time(),
                'uidnext' => 1),
            'mails' => array(),
            'permissions' => new Horde_Imap_Client_Data_Acl(),
            'annotations' => array(),
        );
        return true;
    }

    /**
     */
    protected function _deleteMailbox($mailbox)
    {
        $folder = $this->_parseFolder($mailbox);
        if (!isset(self::$storage[$folder])) {
            $this->_exception(sprintf("IMAP folder %s does not exist!", $folder));
        }
        unset(self::$storage[$folder]);
        return true;
    }

    /**
     */
    protected function _renameMailbox($old, $new)
    {
        $old = $this->_parseFolder($old);
        $new = $this->_parseFolder($new);

        if (!isset(self::$storage[$old])) {
            $this->_exception(sprintf("IMAP folder %s does not exist!", $old));
        }
        if (isset(self::$storage[$new])) {
            $this->_exception(sprintf("IMAP folder %s already exists!", $new));
        }
        self::$storage[$new] = self::$storage[$old];
        unset(self::$storage[$old]);
        return true;
    }

    /**
     */
    protected function _subscribeMailbox($mailbox, $subscribe)
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _listMailboxes($pattern, $mode, $options)
    {
        $mboxes  = array_keys(self::$storage);
        $user    = explode('@', $this->_user);
        $pattern = '#^user/' . $user[0] . '#';
        $result  = array();
        foreach ($mboxes as $mbox) {
            if (preg_match($pattern, $mbox)) {
                $result[] = preg_replace($pattern, 'INBOX', $mbox);
            } elseif (strpos(self::$storage[$mbox]['permissions'][$this->_user], 'l') !== false) {
                $result[] = $mbox;
            }
        }
        return $result;
    }

    /**
     */
    protected function _status($mailbox, $flags)
    {
        $this->openMailbox($mailbox);
        return $this->_mbox['status'];
    }

    /**
     * @return boolean  True.
     */
    protected function _append($mailbox, $data, $options)
    {
        foreach ($data as $element) {
            $split = strpos($element['data'], "\r\n\r\n");
            $mail  = array(
                'body' => substr($element['data'], $split + 3),
                'header' => substr($element['data'], 0, $split + 2)
            );
            $this->_appendMessage($mailbox, $mail);
        }
    }

    /**
     * Appends a message to the current folder.
     *
     * @param string $mailbox  The mailbox to append the message(s) to
     *                         (UTF7-IMAP).
     * @param array  $msg      The message to append.
     */
    private function _appendMessage($mailbox, $msg)
    {
        $this->openMailbox($mailbox);

        $this->_mbox['mails'][$this->_mbox['status']['uidnext']++] = array(
            'flags' => self::FLAG_NONE,
            'header' => $msg['header'],
            'body' => $msg['body']
        );
    }

    /**
     */
    protected function _check()
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _close($options)
    {
        $this->_exception('not implemented');
    }

    /**
     * @param array $options  Additional options. 'ids' and 'list' have no
     *                        effect in this driver.
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
    }

    /**
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
                $this->_exception(sprintf('Search command %s not implemented!', $cmd));
            }
        }

        return array('match' => $uids, 'count' => count($uids));
    }

    /**
     */
    protected function _setComparator($comparator)
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _getComparator()
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _thread($options)
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _fetch($query, $results, $options)
    {
        $uid = $options['ids']->ids[0];

        foreach ($query as $type => $c_val) {
            switch ($type) {
            case Horde_Imap_Client::FETCH_HEADERTEXT:
                if (!isset($this->_mbox['mails'][$uid])) {
                    $this->_exception(sprintf("No IMAP message %s!", $uid));
                }

                $results[$uid]->setHeaderText(0, $this->_mbox['mails'][$uid]['header']);
                break;

            case Horde_Imap_Client::FETCH_BODYTEXT:
                if (!isset($this->_mbox['mails'][$uid])) {
                    $this->_exception(sprintf("No IMAP message %s!", $uid));
                }

                $results[$uid]->setBodyText(0, $this->_mbox['mails'][$uid]['body']);
                break;

            default:
                $this->_exception('Not supported!');
            }
        }

        return $results;
    }

    /**
     * @param array $options  Additional options. This driver does not support
     *                        'unchangedsince'.
     */
    protected function _store($options)
    {
        foreach ($options['ids']->ids as $uid) {
            if (!isset($this->_mbox['mails'][$uid])) {
                $this->_exception(sprintf("No IMAP message %s!", $uid));
            }

            foreach ($options['add'] as $flag) {
                $flag = strtoupper($flag);
                switch ($flag) {
                case '\\DELETED':
                    $this->_mbox['mails'][$uid]['flags'] |= self::FLAG_DELETED;
                    break;

                default:
                    $this->_exception(sprintf('Flag %s not implemented!', $flag));
                }
            }
        }

        return new Horde_Imap_Client_Ids();
    }

    /**
     */
    protected function _copy($dest, $options)
    {
        $new_folder = $this->_parseFolder($dest);

        foreach ($options['ids']->ids as $uid) {
            if (!isset($this->_mbox['mails'][$uid])) {
                $this->_exception(sprintf("No IMAP message %s!", $uid));
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
     */
    protected function _setQuota($root, $options)
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _getQuota($root)
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _getQuotaRoot($mailbox)
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _getACL($mailbox)
    {
        $folder = $this->_getMailbox($mailbox);

        return empty(self::$storage[$folder]['permissions'])
            ? array()
            : self::$storage[$folder]['permissions'];
    }

    /**
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
     */
    protected function _listACLRights($mailbox, $identifier)
    {
        $this->_exception('not implemented');
    }

    /**
     */
    protected function _getMyACLRights($mailbox)
    {
        $folder = $this->_getMailbox($mailbox);

        return isset(self::$storage[$folder]['permissions'][$this->_user])
            ? self::$storage[$folder]['permissions'][$this->_user]
            : new Horde_Imap_Client_Data_Acl();
    }

    /**
     */
    protected function _getMetadata($mailbox, $entries, $options)
    {
        $folder = $this->_getMailbox($mailbox);
        $metadata = array();

        foreach ($entries as $entry) {
            $result = false;
            if (isset(self::$storage[$folder]['annotations'])) {
                $ref = &self::$storage[$folder]['annotations'];
                $path = explode('/', $entry);
                foreach ($path as $element) {
                    if (!isset($ref[$element])) {
                        $result = false;
                        break;
                    } else {
                        $ref = &$ref[$element];
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
     */
    protected function _setMetadata($mailbox, $data)
    {
        $folder = $this->_getMailbox($mailbox);

        foreach ($data as $key => $value) {
            $path = explode('/', $key);
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

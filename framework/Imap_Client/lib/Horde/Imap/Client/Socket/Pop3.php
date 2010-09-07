<?php
/**
 * Horde_Imap_Client_Socket_Pop3 provides an interface to a POP3 server using
 * PHP functions.
 * This driver is an abstraction layer allowing POP3 commands to be used based
 * on the IMAP equivalents.
 *
 * Caching is not supported in this driver.
 *
 * This driver implements the following POP3-related RFCs:
 * STD 53/RFC 1939 - POP3 specification
 * RFC 2195 - CRAM-MD5 authentication
 * RFC 2449 - POP3 extension mechanism
 * RFC 2595/4616 - PLAIN authentication
 * RFC 1734/5034 - POP3 SASL
 *
 * TODO (or not necessary?):
 * RFC 3206 - AUTH/SYS response codes
 *
 * ---------------------------------------------------------------------------
 *
 * Originally based on the PEAR Net_POP3 package (version 1.3.6) by:
 *     Richard Heyes <richard@phpguru.org>
 *     Damian Fernandez Sosa <damlists@cnba.uba.ar>
 *
 * Copyright (c) 2002, Richard Heyes
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * o Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * o Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 * o The names of the authors may not be used to endorse or promote
 *   products derived from this software without specific prior written
 *   permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * ---------------------------------------------------------------------------
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 */
class Horde_Imap_Client_Socket_Pop3 extends Horde_Imap_Client_Base
{
    /**
     * The socket connection to the POP3 server.
     *
     * @var resource
     */
    protected $_stream = null;

    /**
     * The list of deleted messages.
     *
     * @var array
     */
    protected $_deleted = array();

    /**
     * Constructs a new object.
     *
     * @param array $params  A hash containing configuration parameters.
     */
    public function __construct(array $params = array())
    {
        if (empty($params['port'])) {
            $params['port'] = ($params['secure'] == 'ssl') ? 995 : 110;
        }

        parent::__construct($params);

        // Disable caching.
        $this->_params['cache'] = array('fields' => array());
    }

    /**
     * Unserialize.
     *
     * @param string $data  Serialized data.
     *
     * @throws Exception
     */
    public function unserialize($data)
    {
        parent::unserialize($data);

        // Disable caching.
        $this->_params['cache'] = array('fields' => array());
    }

    /**
     * Get CAPABILITY info from the server.
     *
     * @return array  The capability array.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _capability()
    {
        $this->_connect();

        $this->_init['capability'] = array();

        try {
            $this->_sendLine('CAPA');

            foreach ($this->_getMultiline(true) as $val) {
                $prefix = explode(' ', $val);

                $this->_init['capability'][strtoupper($prefix[0])] = (count($prefix) > 1)
                    ? array_slice($prefix, 1)
                    : true;
            }
        } catch (Horde_Imap_Client_Exception $e) {
            /* Need to probe for capabilities if CAPA command is not
             * available. */
            $this->_init['capability'] = array('USER', 'SASL');

            try {
                $this->_sendLine('UIDL');
                fclose($this->_getMultiline());
                $this->_init['capability'][] = 'UIDL';
            } catch (Horde_Imap_Client_Exception $e) {}

            try {
                $this->_sendLine('TOP 1 0');
                fclose($this->_getMultiline());
                $this->_init['capability'][] = 'TOP';
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return $this->_init['capability'];
    }

    /**
     * Send a NOOP command.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _noop()
    {
        $this->_sendLine('NOOP');
    }

    /**
     * Get the NAMESPACE information from the server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getNamespaces()
    {
        $this->_exception('IMAP namespaces not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     * Return a list of alerts that MUST be presented to the user.
     *
     * @return array  An array of alert messages.
     */
    public function alerts()
    {
        return array();
    }

    /**
     * Login to the server.
     *
     * @return boolean  Return true if global login tasks should be run.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _login()
    {
        $this->_connect();

        // Switch to secure channel if using TLS.
        if (!$this->_isSecure &&
            ($this->_params['secure'] == 'tls')) {
            // Switch over to a TLS connection.
            if (!$this->queryCapability('STLS')) {
                $this->_exception('Could not open secure TLS connection to the POP3 server - server does not support TLS.');
            }

            $this->_sendLine('STLS');

            $old_error = error_reporting(0);
            $res = stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            error_reporting($old_error);

            if (!$res) {
                $this->logout();
                $this->_exception('Could not open secure TLS connection to the POP3 server.');
            }

            // Expire cached CAPABILITY information
            unset($this->_init['capability']);

            $this->_isSecure = true;
        }

        if (empty($this->_init['authmethod'])) {
            $auth_mech = ($sasl = $this->queryCapability('SASL'))
                ? $sasl
                : array();

            if (isset($this->_temp['pop3timestamp'])) {
                $auth_mech[] = 'APOP';
            }

            $auth_mech[] = 'USER';
        } else {
            $auth_mech = array($this->_init['authmethod']);
        }

        foreach ($auth_mech as $method) {
            try {
                $this->_tryLogin($method);
                $this->_init['authmethod'] = $method;
                return true;
            } catch (Horde_Imap_Client_Exception $e) {
                if (!empty($this->_init['authmethod'])) {
                    unset($this->_init['authmethod']);
                    return $this->login();
                }
            }
        }

        $this->_exception('POP3 server denied authentication.');
    }

    /**
     * Connects to the server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _connect()
    {
        if (!is_null($this->_stream)) {
            return;
        }

        if (!empty($this->_params['secure']) && !extension_loaded('openssl')) {
            $this->_exception('Secure connections require the PHP openssl extension.');
        }

        switch ($this->_params['secure']) {
        case 'ssl':
            $conn = 'ssl://';
            $this->_isSecure = true;
            break;

        case 'tls':
        default:
            $conn = 'tcp://';
            break;
        }

        $old_error = error_reporting(0);
        $this->_stream = stream_socket_client($conn . $this->_params['hostspec'] . ':' . $this->_params['port'], $error_number, $error_string, $this->_params['timeout']);
        error_reporting($old_error);

        if ($this->_stream === false) {
            $this->_stream = null;
            $this->_isSecure = false;
            $this->_exception('Error connecting to POP3 server: [' . $error_number . '] ' . $error_string, 'SERVER_CONNECT');
        }

        stream_set_timeout($this->_stream, $this->_params['timeout']);

        // Add separator to make it easier to read debug log.
        if ($this->_debug) {
            fwrite($this->_debug, str_repeat('-', 30) . "\n");
        }

        $line = $this->_getLine();

        // Check for string matching APOP timestamp
        if (preg_match('/<.+@.+>/U', $line['line'], $matches)) {
            $this->_temp['pop3timestamp'] = $matches[0];
        }
    }

    /**
     * Authenticate to the IMAP server.
     *
     * @param string $method  IMAP login method.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _tryLogin($method)
    {
        switch ($method) {
        case 'CRAM-MD5':
            // RFC 5034
            if (!class_exists('Auth_SASL')) {
                $this->_exception('The Auth_SASL package is required for CRAM-MD5 authentication');
            }

            $challenge = $this->_sendLine('AUTH CRAM-MD5');

            $auth_sasl = Auth_SASL::factory('crammd5');
            $response = base64_encode($auth_sasl->getResponse($this->_params['username'], $this->getParam('password'), base64_decode(substr($challenge['line'], 2))));
            $this->_sendLine($response, array('debug' => '[CRAM-MD5 Response]'));
            break;

        case 'DIGEST-MD5':
            // RFC 5034
            if (!class_exists('Auth_SASL')) {
                $this->_exception('The Auth_SASL package is required for DIGEST-MD5 authentication');
            }

            $challenge = $this->_sendLine('AUTH DIGEST-MD5');

            $auth_sasl = Auth_SASL::factory('digestmd5');
            $response = base64_encode($auth_sasl->getResponse($this->_params['username'], $this->getParam('password'), base64_decode(substr($challenge['line'], 2)), $this->_params['hostspec'], 'pop3'));

            $sresponse = $this->_sendLine($response, array('debug' => '[DIGEST-MD5 Response]'));
            if (stripos(base64_decode(substr($sresponse['line'], 2)), 'rspauth=') === false) {
                $this->_exception('Unexpected response from server to Digest-MD5 response.');
            }

            /* POP3 doesn't use protocol's third step. */
            $this->_sendLine('');
            break;

        case 'LOGIN':
            // RFC 5034
            $this->_sendLine('AUTH LOGIN');
            $this->_sendLine(base64_encode($this->_params['username']));
            $this->_sendLine(base64_encode($this->getParam('password')));
            break;

        case 'PLAIN':
            // RFC 5034
            $this->_sendLine('AUTH PLAIN ' . base64_encode(chr(0) . $this->_params['username'] . chr(0) . $this->getParam('password')));
            break;

        case 'APOP':
            // RFC 1939 [7]
            $this->_sendLine('APOP ' . $this->_params['username'] . ' ' . hash('md5', $this->_timestamp . $pass));
            break;

        case 'USER':
            // RFC 1939 [7]
            $this->_sendLine('USER ' . $this->_params['username']);
            $this->_sendLine('PASS ' . $this->getParam('password'));
            break;
        }
    }

    /**
     * Logout from the server.
     */
    protected function _logout()
    {
        if (!is_null($this->_stream)) {
            try {
                $this->_sendLine('QUIT');
            } catch (Horde_Imap_Client_Exception $e) {}
            fclose($this->_stream);
            $this->_stream = null;
            $this->_deleted = array();
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
        $this->_exception('IMAP ID command not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     * Return ID information from the POP3 server (RFC 2449[6.9]).
     *
     * @return array  An array of information returned, with the keys as the
     *                'field' and the values as the 'value'.
     */
    protected function _getID()
    {
        $id = $this->queryCapability('IMPLEMENTATION');
        return empty($id)
            ? array()
            : array('implementation' => $id);
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
        $this->_exception('IMAP LANGUAGE extension not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
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
        $this->_exception('IMAP LANGUAGE extension not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
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
            $this->_exception('Mailboxes other than INBOX not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
        }

        $this->login();
    }

    /**
     * Create a mailbox.
     *
     * @param string $mailbox  The mailbox to create (UTF7-IMAP).
     * @param array $opts      Additional options. See self::createMailbox().
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _createMailbox($mailbox, $opts)
    {
        $this->_exception('Creating mailboxes not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
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
        $this->_exception('Deleting mailboxes not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
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
        $this->_exception('Renaming mailboxes not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
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
        $this->_exception('Mailboxes other than INBOX not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
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
     * @param integer $flags   A bitmask of information requested from the
     *                         server. This driver only supports the options
     *                         listed under Horde_Imap_Client::STATUS_ALL.
     *
     * @return array  See Horde_Imap_Client_Base::status().
     * @throws Horde_Imap_Client_Exception
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

        $ret = array();

        if ($flags & Horde_Imap_Client::STATUS_MESSAGES) {
            $res = $this->_pop3Cache('stat');
            $ret['messages'] = $res['msgs'];
        }

        if ($flags & Horde_Imap_Client::STATUS_RECENT) {
            $res = $this->_pop3Cache('stat');
            $ret['recent'] = $res['msgs'];
        }

        if ($flags & Horde_Imap_Client::STATUS_UIDNEXT) {
            $res = $this->_pop3Cache('stat');
            $ret['uidnext'] = $res['msgs'] + 1;
        }

        if ($flags & Horde_Imap_Client::STATUS_UIDVALIDITY) {
            $ret['uidvalidity'] = microtime(true);
        }

        if ($flags & Horde_Imap_Client::STATUS_UNSEEN) {
            $ret['unseen'] = 0;
        }

        return $ret;
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
        $this->_exception('Appending messages not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     * Request a checkpoint of the currently selected mailbox.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _check()
    {
        $this->noop();
    }

    /**
     * Close the connection to the currently selected mailbox, optionally
     * expunging all deleted messages.
     *
     * @param array $options  Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _close($options)
    {
        if (!empty($options['expunge'])) {
            $this->logout();
        }
    }

    /**
     * Expunge all deleted messages from the given mailbox.
     *
     * @param array $options  Additional options. 'ids' and 'sequence' have
     *                        no effect in this driver.
     *
     * @return array  If 'count' option is true, returns the list of
     *                expunged messages.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _expunge($options)
    {
        $msg_list = $this->_deleted();
        $this->logout();
        return empty($options['list']) ? null : $msg_list;
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
        $sort = empty($options['sort'])
            ? null
            : reset($options['sort']);

        // Only support a single query: an ALL search sorted by sequence.
        if ((reset($options['_query']['query']) != 'ALL') ||
            ($sort &&
             ((count($options['sort']) > 1) ||
              ($sort != Horde_Imap_Client::SORT_SEQUENCE)))) {
            $this->_exception('Server search not supported on POP3 server.', 'POP3_NOTSUPPORTED');
        }

        $status = $this->status($this->_selected, Horde_Imap_Client::STATUS_MESSAGES);
        $res = range(1, $status['messages']);

        if (empty($options['sequence'])) {
            $tmp = array();
            $uidllist = $this->_pop3Cache('uidl');
            foreach ($res as $val) {
                $tmp[] = $uidllist[$val];
            }
            $res = $tmp;
        }

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
     * @throws Horde_Imap_Client_Exception
     */
    protected function _setComparator($comparator)
    {
        $this->_exception('Search comparators not supported on POP3 server.', 'POP3_NOTSUPPORTED');
    }

    /**
     * Get the comparator used for searching/sorting (RFC 5255).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getComparator()
    {
        $this->_exception('Search comparators not supported on POP3 server.', 'POP3_NOTSUPPORTED');
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
        $this->_exception('Server threading not supported on POP3 server.', 'POP3_NOTSUPPORTED');
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

        // These options are not supported by this driver.
        if (!empty($options['changedsince']) ||
            !empty($options['vanished']) ||
            (reset($options['ids']) == Horde_Imap_Client::USE_SEARCHRES)) {
            $this->_exception('Fetch options not supported on POP3 server.', 'POP3_NOTSUPPORTED');
        }

        // Ignore 'sequence' - IDs will always be the message number.
        $use_seq = !empty($options['sequence']);
        $seq_ids = $this->_getSeqIds(empty($options['ids']) ? array() : $options['ids'], $use_seq);
        if (empty($seq_ids)) {
            return array();
        }

        $ret = array_combine($seq_ids, array_fill(0, count($seq_ids), array()));

        foreach ($criteria as $type => $c_val) {
            if (!is_array($c_val)) {
                $c_val = array();
            }

            switch ($type) {
            case Horde_Imap_Client::FETCH_FULLMSG:
                foreach ($seq_ids as $id) {
                    $tmp = $this->_pop3Cache('msg', $id);
                    if (isset($c_val['start']) && !empty($c_val['length'])) {
                        $tmp2 = fopen('php://temp', 'r+');
                        stream_copy_to_stream($tmp, $tmp2, $c_val['length'], $c_val['start']);
                        if (empty($c_val['stream'])) {
                            rewind($tmp2);
                            $ret[$id]['fullmsg'] = stream_get_contents($tmp2);
                            fclose($tmp2);
                        } else {
                            $ret[$id]['fullmsg'] = $tmp2;
                        }
                    } else {
                        $ret[$id]['fullmsg'] = empty($c_val['stream'])
                            ? stream_get_contents($tmp)
                            : $tmp;
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_HEADERTEXT:
            case Horde_Imap_Client::FETCH_BODYTEXT:
            case Horde_Imap_Client::FETCH_MIMEHEADER:
            case Horde_Imap_Client::FETCH_BODYPART:
                foreach ($c_val as $val) {
                    switch ($type) {
                    case Horde_Imap_Client::FETCH_HEADERTEXT:
                        // Ignore 'peek' option
                        $label = 'headertext';
                        $rawtype = 'header';

                        if (empty($val['id'])) {
                            $val['id'] = 0;
                        }
                        break;

                    case Horde_Imap_Client::FETCH_BODYTEXT:
                        $label = 'bodytext';
                        $rawtype = 'body';

                        if (empty($val['id'])) {
                            $val['id'] = 0;
                        }
                        break;

                    case Horde_Imap_Client::FETCH_MIMEHEADER:
                        $label = 'mimeheader';
                        $rawtype = 'header';

                        if (empty($val['id'])) {
                            $this->_exception('Need a non-zero MIME ID when retrieving a MIME header.');
                        }
                        break;

                    case Horde_Imap_Client::FETCH_BODYPART:
                        // Ignore 'decode' parameter
                        $label = 'bodypart';
                        $rawtype = 'body';

                        if (empty($val['id'])) {
                            $this->_exception('Need a non-zero MIME ID when retrieving a MIME body part.');
                        }
                        break;
                    }

                    foreach ($seq_ids as $id) {
                        if (!isset($ret[$id][$label])) {
                            $ret[$id][$label] = array();
                        }

                        try {
                            /* Special case: Message header can be retrieved
                             * via TOP, if the command is available. */
                            if (($val['id'] == 0) &&
                                ($type == Horde_Imap_Client::FETCH_HEADERTEXT)) {
                                $tmp = $this->_pop3Cache('hdr', $id);
                            } else {
                                $tmp = Horde_Mime_Part::getRawPartText(stream_get_contents($this->_pop3Cache('msg', $id)), $rawtype, $val['id']);
                            }

                            if (isset($val['start']) &&
                                !empty($val['length'])) {
                                $tmp = substr($tmp, $val['start'], $val['length']);
                            }

                            if (($rawtype == 'body') &&
                                !empty($val['stream'])) {
                                $ptr = fopen('php://temp', 'r+');
                                fwrite($ptr, $tmp);
                                $tmp = $ptr;
                            }
                        } catch (Horde_Mime_Exception $e) {
                            $tmp = false;
                        }

                        $ret[$id][$label][$val['id']] = $tmp;
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_HEADERS:
                // Ignore 'length', 'peek'
                foreach ($seq_ids as $id) {
                    $ob = $this->_pop3Cache('hdrob', $id);
                    foreach ($c_val as $val) {
                        $tmp = $ob;

                        if (empty($val['label'])) {
                            $this->_exception('Need a unique label when doing a headers field search.');
                        } elseif (empty($val['headers'])) {
                            $this->_exception('Need headers to query when doing a headers field search.');
                        }

                        if (empty($val['notsearch'])) {
                            $tmp2 = $tmp->toArray(array('nowrap' => true));
                            foreach (array_keys($tmp2) as $hdr) {
                                if (!in_array($hdr, $val['headers'])) {
                                    $tmp->removeHeader($hdr);
                                }
                            }
                        } else {
                            foreach ($val['headers'] as $hdr) {
                                $tmp->removeHeader($hdr);
                            }
                        }

                        $ret[$id]['headers'][$val['label']] = empty($val['parse'])
                            ? $tmp->toString(array('nowrap' => true))
                            : $tmp;
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_STRUCTURE:
                foreach ($seq_ids as $id) {
                    $tmp = false;
                    if ($ptr = $this->_pop3Cache('msg', $id)) {
                        try {
                            $tmp = Horde_Mime_Part::parseMessage(stream_get_contents($ptr), array('structure' => empty($c_val['parse'])));
                        } catch (Horde_Exception $e) {}
                    }
                    $ret[$id]['structure'] = $tmp;
                }
                break;

            case Horde_Imap_Client::FETCH_ENVELOPE:
                foreach ($seq_ids as $id) {
                    $tmp = $this->_pop3Cache('hdrob', $id);
                    $ret[$id]['envelope'] = array(
                        'date' => $tmp->getValue('date'),
                        'subject' => $tmp->getValue('subject'),
                        'from' => $tmp->getOb('from'),
                        'reply-to' => $tmp->getOb('reply-to'),
                        'to' => $tmp->getOb('to'),
                        'cc' => $tmp->getOb('cc'),
                        'bcc' => $tmp->getOb('bcc'),
                        'in-reply-to' => $tmp->getValue('in-reply-to'),
                        'message-id' => $tmp->getValue('message-id')
                    );
                }
                break;

            case Horde_Imap_Client::FETCH_FLAGS:
                foreach ($seq_ids as $id) {
                    $ret[$id]['flags'] = array();
                }
                break;

            case Horde_Imap_Client::FETCH_DATE:
                foreach ($seq_ids as $id) {
                    $tmp = $this->_pop3Cache('hdrob', $id);
                    $ret[$id]['date'] = new Horde_Imap_Client_DateTime($tmp->getValue('date'));
                }
                break;

            case Horde_Imap_Client::FETCH_SIZE:
                $sizelist = $this->_pop3Cache('size');
                foreach ($seq_ids as $id) {
                    $ret[$id]['size'] = $sizelist[$id];
                }
                break;

            case Horde_Imap_Client::FETCH_SEQ:
                foreach ($seq_ids as $id) {
                    $ret[$id]['seq'] = $id;
                }
                break;

            case Horde_Imap_Client::FETCH_UID:
                $uidllist = $this->_pop3Cache('uidl');
                foreach ($seq_ids as $id) {
                    $ret[$id]['uid'] = isset($uidllist[$id])
                        ? $uidllist[$id]
                        : false;
                }
                break;
            }
        }

        if ($use_seq) {
            return $ret;
        }

        $tmp = array();
        $uidllist = $this->_pop3Cache('uidl');
        foreach (array_keys($ret) as $key) {
            $tmp[$uidllist[$key]] = $ret[$key];
        }

        return $tmp;
    }

    /**
     * Retrieve locally cached message data.
     *
     * @param string $type    Either 'hdr', 'hdrob', 'msg', 'size', 'stat',
     *                        or 'uidl'.
     * @param integer $index  The message index.
     * @param mixed $data     Additional information needed.
     *
     * @return mixed  The cached data. 'msg' returns a stream resource. All
     *                other types return strings.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _pop3Cache($type, $index = null, $data = null)
    {
        if (isset($this->_temp['pop3cache'][$index][$type])) {
            if ($type == 'msg') {
                rewind($this->_temp['pop3cache'][$index][$type]);
            }
            return $this->_temp['pop3cache'][$index][$type];
        }

        switch ($type) {
        case 'hdr':
            $data = null;
            if ($this->queryCapability('TOP')) {
                try {
                    $resp = $this->_sendLine('TOP ' . $index . ' 0');
                    $ptr = $this->_getMultiline();
                    rewind($ptr);
                    $data = stream_get_contents($ptr);
                    fclose($ptr);
                } catch (Horde_Imap_Client_Exception $e) {}
            }

            if (is_null($data)) {
                $data = Horde_Mime_Part::getRawPartText(stream_get_contents($this->_pop3Cache('msg', $index)), 'header', 0);
            }
            break;

        case 'hdrob':
            $data = Horde_Mime_Headers::parseHeaders($this->_pop3Cache('hdr', $index));
            break;

        case 'msg':
            $resp = $this->_sendLine('RETR ' . $index);
            $data = $this->_getMultiline();
            rewind($data);
            break;

        case 'size':
        case 'uidl':
            $data = array();
            try {
                $this->_sendLine(($type == 'size') ? 'LIST' : 'UIDL');
                foreach ($this->_getMultiline(true) as $val) {
                    $resp_data = explode(' ', $val, 2);
                    $data[$resp_data[0]] = $resp_data[1];
                }
            } catch (Horde_Imap_Client_Exception $e) {}
            break;

        case 'stat':
            $resp = $this->_sendLine('STAT');
            $resp_data = explode(' ', $resp['line'], 2);
            $data = array('msgs' => $resp_data[0], 'size' => $resp_data[1]);
            break;
        }

        $this->_temp['pop3cache'][$index][$type] = $data;

        return $data;
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
        $delete = $reset = false;

        /* Only support deleting/undeleting messages. */
        if (isset($options['replace'])) {
            $delete = (bool)(count(array_intersect($options['replace'], array('\\deleted'))));
            $reset = !$delete;
        } else {
            if (!empty($options['add'])) {
                $delete = (bool)(count(array_intersect($options['add'], array('\\deleted'))));
            }

            if (!empty($options['remove'])) {
                $reset = !(bool)(count(array_intersect($options['remove'], array('\\deleted'))));
            }
        }

        if ($reset) {
            $this->_sendLine('RSET');
        } elseif ($delete) {
            $seq_ids = $this->_getSeqIds(empty($options['ids']) ? array() : $options['ids'], !empty($options['sequence']));
            foreach ($seq_ids as $id) {
                try {
                    $this->_sendLine('DELE ' . $id);
                } catch (Horde_Imap_Client_Exception $e) {}
            }
        }
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
        $this->_exception('Copying messages not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
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
        $this->_exception('IMAP quotas not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
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
        $this->_exception('IMAP quotas not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
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
        $this->_exception('IMAP quotas not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
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
        $this->_exception('IMAP ACLs not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
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
        $this->_exception('IMAP ACLs not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     * Get ACL rights for a given mailbox/identifier.
     *
     * @param string $mailbox     A mailbox (UTF7-IMAP).
     * @param string $identifier  The identifier to query (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _listACLRights($mailbox, $identifier)
    {
        $this->_exception('IMAP ACLs not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
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
        $this->_exception('IMAP ACLs not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /**
     * Get metadata for a given mailbox.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     * @param array $entries   The entries to fetch.
     * @param array $options   Additional options.
     *
     * @return array  An array with identifiers as the keys and the
     *                metadata as the values.
     * @throws Horde_Imap_Client_Exception
     */
     protected function _getMetadata($mailbox, $entries, $options)
     {
        $this->_exception('IMAP metadata not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
     }

    /**
     * Set metadata for a given mailbox/identifier.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     * @param array $data      A set of data values. The metadata values
     *                         corresponding to the keys of the array will
     *                         be set to the values in the array.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _setMetadata($mailbox, $data)
    {
        $this->_exception('IMAP metadata not supported on POP3 servers.', 'POP3_NOTSUPPORTED');
    }

    /* Internal functions. */

    /**
     * Perform a command on the server. A connection to the server must have
     * already been made.
     *
     * @param string $query   The command to execute.
     * @param array $options  Additional options:
     * <pre>
     * 'debug' - (string) When debugging, send this string instead of the
     *           actual command/data sent.
     *           DEFAULT: Raw data output to debug stream.
     * </pre>
     */
    protected function _sendLine($query, $options = array())
    {
        if ($this->_debug) {
            fwrite($this->_debug, 'C (' . microtime(true) . '): ' . (empty($options['debug']) ? $query : $options['debug']) . "\n");
        }

        fwrite($this->_stream, $query . "\r\n");

        return $this->_getLine();
    }

    /**
     * Gets a line from the stream and parses it.
     *
     * @return array  An array with the following keys:
     * <pre>
     * 'line' - (string) The server response text.
     * 'response' - (string) Either 'OK', 'END', '+', or ''.
     * </pre>
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getLine()
    {
        $ob = array('line' => '', 'response' => '');

        if (feof($this->_stream)) {
            $this->logout();
            $this->_exception('POP3 Server closed the connection unexpectedly.', 'DISCONNECT');
        }

        $read = rtrim(fgets($this->_stream));
        if (empty($read)) {
            return;
        }

        if ($this->_debug) {
            fwrite($this->_debug, 'S (' . microtime(true) . '): ' . $read . "\n");
        }

        $prefix = explode(' ', $read, 2);

        switch ($prefix[0]) {
        case '+OK':
            $ob['response'] = 'OK';
            if (isset($prefix[1])) {
                $ob['line'] = $prefix[1];
            }
            break;

        case '-ERR':
            $this->_exception('POP3 Error: ' . isset($prefix[1]) ? $prefix[1] : 'no error message');

        case '.':
            $ob['response'] = 'END';
            break;

        case '+':
            $ob['response'] = '+';
            break;

        default:
            $ob['line'] = $read;
            break;
        }

        return $ob;
    }

    /**
     * Obtain multiline input.
     *
     * @param boolean $retarray  Return an array?
     *
     * @return mixed  An array if $retarray is true, a stream resource
     *                otherwise.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getMultiline($retarray = false)
    {
        $data = $retarray
            ? array()
            : fopen('php://temp', 'r+');

        do {
            $line = $this->_getLine();
            if (empty($line['response'])) {
                if (substr($line['line'], 0, 2) == '..') {
                    $line['line'] = substr($line['line'], 1);
                }

                if ($retarray) {
                    $data[] = $line['line'];
                } else {
                    fwrite($data, $line['line'] . "\r\n");
                }
            }
        } while ($line['response'] != 'END');

        return $data;
    }

    /**
     * Returns a list of sequence IDs.
     *
     * @param array $ids    The ID list.
     * @param boolean $seq  Are the IDs sequence IDs?
     *
     * @return array  A list of sequence IDs.
     */
    protected function _getSeqIds($ids, $seq)
    {
        if (empty($ids)) {
            $status = $this->status($this->_selected, Horde_Imap_Client::STATUS_MESSAGES);
            return range(1, $status['messages']);
        } elseif ($seq) {
            return $ids;
        } else {
            return array_keys(array_intersect($this->_pop3Cache('uidl'), $ids));
        }
    }

}

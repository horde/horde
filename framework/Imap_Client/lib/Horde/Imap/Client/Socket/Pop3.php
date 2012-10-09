<?php
/**
 * An interface to a POP3 server using PHP functions.
 *
 * It is an abstraction layer allowing POP3 commands to be used based on
 * IMAP equivalents.
 *
 * This driver implements the following POP3-related RFCs:
 *   - STD 53/RFC 1939: POP3 specification
 *   - RFC 2195: CRAM-MD5 authentication
 *   - RFC 2449: POP3 extension mechanism
 *   - RFC 2595/4616: PLAIN authentication
 *   - RFC 2831: DIGEST-MD5 SASL Authentication (obsoleted by RFC 6331)
 *   - RFC 3206: AUTH/SYS response codes
 *   - RFC 1734/5034: POP3 SASL
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
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Socket_Pop3 extends Horde_Imap_Client_Base
{
    /**
     * The list of deleted messages.
     *
     * @var array
     */
    protected $_deleted = array();

    /**
     * This object returns POP3 Fetch data objects.
     *
     * @var string
     */
    protected $_fetchDataClass = 'Horde_Imap_Client_Data_Fetch_Pop3';

    /**
     * The socket connection to the POP3 server.
     *
     * @var resource
     */
    protected $_stream = null;

    /**
     */
    protected $_utilsClass = 'Horde_Imap_Client_Utils_Pop3';

    /**
     */
    public function __construct(array $params = array())
    {
        if (empty($params['port'])) {
            $params['port'] = (isset($params['secure']) && ($params['secure'] == 'ssl'))
                ? 995
                : 110;
        }

        parent::__construct($params);
    }

    /**
     */
    protected function _initCache($current = false)
    {
        return parent::_initCache($current) &&
               $this->queryCapability('UIDL');
    }

    /**
     */
    public function getIdsOb($ids = null, $sequence = false)
    {
        return new Horde_Imap_Client_Ids_Pop3($ids, $sequence);
    }

    /**
     */
    protected function _capability()
    {
        $this->_connect();

        $capability = array();

        try {
            $this->_sendLine('CAPA');

            foreach ($this->_getMultiline(true) as $val) {
                $prefix = explode(' ', $val);

                $capability[strtoupper($prefix[0])] = (count($prefix) > 1)
                    ? array_slice($prefix, 1)
                    : true;
            }
        } catch (Horde_Imap_Client_Exception $e) {
            /* Need to probe for capabilities if CAPA command is not
             * available. */
            $capability = array('USER', 'SASL');

            try {
                $this->_sendLine('UIDL');
                fclose($this->_getMultiline());
                $capability[] = 'UIDL';
            } catch (Horde_Imap_Client_Exception $e) {}

            try {
                $this->_sendLine('TOP 1 0');
                fclose($this->_getMultiline());
                $capability[] = 'TOP';
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        $this->_setInit('capability', $capability);

        return $this->_init['capability'];
    }

    /**
     */
    protected function _noop()
    {
        $this->_sendLine('NOOP');
    }

    /**
     */
    protected function _getNamespaces()
    {
        $this->_exception('IMAP namespaces not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    public function alerts()
    {
        return array();
    }

    /**
     */
    protected function _login()
    {
        $this->_connect();

        // Switch to secure channel if using TLS.
        if (!$this->_isSecure &&
            ($this->_params['secure'] == 'tls')) {
            // Switch over to a TLS connection.
            if (!$this->queryCapability('STLS')) {
                $this->_exception(Horde_Imap_Client_Translation::t("Could not open secure connection to the POP3 server.") . ' ' . Horde_Imap_Client_Translation::t("Server does not support secure connections."), 'LOGIN_TLSFAILURE');
            }

            $this->_sendLine('STLS');

            $res = @stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            if (!$res) {
                $this->logout();
                $this->_exception(Horde_Imap_Client_Translation::t("Could not open secure connection to the POP3 server."), 'LOGIN_TLSFAILURE');
            }

            // Expire cached CAPABILITY information
            $this->_setInit('capability');

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
                $this->_setInit('authmethod', $method);
                return true;
            } catch (Horde_Imap_Client_Exception $e) {
                if (!empty($this->_init['authmethod'])) {
                    $this->_setInit('authmethod');
                    return $this->login();
                }
            }
        }

        $this->_exception(Horde_Imap_Client_Translation::t("POP3 server denied authentication."), $e->getCode() ? $e->getCode() : 'LOGIN_AUTHENTICATIONFAILED');
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
            new InvalidArgumentException('Secure connections require the PHP openssl extension.');
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

        $this->_stream = @stream_socket_client($conn . $this->_params['hostspec'] . ':' . $this->_params['port'], $error_number, $error_string, $this->_params['timeout']);

        if ($this->_stream === false) {
            $this->_stream = null;
            $this->_isSecure = false;
            $this->_exception(array(
                Horde_Imap_Client_Translation::t("Error connecting to POP3 server."),
                sprintf("[%u] %s.", $error_number, $error_string)
            ), 'SERVER_CONNECT');
        }

        stream_set_timeout($this->_stream, $this->_params['timeout']);

        $line = $this->_getLine();

        // Check for string matching APOP timestamp
        if (preg_match('/<.+@.+>/U', $line['line'], $matches)) {
            $this->_temp['pop3timestamp'] = $matches[0];
        }
    }

    /**
     * Authenticate to the POP3 server.
     *
     * @param string $method  POP3 login method.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _tryLogin($method)
    {
        switch ($method) {
        case 'CRAM-MD5':
        case 'CRAM-SHA1':
        case 'CRAM-SHA256':
            // RFC 5034: CRAM-MD5
            // CRAM-SHA1 & CRAM-SHA256 supported by Courier SASL library
            $challenge = $this->_sendLine('AUTH ' . $method);
            $response = base64_encode($this->_params['username'] . ' ' . hash_hmac(strtolower($method, 5), $this->getParam('password'), base64_decode(substr($challenge['line'], 2)), true));
            $this->_sendLine($response, array('debug' => '[' . $method . ' Response]'));
            break;

        case 'DIGEST-MD5':
            // RFC 2831; Obsoleted by RFC 6331
            $challenge = $this->_sendLine('AUTH DIGEST-MD5');
            $response = base64_encode(new Horde_Imap_Client_Auth_DigestMD5(
                $this->_params['username'],
                $this->getParam('password'),
                base64_decode(substr($challenge['line'], 2)),
                $this->_params['hostspec'],
                'pop3'
            ));
            $sresponse = $this->_sendLine($response, array(
                'debug' => '[DIGEST-MD5 Response]'
            ));
            if (stripos(base64_decode(substr($sresponse['line'], 2)), 'rspauth=') === false) {
                $this->_exception(Horde_Imap_Client_Translation::t("Unexpected response from server when authenticating."), 'SERVER_CONNECT');
            }

            /* POP3 doesn't use protocol's third step. */
            $this->_sendLine('');
            break;

        case 'LOGIN':
            // RFC 5034
            $this->_sendLine('AUTH LOGIN');
            $this->_sendLine(base64_encode($this->_params['username']));
            $this->_sendLine(base64_encode($this->getParam('password')), array(
                'debug' => '[AUTH LOGIN Command - password]'
            ));
            break;

        case 'PLAIN':
            // RFC 5034
            $this->_sendLine('AUTH PLAIN ' . base64_encode(chr(0) . $this->_params['username'] . chr(0) . $this->getParam('password')), array(
                'debug' => sprintf('[AUTH PLAIN Command - username: %s]', $this->_params['username'])
            ));
            break;

        case 'APOP':
            // RFC 1939 [7]
            $this->_sendLine('APOP ' . $this->_params['username'] . ' ' . hash('md5', $this->_temp['pop3timestamp'] . $this->_params['password']));
            break;

        case 'USER':
            // RFC 1939 [7]
            $this->_sendLine('USER ' . $this->_params['username']);
            $this->_sendLine('PASS ' . $this->getParam('password'), array(
                'debug' => '[USER Command - password]'
            ));
            break;

        default:
            $this->_exception(sprintf(Horde_Imap_Client_Translation::t("Unknown authentication method: %s"), $method), 'SERVER_CONNECT');
        }
    }

    /**
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
     */
    protected function _sendID($info)
    {
        $this->_exception('IMAP ID command not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     * Return implementation information from the POP3 server (RFC 2449 [6.9]).
     */
    protected function _getID()
    {
        $id = $this->queryCapability('IMPLEMENTATION');
        return empty($id)
            ? array()
            : array('implementation' => $id);
    }

    /**
     */
    protected function _setLanguage($langs)
    {
        $this->_exception('IMAP LANGUAGE extension not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _getLanguage($list)
    {
        $this->_exception('IMAP LANGUAGE extension not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _openMailbox(Horde_Imap_Client_Mailbox $mailbox, $mode)
    {
        if (strcasecmp($mailbox, 'INBOX') !== 0) {
            $this->_exception('Mailboxes other than INBOX not supported on POP3 servers.', 'NO_SUPPORT');
        }
    }

    /**
     */
    protected function _createMailbox(Horde_Imap_Client_Mailbox $mailbox, $opts)
    {
        $this->_exception('Creating mailboxes not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _deleteMailbox(Horde_Imap_Client_Mailbox $mailbox)
    {
        $this->_exception('Deleting mailboxes not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _renameMailbox(Horde_Imap_Client_Mailbox $old,
                                      Horde_Imap_Client_Mailbox $new)
    {
        $this->_exception('Renaming mailboxes not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _subscribeMailbox(Horde_Imap_Client_Mailbox $mailbox,
                                         $subscribe)
    {
        $this->_exception('Mailboxes other than INBOX not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _listMailboxes($pattern, $mode, $options)
    {
        $tmp = array(
            'mailbox' => Horde_Imap_Client_Mailbox::get('INBOX')
        );

        if (!empty($options['attributes'])) {
            $tmp['attributes'] = array();
        }
        if (!empty($options['delimiter'])) {
            $tmp['delimiter'] = '';
        }

        return array('INBOX' => $tmp);
    }

    /**
     * @param integer $flags   This driver only supports the options listed
     *                         under Horde_Imap_Client::STATUS_ALL.
     */
    protected function _status(Horde_Imap_Client_Mailbox $mailbox, $flags)
    {
        $this->openMailbox($mailbox);

        // This driver only supports the base flags given by c-client.
        if (($flags & Horde_Imap_Client::STATUS_FIRSTUNSEEN) ||
            ($flags & Horde_Imap_Client::STATUS_FLAGS) ||
            ($flags & Horde_Imap_Client::STATUS_PERMFLAGS) ||
            ($flags & Horde_Imap_Client::STATUS_HIGHESTMODSEQ) ||
            ($flags & Horde_Imap_Client::STATUS_UIDNOTSTICKY)) {
            $this->_exception('Improper status request on POP3 server.', 'NO_SUPPORT');
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
            $ret['uidvalidity'] = $this->queryCapability('UIDL')
                ? 1
                : microtime(true);
        }

        if ($flags & Horde_Imap_Client::STATUS_UNSEEN) {
            $ret['unseen'] = 0;
        }

        return $ret;
    }

    /**
     */
    protected function _append(Horde_Imap_Client_Mailbox $mailbox, $data,
                               $options)
    {
        $this->_exception('Appending messages not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _check()
    {
        $this->noop();
    }

    /**
     */
    protected function _close($options)
    {
        if (!empty($options['expunge'])) {
            $this->logout();
        }
    }

    /**
     * @param array $options  Additional options. 'ids' has no effect in this
     *                        driver.
     */
    protected function _expunge($options)
    {
        $msg_list = $this->_deleted;
        $this->logout();
        return empty($options['list'])
            ? null
            : $msg_list;
    }

    /**
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
            $this->_exception('Server search not supported on POP3 server.', 'NO_SUPPORT');
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
            case Horde_Imap_Client::SEARCH_RESULTS_COUNT:
                $ret['count'] = count($res);
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_MATCH:
                $ret['match'] = $this->getIdsOb($res);
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_MAX:
                $ret['max'] = empty($res) ? null : max($res);
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_MIN:
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
        $this->_exception('Search comparators not supported on POP3 server.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _getComparator()
    {
        $this->_exception('Search comparators not supported on POP3 server.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _thread($options)
    {
        $this->_exception('Server threading not supported on POP3 server.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _fetch($query, $results, $options)
    {
        // These options are not supported by this driver.
        if (!empty($options['changedsince']) ||
            !empty($options['vanished'])) {
            $this->_exception('Fetch options not supported on POP3 server.', 'NO_SUPPORT');
        }

        // Grab sequence IDs - IDs will always be the message number for
        // POP3 fetch commands.
        $seq_ids = $this->_getSeqIds($options['ids']);
        if (empty($seq_ids)) {
            return $results;
        }

        $lookup = $options['ids']->sequence
            ? array_combine($seq_ids, $seq_ids)
            : $this->_pop3Cache('uidl');

        foreach ($query as $type => $c_val) {
            switch ($type) {
            case Horde_Imap_Client::FETCH_FULLMSG:
                foreach ($seq_ids as $id) {
                    $tmp = $this->_pop3Cache('msg', $id);

                    if (empty($c_val['start']) && empty($c_val['length'])) {
                        $tmp2 = fopen('php://temp', 'r+');
                        stream_copy_to_stream($tmp, $tmp2, empty($c_val['length']) ? -1 : $c_val['length'], empty($c_val['start']) ? 0 : $c_val['start']);
                        $results[$lookup[$id]]->setFullMsg($tmp2);
                    } else {
                        $results[$lookup[$id]]->setFullMsg($tmp);
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_HEADERTEXT:
                // Ignore 'peek' option
                foreach ($c_val as $key => $val) {
                    foreach ($seq_ids as $id) {
                        /* Message header can be retrieved via TOP, if the
                         * command is available. */
                        try {
                            $tmp = ($key == 0)
                                ? $this->_pop3Cache('hdr', $id)
                                : Horde_Mime_Part::getRawPartText(stream_get_contents($this->_pop3Cache('msg', $id)), 'header', $key);
                            $results[$lookup[$id]]->setHeaderText($key, $this->_processString($tmp, $c_val));
                        } catch (Horde_Mime_Exception $e) {}
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_BODYTEXT:
                // Ignore 'peek' option
                foreach ($c_val as $key => $val) {
                    foreach ($seq_ids as $id) {
                        try {
                            $results[$lookup[$id]]->setBodyText($key, $this->_processString(Horde_Mime_Part::getRawPartText(stream_get_contents($this->_pop3Cache('msg', $id)), 'body', $key), $val));
                        } catch (Horde_Mime_Exception $e) {}
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_MIMEHEADER:
                // Ignore 'peek' option
                foreach ($c_val as $key => $val) {
                    foreach ($seq_ids as $id) {
                        try {
                            $results[$lookup[$id]]->setMimeHeader($key, $this->_processString(Horde_Mime_Part::getRawPartText(stream_get_contents($this->_pop3Cache('msg', $id)), 'header', $key), $val));
                        } catch (Horde_Mime_Exception $e) {}
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_BODYPART:
                // Ignore 'decode', 'peek'
                foreach ($c_val as $key => $val) {
                    foreach ($seq_ids as $id) {
                        try {
                            $results[$lookup[$id]]->setBodyPart($key, $this->_processString(Horde_Mime_Part::getRawPartText(stream_get_contents($this->_pop3Cache('msg', $id)), 'body', $key), $val));
                        } catch (Horde_Mime_Exception $e) {}
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_HEADERS:
                // Ignore 'length', 'peek'
                foreach ($seq_ids as $id) {
                    $ob = $this->_pop3Cache('hdrob', $id);
                    foreach ($c_val as $key => $val) {
                        $tmp = $ob;

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

                        $results[$lookup[$id]]->setHeaders($key, $tmp);
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_STRUCTURE:
                foreach ($seq_ids as $id) {
                    if ($ptr = $this->_pop3Cache('msg', $id)) {
                        try {
                            $results[$lookup[$id]]->setStructure(Horde_Mime_Part::parseMessage(stream_get_contents($ptr)));
                        } catch (Horde_Exception $e) {}
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_ENVELOPE:
                $rfc822 = new Horde_Mail_Rfc822();
                foreach ($seq_ids as $id) {
                    $tmp = $this->_pop3Cache('hdrob', $id);
                    $results[$lookup[$id]]->setEnvelope(array(
                        'date' => $tmp->getValue('date'),
                        'subject' => $tmp->getValue('subject'),
                        'from' => $tmp->getOb('from'),
                        'sender' => $tmp->getOb('sender'),
                        'reply_to' => $tmp->getOb('reply-to'),
                        'to' => $rfc822->parseAddressList(Horde_Mime_Address::addrArray2String($tmp->getOb('to'))),
                        'cc' => $rfc822->parseAddressList(Horde_Mime_Address::addrArray2String($tmp->getOb('cc'))),
                        'bcc' => $rfc822->parseAddressList(Horde_Mime_Address::addrArray2String($tmp->getOb('bcc'))),
                        'in_reply_to' => $tmp->getValue('in-reply-to'),
                        'message_id' => $tmp->getValue('message-id')
                    ));
                }
                break;

            case Horde_Imap_Client::FETCH_IMAPDATE:
                foreach ($seq_ids as $id) {
                    $tmp = $this->_pop3Cache('hdrob', $id);
                    $results[$lookup[$id]]->setImapDate($tmp->getValue('date'));
                }
                break;

            case Horde_Imap_Client::FETCH_SIZE:
                $sizelist = $this->_pop3Cache('size');
                foreach ($seq_ids as $id) {
                    $results[$lookup[$id]]->setSize($sizelist[$id]);
                }
                break;

            case Horde_Imap_Client::FETCH_SEQ:
                foreach ($seq_ids as $id) {
                    $results[$lookup[$id]]->setSeq($id);
                }
                break;

            case Horde_Imap_Client::FETCH_UID:
                $uidllist = $this->_pop3Cache('uidl');
                foreach ($seq_ids as $id) {
                    if (isset($uidllist[$id])) {
                        $results[$lookup[$id]]->setUid($uidllist[$id]);
                    }
                }
                break;
            }
        }

        $this->_updateCache($results, array(
            'seq' => $options['ids']->sequence
        ));

        return $results;
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
     *
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
     * @param array $options  Additional options. This driver does not support
     *                        'unchangedsince'.
     */
    protected function _store($options)
    {
        $delete = $reset = false;

        /* Only support deleting/undeleting messages. */
        if (isset($options['replace'])) {
            $delete = (bool)(count(array_intersect($options['replace'], array(
                Horde_Imap_Client::FLAG_DELETED
            ))));
            $reset = !$delete;
        } else {
            if (!empty($options['add'])) {
                $delete = (bool)(count(array_intersect($options['add'], array(
                    Horde_Imap_Client::FLAG_DELETED
                ))));
            }

            if (!empty($options['remove'])) {
                $reset = !(bool)(count(array_intersect($options['remove'], array(
                    Horde_Imap_Client::FLAG_DELETED
                ))));
            }
        }

        if ($reset) {
            $this->_sendLine('RSET');
        } elseif ($delete) {
            foreach ($this->_getSeqIds($options['ids']) as $id) {
                try {
                    $this->_sendLine('DELE ' . $id);
                } catch (Horde_Imap_Client_Exception $e) {}
            }
        }

        return $this->getIdsOb();
    }

    /**
     */
    protected function _copy(Horde_Imap_Client_Mailbox $dest, $options)
    {
        $this->_exception('Copying messages not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _setQuota(Horde_Imap_Client_Mailbox $root, $options)
    {
        $this->_exception('IMAP quotas not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _getQuota(Horde_Imap_Client_Mailbox $root)
    {
        $this->_exception('IMAP quotas not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _getQuotaRoot(Horde_Imap_Client_Mailbox $mailbox)
    {
        $this->_exception('IMAP quotas not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _setACL(Horde_Imap_Client_Mailbox $mailbox, $identifier,
                               $options)
    {
        $this->_exception('IMAP ACLs not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _getACL(Horde_Imap_Client_Mailbox $mailbox)
    {
        $this->_exception('IMAP ACLs not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _listACLRights(Horde_Imap_Client_Mailbox $mailbox,
                                      $identifier)
    {
        $this->_exception('IMAP ACLs not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _getMyACLRights(Horde_Imap_Client_Mailbox $mailbox)
    {
        $this->_exception('IMAP ACLs not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _getMetadata(Horde_Imap_Client_Mailbox $mailbox,
                                    $entries, $options)
    {
        $this->_exception('IMAP metadata not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _setMetadata(Horde_Imap_Client_Mailbox $mailbox, $data)
    {
        $this->_exception('IMAP metadata not supported on POP3 servers.', 'NO_SUPPORT');
    }

    /**
     */
    protected function _getSearchCache($type, $mailbox, $options)
    {
        /* POP3 does not support search caching. */
        return null;
    }

    /* Internal functions. */

    /**
     * Perform a command on the server. A connection to the server must have
     * already been made.
     *
     * @param string $query   The command to execute.
     * @param array $options  Additional options:
     *   - debug: (string) When debugging, send this string instead of the
     *            actual command/data sent.
     *            DEFAULT: Raw data output to debug stream.
     */
    protected function _sendLine($query, $options = array())
    {
        $this->writeDebug((empty($options['debug']) ? $query : $options['debug']) . "\n", Horde_Imap_Client::DEBUG_CLIENT);

        fwrite($this->_stream, $query . "\r\n");

        return $this->_getLine();
    }

    /**
     * Gets a line from the stream and parses it.
     *
     * @return array  An array with the following keys:
     *   - line: (string) The server response text.
     *   - response: (string) Either 'OK', 'END', '+', or ''.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getLine()
    {
        $ob = array('line' => '', 'response' => '');

        if (feof($this->_stream)) {
            $this->logout();
            $this->_exception(Horde_Imap_Client_Translation::t("POP3 Server closed the connection unexpectedly."), 'DISCONNECT');
        }

        $read = rtrim(fgets($this->_stream));
        if (empty($read)) {
            return;
        }

        $this->writeDebug($read . "\n", Horde_Imap_Client::DEBUG_SERVER);

        $orig_read = $read;
        $read = explode(' ', $read, 2);

        switch ($read[0]) {
        case '+OK':
            $ob['response'] = 'OK';
            if (isset($read[1])) {
                $response = $this->_parseResponseText($read[1]);
                $ob['line'] = $response->text;
            }
            break;

        case '-ERR':
            $errcode = 0;
            if (isset($read[1])) {
                $response = $this->_parseResponseText($read[1]);
                $errtext = $response->text;
                if (isset($response->code)) {
                    switch ($response->code) {
                    // RFC 2449 [8.1.1]
                    case 'IN-USE':
                    // RFC 2449 [8.1.2]
                    case 'LOGIN-DELAY':
                        $errcode = 'LOGIN_UNAVAILABLE';
                        break;

                    // RFC 3206 [4]
                    case 'SYS/TEMP':
                        $errcode = 'POP3_TEMP_ERROR';
                        break;

                    // RFC 3206 [4]
                    case 'SYS/PERM':
                        $errcode = 'POP3_PERM_ERROR';
                        break;

                    // RFC 3206 [5]
                    case 'AUTH':
                        $errcode = 'LOGIN_AUTHENTICATIONFAILED';
                        break;
                    }
                }
            } else {
                $errtext = '[No error message provided by server]';
            }

            $this->_exception(array(
                Horde_Imap_Client_Translation::t("POP3 error reported by server."),
                $errtext
            ), $errcode);

        case '.':
            $ob['response'] = 'END';
            break;

        case '+':
            $ob['response'] = '+';
            break;

        default:
            $ob['line'] = $orig_read;
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
     *
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
     * @param Horde_Imap_Client_Ids $ids  The ID list.
     *
     * @return array  A list of sequence IDs.
     */
    protected function _getSeqIds(Horde_Imap_Client_Ids $ids)
    {
        if (!count($ids)) {
            $status = $this->status($this->_selected, Horde_Imap_Client::STATUS_MESSAGES);
            return range(1, $status['messages']);
        } elseif ($ids->sequence) {
            return $ids->ids;
        }

        return array_keys(array_intersect($this->_pop3Cache('uidl'), $ids->ids));
    }

}

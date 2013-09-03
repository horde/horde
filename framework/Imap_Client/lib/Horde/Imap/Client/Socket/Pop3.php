<?php
/**
 * Copyright 2009-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * ---------------------------------------------------------------------------
 *
 * Based on the PEAR Net_POP3 package (version 1.3.6) by:
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
 * @category  Horde
 * @copyright 2002 Richard Heyes
 * @copyright 2009-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */

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
 *   - RFC 4616: AUTH=PLAIN
 *   - RFC 5034: POP3 SASL
 *
 * @author    Richard Heyes <richard@phpguru.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002 Richard Heyes
 * @copyright 2009-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */
class Horde_Imap_Client_Socket_Pop3 extends Horde_Imap_Client_Base
{
    /**
     * The default ports to use for a connection.
     *
     * @var array
     */
    protected $_defaultPorts = array(110, 995);

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
            $res = $this->_sendLine('CAPA', array(
                'multiline' => 'array'
            ));

            foreach ($res['data'] as $val) {
                $prefix = explode(' ', $val);
                $capability[strtoupper($prefix[0])] = (count($prefix) > 1)
                    ? array_slice($prefix, 1)
                    : true;
            }
        } catch (Horde_Imap_Client_Exception $e) {
            $this->_temp['no_capa'] = true;

            /* Need to probe for capabilities if CAPA command is not
             * available. */
            $capability = array('USER' => true);

            /* Capability sniffing only guaranteed after authentication is
             * completed (if any). */
            if (!empty($this->_init['authmethod'])) {
                $this->_pop3Cache('uidl');
                if (empty($this->_temp['no_uidl'])) {
                    $capability['UIDL'] = true;
                }

                $this->_pop3Cache('top', 1);
                if (empty($this->_temp['no_top'])) {
                    $capability['TOP'] = true;
                }
            }
        }

        $this->_setInit('capability', $capability);
    }

    /**
     */
    protected function _noop()
    {
        $this->_sendLine('NOOP');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _getNamespaces()
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Namespaces');
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

        $secure = $this->getParam('secure');

        // Switch to secure channel if using TLS.
        if (!$this->isSecureConnection() &&
            (($secure === 'tls') || $secure === true)) {
            // Switch over to a TLS connection.
            if (!$this->queryCapability('STLS')) {
                if ($secure === 'tls') {
                    throw new Horde_Imap_Client_Exception(
                        Horde_Imap_Client_Translation::t("Could not open secure connection to the POP3 server.") . ' ' . Horde_Imap_Client_Translation::t("Server does not support secure connections."),
                        Horde_Imap_Client_Exception::LOGIN_TLSFAILURE
                    );
                } else {
                    $this->setParam('secure', false);
                }
            } else {
                $this->_sendLine('STLS');

                $this->setParam('secure', 'tls');

                if (!$this->_connection->startTls()) {
                    $this->logout();
                    throw new Horde_Imap_Client_Exception(
                        Horde_Imap_Client_Translation::t("Could not open secure connection to the POP3 server."),
                        Horde_Imap_Client_Exception::LOGIN_TLSFAILURE
                    );
                }
            }

            // Expire cached CAPABILITY information
            $this->_setInit('capability');
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

                if (!empty($this->_temp['no_capa']) ||
                    !$this->queryCapability('UIDL')) {
                    $this->_capability();
                }

                return true;
            } catch (Horde_Imap_Client_Exception $e) {
                if (!empty($this->_init['authmethod'])) {
                    $this->_setInit();
                    return $this->login();
                }
            }
        }

        throw new Horde_Imap_Client_Exception(
            Horde_Imap_Client_Translation::t("POP3 server denied authentication."),
            $e->getCode() ? $e->getCode() : Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED
        );
    }

    /**
     * Connects to the server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _connect()
    {
        if (!is_null($this->_connection)) {
            return;
        }

        $this->_connection = new Horde_Imap_Client_Socket_Connection_Pop3($this, $this->_debug);

        $line = $this->_getResponse();

        // Check for string matching APOP timestamp
        if (preg_match('/<.+@.+>/U', $line['resp'], $matches)) {
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
        $username = $this->getParam('username');
        $password = $this->getParam('password');

        switch ($method) {
        case 'CRAM-MD5':
        case 'CRAM-SHA1':
        case 'CRAM-SHA256':
            // RFC 5034: CRAM-MD5
            // CRAM-SHA1 & CRAM-SHA256 supported by Courier SASL library
            $challenge = $this->_sendLine('AUTH ' . $method);
            $response = base64_encode($username . ' ' . hash_hmac(strtolower(substr($method, 5)), base64_decode(substr($challenge['resp'], 2)), $password, true));
            $this->_sendLine($response, array(
                'debug' => sprintf('[%s Response - username: %s]', $method, $username)
            ));
            break;

        case 'DIGEST-MD5':
            // RFC 2831; Obsoleted by RFC 6331
            $challenge = $this->_sendLine('AUTH DIGEST-MD5');
            $response = base64_encode(new Horde_Imap_Client_Auth_DigestMD5(
                $username,
                $password,
                base64_decode(substr($challenge['resp'], 2)),
                $this->getParam('hostspec'),
                'pop3'
            ));
            $sresponse = $this->_sendLine($response, array(
                'debug' => sprintf('[%s Response - username: %s]', $method, $username)
            ));
            if (stripos(base64_decode(substr($sresponse['resp'], 2)), 'rspauth=') === false) {
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Unexpected response from server when authenticating."),
                    Horde_Imap_Client_Exception::SERVER_CONNECT
                );
            }

            /* POP3 doesn't use protocol's third step. */
            $this->_sendLine('');
            break;

        case 'LOGIN':
            // RFC 4616 (AUTH=PLAIN) & 5034 (POP3 SASL)
            $this->_sendLine('AUTH LOGIN');
            $this->_sendLine(base64_encode($username), array(
                'debug' => sprintf('[AUTH LOGIN Command - username: %s]', $username)
            ));
            $this->_sendLine(base64_encode($password), array(
                'debug' => '[AUTH LOGIN Command - password]'
            ));
            break;

        case 'PLAIN':
            // RFC 5034
            $this->_sendLine('AUTH PLAIN ' . base64_encode(implode("\0", array(
                $username,
                $username,
                $password
            ))), array(
                'debug' => sprintf('[AUTH PLAIN Command - username: %s]', $username)
            ));
            break;

        case 'APOP':
            // RFC 1939 [7]
            $this->_sendLine('APOP ' . $username . ' ' . hash('md5', $this->_temp['pop3timestamp'] . $password));
            break;

        case 'USER':
            // RFC 1939 [7]
            $this->_sendLine('USER ' . $username);
            $this->_sendLine('PASS ' . $password, array(
                'debug' => '[USER Command - password]'
            ));
            break;

        default:
            throw new Horde_Imap_Client_Exception(
                sprintf(Horde_Imap_Client_Translation::t("Unknown authentication method: %s"), $method),
                Horde_Imap_Client_Exception::SERVER_CONNECT
            );
        }
    }

    /**
     */
    protected function _logout()
    {
        try {
            $this->_sendLine('QUIT');
        } catch (Horde_Imap_Client_Exception $e) {}
        $this->_deleted = array();
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _sendID($info)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('ID command');
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
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _setLanguage($langs)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('LANGUAGE extension');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _getLanguage($list)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('LANGUAGE extension');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _openMailbox(Horde_Imap_Client_Mailbox $mailbox, $mode)
    {
        if (strcasecmp($mailbox, 'INBOX') !== 0) {
            throw new Horde_Imap_Client_Exception_NoSupportPop3('Mailboxes other than INBOX');
        }
        $this->_changeSelected($mailbox, $mode);
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _createMailbox(Horde_Imap_Client_Mailbox $mailbox, $opts)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Creating mailboxes');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _deleteMailbox(Horde_Imap_Client_Mailbox $mailbox)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Deleting mailboxes');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _renameMailbox(Horde_Imap_Client_Mailbox $old,
                                      Horde_Imap_Client_Mailbox $new)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Renaming mailboxes');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _subscribeMailbox(Horde_Imap_Client_Mailbox $mailbox,
                                         $subscribe)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Mailboxes other than INBOX');
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
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _status($mboxes, $flags)
    {
        if ((count($mboxes) > 1) ||
            (strcasecmp(reset($mboxes), 'INBOX') !== 0)) {
            throw new Horde_Imap_Client_Exception_NoSupportPop3('Mailboxes other than INBOX');
        }

        $this->openMailbox('INBOX');

        $ret = array();

        if ($flags & Horde_Imap_Client::STATUS_MESSAGES) {
            $res = $this->_pop3Cache('stat');
            $ret['messages'] = $res['msgs'];
        }

        if ($flags & Horde_Imap_Client::STATUS_RECENT) {
            $res = $this->_pop3Cache('stat');
            $ret['recent'] = $res['msgs'];
        }

        // No need for STATUS_UIDNEXT_FORCE handling since STATUS_UIDNEXT will
        // always return a value.
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

        return array('INBOX' => $ret);
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _append(Horde_Imap_Client_Mailbox $mailbox, $data,
                               $options)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Appending messages');
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
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _search($query, $options)
    {
        $sort = empty($options['sort'])
            ? null
            : reset($options['sort']);

        // Only support a single query: an ALL search sorted by sequence.
        if ((strval($options['_query']['query']) != 'ALL') ||
            ($sort &&
             ((count($options['sort']) > 1) ||
              ($sort != Horde_Imap_Client::SORT_SEQUENCE)))) {
            throw new Horde_Imap_Client_Exception_NoSupportPop3('Server search');
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
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _setComparator($comparator)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Search comparators');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _getComparator()
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Search comparators');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _thread($options)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Server threading');
    }

    /**
     */
    protected function _fetch(Horde_Imap_Client_Fetch_Results $results,
                              $queries)
    {
        foreach ($queries as $options) {
            $this->_fetchCmd($results, $options);
        }

        $this->_updateCache($results);
    }

     /**
     * Fetch data for a given fetch query.
     *
     * @param Horde_Imap_Client_Fetch_Results $results  Fetch results.
     * @param array $options                            Fetch query options.
     */
    protected function _fetchCmd(Horde_Imap_Client_Fetch_Results $results,
                                 $options)
    {
        // Grab sequence IDs - IDs will always be the message number for
        // POP3 fetch commands.
        $seq_ids = $this->_getSeqIds($options['ids']);
        if (empty($seq_ids)) {
            return;
        }

        $lookup = $options['ids']->sequence
            ? array_combine($seq_ids, $seq_ids)
            : $this->_pop3Cache('uidl');

        foreach ($options['_query'] as $type => $c_val) {
            switch ($type) {
            case Horde_Imap_Client::FETCH_FULLMSG:
                foreach ($seq_ids as $id) {
                    $tmp = $this->_pop3Cache('msg', $id);

                    if (empty($c_val['start']) && empty($c_val['length'])) {
                        $tmp2 = fopen('php://temp', 'r+');
                        stream_copy_to_stream($tmp, $tmp2, empty($c_val['length']) ? -1 : $c_val['length'], empty($c_val['start']) ? 0 : $c_val['start']);
                        $results->get($lookup[$id])->setFullMsg($tmp2);
                    } else {
                        $results->get($lookup[$id])->setFullMsg($tmp);
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
                            $results->get($lookup[$id])->setHeaderText($key, $this->_processString($tmp, $c_val));
                        } catch (Horde_Mime_Exception $e) {}
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_BODYTEXT:
                // Ignore 'peek' option
                foreach ($c_val as $key => $val) {
                    foreach ($seq_ids as $id) {
                        try {
                            $results->get($lookup[$id])->setBodyText($key, $this->_processString(Horde_Mime_Part::getRawPartText(stream_get_contents($this->_pop3Cache('msg', $id)), 'body', $key), $val));
                        } catch (Horde_Mime_Exception $e) {}
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_MIMEHEADER:
                // Ignore 'peek' option
                foreach ($c_val as $key => $val) {
                    foreach ($seq_ids as $id) {
                        try {
                            $results->get($lookup[$id])->setMimeHeader($key, $this->_processString(Horde_Mime_Part::getRawPartText(stream_get_contents($this->_pop3Cache('msg', $id)), 'header', $key), $val));
                        } catch (Horde_Mime_Exception $e) {}
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_BODYPART:
                // Ignore 'decode', 'peek'
                foreach ($c_val as $key => $val) {
                    foreach ($seq_ids as $id) {
                        try {
                            $results->get($lookup[$id])->setBodyPart($key, $this->_processString(Horde_Mime_Part::getRawPartText(stream_get_contents($this->_pop3Cache('msg', $id)), 'body', $key), $val));
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

                        $results->get($lookup[$id])->setHeaders($key, $tmp);
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_STRUCTURE:
                foreach ($seq_ids as $id) {
                    if ($ptr = $this->_pop3Cache('msg', $id)) {
                        try {
                            $results->get($lookup[$id])->setStructure(Horde_Mime_Part::parseMessage(stream_get_contents($ptr), array('no_body' => true)));
                        } catch (Horde_Exception $e) {}
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_ENVELOPE:
                foreach ($seq_ids as $id) {
                    $tmp = $this->_pop3Cache('hdrob', $id);
                    $results->get($lookup[$id])->setEnvelope(array(
                        'date' => $tmp->getValue('date'),
                        'subject' => $tmp->getValue('subject'),
                        'from' => $tmp->getOb('from'),
                        'sender' => $tmp->getOb('sender'),
                        'reply_to' => $tmp->getOb('reply-to'),
                        'to' => $tmp->getOb('to'),
                        'cc' => $tmp->getOb('cc'),
                        'bcc' => $tmp->getOb('bcc'),
                        'in_reply_to' => $tmp->getValue('in-reply-to'),
                        'message_id' => $tmp->getValue('message-id')
                    ));
                }
                break;

            case Horde_Imap_Client::FETCH_IMAPDATE:
                foreach ($seq_ids as $id) {
                    $tmp = $this->_pop3Cache('hdrob', $id);
                    $results->get($lookup[$id])->setImapDate($tmp->getValue('date'));
                }
                break;

            case Horde_Imap_Client::FETCH_SIZE:
                $sizelist = $this->_pop3Cache('size');
                foreach ($seq_ids as $id) {
                    $results->get($lookup[$id])->setSize($sizelist[$id]);
                }
                break;

            case Horde_Imap_Client::FETCH_SEQ:
                foreach ($seq_ids as $id) {
                    $results->get($lookup[$id])->setSeq($id);
                }
                break;

            case Horde_Imap_Client::FETCH_UID:
                $uidllist = $this->_pop3Cache('uidl');
                foreach ($seq_ids as $id) {
                    if (isset($uidllist[$id])) {
                        $results->get($lookup[$id])->setUid($uidllist[$id]);
                    }
                }
                break;
            }
        }
    }

    /**
     * Retrieve locally cached message data.
     *
     * @param string $type    Either 'hdr', 'hdrob', 'msg', 'size', 'stat',
     *                        'top', or 'uidl'.
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
        case 'top':
            $data = null;
            if ($this->queryCapability('TOP') || ($type == 'top')) {
                try {
                    $res = $this->_sendLine('TOP ' . $index . ' 0', array(
                        'multiline' => 'stream'
                    ));
                    rewind($res['data']);
                    $data = stream_get_contents($res['data']);
                    fclose($res['data']);
                } catch (Horde_Imap_Client_Exception $e) {
                    $this->_temp['no_top'] = true;
                    if ($type == 'top') {
                        return null;
                    }
                }
            }

            if (is_null($data)) {
                $data = Horde_Mime_Part::getRawPartText(stream_get_contents($this->_pop3Cache('msg', $index)), 'header', 0);
            }
            break;

        case 'hdrob':
            $data = Horde_Mime_Headers::parseHeaders($this->_pop3Cache('hdr', $index));
            break;

        case 'msg':
            $res = $this->_sendLine('RETR ' . $index, array(
                'multiline' => 'stream'
            ));
            $data = $res['data'];
            rewind($data);
            break;

        case 'size':
        case 'uidl':
            $data = array();
            try {
                $res = $this->_sendLine(($type == 'size') ? 'LIST' : 'UIDL', array(
                    'multiline' => 'array'
                ));
                foreach ($res['data'] as $val) {
                    $resp_data = explode(' ', $val, 2);
                    $data[$resp_data[0]] = $resp_data[1];
                }
            } catch (Horde_Imap_Client_Exception $e) {
                if ($type == 'uidl') {
                    $this->_temp['no_uidl'] = true;
                }
            }
            break;

        case 'stat':
            $resp = $this->_sendLine('STAT');
            $resp_data = explode(' ', $resp['resp'], 2);
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
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _vanished($modseq, Horde_Imap_Client_Ids $ids)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('QRESYNC commands');
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
                    $this->_deleted[] = $id;
                } catch (Horde_Imap_Client_Exception $e) {}
            }
        }

        return $this->getIdsOb();
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _copy(Horde_Imap_Client_Mailbox $dest, $options)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Copying messages');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _setQuota(Horde_Imap_Client_Mailbox $root, $options)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Quotas');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _getQuota(Horde_Imap_Client_Mailbox $root)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Quotas');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _getQuotaRoot(Horde_Imap_Client_Mailbox $mailbox)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Quotas');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _setACL(Horde_Imap_Client_Mailbox $mailbox, $identifier,
                               $options)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('ACLs');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _deleteACL(Horde_Imap_Client_Mailbox $mailbox, $identifier)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('ACLs');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _getACL(Horde_Imap_Client_Mailbox $mailbox)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('ACLs');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _listACLRights(Horde_Imap_Client_Mailbox $mailbox,
                                      $identifier)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('ACLs');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _getMyACLRights(Horde_Imap_Client_Mailbox $mailbox)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('ACLs');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _getMetadata(Horde_Imap_Client_Mailbox $mailbox,
                                    $entries, $options)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Metadata');
    }

    /**
     * @throws Horde_Imap_Client_Exception_NoSupportPop3
     */
    protected function _setMetadata(Horde_Imap_Client_Mailbox $mailbox, $data)
    {
        throw new Horde_Imap_Client_Exception_NoSupportPop3('Metadata');
    }

    /**
     */
    protected function _getSearchCache($type, $options)
    {
        /* POP3 does not support search caching. */
        return null;
    }

    /**
     */
    public function resolveIds(Horde_Imap_Client_Mailbox $mailbox,
                               Horde_Imap_Client_Ids $ids, $convert = 0)
    {
        if (!$ids->special &&
            (!$convert ||
             (!$ids->sequence && ($convert == 1)) ||
             $ids->isEmpty())) {
            return clone $ids;
        }

        $uids = $this->_pop3Cache('uidl');

        return $this->getIdsOb(
            $ids->all ? array_values($uids) : array_intersect_keys($uids, $ids->ids)
        );
    }

    /* Internal functions. */

    /**
     * Perform a command on the server. A connection to the server must have
     * already been made.
     *
     * @param string $cmd     The command to execute.
     * @param array $options  Additional options:
     * <pre>
     *   - debug: (string) When debugging, send this string instead of the
     *            actual command/data sent.
     *            DEFAULT: Raw data output to debug stream.
     *   - multiline: (mixed) 'array', 'none', or 'stream'.
     * </pre>
     *
     * @return array  See _getResponse().
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _sendLine($cmd, $options = array())
    {
        $old_debug = $this->_debug->debug;
        if (!empty($options['debug'])) {
            $this->_debug->raw($options['debug'] . "\n");
            $this->_debug->debug = false;
        }

        try {
            $this->_connection->write($cmd);
        } catch (Horde_Imap_Client_Exception $e) {
            $this->_debug->debug = $old_debug;
            throw $e;
        }

        $this->_debug->debug = $old_debug;

        return $this->_getResponse(
            empty($options['multiline']) ? false : $options['multiline']
        );
    }

    /**
     * Gets a line from the stream and parses it.
     *
     * @param mixed $multiline  'array', 'none', 'stream', or null.
     *
     * @return array  An array with the following keys:
     *   - data: (mixed) Stream, array, or null.
     *   - resp: (string) The server response text.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getResponse($multiline = false)
    {
        $ob = array('resp' => '');

        $read = explode(' ', rtrim($this->_connection->read(), "\r\n"), 2);
        if (!in_array($read[0], array('+OK', '-ERR', '+'))) {
            $this->_debug->info("ERROR: IMAP read/timeout error.");
            throw new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Error when communicating with the mail server."),
                Horde_Imap_Client_Exception::SERVER_READERROR
            );
        }

        $respcode = null;
        if (isset($read[1]) &&
            isset($this->_init['capability']) &&
            $this->queryCapability('RESP-CODES')) {
            $respcode = $this->_parseResponseCode($read[1]);
        }

        switch ($read[0]) {
        case '+OK':
        case '+':
            if ($respcode) {
                $ob['resp'] = $respcode->text;
            } elseif (isset($read[1])) {
                $ob['resp'] = $read[1];
            }
            break;

        case '-ERR':
            $errcode = 0;
            if ($respcode) {
                $errtext = $respcode->text;

                if (isset($respcode->code)) {
                    switch ($respcode->code) {
                    // RFC 2449 [8.1.1]
                    case 'IN-USE':
                    // RFC 2449 [8.1.2]
                    case 'LOGIN-DELAY':
                        $errcode = Horde_Imap_Client_Exception::LOGIN_UNAVAILABLE;
                        break;

                    // RFC 3206 [4]
                    case 'SYS/TEMP':
                        $errcode = Horde_Imap_Client_Exception::POP3_TEMP_ERROR;
                        break;

                    // RFC 3206 [4]
                    case 'SYS/PERM':
                        $errcode = Horde_Imap_Client_Exception::POP3_PERM_ERROR;
                        break;

                    // RFC 3206 [5]
                    case 'AUTH':
                        $errcode = Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED;
                        break;
                    }
                }
            } elseif (isset($read[1])) {
                $errtext = $read[1];
            } else {
                $errtext = '[No error message provided by server]';
            }

            $e = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("POP3 error reported by server."),
                $errcode
            );
            $e->details = $errtext;
            throw $e;
        }

        switch ($multiline) {
        case 'array':
            $ob['data'] = array();
            break;

        case 'none':
            $ob['data'] = null;
            break;

        case 'stream':
            $ob['data'] = fopen('php://temp', 'r+');
            break;

        default:
            return $ob;
        }

        do {
            $orig_read = $this->_connection->read();
            $read = rtrim($orig_read, "\r\n");

            if ($read == '.') {
                break;
            } elseif (substr($read, 0, 2) == '..') {
                $read = substr($read, 1);
            }

            if (is_array($ob['data'])) {
                $ob['data'][] = $read;
            } elseif (!is_null($ob['data'])) {
                fwrite($ob['data'], $orig_read);
            }
        } while (true);

        return $ob;
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

    /**
     * Parses response text for response codes (RFC 2449 [8]).
     *
     * @param string $text  The response text.
     *
     * @return object  An object with the following properties:
     *   - code: (string) The response code, if it exists.
     *   - data: (string) The response code data, if it exists.
     *   - text: (string) The human-readable response text.
     */
    protected function _parseResponseCode($text)
    {
        $ret = new stdClass;

        $text = trim($text);
        if ($text[0] == '[') {
            $pos = strpos($text, ' ', 2);
            $end_pos = strpos($text, ']', 2);
            if ($pos > $end_pos) {
                $ret->code = strtoupper(substr($text, 1, $end_pos - 1));
            } else {
                $ret->code = strtoupper(substr($text, 1, $pos - 1));
                $ret->data = substr($text, $pos + 1, $end_pos - $pos - 1);
            }
            $ret->text = trim(substr($text, $end_pos + 1));
        } else {
            $ret->text = $text;
        }

        return $ret;
    }

}

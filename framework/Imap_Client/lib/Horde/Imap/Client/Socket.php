<?php
/**
 * Horde_Imap_Client_Socket:: provides an interface to an IMAP4rev1 server
 * (RFC 3501) using PHP functions.
 *
 * This driver implements the following IMAP-related RFCs:
 * <pre>
 *   RFC 2086/4314 - ACL
 *   RFC 2087 - QUOTA
 *   RFC 2088 - LITERAL+
 *   RFC 2195 - AUTH=CRAM-MD5
 *   RFC 2221 - LOGIN-REFERRALS
 *   RFC 2342 - NAMESPACE
 *   RFC 2595/4616 - TLS & AUTH=PLAIN
 *   RFC 2831 - DIGEST-MD5 authentication mechanism.
 *   RFC 2971 - ID
 *   RFC 3348 - CHILDREN
 *   RFC 3501 - IMAP4rev1 specification
 *   RFC 3502 - MULTIAPPEND
 *   RFC 3516 - BINARY
 *   RFC 3691 - UNSELECT
 *   RFC 4315 - UIDPLUS
 *   RFC 4422 - SASL Authentication (for DIGEST-MD5)
 *   RFC 4466 - Collected extensions (updates RFCs 2088, 3501, 3502, 3516)
 *   RFC 4469/5550 - CATENATE
 *   RFC 4551 - CONDSTORE
 *   RFC 4731 - ESEARCH
 *   RFC 4959 - SASL-IR
 *   RFC 5032 - WITHIN
 *   RFC 5161 - ENABLE
 *   RFC 5162 - QRESYNC
 *   RFC 5182 - SEARCHRES
 *   RFC 5255 - LANGUAGE/I18NLEVEL
 *   RFC 5256 - THREAD/SORT
 *   RFC 5258 - LIST-EXTENDED
 *   RFC 5267 - ESORT
 *   RFC 5464 - METADATA
 *   RFC 5530 - IMAP Response Codes
 *   RFC 5819 - LIST-STATUS
 *
 *   draft-ietf-morg-list-specialuse-02  CREATE-SPECIAL-USE
 *   draft-ietf-morg-sortdisplay-02      SORT=DISPLAY
 *   draft-ietf-morg-inthread-00         THREAD=REFS
 *
 *   [NO RFC] - XIMAPPROXY
 *       + Requires imapproxy v1.2.7-rc1 or later
 *       + See http://lists.andrew.cmu.edu/pipermail/imapproxy-info/2008-October/000771.html and
 *         http://lists.andrew.cmu.edu/pipermail/imapproxy-info/2008-October/000772.html
 *
 * TODO (or not necessary?):
 *   RFC 2177 - IDLE (probably not necessary due to the limited connection
 *                    time by each HTTP/PHP request)
 *   RFC 2193 - MAILBOX-REFERRALS
 *   RFC 4467/5092/5524/5550 - URLAUTH, URLFETCH=BINARY, URL-PARTIAL
 *   RFC 4978 - COMPRESS=DEFLATE
 *              See: http://bugs.php.net/bug.php?id=48725
 *   RFC 5257 - ANNOTATE (Experimental)
 *   RFC 5259 - CONVERT
 *   RFC 5267 - CONTEXT
 *   RFC 5465 - NOTIFY
 *   RFC 5466 - FILTERS
 *   RFC 5738 - UTF8
 *
 *   draft-ietf-morg-inthread-00 - SEARCH=INTHREAD
 *
 *   [NO RFC] - XLIST
 *       + See http://markmail.org/message/vxbqgt5omnph3hnt
 *
 * [See: http://www.iana.org/assignments/imap4-capabilities]
 * </pre>
 *
 * Originally based on code from:
 *   + auth.php (1.49)
 *   + imap_general.php (1.212)
 *   + imap_messages.php (revision 13038)
 *   + strings.php (1.184.2.35)
 *   from the Squirrelmail project.
 *   Copyright (c) 1999-2007 The SquirrelMail Project Team
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 */
class Horde_Imap_Client_Socket extends Horde_Imap_Client_Base
{
    /**
     * The unique tag to use when making an IMAP query.
     *
     * @var integer
     */
    protected $_tag = 0;

    /**
     * The socket connection to the IMAP server.
     *
     * @var resource
     */
    protected $_stream = null;

    /**
     * @param array $params  A hash containing configuration parameters.
     *                       Additional parameters:
     * <pre>
     * debug_literal - (boolean) If true, will output the raw text of literal
     *                 responses to the debug stream. Otherwise, outputs a
     *                 summary of the literal response.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        $params = array_merge(array(
            'debug_literal' => false
        ), $params);

        parent::__construct($params);
    }

    /**
     */
    protected function _capability()
    {
        // Need to use connect call here or else we run into loop issues
        // because _connect() can call capability() internally.
        $this->_connect();

        // It is possible the server provided capability information on
        // connect, so check for it now.
        if (!isset($this->_init['capability'])) {
            $this->_sendLine('CAPABILITY');
        }

        return $this->_init['capability'];
    }

    /**
     * Parse a CAPABILITY Response (RFC 3501 [7.2.1]).
     *
     * @param array $data  The CAPABILITY data.
     */
    protected function _parseCapability($data)
    {
        if (!empty($this->_temp['no_cap'])) {
            return;
        }

        if (empty($this->_temp['in_login'])) {
            $c = array();
        } else {
            $c = $this->_init['capability'];
            $this->_temp['logincapset'] = true;
        }

        foreach ($data as $val) {
            $cap_list = explode('=', $val);
            $cap_list[0] = strtoupper($cap_list[0]);
            if (isset($cap_list[1])) {
                if (!isset($c[$cap_list[0]]) || !is_array($c[$cap_list[0]])) {
                    $c[$cap_list[0]] = array();
                }
                $c[$cap_list[0]][] = $cap_list[1];
            } elseif (!isset($c[$cap_list[0]])) {
                $c[$cap_list[0]] = true;
            }
        }

        /* RFC 5162 [1] - QRESYNC implies CONDSTORE, even if CONDSTORE is not
         * listed as a capability. */
        if (isset($c['QRESYNC'])) {
            $c['CONDSTORE'] = true;
        }

        $this->_setInit('capability', $c);
    }

    /**
     */
    protected function _noop()
    {
        // NOOP doesn't return any specific response
        $this->_sendLine('NOOP');
    }

    /**
     */
    protected function _getNamespaces()
    {
        $this->login();

        if ($this->queryCapability('NAMESPACE')) {
            $this->_sendLine('NAMESPACE');
            return $this->_temp['namespace'];
        }

        return array();
    }

    /**
     * Parse a NAMESPACE response (RFC 2342 [5] & RFC 5255 [3.4]).
     *
     * @param array $data  The NAMESPACE data.
     */
    protected function _parseNamespace($data)
    {
        $namespace_array = array(
            0 => 'personal',
            1 => 'other',
            2 => 'shared'
        );

        $c = &$this->_temp['namespace'];
        $c = array();
        $lang = $this->queryCapability('LANGUAGE');

        // Per RFC 2342, response from NAMESPACE command is:
        // (PERSONAL NAMESPACES) (OTHER_USERS NAMESPACE) (SHARED NAMESPACES)
        foreach ($namespace_array as $i => $val) {
            if (!is_array($data[$i]) && (strtoupper($data[$i]) == 'NIL')) {
                continue;
            }
            reset($data[$i]);
            while (list(,$v) = each($data[$i])) {
                $c[$v[0]] = array(
                    'name' => $v[0],
                    'delimiter' => $v[1],
                    'type' => $val,
                    'hidden' => false
                );
                // RFC 5255 [3.4] - TRANSLATION extension
                if ($lang && (strtoupper($v[2] == 'TRANSLATION'))) {
                    $c[$v[0]]['translation'] = reset($v[3]);
                }
            }
        }
    }

    /**
     */
    public function alerts()
    {
        $alerts = empty($this->_temp['alerts'])
            ? array()
            : $this->_temp['alerts'];
        $this->_temp['alerts'] = array();
        return $alerts;
    }

    /**
     */
    protected function _login()
    {
        if (!empty($this->_temp['preauth'])) {
            return $this->_loginTasks();
        }

        $this->_connect();

        $first_login = empty($this->_init['authmethod']);
        $t = &$this->_temp;

        // Switch to secure channel if using TLS.
        if (!$this->_isSecure &&
            ($this->_params['secure'] == 'tls')) {
            if ($first_login && !$this->queryCapability('STARTTLS')) {
                // We should never hit this - STARTTLS is required pursuant
                // to RFC 3501 [6.2.1].
                $this->_exception('Server does not support TLS connections.', 'NOSUPPORTIMAPEXT');
            }

            // Switch over to a TLS connection.
            // STARTTLS returns no untagged response.
            $this->_sendLine('STARTTLS');

            $res = @stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            if (!$res) {
                $this->logout();
                $this->_exception('Could not open secure TLS connection to the IMAP server.', 'LOGIN_TLSFAILURE');
            }

            if ($first_login) {
                // Expire cached CAPABILITY information (RFC 3501 [6.2.1])
                $this->_setInit('capability');

                // Reset language (RFC 5255 [3.1])
                $this->_setInit('lang');
            }

            // Set language if not using imapproxy
            if ($this->_init['imapproxy']) {
                $this->setLanguage();
            }

            $this->_isSecure = true;
        }

        if ($first_login) {
            $imap_auth_mech = array();

            $auth_methods = $this->queryCapability('AUTH');
            if (!empty($auth_methods)) {
                // Add SASL methods.
                $imap_auth_mech = array_intersect(array('DIGEST-MD5', 'CRAM-MD5'), $auth_methods);

                // Next, try 'PLAIN' authentication.
                if (in_array('PLAIN', $auth_methods)) {
                    $imap_auth_mech[] = 'PLAIN';
                }
            }

            // Fall back to 'LOGIN' if available.
            if (!$this->queryCapability('LOGINDISABLED')) {
                $imap_auth_mech[] = 'LOGIN';
            }

            if (empty($imap_auth_mech)) {
                $this->_exception('No supported IMAP authentication method could be found.', 'LOGIN_NOAUTHMETHOD');
            }

            /* Use MD5 authentication first, if available. But no need to use
             * special authentication if we are already using an encrypted
             * connection. */
            if ($this->_isSecure) {
                $imap_auth_mech = array_reverse($imap_auth_mech);
            }
        } else {
            $imap_auth_mech = array($this->_init['authmethod']);
        }

        /* Default to AUTHENTICATIONFAILED error (see RFC 5530[3]). */
        $t['loginerr'] = 'LOGIN_AUTHENTICATIONFAILED';

        foreach ($imap_auth_mech as $method) {
            $t['referral'] = null;

            /* Set a flag indicating whether we have received a CAPABILITY
             * response after we successfully login. Since capabilities may
             * be different after login, we need to merge this information into
             * the current CAPABILITY array (since some servers, e.g. Cyrus,
             * may not include authentication capabilities that are still
             * needed in the event this object is eventually serialized). */
            $this->_temp['in_login'] = true;

            try {
                $this->_tryLogin($method);
                $success = true;
                $this->_setInit('authmethod', $method);
                unset($t['referralcount']);
            } catch (Horde_Imap_Client_Exception $e) {
                $success = false;
                if (!empty($this->_init['authmethod'])) {
                    $this->_setInit('authmethod');
                    return $this->login();
                }
            }

            unset($this->_temp['in_login']);

            // Check for login referral (RFC 2221) response - can happen for
            // an OK, NO, or BYE response.
            if (!is_null($t['referral'])) {
                foreach (array('hostspec', 'port', 'username') as $val) {
                    if (isset($t['referral'][$val])) {
                        $this->_params[$val] = $t['referral'][$val];
                    }
                }

                if (isset($t['referral']['auth'])) {
                    $this->_setInit('authmethod', $t['referral']['auth']);
                }

                if (!isset($t['referralcount'])) {
                    $t['referralcount'] = 0;
                }

                // RFC 2221 [3] - Don't follow more than 10 levels of referral
                // without consulting the user.
                if (++$t['referralcount'] < 10) {
                    $this->logout();
                    $this->_setInit('capability');
                    $this->_setInit('namespace', array());
                    return $this->login();
                }

                unset($t['referralcount']);
            }

            if ($success) {
                return $this->_loginTasks($first_login);
            }
        }

        $this->_exception('IMAP server denied authentication.', $t['loginerr']);
    }

    /**
     * Connects to the IMAP server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _connect()
    {
        if (!is_null($this->_stream)) {
            return;
        }

        if (!empty($this->_params['secure']) && !extension_loaded('openssl')) {
            $this->_exception('Secure connections require the PHP openssl extension.', 'SERVER_CONNECT');
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
            $this->_exception('Error connecting to IMAP server: [' . $error_number . '] ' . $error_string, 'SERVER_CONNECT');
        }

        stream_set_timeout($this->_stream, $this->_params['timeout']);

        // If we already have capability information, don't re-set with
        // (possibly) limited information sent in the inital banner.
        if (isset($this->_init['capability'])) {
            $this->_temp['no_cap'] = true;
        }

        // Add separator to make it easier to read debug log.
        if ($this->_debug) {
            fwrite($this->_debug, str_repeat('-', 30) . "\n");
        }

        // Get greeting information.  This is untagged so we need to specially
        // deal with it here.  A BYE response will be caught and thrown in
        // _getLine().
        $ob = $this->_getLine();
        switch ($ob['response']) {
        case 'BAD':
            // Server is rejecting our connection.
            $this->_exception('Server rejected connection: ' . $ob['line'], 'SERVER_CONNECT');

        case 'PREAUTH':
            // The user was pre-authenticated.
            $this->_temp['preauth'] = true;
            break;

        default:
            $this->_temp['preauth'] = false;
            break;
        }
        $this->_parseServerResponse($ob);

        // Check for IMAP4rev1 support
        if (!$this->queryCapability('IMAP4REV1')) {
            $this->_exception('This server does not support IMAP4rev1 (RFC 3501).', 'SERVER_CONNECT');
        }

        // Set language if not using imapproxy
        if (empty($this->_init['imapproxy'])) {
            $this->_setInit('imapproxy', $this->queryCapability('XIMAPPROXY'));
            if (!$this->_init['imapproxy']) {
                $this->setLanguage();
            }
        }

        // If pre-authenticated, we need to do all login tasks now.
        if ($this->_temp['preauth']) {
            $this->login();
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
        case 'DIGEST-MD5':
            $ob = $this->_sendLine(array(
                'AUTHENTICATE',
                array('t' => Horde_Imap_Client::DATA_ATOM, 'v' => $method)
            ), array(
                'noparse' => true
            ));

            switch ($method) {
            case 'CRAM-MD5':
                // RFC 2195
                if (!class_exists('Auth_SASL')) {
                    $this->_exception('The Auth_SASL package is required for CRAM-MD5 authentication');
                }
                $auth_sasl = Auth_SASL::factory('crammd5');
                $response = base64_encode($auth_sasl->getResponse($this->_params['username'], $this->getParam('password'), base64_decode($ob['line'])));
                $this->_sendLine($response, array(
                    'debug' => '[CRAM-MD5 Response]',
                    'notag' => true
                ));
                break;

            case 'DIGEST-MD5':
                if (!class_exists('Auth_SASL')) {
                    $this->_exception('The Auth_SASL package is required for DIGEST-MD5 authentication');
                }
                $auth_sasl = Auth_SASL::factory('digestmd5');
                $response = base64_encode($auth_sasl->getResponse($this->_params['username'], $this->getParam('password'), base64_decode($ob['line']), $this->_params['hostspec'], 'imap'));
                $ob = $this->_sendLine($response, array(
                    'debug' => '[DIGEST-MD5 Response]',
                    'noparse' => true,
                    'notag' => true
                ));
                $response = base64_decode($ob['line']);
                if (strpos($response, 'rspauth=') === false) {
                    $this->_exception('Unexpected response from server to Digest-MD5 response.');
                }
                $this->_sendLine('', array(
                    'notag' => true
                ));
                break;
            }
            break;

        case 'LOGIN':
            $this->_sendLine(array(
                'LOGIN',
                array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $this->_params['username']),
                array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $this->getParam('password'))
            ), array(
                'debug' => sprintf('[LOGIN Command - username: %s]', $this->_params['username'])
            ));
            break;

        case 'PLAIN':
            // RFC 2595/4616 - PLAIN SASL mechanism
            $auth = base64_encode(implode("\0", array($this->_params['username'], $this->_params['username'], $this->getParam('password'))));
            if ($this->queryCapability('SASL-IR')) {
                // IMAP Extension for SASL Initial Client Response (RFC 4959)
                $this->_sendLine(array(
                    'AUTHENTICATE',
                    'PLAIN',
                    $auth
                ), array(
                    'debug' => sprintf('[SASL-IR AUTHENTICATE Command - username: %s]', $this->_params['username'])
                ));
            } else {
                $this->_sendLine('AUTHENTICATE PLAIN', array(
                    'noparse' => true
                ));
                $this->_sendLine($auth, array(
                    'debug' => sprintf('[AUTHENTICATE Command - username: %s]', $this->_params['username']),
                    'notag' => true
                ));
            }
            break;
        }
    }

    /**
     * Perform login tasks.
     *
     * @param boolean $firstlogin  Is this the first login?
     *
     * @return boolean  True if global login tasks should be performed.
     */
    protected function _loginTasks($firstlogin = true)
    {
        /* If reusing an imapproxy connection, no need to do any of these
         * login tasks again. */
        if (!$firstlogin && !empty($this->_temp['proxyreuse'])) {
            // If we have not yet set the language, set it now.
            if (!isset($this->_init['lang'])) {
                $this->setLanguage();
            }
            return false;
        }

        $this->_setInit('enabled', array());

        /* If we logged in for first time, and server did not return
         * capability information, we need to grab it now. */
        if ($firstlogin && empty($this->_temp['logincapset'])) {
            $this->_setInit('capability');
        }
        $this->setLanguage();

        /* Only active QRESYNC/CONDSTORE if caching is enabled. */
        if ($this->_initCache()) {
            if ($this->queryCapability('QRESYNC')) {
                /* QRESYNC requires ENABLE, so we just need to send one ENABLE
                 * QRESYNC call to enable both QRESYNC && CONDSTORE. */
                $this->_enable(array('QRESYNC'));
                $this->_setInit('enabled', array_merge($this->_init['enabled'], array('CONDSTORE' => true)));
            } elseif ($this->queryCapability('CONDSTORE') &&
                      $this->queryCapability('ENABLE')) {
                /* CONDSTORE may be available, but ENABLE may not be. */
                $this->_enable(array('CONDSTORE'));
            }
        }

        return true;
    }

    /**
     */
    protected function _logout()
    {
        if (!is_null($this->_stream)) {
            if (empty($this->_temp['logout'])) {
                $this->_temp['logout'] = true;
                $this->_sendLine('LOGOUT', array('errignore' => true));
            }
            unset($this->_temp['logout']);
            @fclose($this->_stream);
            $this->_stream = null;
        }
    }

    /**
     */
    protected function _sendID($info)
    {
        $cmd = array('ID');

        if (empty($info)) {
            $cmd[] = array('t' => Horde_Imap_Client::DATA_NSTRING, null);
        } else {
            $tmp = array();
            foreach ($info as $key => $val) {
                $tmp[] = array('t' => Horde_Imap_Client::DATA_STRING, strtolower($key));
                $tmp[] = array('t' => Horde_Imap_Client::DATA_NSTRING, $val);
            }
            $cmd[] = $tmp;
        }

        $this->_sendLine($cmd);
    }

    /**
     * Parse an ID response (RFC 2971 [3.2])
     *
     * @param array $data  The server response.
     */
    protected function _parseID($data)
    {
        $this->_temp['id'] = array();
        $d = reset($data);
        if (is_array($d)) {
            for ($i = 0, $cnt = count($d); $i < $cnt; $i += 2) {
                if ($d[$i + 1] != 'NIL') {
                    $this->_temp['id'][$d[$i]] = $d[$i + 1];
                }
            }
        }
    }

    /**
     */
    protected function _getID()
    {
        if (!isset($this->_temp['id'])) {
            $this->sendID();
        }
        return $this->_temp['id'];
    }

    /**
     */
    protected function _setLanguage($langs)
    {
        $cmd = array('LANGUAGE');
        foreach ($langs as $lang) {
            $cmd[] = array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $lang);
        }

        try {
            $this->_sendLine($cmd);
        } catch (Horde_Imap_Client_Exception $e) {
            $this->_setInit('lang', false);
            return null;
        }

        return $this->_init['lang'];
    }

    /**
     */
    protected function _getLanguage($list)
    {
        if (!$list) {
            return empty($this->_init['lang'])
                ? null
                : $this->_init['lang'];
        }

        if (!isset($this->_init['langavail'])) {
            try {
                $this->_sendLine('LANGUAGE');
            } catch (Horde_Imap_Client_Exception $e) {
                $this->_setInit('langavail', array());
            }
        }

        return $this->_init['langavail'];
    }

    /**
     * Parse a LANGUAGE response (RFC 5255 [3.3])
     *
     * @param array $data  The server response.
     */
    protected function _parseLanguage($data)
    {
        // Store data in $_params because it mustbe saved across page accesses
        if (count($data[0]) == 1) {
            // This is the language that was set.
            $this->_setInit('lang', reset($data[0]));
        } else {
            // These are the languages that are available.
            $this->_setInit('langavail', $data[0]);
        }
    }

    /**
     * Enable an IMAP extension (see RFC 5161).
     *
     * @param array $exts  The extensions to enable.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _enable($exts)
    {
        // Only enable non-enabled extensions
        $exts = array_diff($exts, array_keys($this->_init['enabled']));
        if (!empty($exts)) {
            $this->_sendLine(array_merge(array('ENABLE'), $exts));
        }
    }

    /**
     * Parse an ENABLED response (RFC 5161 [3.2])
     *
     * @param array $data  The server response.
     */
    protected function _parseEnabled($data)
    {
        $this->_setInit('enabled', array_merge($this->_init['enabled'], array_flip($data)));
    }

    /**
     */
    protected function _openMailbox($mailbox, $mode)
    {
        $this->login();

        $condstore = false;
        $qresync = isset($this->_init['enabled']['QRESYNC']);

        /* Let the 'CLOSE' response code handle mailbox switching if QRESYNC
         * is active. */
        if (empty($this->_temp['mailbox']['name']) ||
            (!$qresync && ($mailbox != $this->_temp['mailbox']['name']))) {
            $this->_temp['mailbox'] = array('name' => $mailbox);
            $this->_selected = $mailbox;
        } elseif ($qresync) {
            $this->_temp['qresyncmbox'] = $mailbox;
        }

        $cmd = array(
            (($mode == Horde_Imap_Client::OPEN_READONLY) ? 'EXAMINE' : 'SELECT'),
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox)
        );

        /* If QRESYNC is available, synchronize the mailbox. */
        if ($qresync) {
            $this->_initCache();
            $metadata = $this->cache->getMetaData($mailbox, null, array('HICmodseq', 'uidvalid'));

            if (isset($metadata['HICmodseq'])) {
                $uids = $this->cache->get($mailbox);
                if (!empty($uids)) {
                    /* This command may cause several things to happen.
                     * 1. UIDVALIDITY may have changed.  If so, we need
                     * to expire the cache immediately (done below).
                     * 2. NOMODSEQ may have been returned.  If so, we also
                     * need to expire the cache immediately (done below).
                     * 3. VANISHED/FETCH information was returned. These
                     * responses will have already been handled by those
                     * response handlers.
                     * TODO: Use 4th parameter (useful if we keep a sequence
                     * number->UID lookup in the future). */
                    $cmd[] = array(
                        'QRESYNC',
                        array(
                            $metadata['uidvalid'],
                            $metadata['HICmodseq'],
                            $this->utils->toSequenceString($uids)
                        )
                    );
                }
            }
        } elseif (!isset($this->_init['enabled']['CONDSTORE']) &&
                  $this->_initCache() &&
                  $this->queryCapability('CONDSTORE')) {
            /* Activate CONDSTORE now if ENABLE is not available. */
            $cmd[] = array('CONDSTORE');
            $condstore = true;
        }

        try {
            $this->_sendLine($cmd);
        } catch (Horde_Imap_Client_Exception $e) {
            // An EXAMINE/SELECT failure with a return of 'NO' will cause the
            // current mailbox to be unselected.
            if (isset($this->_temp['parseresperr']['response']) &&
                ($this->_temp['parseresperr']['response'] == 'NO')) {
                $this->_selected = null;
                $this->_mode = 0;
                $this->_exception($e->getMessage(), 'MAILBOX_NOOPEN');
            }
            throw $e;
        }

        if ($condstore) {
            $this->_setInit('enabled', array_merge($this->_init['enabled'], array('CONDSTORE' => true)));
        }

        /* MODSEQ should be set if CONDSTORE is active. Some servers won't
         * advertise in SELECT/EXAMINE info though. */
        if (isset($this->_init['enabled']['CONDSTORE']) &&
            !isset($this->_temp['mailbox']['highestmodseq'])) {
            $this->_temp['mailbox']['highestmodseq'] = 1;
        }
    }

    /**
     */
    protected function _createMailbox($mailbox, $opts)
    {
        $this->login();

        $cmd = array(
            'CREATE',
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox)
        );

        if (isset($opts['special_use'])) {
            $cmd[] = 'USE';

            $flags = array();
            foreach ($opts['special_use'] as $val) {
                $flags[] = array('t' => Horde_Imap_Client::DATA_ATOM, 'v' => $val);
            }
            $cmd[] = $flags;
        }

        // CREATE returns no untagged information (RFC 3501 [6.3.3])
        $this->_sendLine($cmd);
    }

    /**
     */
    protected function _deleteMailbox($mailbox)
    {
        $this->login();

        // Some IMAP servers will not allow a delete of a currently open
        // mailbox.
        if ($this->_selected == $mailbox) {
            $this->close();
        }

        try {
            // DELETE returns no untagged information (RFC 3501 [6.3.4])
            $this->_sendLine(array(
                'DELETE',
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox)
            ));
        } catch (Horde_Imap_Client_Exception $e) {
            // Some IMAP servers won't allow a mailbox delete unless all
            // messages in that mailbox are deleted.
            if (!empty($this->_temp['deleteretry'])) {
                unset($this->_temp['deleteretry']);
                throw $e;
            }

            $this->store($mailbox, array('add' => array('\\deleted')));
            $this->expunge($mailbox);

            $this->_temp['deleteretry'] = true;
            $this->deleteMailbox($mailbox);
        }

        unset($this->_temp['deleteretry']);
    }

    /**
     */
    protected function _renameMailbox($old, $new)
    {
        $this->login();

        // RENAME returns no untagged information (RFC 3501 [6.3.5])
        $this->_sendLine(array(
            'RENAME',
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $old),
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $new)
        ));
    }

    /**
     */
    protected function _subscribeMailbox($mailbox, $subscribe)
    {
        $this->login();

        // SUBSCRIBE/UNSUBSCRIBE returns no untagged information (RFC 3501
        // [6.3.6 & 6.3.7])
        $this->_sendLine(array(
            ($subscribe ? 'SUBSCRIBE' : 'UNSUBSCRIBE'),
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox)
        ));
    }

    /**
     */
    protected function _listMailboxes($pattern, $mode, $options)
    {
        $this->login();

        // Get the list of subscribed/unsubscribed mailboxes. Since LSUB is
        // not guaranteed to have correct attributes, we must use LIST to
        // ensure we receive the correct information.
        // TODO: Use LSUB for MBOX_SUBSCRIBED if no other options are
        // set (RFC 5258 3.1)
        if (($mode != Horde_Imap_Client::MBOX_ALL) &&
            !$this->queryCapability('LIST-EXTENDED')) {
            $subscribed = $this->_getMailboxList($pattern, Horde_Imap_Client::MBOX_SUBSCRIBED, array('flat' => true));

            // If mode is subscribed, and 'flat' option is true, we can
            // return now.
            if (($mode == Horde_Imap_Client::MBOX_SUBSCRIBED) &&
                !empty($options['flat'])) {
                return $subscribed;
            }
        } else {
            $subscribed = null;
        }

        return $this->_getMailboxList($pattern, $mode, $options, $subscribed);
    }

    /**
     * Obtain a list of mailboxes.
     *
     * @param mixed $pattern     The mailbox search pattern(s).
     * @param integer $mode      Which mailboxes to return.
     * @param array $options     Additional options.
     * @param array $subscribed  A list of subscribed mailboxes.
     *
     * @return array  See self::listMailboxes(().
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getMailboxList($pattern, $mode, $options,
                                       $subscribed = null)
    {
        $check = (($mode != Horde_Imap_Client::MBOX_ALL) && !is_null($subscribed));

        // Setup cache entry for use in _parseList()
        $t = &$this->_temp;
        $t['mailboxlist'] = array(
            'check' => $check,
            'ext' => false,
            'options' => $options,
            'subexist' => ($mode == Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS),
            'subscribed' => ($check ? array_flip($subscribed) : null)
        );
        $t['listresponse'] = array();

        if ($this->queryCapability('LIST-EXTENDED')) {
            $cmd = array('LIST');
            $t['mailboxlist']['ext'] = true;

            $return_opts = $select_opts = array();

            if (($mode == Horde_Imap_Client::MBOX_SUBSCRIBED) ||
                ($mode == Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS)) {
                $select_opts[] = 'SUBSCRIBED';
                $return_opts[] = 'SUBSCRIBED';
            }

            if (!empty($options['remote'])) {
                $select_opts[] = 'REMOTE';
            }

            if (!empty($options['recursivematch'])) {
                $select_opts[] = 'RECURSIVEMATCH';
            }

            if (!empty($select_opts)) {
                $cmd[] = $select_opts;
            }

            $cmd[] = '""';

            if (!is_array($pattern)) {
                $pattern = array($pattern);
            }
            $tmp = array();
            foreach ($pattern as $val) {
                $tmp[] = array('t' => Horde_Imap_Client::DATA_LISTMAILBOX, 'v' => $val);
            }
            $cmd[] = $tmp;

            if (!empty($options['children'])) {
                $return_opts[] = 'CHILDREN';
            }

            if (!empty($options['special_use']) &&
                $this->queryCapability('CREATE-SPECIAL-USE')) {
                $return_opts[] = 'SPECIAL-USE';
            }

            if (!empty($options['status']) &&
                $this->queryCapability('LIST-STATUS')) {
                $status_mask = array(
                    Horde_Imap_Client::STATUS_MESSAGES => 'MESSAGES',
                    Horde_Imap_Client::STATUS_RECENT => 'RECENT',
                    Horde_Imap_Client::STATUS_UIDNEXT => 'UIDNEXT',
                    Horde_Imap_Client::STATUS_UIDVALIDITY => 'UIDVALIDITY',
                    Horde_Imap_Client::STATUS_UNSEEN => 'UNSEEN',
                    Horde_Imap_Client::STATUS_HIGHESTMODSEQ => 'HIGHESTMODSEQ'
                );

                $status_opts = array();
                foreach ($status_mask as $key => $val) {
                    if ($options['status'] & $key) {
                        $status_opts[] = $val;
                    }
                }

                if (!empty($status_opts)) {
                    $return_opts[] = 'STATUS';
                    $return_opts[] = $status_opts;
                }
            }

            if (!empty($return_opts)) {
                $cmd[] = 'RETURN';
                $cmd[] = $return_opts;
            }
        } else {
            if (is_array($pattern)) {
                $return_array = array();
                foreach ($pattern as $val) {
                    $return_array = array_merge($return_array, $this->_getMailboxList($val, $mode, $options, $subscribed));
                }
                return $return_array;
            }

            $cmd = array(
                (($mode == Horde_Imap_Client::MBOX_SUBSCRIBED) ? 'LSUB' : 'LIST'),
                '""',
                array('t' => Horde_Imap_Client::DATA_LISTMAILBOX, 'v' => $pattern)
            );
        }

        $this->_sendLine($cmd);

        if (!empty($options['flat'])) {
            return array_values($t['listresponse']);
        }

        /* Add in STATUS return, if needed. */
        if (!empty($options['status'])) {
            if (!is_array($pattern)) {
                $pattern = array($pattern);
            }

            foreach ($pattern as $val) {
                if (!empty($t['status'][$val])) {
                    $t['listresponse'][$val]['status'] = $t['status'][$val];
                }
            }
        }

        return $t['listresponse'];
    }

    /**
     * Parse a LIST/LSUB response (RFC 3501 [7.2.2 & 7.2.3]).
     *
     * @param array $data  The server response (includes type as first
     *                     element).
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _parseList($data)
    {
        $ml = $this->_temp['mailboxlist'];
        $mlo = $ml['options'];
        $lr = &$this->_temp['listresponse'];

        $mode = strtoupper($data[0]);
        $mbox = $data[3];

        if ($ml['check'] &&
            $ml['subexist'] &&
            !isset($ml['subscribed'][$mbox])) {
            return;
        } else if ((!$ml['check'] && $ml['subexist']) ||
                   (empty($mlo['flat']) && !empty($mlo['attributes']))) {
            $attr = array_flip(array_map('strtolower', $data[1]));
            if ($ml['subexist'] &&
                !$ml['check'] &&
                isset($attr['\\nonexistent'])) {
                return;
            }
        }

        if (!empty($mlo['utf8'])) {
            $mbox = Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($mbox);
        }

        if (empty($mlo['flat'])) {
            $tmp = array('mailbox' => $mbox);
            if (!empty($mlo['attributes'])) {
                /* RFC 5258 [3.4]: inferred attributes. */
                if ($ml['ext']) {
                    if (isset($attr['\\noinferiors'])) {
                        $attr['\\hasnochildren'] = 1;
                    }
                    if (isset($attr['\\nonexistent'])) {
                        $attr['\\noselect'] = 1;
                    }
                }
                $tmp['attributes'] = array_keys($attr);
            }
            if (!empty($mlo['delimiter'])) {
                $tmp['delimiter'] = $data[2];
            }
            if (isset($data[4])) {
                $tmp['extended'] = $data[4];
            }
            $lr[$mbox] = $tmp;
        } else {
            $lr[] = $mbox;
        }
    }

    /**
     */
    protected function _status($mailbox, $flags)
    {
        $data = $query = array();
        $search = null;

        $items = array(
            Horde_Imap_Client::STATUS_MESSAGES => 'messages',
            Horde_Imap_Client::STATUS_RECENT => 'recent',
            Horde_Imap_Client::STATUS_UIDNEXT => 'uidnext',
            Horde_Imap_Client::STATUS_UIDVALIDITY => 'uidvalidity',
            Horde_Imap_Client::STATUS_UNSEEN => 'unseen',
            Horde_Imap_Client::STATUS_FIRSTUNSEEN => 'firstunseen',
            Horde_Imap_Client::STATUS_FLAGS => 'flags',
            Horde_Imap_Client::STATUS_PERMFLAGS => 'permflags',
            Horde_Imap_Client::STATUS_UIDNOTSTICKY => 'uidnotsticky',
        );

        /* Don't include modseq returns if server does not support it.
         * OK to use queryCapability('CONDSTORE') here because we may not have
         * yet sent an enabling command. */
        if ($this->queryCapability('CONDSTORE')) {
            $items[Horde_Imap_Client::STATUS_HIGHESTMODSEQ] = 'highestmodseq';
        }

        /* If FLAGS/PERMFLAGS/UIDNOTSTICKY/FIRSTUNSEEN are needed, we must do
         * a SELECT/EXAMINE to get this information (data will be caught in
         * the code below). */
        if (($flags & Horde_Imap_Client::STATUS_FIRSTUNSEEN) ||
            ($flags & Horde_Imap_Client::STATUS_FLAGS) ||
            ($flags & Horde_Imap_Client::STATUS_PERMFLAGS) ||
            ($flags & Horde_Imap_Client::STATUS_UIDNOTSTICKY)) {
            $this->openMailbox($mailbox);
        } else {
            $this->login();
        }

        foreach ($items as $key => $val) {
            if ($key & $flags) {
                if ($mailbox == $this->_selected) {
                    if (isset($this->_temp['mailbox'][$val])) {
                        $data[$val] = $this->_temp['mailbox'][$val];
                    } elseif ($key == Horde_Imap_Client::STATUS_UIDNEXT) {
                        /* UIDNEXT is not strictly required on mailbox open.
                         * See RFC 3501 [6.3.1]. */
                        $data[$val] = 0;
                    } else {
                        if ($key == Horde_Imap_Client::STATUS_UIDNOTSTICKY) {
                            /* In the absence of uidnotsticky information, or
                             * if UIDPLUS is not supported, we assume the UIDs
                             * are sticky. */
                            $data[$val] = false;
                        } elseif (in_array($key, array(Horde_Imap_Client::STATUS_FIRSTUNSEEN, Horde_Imap_Client::STATUS_UNSEEN))) {
                            /* If we already know there are no messages in the
                             * current mailbox, we know there is no
                             * firstunseen and unseen info also. */
                            if (empty($this->_temp['mailbox']['messages'])) {
                                $data[$val] = ($key == Horde_Imap_Client::STATUS_FIRSTUNSEEN) ? null : 0;
                            } else {
                                /* RFC 3501 [6.3.1] - FIRSTUNSEEN information
                                 * is not mandatory. If missing EXAMINE/SELECT
                                 * we need to do a search. An UNSEEN count
                                 * also requires a search. */
                                if (is_null($search)) {
                                    $search_query = new Horde_Imap_Client_Search_Query();
                                    $search_query->flag('\\seen', false);
                                    $search = $this->search($mailbox, $search_query, array('results' => array(($key == Horde_Imap_Client::STATUS_FIRSTUNSEEN) ? Horde_Imap_Client::SORT_RESULTS_MIN : Horde_Imap_Client::SORT_RESULTS_COUNT), 'sequence' => true));
                                }

                                $data[$val] = $search[($key == Horde_Imap_Client::STATUS_FIRSTUNSEEN) ? 'min' : 'count'];
                            }
                        }
                    }
                } else {
                    $query[] = $val;
                }
            }
        }

        if (empty($query)) {
            return $data;
        }

        $this->_sendLine(array(
            'STATUS',
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox),
            array_map('strtoupper', $query)
        ));

        return $this->_temp['status'][$mailbox];
    }

    /**
     * Parse a STATUS response (RFC 3501 [7.2.4], RFC 4551 [3.6])
     *
     * @param string $mailbox  The mailbox name (UTF7-IMAP).
     * @param array $data      The server response.
     */
    protected function _parseStatus($mailbox, $data)
    {
        $this->_temp['status'][$mailbox] = array();

        for ($i = 0, $len = count($data); $i < $len; $i += 2) {
            $item = strtolower($data[$i]);
            $this->_temp['status'][$mailbox][$item] = $data[$i + 1];
        }
    }

    /**
     */
    protected function _append($mailbox, $data, $options)
    {
        $this->login();

        // Check for MULTIAPPEND extension (RFC 3502)
        if ((count($data) > 1) && !$this->queryCapability('MULTIAPPEND')) {
            $result = new Horde_Imap_Client_Ids();
            foreach (array_keys($data) as $key) {
                $res = $this->_append($mailbox, array($data[$key]), $options);
                if (($res === true) || ($result === true)) {
                    $result = true;
                } else {
                    $result->add($res);
                }
            }
            return $result;
        }

        // If the mailbox is currently selected read-only, we need to close
        // because some IMAP implementations won't allow an append.
        $this->close();

        // Check for CATENATE extension (RFC 4469)
        $catenate = $this->queryCapability('CATENATE');

        $t = &$this->_temp;
        $t['appenduid'] = array();
        $t['trycreate'] = null;
        $t['uidplusmbox'] = $mailbox;

        $cmd = array(
            'APPEND',
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox)
        );

        foreach (array_keys($data) as $key) {
            if (!empty($data[$key]['flags'])) {
                $tmp = array();
                foreach ($data[$key]['flags'] as $val) {
                    /* Ignore recent flag. RFC 3501 [9]: flag definition */
                    if (strcasecmp($val, '\\recent') !== 0) {
                        $tmp[] = array('t' => Horde_Imap_Client::DATA_ATOM, 'v' => $val);
                    }
                }
                $cmd[] = $tmp;
            }

            if (!empty($data[$key]['internaldate'])) {
                $cmd[] = array(
                    't' => Horde_Imap_Client::DATA_DATETIME,
                    'v' => $data[$key]['internaldate']->format('j-M-Y H:i:s O')
                );
            }

            if (is_array($data[$key]['data'])) {
                if ($catenate) {
                    $cmd[] = 'CATENATE';

                    $tmp = array();
                    foreach (array_keys($data[$key]['data']) as $key2) {
                        switch ($data[$key]['data'][$key2]['t']) {
                        case 'text':
                            $tmp[] = 'TEXT';
                            $tmp[] = $this->_prepareAppendData($data[$key]['data'][$key2]['v']);
                            break;

                        case 'url':
                            $tmp[] = 'URL';
                            $tmp[] = $data[$key]['data'][$key2]['v'];
                            break;
                        }
                    }
                    $cmd[] = $tmp;
                } else {
                    $cmd[] = $this->_buildCatenateData($data[$key]['data']);
                }
            } else {
                $cmd[] = $this->_prepareAppendData($data[$key]['data']);
            }
        }

        try {
            $this->_sendLine($cmd);
        } catch (Horde_Imap_Client_Exception $e) {
            if (!empty($options['create']) && $this->_temp['trycreate']) {
                $this->createMailbox($mailbox);
                unset($options['create']);
                return $this->_append($mailbox, $data, $options);
            }
            throw $e;
        }

        /* If we reach this point and have data in $_temp['appenduid'],
         * UIDPLUS (RFC 4315) has done the dirty work for us. */
        return empty($t['appenduid'])
            ? true
            : new Horde_Imap_Client_Ids($t['appenduid']);
    }

    /**
     */
    protected function _check()
    {
        // CHECK returns no untagged information (RFC 3501 [6.4.1])
        $this->_sendLine('CHECK');
    }

    /**
     */
    protected function _close($options)
    {
        if (empty($options['expunge'])) {
            if ($this->queryCapability('UNSELECT')) {
                // RFC 3691 defines 'UNSELECT' for precisely this purpose
                $this->_sendLine('UNSELECT');
            } else {
                // RFC 3501 [6.4.2]: to close a mailbox without expunge,
                // select a non-existent mailbox. Selecting a null mailbox
                // should do the trick.
                $this->_sendLine('SELECT ""', array('errignore' => true));
            }
        } else {
            // If caching, we need to know the UIDs being deleted, so call
            // expunge() before calling close().
            if ($this->_initCache(true)) {
                $this->expunge($this->_selected);
            }

            // CLOSE returns no untagged information (RFC 3501 [6.4.2])
            $this->_sendLine('CLOSE');

            /* Ignore HIGHESTMODSEQ information (RFC 5162 [3.4]) since the
             * expunge() call would have already caught it. */
        }

        // Need to clear status cache since we are no longer in mailbox.
        $this->_temp['mailbox'] = array();
    }

    /**
     */
    protected function _expunge($options)
    {
        $unflag = array();
        $mailbox = $this->_selected;
        $s_res = null;
        $uidplus = $this->queryCapability('UIDPLUS');
        $use_cache = $this->_initCache(true);

        if ($options['ids']->all) {
            $uid_string = '1:*';
        } elseif ($uidplus) {
            /* UID EXPUNGE command needs UIDs. */
            if ($options['ids']->search_res) {
                $uid_string = '$';
            } elseif ($options['ids']->sequence) {
                $results = array(Horde_Imap_Client::SORT_RESULTS_MATCH);
                if ($this->queryCapability('SEARCHRES')) {
                    $results[] = Horde_Imap_Client::SORT_RESULTS_SAVE;
                }
                $s_res = $this->search($mailbox, null, array(
                    'results' => $results
                ));
                $uid_string = (in_array(Horde_Imap_Client::SORT_RESULTS_SAVE, $results) && !empty($s_res['save']))
                    ? '$'
                    : strval($s_res['match']);
            } else {
                $uid_string = strval($options['ids']);
            }
        } else {
            /* Without UIDPLUS, need to temporarily unflag all messages marked
             * as deleted but not a part of requested IDs to delete. Use NOT
             * searches to accomplish this goal. */
            $search_query = new Horde_Imap_Client_Search_Query();
            $search_query->flag('\\deleted', true);
            if ($options['ids']->search_res) {
                $search_query->previousSearch(true);
            } else {
                $search_query->ids($options['ids'], true);
            }

            $res = $this->search($mailbox, $search_query);

            $this->store($mailbox, array(
                'ids' => $res['match'],
                'remove' => array('\\deleted')
            ));

            $unflag = $res['match'];
        }

        $list_msgs = !empty($options['list']);
        $tmp = &$this->_temp;
        $tmp['expunge'] = $tmp['vanished'] = array();

        /* We need to get sequence num -> UID lookup table if we are caching.
         * There is no guarantee that if we are using QRESYNC that we will get
         * VANISHED responses, so this is unfortunately necessary. */
        if (($list_msgs || $use_cache) && is_null($s_res)) {
            /* Keys in $s_res['match'] start at 0, not 1. */
            $s_res = $this->_getSeqUidLookup(new Horde_Imap_Client_Ids(Horde_Imap_Client_Ids::ALL, true));
            $lookup = $s_res['uids']->ids;
        } else {
            $lookup = $s_res['match']->ids;
        }

        /* Always use UID EXPUNGE if available. */
        if ($uidplus) {
            $this->_sendLine(array(
                'UID',
                'EXPUNGE',
                $uid_string
            ));
        } elseif ($use_cache || $list_msgs) {
            $this->_sendLine('EXPUNGE');
        } else {
            /* This is faster than an EXPUNGE because the server will not
             * return untagged EXPUNGE responses. We can only do this if
             * we are not updating cache information. */
            $this->close(array('expunge' => true));
        }

        if (!empty($unflag)) {
            $this->store($mailbox, array(
                'add' => array('\\deleted'),
                'ids' => $unflag
            ));
        }

        if ($use_cache || $list_msgs) {
            $expunged = array();

            if (!empty($tmp['vanished'])) {
                $i = count($tmp['vanished']);
                $expunged = $tmp['vanished'];
            } elseif (!empty($tmp['expunge'])) {
                $i = $last = 0;

                /* Expunge responses can come in any order. Thus, we need to
                 * reindex anytime we have an index that appears equal to or
                 * after a previously seen index. If an IMAP server is smart,
                 * it will expunge in reverse order instead. */
                foreach ($tmp['expunge'] as $val) {
                    if ($i++ && ($val >= $last)) {
                        $lookup = array_values($lookup);
                    }
                    $expunged[] = $lookup[$val - 1];
                    $last = $val;
                }
            }

            if (!empty($expunged)) {
                if ($use_cache) {
                    $this->_deleteMsgs($mailbox, $expunged);
                }
                $tmp['mailbox']['messages'] -= $i;

                /* Update MODSEQ if active for mailbox. */
                if (!empty($this->_temp['mailbox']['highestmodseq'])) {
                    if (isset($this->_init['enabled']['QRESYNC'])) {
                        $this->_updateMetaData($mailbox, array('HICmodseq' => $this->_temp['mailbox']['highestmodseq']), isset($this->_temp['mailbox']['uidvalidity']) ? $this->_temp['mailbox']['uidvalidity'] : null);
                    } else {
                        /* Unfortunately, RFC 4551 does not provide any method
                         * to obtain the HIGHESTMODSEQ after an EXPUNGE is
                         * completed. Instead, unselect the mailbox - if we
                         * need to reselect the mailbox, the HIGHESTMODSEQ
                         * info will appear in the EXAMINE/SELECT
                         * HIGHESTMODSEQ response. */
                        $this->close();
                    }
                }
            }

            return $list_msgs
                ? new Horde_Imap_Client_Ids($expunged, $options['ids']->sequence)
                : null;
        } elseif (!empty($tmp['expunge'])) {
            /* Updates status message count if not using cache. */
            $tmp['mailbox']['messages'] -= count($tmp['expunge']);
        }
    }

    /**
     * Parse an EXPUNGE response (RFC 3501 [7.4.1]).
     *
     * @param integer $seq  The message sequence number.
     */
    protected function _parseExpunge($seq)
    {
        $this->_temp['expunge'][] = $seq;
    }

    /**
     * Parse a VANISHED response (RFC 5162 [3.6]).
     *
     * @param array $data  The response data.
     */
    protected function _parseVanished($data)
    {
        /* There are two forms of VANISHED.  VANISHED (EARLIER) will be sent
         * be sent in a FETCH (VANISHED) or SELECT/EXAMINE (QRESYNC) call.
         * If this is the case, we can go ahead and update the cache
         * immediately (we know we are caching or else QRESYNC would not be
         * enabled). HIGHESTMODSEQ information will be grabbed at the end in
         * the tagged response. */
        if (is_array($data[0])) {
            if (strtoupper(reset($data[0])) == 'EARLIER') {
                /* Caching is guaranteed to be active if we are using
                 * QRESYNC. */
                $this->_deleteMsgs($this->_temp['mailbox']['name'], $this->utils->fromSequenceString($data[1]));
            }
        } else {
            /* The second form is just VANISHED. This is returned from an
             * EXPUNGE command and will be processed in _expunge() (since
             * we need to adjust message counts in the current mailbox). */
            $this->_temp['vanished'] = $this->utils->fromSequenceString($data[0]);
        }
    }

    /**
     * Search a mailbox.  This driver supports all IMAP4rev1 search criteria
     * as defined in RFC 3501.
     */
    protected function _search($query, $options)
    {
        /* RFC 4551 [3.1] - trying to do a MODSEQ SEARCH on a mailbox that
         * doesn't support it will return BAD. Catch that here and thrown
         * an exception. */
        if (in_array('CONDSTORE', $options['_query']['exts']) &&
            empty($this->_temp['mailbox']['highestmodseq'])) {
            $this->_exception('Mailbox does not support mod-sequences.', 'MBOXNOMODSEQ');
        }

        $cmd = array();
        if (empty($options['sequence'])) {
            $cmd[] = 'UID';
        }

        $sort_criteria = array(
            Horde_Imap_Client::SORT_ARRIVAL => 'ARRIVAL',
            Horde_Imap_Client::SORT_CC => 'CC',
            Horde_Imap_Client::SORT_DATE => 'DATE',
            Horde_Imap_Client::SORT_DISPLAYFROM => 'DISPLAYFROM',
            Horde_Imap_Client::SORT_DISPLAYTO => 'DISPLAYTO',
            Horde_Imap_Client::SORT_FROM => 'FROM',
            Horde_Imap_Client::SORT_REVERSE => 'REVERSE',
            // This is a bogus entry to allow the sort options check to
            // correctly work below.
            Horde_Imap_Client::SORT_SEQUENCE => 'SEQUENCE',
            Horde_Imap_Client::SORT_SIZE => 'SIZE',
            Horde_Imap_Client::SORT_SUBJECT => 'SUBJECT',
            Horde_Imap_Client::SORT_TO => 'TO'
        );

        $results_criteria = array(
            Horde_Imap_Client::SORT_RESULTS_COUNT => 'COUNT',
            Horde_Imap_Client::SORT_RESULTS_MATCH => 'ALL',
            Horde_Imap_Client::SORT_RESULTS_MAX => 'MAX',
            Horde_Imap_Client::SORT_RESULTS_MIN => 'MIN',
            Horde_Imap_Client::SORT_RESULTS_SAVE => 'SAVE'
        );

        // Check if the server supports server-side sorting (RFC 5256).
        $esearch = $return_sort = $server_seq_sort = $server_sort = false;
        if (!empty($options['sort'])) {
            /* Make sure sort options are correct. If not, default to no
             * sort. */
            if (count(array_intersect($options['sort'], array_keys($sort_criteria))) === 0) {
                unset($options['sort']);
            } else {
                $return_sort = true;

                $server_sort =
                    $this->queryCapability('SORT') &&
                    /* Make sure server supports DISPLAYFROM & DISPLAYTO. */
                    !((in_array(Horde_Imap_Client::SORT_DISPLAYFROM, $options['sort']) ||
                       in_array(Horde_Imap_Client::SORT_DISPLAYTO, $options['sort'])) &&
                      (!is_array($server_sort) || !in_array('DISPLAY', $server_sort)));

                /* If doing a sequence sort, need to do this on the client
                 * side. */
                if ($server_sort &&
                    in_array(Horde_Imap_Client::SORT_SEQUENCE, $options['sort'])) {
                    $server_sort = false;

                    /* Optimization: If doing only a sequence sort, just do a
                     * simple search and sort UIDs/sequences on client side. */
                    switch (count($options['sort'])) {
                    case 1:
                        $server_seq_sort = true;
                        break;

                    case 2:
                        $server_seq_sort = (reset($options['sort']) == Horde_Imap_Client::SORT_REVERSE);
                        break;
                    }
                }
            }
        }

        if ($server_sort) {
            $cmd[] = 'SORT';
            // Check for ESORT capability (RFC 5267)
            if ($this->queryCapability('ESORT')) {
                $results = array();
                foreach ($options['results'] as $val) {
                    if (isset($results_criteria[$val]) &&
                        ($val != Horde_Imap_Client::SORT_RESULTS_SAVE)) {
                        $results[] = $results_criteria[$val];
                    }
                }
                $cmd[] = 'RETURN';
                $cmd[] = $results;
            }

            $tmp = array();
            foreach ($options['sort'] as $val) {
                if (isset($sort_criteria[$val])) {
                    $tmp[] = $sort_criteria[$val];
                }
            }
            $cmd[] = $tmp;

            // Charset is mandatory for SORT (RFC 5256 [3]).
            $cmd[] = $options['_query']['charset'];
        } else {
            // Check if the server supports ESEARCH (RFC 4731).
            $esearch = $this->queryCapability('ESEARCH');

            $cmd[] = 'SEARCH';

            if ($esearch) {
                // Always use ESEARCH if available because it returns results
                // in a more compact sequence-set list
                $results = array();
                foreach ($options['results'] as $val) {
                    if (isset($results_criteria[$val])) {
                        $results[] = $results_criteria[$val];
                    }
                }
                $cmd[] = 'RETURN';
                $cmd[] = $results;
            }

            // Charset is optional for SEARCH (RFC 3501 [6.4.4]).
            if ($options['_query']['charset'] != 'US-ASCII') {
                $cmd[] = 'CHARSET';
                $cmd[] = $options['_query']['charset'];
            }

            // SEARCHRES requires ESEARCH
            unset($this->_temp['searchnotsaved']);
        }

        $er = &$this->_temp['esearchresp'];
        $sr = &$this->_temp['searchresp'];
        $er = $sr = array();

        $cmd = array_merge($cmd, $options['_query']['query']);

        $this->_sendLine($cmd);

        if ($return_sort && !$server_sort) {
            if ($server_seq_sort) {
                sort($sr, SORT_NUMERIC);
                if (reset($options['sort']) == Horde_Imap_Client::SORT_REVERSE) {
                    $sr = array_reverse($sr);
                }
            } else {
                $sr = array_values($this->_clientSort($sr, $options));
            }
        }

        $ret = array();
        foreach ($options['results'] as $val) {
            switch ($val) {
            case Horde_Imap_Client::SORT_RESULTS_COUNT:
                $ret['count'] = $esearch ? $er['count'] : count($sr);
                break;

            case Horde_Imap_Client::SORT_RESULTS_MATCH:
                $ret['match'] = new Horde_Imap_Client_Ids($sr, !empty($options['sequence']));
                break;

            case Horde_Imap_Client::SORT_RESULTS_MAX:
                $ret['max'] = $esearch ? (isset($er['max']) ? $er['max'] : null) : (empty($sr) ? null : max($sr));
                break;

            case Horde_Imap_Client::SORT_RESULTS_MIN:
                $ret['min'] = $esearch ? (isset($er['min']) ? $er['min'] : null) : (empty($sr) ? null : min($sr));
                break;

            case Horde_Imap_Client::SORT_RESULTS_SAVE:
                $ret['save'] = $esearch ? empty($this->_temp['searchnotsaved']) : false;
            }
        }

        // Add modseq data, if needed.
        if (!empty($er['modseq'])) {
            $ret['modseq'] = $er['modseq'];
        }

        return $ret;
    }

    /**
     * Parse a SEARCH/SORT response (RFC 3501 [7.2.5]; RFC 4466 [3];
     * RFC 5256 [4]; RFC 5267 [3]).
     *
     * @param array $data  The server response.
     */
    protected function _parseSearch($data)
    {
        // The extended search response will have a (NAME VAL) entry(s) at
        // the end of the returned data. Do a check for this data.
        if (is_array(end($data))) {
            $this->_parseEsearch(array_pop($data));
        }

        $this->_temp['searchresp'] = $data;
    }

    /**
     * Parse an ESEARCH response (RFC 4466 [2.6.2])
     * Format: (TAG "a567") UID COUNT 5 ALL 4:19,21,28
     *
     * @param array $data  The server response.
     */
    protected function _parseEsearch($data)
    {
        $i = 0;
        $len = count($data);

        // Ignore search correlator information
        if (is_array($data[$i])) {
            ++$i;
        }

        // Ignore UID tag
        if (($i != $len) && (strtoupper($data[$i]) == 'UID')) {
            ++$i;
        }

        // This catches the case of an '(ALL)' esearch with no results
        if ($i == $len) {
            return;
        }

        for (; $i < $len; $i += 2) {
            $val = $data[$i + 1];
            $tag = strtoupper($data[$i]);
            switch ($tag) {
            case 'ALL':
                $this->_temp['searchresp'] = $this->utils->fromSequenceString($val);
                break;

            case 'COUNT':
            case 'MAX':
            case 'MIN':
            case 'MODSEQ':
                $this->_temp['esearchresp'][strtolower($tag)] = $val;
                break;
            }
        }
    }

    /**
     * If server does not support the SORT IMAP extension (RFC 5256), we need
     * to do sorting on the client side.
     *
     * @param array $res   The search results.
     * @param array $opts  The options to search().
     *
     * @return array  The sort results.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _clientSort($res, $opts)
    {
        if (empty($res)) {
            return $res;
        }

        /* Generate the FETCH command needed. */
        $query = new Horde_Imap_Client_Fetch_Query();

        foreach ($opts['sort'] as $val) {
            switch ($val) {
            case Horde_Imap_Client::SORT_ARRIVAL:
                $query->imapDate();
                break;

            case Horde_Imap_Client::SORT_DATE:
                $query->imapDate();
                $query->envelope();
                break;

            case Horde_Imap_Client::SORT_CC:
            case Horde_Imap_Client::SORT_FROM:
            case Horde_Imap_Client::SORT_SUBJECT:
            case Horde_Imap_Client::SORT_TO:
                $query->envelope();
                break;

            case Horde_Imap_Client::SORT_SIZE:
                $query->size();
                break;
            }
        }

        /* Get the FETCH results now. */
        if (count($query)) {
            $fetch_res = $this->fetch($this->_selected, $query, array(
                'ids' => new Horde_Imap_Client_Ids($res, !empty($opts['sequence']))
            ));
        }

        /* The initial sort is on the entire set. */
        $slices = array(0 => $res);

        $reverse = false;
        foreach ($opts['sort'] as $val) {
            if ($val == Horde_Imap_Client::SORT_REVERSE) {
                $reverse = true;
                continue;
            }

            $slices_list = $slices;
            $slices = array();

            foreach ($slices_list as $slice_start => $slice) {
                $display_sort = false;
                $sorted = array();

                if ($reverse) {
                    $slice = array_reverse($slice);
                }

                switch ($val) {
                case Horde_Imap_Client::SORT_SEQUENCE:
                    /* There is no requirement that IDs be returned in
                     * sequence order (see RFC 4549 [4.3.1]). So we must sort
                     * ourselves. */
                    $sorted = array_flip($slice);
                    ksort($sorted, SORT_NUMERIC);
                    break;

                case Horde_Imap_Client::SORT_SIZE:
                    foreach ($slice as $num) {
                        $sorted[$num] = $fetch_res[$num]->getSize();
                    }
                    asort($sorted, SORT_NUMERIC);
                    break;

                case Horde_Imap_Client::SORT_DISPLAYFROM:
                case Horde_Imap_Client::SORT_DISPLAYTO:
                    $display_sort = true;
                    // Fallthrough

                case Horde_Imap_Client::SORT_CC:
                case Horde_Imap_Client::SORT_FROM:
                case Horde_Imap_Client::SORT_TO:
                    if ($val == Horde_Imap_Client::SORT_CC) {
                        $field = 'cc';
                    } elseif (in_array($val, array(Horde_Imap_Client::SORT_DISPLAYFROM, Horde_Imap_Client::SORT_FROM))) {
                        $field = 'from';
                    } else {
                        $field = 'to';
                    }

                    foreach ($slice as $num) {
                        $env = $fetch_res[$num]->getEnvelope();
                        if (empty($env->$field)) {
                            $sorted[$num] = null;
                        } else {
                            $tmp = ($display_sort && !empty($env->$field[0]['personal']))
                                ? 'personal'
                                : 'mailbox';
                            $sorted[$num] = $env->$field[0][$tmp];
                        }
                    }
                    asort($sorted, SORT_LOCALE_STRING);
                    break;

                case Horde_Imap_Client::SORT_ARRIVAL:
                    $sorted = $this->_getSentDates($fetch_res, $slice, true);
                    asort($sorted, SORT_NUMERIC);
                    break;

                case Horde_Imap_Client::SORT_DATE:
                    // Date sorting rules in RFC 5256 [2.2]
                    $sorted = $this->_getSentDates($fetch_res, $slice);
                    asort($sorted, SORT_NUMERIC);
                    break;

                case Horde_Imap_Client::SORT_SUBJECT:
                    // Subject sorting rules in RFC 5256 [2.1]
                    foreach ($slice as $num) {
                        $sorted[$num] = $this->utils->getBaseSubject($fetch_res[$num]->getEnvelope()->subject);
                    }
                    asort($sorted, SORT_LOCALE_STRING);
                    break;
                }

                // At this point, keys of $sorted are sequence/UID and values
                // are the sort strings
                if (!empty($sorted)) {
                    if (count($sorted) == count($res)) {
                        $res = array_keys($sorted);
                    } else {
                        array_splice($res, $slice_start, count($slice), array_keys($sorted));
                    }

                    // Check for ties.
                    $last = $start = null;
                    $i = 0;
                    reset($sorted);
                    while (list($k, $v) = each($sorted)) {
                        if (is_null($last) || ($last != $v)) {
                            if ($i) {
                                $slices[array_search($res, $start)] = array_slice($sorted, array_search($sorted, $start), $i + 1);
                                $i = 0;
                            }
                            $last = $v;
                            $start = $k;
                        } else {
                            ++$i;
                        }
                    }
                    if ($i) {
                        $slices[array_search($res, $start)] = array_slice($sorted, array_search($sorted, $start), $i + 1);
                    }
                }
            }

            $reverse = false;
        }

        return $res;
    }

    /**
     * Get the sent dates for purposes of SORT/THREAD sorting under RFC 5256
     * [2.2].
     *
     * @param array $data        Data returned from fetch() that includes both
     *                           the 'envelope' and 'date' items.
     * @param array $ids         The IDs to process.
     * @param boolean $internal  Only use internal date?
     *
     * @return array  A mapping of IDs -> UNIX timestamps.
     */
    protected function _getSentDates($data, $ids, $internal = false)
    {
        $dates = array();

        foreach ($ids as $num) {
            $dt = ($internal || !isset($data[$num]->getEnvelope()->date))
                // RFC 5256 [3] & 3501 [6.4.4]: disregard timezone when
                // using internaldate.
                ? $data[$num]->getImapDate()
                : $data[$num]->getEnvelope()->date;
            $dates[$num] = $dt->format('U');
        }

        return $dates;
    }

    /**
     */
    protected function _setComparator($comparator)
    {
        $this->_login();

        $cmd = array('COMPARATOR');
        foreach ($comparator as $val) {
            $cmd[] = array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $val);
        }
        $this->_sendLine($cmd);
    }

    /**
     */
    protected function _getComparator()
    {
        $this->_login();

        $this->_sendLine('COMPARATOR');

        return isset($this->_temp['comparator'])
            ? $this->_temp['comparator']
            : null;
    }

    /**
     * Parse a COMPARATOR response (RFC 5255 [4.8])
     *
     * @param array $data  The server response.
     */
    protected function _parseComparator($data)
    {
        $this->_temp['comparator'] = $data;
    }

    /**
     */
    protected function _thread($options)
    {
        $thread_criteria = array(
            Horde_Imap_Client::THREAD_ORDEREDSUBJECT => 'ORDEREDSUBJECT',
            Horde_Imap_Client::THREAD_REFERENCES => 'REFERENCES',
            Horde_Imap_Client::THREAD_REFS => 'REFS'
        );

        $tsort = (isset($options['criteria']))
            ? (is_string($options['criteria']) ? strtoupper($options['criteria']) : $thread_criteria[$options['criteria']])
            : 'ORDEREDSUBJECT';

        $cap = $this->queryCapability('THREAD');
        if (!$cap || !in_array($tsort, $cap)) {
            switch ($tsort) {
            case 'ORDEREDSUBJECT':
                if (empty($options['search'])) {
                    $ids = new Horde_Imap_Client_Ids(Horde_Imap_Client_Ids::ALL, !empty($options['sequence']));
                } else {
                    $search_res = $this->search($this->_selected, $options['search'], array('sequence' => !empty($options['sequence'])));
                    $ids = $search_res['match'];
                }

                /* Do client-side ORDEREDSUBJECT threading. */
                $query = new Horde_Imap_Client_Fetch_Query();
                $query->envelope();
                $query->imapDate();

                $fetch_res = $this->fetch($this->_selected, $query, array(
                    'ids' => $ids
                ));
                return $this->_clientThreadOrderedsubject($fetch_res);

            case 'REFERENCES':
            case 'REFS':
                $this->_exception('Server does not support ' . $tsort . ' thread sort.', 'NOSUPPORTIMAPEXT');
            }
        }

        if (empty($options['search'])) {
            $charset = 'US-ASCII';
            $search = array('ALL');
        } else {
            $search_query = $options['search']->build();
            $charset = $search_query['charset'];
            $search = $search_query['query'];
        }

        $this->_temp['threadparse'] = array('base' => null, 'resp' => array());

        $this->_sendLine(array_merge(array(
            (empty($options['sequence']) ? 'UID' : null),
            'THREAD',
            $tsort,
            $charset
        ), $search));

        return $this->_temp['threadparse']['resp'];
    }

    /**
     * Parse a THREAD response (RFC 5256 [4]).
     *
     * @param array $data      An array of thread token data.
     * @param integer $level   The current tree level.
     * @param boolean $islast  Is this the last item in the level?
     */
    protected function _parseThread($data, $level = 0, $islast = true)
    {
        $tp = &$this->_temp['threadparse'];

        if (!$level) {
            $tp['base'] = null;
        }
        $cnt = count($data) - 1;

        reset($data);
        while (list($key, $val) = each($data)) {
            if (is_array($val)) {
                $this->_parseThread($val, $level ? $level : 1, ($key == $cnt));
            } else {
                if (is_null($tp['base']) && ($level || $cnt)) {
                    $tp['base'] = $val;
                }

                $tp['resp'][$val] = array();
                $ptr = &$tp['resp'][$val];

                if (!is_null($tp['base'])) {
                    $ptr['b'] = $tp['base'];
                }

                if (!$islast) {
                    $ptr['s'] = true;
                }

                if ($level++) {
                    $ptr['l'] = $level - 1;
                }
            }
            $islast = true;
        }
    }

    /**
     * If server does not support the THREAD IMAP extension (RFC 5256), do
     * ORDEREDSUBJECT threading on the client side.
     *
     * @param array $res   Fetch results.
     * @param array $opts  The options to search().
     *
     * @return array  The sort results.
     */
    protected function _clientThreadOrderedsubject($data)
    {
        $dates = $this->_getSentDates($data, array_keys($data));
        $level = $sorted = $tsort = array();
        $this->_temp['threadparse'] = array('base' => null, 'resp' => array());

        reset($data);
        while (list($k, $v) = each($data)) {
            $subject = $this->utils->getBaseSubject($v->getEnvelope()->subject);
            if (!isset($sorted[$subject])) {
                $sorted[$subject] = array();
            }
            $sorted[$subject][$k] = $dates[$k];
        }

        /* Step 1: Sort by base subject (already done).
         * Step 2: Sort by sent date within each thread. */
        foreach (array_keys($sorted) as $key) {
            asort($sorted[$key], SORT_NUMERIC);
            $tsort[$key] = reset($sorted[$key]);
        }

        /* Step 3: Sort by the sent date of the first message in the
         * thread. */
        asort($tsort, SORT_NUMERIC);

        /* Now, $tsort contains the order of the threads, and each thread
         * is sorted in $sorted. */
        foreach (array_keys($tsort) as $key) {
            $keys = array_keys($sorted[$key]);
            $tmp = array($keys[0]);
            if (count($keys) > 1) {
                $tmp[] = array_slice($keys, 1);
            }
            $this->_parseThread($tmp);
        }

        return $this->_temp['threadparse']['resp'];
    }

    /**
     */
    protected function _fetch($query, $results, $options)
    {
        $t = &$this->_temp;
        $t['fetchcmd'] = array();
        $fetch = array();

        /* Build an IMAP4rev1 compliant FETCH query. We handle the following
         * criteria:
         *   BINARY[.PEEK][<section #>]<<partial>> (RFC 3516)
         *     see BODY[] response
         *   BINARY.SIZE[<section #>] (RFC 3516)
         *   BODY[.PEEK][<section>]<<partial>>
         *     <section> = HEADER, HEADER.FIELDS, HEADER.FIELDS.NOT, MIME,
         *                 TEXT, empty
         *     <<partial>> = 0.# (# of bytes)
         *   BODYSTRUCTURE
         *   ENVELOPE
         *   FLAGS
         *   INTERNALDATE
         *   MODSEQ (RFC 4551)
         *   RFC822.SIZE
         *   UID
         *
         * No need to support these (can be built from other queries):
         * ===========================================================
         *   ALL macro => (FLAGS INTERNALDATE RFC822.SIZE ENVELOPE)
         *   BODY => Use BODYSTRUCTURE instead
         *   FAST macro => (FLAGS INTERNALDATE RFC822.SIZE)
         *   FULL macro => (FLAGS INTERNALDATE RFC822.SIZE ENVELOPE BODY)
         *   RFC822 => BODY[]
         *   RFC822.HEADER => BODY[HEADER]
         *   RFC822.TEXT => BODY[TEXT]
         */

        foreach ($query as $type => $c_val) {
            switch ($type) {
            case Horde_Imap_Client::FETCH_STRUCTURE:
                $fetch[] = 'BODYSTRUCTURE';
                break;

            case Horde_Imap_Client::FETCH_FULLMSG:
                if (empty($c_val['peek'])) {
                    $this->openMailbox($this->_selected, Horde_Imap_Client::OPEN_READWRITE);
                }
                $fetch[] = 'BODY' .
                    (!empty($c_val['peek']) ? '.PEEK' : '') .
                    '[]' .
                    $this->_partialAtom($c_val);
                break;

            case Horde_Imap_Client::FETCH_HEADERTEXT:
            case Horde_Imap_Client::FETCH_BODYTEXT:
            case Horde_Imap_Client::FETCH_MIMEHEADER:
            case Horde_Imap_Client::FETCH_BODYPART:
            case Horde_Imap_Client::FETCH_HEADERS:
                foreach ($c_val as $key => $val) {
                    $base_id = $cmd = ($key == 0)
                        ? ''
                        : $key . '.';
                    $main_cmd = 'BODY';

                    switch ($type) {
                    case Horde_Imap_Client::FETCH_HEADERTEXT:
                        $cmd .= 'HEADER';
                        break;

                    case Horde_Imap_Client::FETCH_BODYTEXT:
                        $cmd .= 'TEXT';
                        break;

                    case Horde_Imap_Client::FETCH_MIMEHEADER:
                        $cmd .= 'MIME';
                        break;

                    case Horde_Imap_Client::FETCH_BODYPART:
                        // Remove the last dot from the string.
                        $cmd = substr($cmd, 0, -1);

                        if (!empty($val['decode']) &&
                            $this->queryCapability('BINARY')) {
                            $main_cmd = 'BINARY';
                        }
                        break;

                    case Horde_Imap_Client::FETCH_HEADERS:
                        $cmd .= 'HEADER.FIELDS';
                        if (!empty($val['notsearch'])) {
                            $cmd .= '.NOT';
                        }
                        $cmd .= ' (' . implode(' ', array_map('strtoupper', $val['headers'])) . ')';

                        // Maintain a command -> label lookup so we can put
                        // the results in the proper location.
                        $t['fetchcmd'][$cmd] = $key;
                    }

                    if (empty($val['peek'])) {
                        $this->openMailbox($this->_selected, Horde_Imap_Client::OPEN_READWRITE);
                    }

                    $fetch[] = $main_cmd .
                        (!empty($val['peek']) ? '.PEEK' : '') .
                        '[' . $cmd . ']' .
                        $this->_partialAtom($val);
                }
                break;

            case Horde_Imap_Client::FETCH_BODYPARTSIZE:
                if ($this->queryCapability('BINARY')) {
                    foreach ($c_val as $val) {
                        $fetch[] = 'BINARY.SIZE[' . $key . ']';
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_ENVELOPE:
                $fetch[] = 'ENVELOPE';
                break;

            case Horde_Imap_Client::FETCH_FLAGS:
                $fetch[] = 'FLAGS';
                break;

            case Horde_Imap_Client::FETCH_IMAPDATE:
                $fetch[] = 'INTERNALDATE';
                break;

            case Horde_Imap_Client::FETCH_SIZE:
                $fetch[] = 'RFC822.SIZE';
                break;

            case Horde_Imap_Client::FETCH_UID:
                /* A UID FETCH will always return UID information (RFC 3501
                 * [6.4.8]). Don't add to query as it just creates a longer
                 * FETCH command. */
                if ($options['ids']->sequence) {
                    $fetch[] = 'UID';
                }
                break;

            case Horde_Imap_Client::FETCH_SEQ:
                // Nothing we need to add to fetch request unless sequence
                // is the only criteria.
                if (count($query) == 1) {
                    $fetch[] = 'UID';
                }
                break;

            case Horde_Imap_Client::FETCH_MODSEQ:
                /* The 'changedsince' modifier implicitly adds the MODSEQ
                 * FETCH item (RFC 4551 [3.3.1]). Don't add to query as it
                 * just creates a longer FETCH command. */
                if (empty($options['changedsince'])) {
                    /* RFC 4551 [3.1] - trying to do a FETCH of MODSEQ on a
                     * mailbox that doesn't support it will return BAD. Catch
                     * that here and throw an exception. */
                    if (empty($this->_temp['mailbox']['highestmodseq'])) {
                        $this->_exception('Mailbox does not support mod-sequences.', 'MBOXNOMODSEQ');
                    }
                    $fetch[] = 'MODSEQ';
                }
                break;
            }
        }

        $seq = $options['ids']->all
            ? '1:*'
            : ($options['ids']->search_res ? '$' : strval($options['ids']));

        $cmd = array(
            ($options['ids']->sequence ? null : 'UID'),
            'FETCH',
            $seq,
            $fetch
        );

        if (!empty($options['changedsince'])) {
            if (empty($this->_temp['mailbox']['highestmodseq'])) {
                $this->_exception('Mailbox does not support mod-sequences.', 'MBOXNOMODSEQ');
            }
            $cmd[] = array(
                'CHANGEDSINCE',
                array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $options['changedsince'])
            );
        }

        $fetchresp = $options['ids']->sequence
            ? array('seq' => $results, 'uid' => array())
            : array('seq' => array(), 'uid' => $results);

        $this->_sendLine($cmd, array(
            'fetch' => $fetchresp
        ));

        $ret = $t['fetchresp'][$options['ids']->sequence ? 'seq' : 'uid'];
        unset($t['fetchcmd'], $t['fetchresp']);

        return $ret;
    }

    /**
     * Add a partial atom to an IMAP command based on the criteria options.
     *
     * @param array $opts  Criteria options.
     *
     * @return string  The partial atom.
     */
    protected function _partialAtom($opts)
    {
        if (!empty($opts['length'])) {
            return '<' . (empty($opts['start']) ? 0 : intval($opts['start'])) . '.' . intval($opts['length']) . '>';
        }

        return empty($opts['start'])
            ? ''
            : ('<' . intval($opts['start']) . '>');
    }

    /**
     * Parse a FETCH response (RFC 3501 [7.4.2]). A FETCH response may occur
     * due to a FETCH command, or due to a change in a message's state (i.e.
     * the flags change).
     *
     * @param integer $id  The message sequence number.
     * @param array $data  The server response.
     */
    protected function _parseFetch($id, $data)
    {
        $cnt = count($data);
        $fr = &$this->_temp['fetchresp'];
        $i = 0;
        $uid = null;

        /* At this point, we don't have access to the UID of the entry. Thus,
         * need to cache data locally until we reach the end. */
        $ob = new Horde_Imap_Client_Data_Fetch();
        $ob->setSeq($id);

        while ($i < $cnt) {
            $tag = strtoupper($data[$i]);
            switch ($tag) {
            case 'BODYSTRUCTURE':
                $structure = $this->_parseBodystructure($data[++$i]);
                $structure->buildMimeIds();
                $ob->setStructure($structure);
                break;

            case 'ENVELOPE':
                $ob->setEnvelope($this->_parseEnvelope($data[++$i]));
                break;

            case 'FLAGS':
                $ob->setFlags($data[++$i]);
                break;

            case 'INTERNALDATE':
                $ob->setImapDate($data[++$i]);
                break;

            case 'RFC822.SIZE':
                $ob->setSize($data[++$i]);
                break;

            case 'UID':
                $uid = $data[++$i];
                $ob->setUid($uid);
                break;

            case 'MODSEQ':
                $modseq = reset($data[++$i]);

                $ob->setModSeq($modseq);

                /* Update highestmodseq, if it exists. */
                if (!empty($this->_temp['mailbox']['highestmodseq']) &&
                    ($modseq > $this->_temp['mailbox']['highestmodseq'])) {
                    $this->_temp['mailbox']['highestmodseq'] = $modseq;
                }
                break;

            default:
                // Catch BODY[*]<#> responses
                if (strpos($tag, 'BODY[') === 0) {
                    // Remove the beginning 'BODY['
                    $tag = substr($tag, 5);

                    // BODY[HEADER.FIELDS] request
                    if (!empty($this->_temp['fetchcmd']) &&
                        (strpos($tag, 'HEADER.FIELDS') !== false)) {
                        // A HEADER.FIELDS entry will be tokenized thusly:
                        //   [0] => BODY[#.HEADER.FIELDS.NOT
                        //   [1] => Array
                        //     (
                        //       [0] => MESSAGE-ID
                        //     )
                        //   [2] => ]<0>
                        //   [3] => **Header search text**
                        $sig = $tag . ' (' . implode(' ', array_map('strtoupper', $data[++$i])) . ')';

                        // Ignore the trailing bracket
                        ++$i;

                        $ob->setHeaders($this->_temp['fetchcmd'][$sig], $data[++$i]);
                    } else {
                        // Remove trailing bracket and octet start info
                        $tag = substr($tag, 0, strrpos($tag, ']'));

                        if (!strlen($tag)) {
                            // BODY[] request
                            if ($data[++$i] != 'NIL') {
                                $ob->setFullMsg($data[$i]);
                            }
                        } elseif (is_numeric(substr($tag, -1))) {
                            // BODY[MIMEID] request
                            if ($data[++$i] != 'NIL') {
                                $ob->setBodyPart($tag, $data[$i]);
                            }
                        } else {
                            // BODY[HEADER|TEXT|MIME] request
                            if (($last_dot = strrpos($tag, '.')) === false) {
                                $mime_id = 0;
                            } else {
                                $mime_id = substr($tag, 0, $last_dot);
                                $tag = substr($tag, $last_dot + 1);
                            }

                            if ($data[++$i] != 'NIL') {
                                switch ($tag) {
                                case 'HEADER':
                                    $ob->setHeaderText($mime_id, $data[$i]);
                                    break;

                                case 'TEXT':
                                    $ob->setBodyText($mime_id, $data[$i]);
                                    break;

                                case 'MIME':
                                    $ob->setMimeHeader($mime_id, $data[$i]);
                                    break;
                                }
                            }
                        }
                    }
                } elseif (strpos($tag, 'BINARY[') === 0) {
                    // Catch BINARY[*]<#> responses
                    // Remove the beginning 'BINARY[' and the trailing bracket
                    // and octet start info
                    $tag = substr($tag, 7, strrpos($tag, ']') - 7);
                    $ob->setBodyPart($tag, $data[++$i], empty($this->_temp['literal8']) ? '8bit' : 'binary');
                } elseif (strpos($tag, 'BINARY.SIZE[') === 0) {
                    // Catch BINARY.SIZE[*] responses
                    // Remove the beginning 'BINARY.SIZE[' and the trailing
                    // bracket and octet start info
                    $tag = substr($tag, 12, strrpos($tag, ']') - 12);
                    $ob->setBodyPartSize($tag, $data[++$i]);
                }
                break;
            }

            ++$i;
        }

        if (!is_null($uid) && isset($fr['uid'][$uid])) {
            $fr['uid'][$uid]->merge($ob);
        } elseif (isset($fr['seq'][$id])) {
            $fr['seq'][$id]->merge($ob);
        } else {
            $fr['seq'][$id] = $ob;
            if (!is_null($uid)) {
                $fr['uid'][$uid] = $ob;
            }
        }
    }

    /**
     * Recursively parse BODYSTRUCTURE data from a FETCH return (see
     * RFC 3501 [7.4.2]).
     *
     * @param array $data  The tokenized information from the server.
     *
     * @return array  The array of bodystructure information.
     */
    protected function _parseBodystructure($data)
    {
        $ob = new Horde_Mime_Part();

        // If index 0 is an array, this is a multipart part.
        if (is_array($data[0])) {
            // Keep going through array values until we find a non-array.
            for ($i = 0, $cnt = count($data); $i < $cnt; ++$i) {
                if (!is_array($data[$i])) {
                    break;
                }
                $ob->addPart($this->_parseBodystructure($data[$i]));
            }

            // The first string entry after an array entry gives us the
            // subpart type.
            $ob->setType('multipart/' . $data[$i]);

            // After the subtype is further extension information. This
            // information MAY not appear for BODYSTRUCTURE requests.

            // This is parameter information.
            if (isset($data[++$i]) && is_array($data[$i])) {
                foreach ($this->_parseStructureParams($data[$i], 'content-type') as $key => $val) {
                    $ob->setContentTypeParameter($key, $val);
                }
            }

            // This is disposition information.
            if (isset($data[++$i]) && is_array($data[$i])) {
                $ob->setDisposition($data[$i][0]);

                foreach ($this->_parseStructureParams($data[$i][1], 'content-disposition') as $key => $val) {
                    $ob->setDispositionParameter($key, $val);
                }
            }

            // This is language information. It is either a single value or
            // a list of values.
            if (isset($data[++$i])) {
                $ob->setLanguage($data[$i]);
            }

            // Ignore: location (RFC 2557)
            // There can be further information returned in the future, but
            // for now we are done.
        } else {
            $ob->setType($data[0] . '/' . $data[1]);

            foreach ($this->_parseStructureParams($data[2], 'content-type') as $key => $val) {
                $ob->setContentTypeParameter($key, $val);
            }

            if ($data[3] != 'NIL') {
                $ob->setContentId($data[3]);
            }

            if ($data[4] != 'NIL') {
                $ob->setDescription(Horde_Mime::decode($data[4], 'UTF-8'));
            }

            if ($data[5] != 'NIL') {
                $ob->setTransferEncoding($data[5]);
            }

            if ($data[6] != 'NIL') {
                $ob->setBytes($data[6]);
            }

            // If the type is 'message/rfc822' or 'text/*', several extra
            // fields are included
            switch ($ob->getPrimaryType()) {
            case 'message':
                if ($ob->getSubType() == 'rfc822') {
                    // Ignore: envelope
                    $ob->addPart($this->_parseBodystructure($data[8]));
                    // Ignore: lines
                    $i = 10;
                } else {
                    $i = 7;
                }
                break;

            case 'text':
                // Ignore: lines
                $i = 8;
                break;

            default:
                $i = 7;
                break;
            }

            // After the subtype is further extension information. This
            // information MAY appear for BODYSTRUCTURE requests.

            // Ignore: MD5

            // This is disposition information
            if (isset($data[++$i]) && is_array($data[$i])) {
                $ob->setDisposition($data[$i][0]);

                foreach ($this->_parseStructureParams($data[$i][1], 'content-disposition') as $key => $val) {
                    $ob->setDispositionParameter($key, $val);
                }
            }

            // This is language information. It is either a single value or
            // a list of values.
            if (isset($data[++$i])) {
                $ob->setLanguage($data[$i]);
            }

            // Ignore: location (RFC 2557)
        }

        return $ob;
    }

    /**
     * Helper function to parse a parameters-like tokenized array.
     *
     * @param array $data   The tokenized data.
     * @param string $type  The header name.
     *
     * @return array  The parameter array.
     */
    protected function _parseStructureParams($data, $type)
    {
        $params = array();

        if (is_array($data)) {
            for ($i = 0, $cnt = count($data); $i < $cnt; ++$i) {
                $params[strtolower($data[$i])] = $data[++$i];
            }
        }

        $ret = Horde_Mime::decodeParam($type, $params);

        return $ret['params'];
    }

    /**
     * Parse ENVELOPE data from a FETCH return (see RFC 3501 [7.4.2]).
     *
     * @param array $data  The tokenized information from the server.
     *
     * @return Horde_Imap_Client_Data_Envelope  An envelope object.
     */
    protected function _parseEnvelope($data)
    {
        $addr_structure = array(
            'personal', 'adl', 'mailbox', 'host'
        );
        $env_data = array(
            0 => 'date',
            1 => 'subject',
            8 => 'in_reply_to',
            9 => 'message_id'
        );
        $env_data_array = array(
            2 => 'from',
            3 => 'sender',
            4 => 'reply_to',
            5 => 'to',
            6 => 'cc',
            7 => 'bcc'
        );

        $ret = new Horde_Imap_Client_Data_Envelope();

        foreach ($env_data as $key => $val) {
            if (isset($data[$key]) &&
                (strcasecmp($data[$key], 'NIL') !== 0)) {
                $ret->$val = $data[$key];
            }
        }

        // These entries are address structures.
        foreach ($env_data_array as $key => $val) {
            // Check for 'NIL' value here.
            if (is_array($data[$key])) {
                $tmp = array();
                reset($data[$key]);

                while (list(,$a_val) = each($data[$key])) {
                    $tmp_addr = array();
                    foreach ($addr_structure as $add_key => $add_val) {
                        if (strcasecmp($a_val[$add_key], 'NIL') !== 0) {
                            $tmp_addr[$add_val] = $a_val[$add_key];
                        }
                    }
                    $tmp[] = $tmp_addr;
                }

                $ret->$val = $tmp;
            }
        }

        return $ret;
    }

    /**
     */
    protected function _store($options)
    {
        $seq = $options['ids']->all
            ? '1:*'
            : ($options['ids']->search_res ? '$' : strval($options['ids']));

        $cmd = array(
            (empty($options['sequence']) ? 'UID' : null),
            'STORE',
            $seq
        );

        $condstore = $ucsince = null;

        if (empty($this->_temp['mailbox']['highestmodseq'])) {
            if (!empty($options['unchangedsince'])) {
                /* RFC 4551 [3.1] - trying to do a UNCHANGEDSINCE STORE on a
                 * mailbox that doesn't support it will return BAD. Catch that
                 * here and throw an exception. */
                $this->_exception('Mailbox does not support mod-sequences.', 'MBOXNOMODSEQ');
            }
        } else {
            if (!empty($options['unchangedsince'])) {
                $ucsince = intval($options['unchangedsince']);
            }

            if (isset($this->_init['enabled']['CONDSTORE'])) {
                /* If we reach here, MODSEQ is active for mailbox. */
                $condstore = true;

                /* If CONDSTORE is enabled, we need to verify UNCHANGEDSINCE
                 * added to ensure we get MODSEQ updated information. */
                if (is_null($ucsince)) {
                    $ucsince = $this->_temp['mailbox']['highestmodseq'];
                }
            }

            if ($ucsince) {
                $cmd[] = array(
                    'UNCHANGEDSINCE',
                    array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $ucsince)
                );
            }
        }

        $this->_temp['modified'] = new Horde_Imap_Client_Ids();

        if (!empty($options['replace'])) {
            $cmd[] = 'FLAGS' . ($this->_debug ? '' : '.SILENT');
            foreach ($options['replace'] as $val) {
                $cmd[] = array('t' => Horde_Imap_Client::DATA_ATOM, 'v' => $val);
            }

            $this->_sendLine($cmd);
        } else {
            foreach (array('add' => '+', 'remove' => '-') as $k => $v) {
                if (!empty($options[$k])) {
                    $cmdtmp = $cmd;
                    $cmdtmp[] = $v . 'FLAGS' . ($this->_debug ? '' : '.SILENT');
                    foreach ($options[$k] as $val) {
                        $cmdtmp[] = array('t' => Horde_Imap_Client::DATA_ATOM, 'v' => $val);
                    }

                    $this->_sendLine($cmdtmp);
                }
            }
        }

        /* Update the flags in the cache. Only update if store was successful
         * and flag information was not returned. */
        if ($condstore && !empty($this->_temp['fetchresp']['seq'])) {
            $fr = $this->_temp['fetchresp'];
            $tocache = $uids = array();

            if (empty($fr['uid'])) {
                $res = $fr['seq'];
                $seq_res = $this->_getSeqUidLookup(new Horde_Imap_Client_Ids(array_keys($res), true));
            } else {
                $res = $fr['uid'];
                $seq_res = null;
            }

            foreach (array_keys($res) as $key) {
                if (!$res[$key]->exists(Horde_Imap_Client::FETCH_FLAGS)) {
                    $uids[$key] = is_null($seq_res)
                        ? $key
                        : $seq_res['lookup'][$key];
                }
            }

            /* Get the list of flags from the cache. */
            if (empty($options['replace'])) {
                /* Caching is guaranteed to be active if CONDSTORE is
                 * active. */
                $data = $this->cache->get($this->_selected, array_values($uids), array('HICflags'), $this->_temp['mailbox']['uidvalidity']);

                foreach ($uids as $key => $uid) {
                    $flags = isset($data[$uid]['HICflags'])
                        ? $data[$uid]['HICflags']
                        : array();
                    if (!empty($options['add'])) {
                        $flags = array_merge($flags, $options['add']);
                    }
                    if (!empty($options['remove'])) {
                        $flags = array_diff($flags, $options['remove']);
                    }

                    $tocache[$uid] = $res[$key];
                    $tocache[$uid]->setFlags(array_keys(array_flip($flags)));
                }
            } else {
                foreach ($uids as $uid) {
                    $tocache[$uid] = $res[$key];
                    $tocache[$uid]->setFlags($options['replace']);
                }
            }

            if (!empty($tocache)) {
                $this->_updateCache($tocache, array(
                    'fields' => array(
                        Horde_Imap_Client::FETCH_FLAGS
                    )
                ));
            }
        }

        return $this->_temp['modified'];
    }

    /**
     */
    protected function _copy($dest, $options)
    {
        $this->_temp['copyuid'] = $this->_temp['trycreate'] = null;
        $this->_temp['uidplusmbox'] = $dest;

        $seq = $options['ids']->all
            ? '1:*'
            : ($options['ids']->search_res ? '$' : strval($options['ids']));

        // COPY returns no untagged information (RFC 3501 [6.4.7])
        try {
            $this->_sendLine(array(
                ($options['ids']->sequence ? null : 'UID'),
                'COPY',
                $seq,
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $dest)
            ));
        } catch (Horde_Imap_Client_Exception $e) {
            if (!empty($options['create']) && $this->_temp['trycreate']) {
                $this->createMailbox($dest);
                unset($options['create']);
                return $this->_copy($dest, $options);
            }
            throw $e;
        }

        // If moving, delete the old messages now.
        if (!empty($options['move'])) {
            $opts = array('ids' => $options['ids']);
            $this->store($this->_selected, array_merge(array('add' => array('\\deleted')), $opts));
            $this->expunge($this->_selected, $opts);
        }

        /* UIDPLUS (RFC 4315) allows easy determination of the UID of the
         * copied messages. If UID not returned, then destination mailbox
         * does not support persistent UIDs.
         * @todo Use UIDPLUS information to move cached data to new
         * mailbox (see RFC 4549 [4.2.2.1]). */
        return is_null($this->_temp['copyuid'])
            ? true
            : $this->_temp['copyuid'];
    }

    /**
     */
    protected function _setQuota($root, $options)
    {
        $this->login();

        $limits = array();
        if (isset($options['messages'])) {
            $limits[] = 'MESSAGE';
            $limits[] = array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $options['messages']);
        }
        if (isset($options['storage'])) {
            $limits[] = 'STORAGE';
            $limits[] = array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $options['storage']);
        }

        $this->_sendLine(array(
            'SETQUOTA',
            array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $root),
            $limits
        ));
    }

    /**
     */
    protected function _getQuota($root)
    {
        $this->login();

        $this->_temp['quotaresp'] = array();
        $this->_sendLine(array(
            'GETQUOTA',
            array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $root)
        ));
        return reset($this->_temp['quotaresp']);
    }

    /**
     * Parse a QUOTA response (RFC 2087 [5.1]).
     *
     * @param array $data  The server response.
     */
    protected function _parseQuota($data)
    {
        $c = &$this->_temp['quotaresp'];

        $root = $data[0];
        $c[$root] = array();

        for ($i = 0, $len = count($data[1]); $i < $len; $i += 3) {
            if (in_array($data[1][$i], array('MESSAGE', 'STORAGE'))) {
                $c[$root][strtolower($data[1][$i])] = array('limit' => $data[1][$i + 2], 'usage' => $data[1][$i + 1]);

            }
        }
    }

    /**
     */
    protected function _getQuotaRoot($mailbox)
    {
        $this->login();

        $this->_temp['quotaresp'] = array();
        $this->_sendLine(array(
            'GETQUOTAROOT',
            array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $mailbox)
        ));
        return $this->_temp['quotaresp'];
    }

    /**
     */
    protected function _setACL($mailbox, $identifier, $options)
    {
        $this->login();

        // SETACL/DELETEACL returns no untagged information (RFC 4314 [3.1 &
        // 3.2]).
        if (empty($options['rights']) && !empty($options['remove'])) {
            $this->_sendLine(array(
                'DELETEACL',
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox),
                array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $identifier)
            ));
        } else {
            $this->_sendLine(array(
                'SETACL',
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox),
                array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $identifier),
                array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $options['rights'])
            ));
        }
    }

    /**
     */
    protected function _getACL($mailbox)
    {
        $this->login();

        $this->_temp['getacl'] = array();
        $this->_sendLine(array(
            'GETACL',
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox)
        ));
        return $this->_temp['getacl'];
    }

    /**
     * Parse an ACL response (RFC 4314 [3.6]).
     *
     * @param array $data  The server response.
     */
    protected function _parseACL($data)
    {
        $acl = &$this->_temp['getacl'];

        // Ignore mailbox argument -> index 1
        for ($i = 1, $len = count($data); $i < $len; $i += 2) {
            $acl[$data[$i]] = str_split($data[$i + 1]);
        }
    }

    /**
     */
    protected function _listACLRights($mailbox, $identifier)
    {
        $this->login();

        $this->_temp['listaclrights'] = array();
        $this->_sendLine(array(
            'LISTRIGHTS',
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox),
            array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $identifier)
        ));
        return $this->_temp['listaclrights'];
    }

    /**
     * Parse a LISTRIGHTS response (RFC 4314 [3.7]).
     *
     * @param array $data  The server response.
     */
    protected function _parseListRights($data)
    {
        // Ignore mailbox and identifier arguments
        $this->_temp['listaclrights'] = array(
            'required' => str_split($data[2]),
            'optional' => array_slice($data, 3)
        );
    }

    /**
     */
    protected function _getMyACLRights($mailbox)
    {
        $this->login();

        $this->_temp['myrights'] = array();
        $this->_sendLine(array(
            'MYRIGHTS',
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox)
        ));
        return $this->_temp['myrights'];
    }

    /**
     * Parse a MYRIGHTS response (RFC 4314 [3.8]).
     *
     * @param array $data  The server response.
     */
    protected function _parseMyRights($data)
    {
        $this->_temp['myrights'] = $data[1];
    }

    /**
     */
    protected function _getMetadata($mailbox, $entries, $options)
    {
        $this->login();

        $this->_temp['metadata'] = array();
        $queries = array();

        if ($this->queryCapability('METADATA') ||
            ((strlen($mailbox) == 0) &&
             $this->queryCapability('METADATA-SERVER'))) {
            $cmd_options = array();

            if (!empty($options['maxsize'])) {
                $cmd_options[] = array(
                    'MAXSIZE',
                    array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $options['maxsize'])
                );
            }
            if (!empty($options['depth'])) {
                $cmd_options[] = array(
                    'DEPTH',
                    array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $options['depth'])
                );
            }

            foreach ($entries as $md_entry) {
                $queries[] = array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $md_entry);
            }

            $this->_sendLine(array(
                'GETMETADATA',
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox),
                (empty($cmd_options) ? null : $cmd_options),
                $queries
            ));

            return $this->_temp['metadata'];
        }

        if (!$this->queryCapability('ANNOTATEMORE') &&
            !$this->queryCapability('ANNOTATEMORE2')) {
            $this->_exception('Server does not support the METADATA extension.', 'NOSUPPORTIMAPEXT');
        }

        $queries = array();
        foreach ($entries as $md_entry) {
            list($entry, $type) = $this->_getAnnotateMoreEntry($md_entry);

            if (!isset($queries[$type])) {
                $queries[$type] = array();
            }
            $queries[$type][] = array('t' => Horde_Imap_Client::DATA_STRING, 'v' => $entry);
        }

        $result = array();
        foreach ($queries as $key => $val) {
            // TODO: Honor maxsize and depth options.
            $this->_sendLine(array(
                'GETANNOTATION',
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox),
                $val,
                array('t' => Horde_Imap_Client::DATA_STRING, 'v' => $key)
            ));

            $result = array_merge($result, $this->_temp['metadata']);
        }

        return $result;
    }

    /**
     * Split a name for the METADATA extension into the correct syntax for the
     * older ANNOTATEMORE version.
     *
     * @param string $name  A name for a metadata entry.
     *
     * @return array  A list of two elements: The entry name and the value
     *                type.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getAnnotateMoreEntry($name)
    {
        if (substr($name, 0, 7) == '/shared') {
            return array(substr($name, 7), 'value.shared');
        } else if (substr($name, 0, 8) == '/private') {
            return array(substr($name, 8), 'value.priv');
        }

        $this->_exception('Invalid METADATA entry: ' . $name);
    }

    /**
     */
    protected function _setMetadata($mailbox, $data)
    {
        if ($this->queryCapability('METADATA') ||
            ((strlen($mailbox) == 0) &&
             $this->queryCapability('METADATA-SERVER'))) {
            $data_elts = array();

            foreach ($data as $key => $value) {
                $data_elts[] = array(
                    't' => Horde_Imap_Client::DATA_ASTRING,
                    'v' => $key
                );
                $data_elts[] = array(
                    't' => Horde_Imap_Client::DATA_NSTRING,
                    'v' => $value
                );
            }

            $this->_sendLine(array(
                'SETMETADATA',
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox),
                $data_elts
            ));

            return;
        }

        if (!$this->queryCapability('ANNOTATEMORE') &&
            !$this->queryCapability('ANNOTATEMORE2')) {
            $this->_exception('Server does not support the METADATA extension.', 'NOSUPPORTIMAPEXT');
        }

        foreach ($data as $md_entry => $value) {
            list($entry, $type) = $this->_getAnnotateMoreEntry($md_entry);

            $this->_sendLine(array(
                'SETANNOTATION',
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox),
                array('t' => Horde_Imap_Client::DATA_STRING, 'v' => $entry),
                array(
                    array('t' => Horde_Imap_Client::DATA_STRING, 'v' => $type),
                    array('t' => Horde_Imap_Client::DATA_NSTRING, 'v' => $value)
                )
            ));
        }
    }

    /**
     * Parse a METADATA response (RFC 5464 [4.4]).
     *
     * @param array $data  The server response.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _parseMetadata($data)
    {
        switch ($data[0]) {
        case 'ANNOTATION':
            $values = $data[3];
            while (!empty($values)) {
                $type = array_shift($values);
                switch ($type) {
                case 'value.priv':
                    $this->_temp['metadata'][$data[1]]['/private' . $data[2]] = array_shift($values);
                    break;

                case 'value.shared':
                    $this->_temp['metadata'][$data[1]]['/shared' . $data[2]] = array_shift($values);
                    break;

                default:
                    $this->_exception('Invalid METADATA value type ' . $type);
                }
            }
            break;

        case 'METADATA':
            $values = $data[2];
            while (!empty($values)) {
                $entry = array_shift($values);
                $this->_temp['metadata'][$data[1]][$entry] = array_shift($values);
            }
            break;
        }
    }

    /* Internal functions. */

    /**
     * Perform a command on the IMAP server. A connection to the server must
     * have already been made.
     *
     * @todo RFC 3501 allows the sending of multiple commands at once. For
     *       simplicity of implementation at this time, we will execute
     *       commands one at a time. This allows us to easily determine data
     *       meant for a command while scanning for untagged responses
     *       unilaterally sent by the server.
     *
     * @param mixed $data    The IMAP command to execute. If string output as
     *                       is. If array, parsed via parseCommandArray(). If
     *                       resource, output directly to server.
     * @param array $options  Additional options:
     * <pre>
     * 'binary' - (boolean) Does $data contain binary data?  If so, and the
     *            'BINARY' extension is available on the server, the data
     *            will be sent in literal8 format. If not available, an
     *            exception will be returned. 'binary' requires literal to
     *            be defined.
     *            DEFAULT: Sends literals in a non-binary compliant method.
     * 'debug' - (string) When debugging, send this string instead of the
     *           actual command/data sent.
     *           DEFAULT: Raw data output to debug stream.
     * 'errignore' - (boolean) Don't throw error on BAD/NO response.
     *               DEFAULT: false
     * 'fetch' - (array) Use this as the initial value of the fetch response.
     *           DEFAULT: Fetch response is empty
     * 'literal' - (integer) Send the command followed by a literal. The value
     *             of 'literal' is the length of the literal data.
     *             Will attempt to use LITERAL+ capability if possible.
     *             DEFAULT: Do not send literal
     * 'literaldata' - (boolean) Is this literal data?
     *                 DEFAULT: Not literal data
     * 'noparse' - (boolean) Don't parse the response and instead return the
     *             server response.
     *             DEFAULT: Parses the response
     * 'notag' - (boolean) Don't prepend an IMAP tag (i.e. for a continuation
     *           response).
     *           DEFAULT: false
     * </pre>
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _sendLine($data, $options = array())
    {
        $out = '';

        if (empty($options['notag'])) {
            $out = ++$this->_tag . ' ';

            /* Catch all FETCH responses until a tagged response. */
            $this->_temp['fetchresp'] = empty($options['fetch'])
                ? array('seq' => array(), 'uid' => array())
                : $options['fetch'];
        }

        if (is_array($data)) {
            if (!empty($options['debug'])) {
                $this->_temp['sendnodebug'] = true;
            }
            $out = rtrim($this->parseCommandArray($data, array($this, 'parseCommandArrayCallback'), $out));
            unset($this->_temp['sendnodebug']);
        } elseif (is_string($data)) {
            $out .= $data;
        }

        $continuation = $literalplus = false;

        if (!empty($options['literal'])) {
            $out .= ' ';

            /* RFC 2088 - If LITERAL+ is available, saves a roundtrip from
             * the server. */
            $literalplus = $this->queryCapability('LITERAL+');

            /* RFC 3516 - Send literal8 if we have binary data. */
            if (!empty($options['binary'])) {
                if (!$this->queryCapability('BINARY')) {
                    $this->_exception('Can not send binary data to server that does not support it.', 'NOSUPPORTIMAPEXT');
                }
                $out .= '~';
            }

            $out .= '{' . $options['literal'] . ($literalplus ? '+' : '') . '}';
        }

        if ($this->_debug && empty($this->_temp['sendnodebug'])) {
            fwrite($this->_debug, '(' . str_pad(microtime(true), 15, 0) . ') C: ');
            if (is_resource($data)) {
                if (empty($this->_params['debug_literal'])) {
                    fseek($data, 0, SEEK_END);
                    fwrite($this->_debug, '[LITERAL DATA - ' . ftell($data) . ' bytes]' . "\n");
                } else {
                    rewind($data);
                    while ($in = fread($data, 8192)) {
                        fwrite($this->_debug, $in);
                    }
                }
            } else {
                fwrite($this->_debug, (empty($options['debug']) ? $out : $options['debug']) . "\n");
            }
        }

        if (is_resource($data)) {
            rewind($data);
            stream_copy_to_stream($data, $this->_stream);
        } else {
            fwrite($this->_stream, $out);
            if (empty($options['literaldata'])) {
                fwrite($this->_stream, "\r\n");
            }
        }

        if ($literalplus || !empty($options['literaldata'])) {
            return;
        }

        if (!empty($options['literal'])) {
            $ob = $this->_getLine();
            if ($ob['type'] != 'continuation') {
                $this->_exception('Unexpected response from IMAP server while waiting for a continuation request: ' . $ob['line']);
            }
        } elseif (empty($options['noparse'])) {
            $this->_parseResponse($this->_tag, !empty($options['errignore']));
        } else {
            return $this->_getLine();
        }
    }

    /**
     * Callback for parseCommandArray() when literal data is found.
     *
     * @param string $cmd  The unprocessed command string.
     * @param mixed $data  The literal data (either a string or a resource).
     *
     * @return string  The new unprocessed command string.
     */
    public function parseCommandArrayCallback($cmd, $data)
    {
        /* RFC 3516/4466 says we should be able to append binary data
         * using literal8 "~{#} format", but it doesn't seem to work in
         * all servers tried (UW-IMAP/Cyrus). However, there is no other
         * way to append null data, so try anyway. */
        if (is_string($data)) {
            $binary = (strpos($data, "\0") !== false);
            $len = strlen($data);
        } else {
            $binary = false;
            rewind($data);
            while (($in = fread($data, 8192))) {
                if (strpos($in, "\0") !== false) {
                    $binary = true;
                    break;
                }
            }
            fseek($data, 0, SEEK_END);
            $len = ftell($data);
        }

        $this->_sendLine(rtrim($cmd), array(
            'binary' => $binary,
            'literal' => $len,
            'notag' => true
        ));

        $this->_sendLine($data, array(
            'literaldata' => true,
            'notag' => true
        ));

        return '';
    }

    /**
     * Gets data from the IMAP stream and parses it.
     *
     * @return array  An array with the following keys:
     * <pre>
     * 'line' - (string) The server response text (set for all but an untagged
     *          response with no response code).
     * 'response' - (string) Either 'OK', 'NO', 'BAD', 'PREAUTH', or ''.
     * 'tag' - (string) If tagged response, the tag string.
     * 'token' - (array) The tokenized response (set if an untagged response
     *           with no response code).
     * 'type' - (string) Either 'tagged', 'untagged', or 'continuation'.
     * </pre>
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getLine()
    {
        $ob = array('line' => '', 'response' => '', 'tag' => '', 'token' => '');

        $read = explode(' ', $this->_readData(), 3);

        switch ($read[0]) {
        /* Continuation response. */
        case '+':
            $ob['line'] = implode(' ', array_slice($read, 1));
            $ob['type'] = 'continuation';
            break;

        /* Untagged response. */
        case '*':
            $ob['type'] = 'untagged';

            $read[1] = strtoupper($read[1]);
            if ($read[1] == 'BYE') {
                if (!empty($this->_temp['logout'])) {
                    /* A BYE response received as part of a logout cmd should
                     * be treated like a regular command. A client MUST
                     * process the entire command until logging out. RFC 3501
                     * [3.4]. */
                    $ob['response'] = $read[1];
                    $ob['line'] = implode(' ', array_slice($read, 2));
                } else {
                    $this->_temp['logout'] = true;
                    $this->logout();
                    $this->_exception('IMAP Server closed the connection: ' . implode(' ', array_slice($read, 1)), 'DISCONNECT');
                }
            }

            if (in_array($read[1], array('OK', 'NO', 'BAD', 'PREAUTH'))) {
                $ob['response'] = $read[1];
                $ob['line'] = implode(' ', array_slice($read, 2));
            } else {
                /* Tokenize response. */
                $line = implode(' ', array_slice($read, 1));
                $binary = $literal = false;
                $this->_temp['literal8'] = array();

                do {
                    $literal_len = null;

                    if ($literal) {
                        $this->_temp['token']['ptr'][$this->_temp['token']['paren']][] = $line;
                    } else {
                        if (substr($line, -1) == '}') {
                            $pos = strrpos($line, '{');
                            $literal_len = substr($line, $pos + 1, -1);
                            if (is_numeric($literal_len)) {
                                // Check for literal8 response
                                if ($line[$pos - 1] == '~') {
                                    $binary = true;
                                    $line = substr($line, 0, $pos - 1);
                                    $this->_temp['literal8'][substr($line, strrpos($line, ' '))] = true;
                                } else {
                                    $line = substr($line, 0, $pos);
                                }
                            } else {
                                $literal_len = null;
                            }
                        }

                        $this->_tokenizeData($line);
                    }

                    if (is_null($literal_len)) {
                        if (!$literal) {
                            break;
                        }
                        $binary = $literal = false;
                        $line = $this->_readData();
                    } else {
                        $literal = true;
                        $line = $this->_readData($literal_len, $binary);
                    }
                } while (true);

                $ob['token'] = $this->_temp['token']['out'];
                $this->_temp['token'] = null;
            }
            break;

        /* Tagged response. */
        default:
            $ob['type'] = 'tagged';
            $ob['line'] = implode(' ', array_slice($read, 2));
            $ob['tag'] = $read[0];
            $ob['response'] = $read[1];
            break;
        }

        return $ob;
    }

    /**
     * Read data from stream.
     *
     * @param integer $len     The number of bytes to read. If not present,
     *                         reads a single line of data.
     * @param boolean $binary  Binary data?
     *
     * @return string  The data requested (stripped of trailing CRLF).
     * @throws Horde_Imap_Client_Exception
     */
    protected function _readData($len = null, $binary = false)
    {
        if (feof($this->_stream)) {
            $this->_temp['logout'] = true;
            $this->logout();
            if ($this->_debug) {
                fwrite($this->_debug, "[ERROR: IMAP server closed the connection.]\n");
            }
            $this->_exception('IMAP server closed the connection unexpectedly.', 'DISCONNECT');
        }

        $data = '';
        $got_data = $stream = false;

        if (is_null($len)) {
            do {
                /* Can't do a straight fgets() because extremely large lines
                 * will result in read errors. */
                if ($in = fgets($this->_stream, 8192)) {
                    $data .= $in;
                    $got_data = true;
                    if (!isset($in[8190]) || ($in[8190] == "\n")) {
                        break;
                    }
                }
            } while ($in !== false);
        } else {
            // Skip 0-length literal data
            if (!$len) {
                return $data;
            }

            $old_len = $len;

            // Add data to a stream, if we are doing a fetch.
            if (isset($this->_temp['fetchcmd'])) {
                $data = fopen('php://temp', 'r+');
                $stream = true;
            }

            while ($len && ($in = fread($this->_stream, min($len, 8192)))) {
                if ($stream) {
                    fwrite($data, $in);
                } else {
                    $data .= $in;
                }

                $got_data = true;

                $in_len = strlen($in);
                if ($in_len > $len) {
                    break;
                }
                $len -= $in_len;
            }
        }

        if (!$got_data) {
            if ($this->_debug) {
                fwrite($this->_debug, "[ERROR: IMAP read/timeout error.]\n");
            }
            $this->logout();
            $this->_exception('IMAP read error or IMAP connection timed out.', 'SERVER_READERROR');
        }

        if ($this->_debug) {
            fwrite($this->_debug, '(' . str_pad(microtime(true), 15, 0) . ') S: ');
            if ($binary) {
                fwrite($this->_debug, '[BINARY DATA - ' . $old_len . ' bytes]' . "\n");
            } elseif (!is_null($len) &&
                      empty($this->_params['debug_literal'])) {
                fwrite($this->_debug, '[LITERAL DATA - ' . $old_len . ' bytes]' . "\n");
            } elseif ($stream) {
                rewind($data);
                fwrite($this->_debug, rtrim(stream_get_contents($data)) . "\n");
            } else {
                fwrite($this->_debug, rtrim($data) . "\n");
            }
        }

        return is_null($len) ? rtrim($data) : $data;
    }

    /**
     * Tokenize IMAP data. Handles quoted strings and parantheses.
     *
     * @param string $line  The raw IMAP data.
     */
    protected function _tokenizeData($line)
    {
        if (empty($this->_temp['token'])) {
            $this->_temp['token'] = array(
                'in_quote' => false,
                'paren' => 0,
                'out' => array(),
                'ptr' => array()
            );
            $this->_temp['token']['ptr'][0] = &$this->_temp['token']['out'];
        }

        $c = &$this->_temp['token'];
        $tmp = '';

        for ($i = 0, $len = strlen($line); $i < $len; ++$i) {
            $char = $line[$i];
            switch ($char) {
            case '"':
                if ($c['in_quote']) {
                    if ($i && ($line[$i - 1] != '\\')) {
                        $c['in_quote'] = false;
                        $c['ptr'][$c['paren']][] = stripcslashes($tmp);
                        $tmp = '';
                    } else {
                        $tmp .= $char;
                    }
                } else {
                    $c['in_quote'] = true;
                }
                break;

            default:
                if ($c['in_quote']) {
                    $tmp .= $char;
                    break;
                }

                switch ($char) {
                case '(':
                    $c['ptr'][$c['paren']][] = array();
                    $c['ptr'][$c['paren'] + 1] = &$c['ptr'][$c['paren']][count($c['ptr'][$c['paren']]) - 1];
                    ++$c['paren'];
                    break;

                case ')':
                    if (strlen($tmp)) {
                        $c['ptr'][$c['paren']][] = $tmp;
                        $tmp = '';
                    }
                    --$c['paren'];
                    break;

                case ' ':
                    if (strlen($tmp)) {
                        $c['ptr'][$c['paren']][] = $tmp;
                        $tmp = '';
                    }
                    break;

                default:
                    $tmp .= $char;
                    break;
                }
                break;
            }
        }

        if (strlen($tmp)) {
            $c['ptr'][$c['paren']][] = $tmp;
        }
    }

    /**
     * Parse all untagged and tagged responses for a given command.
     *
     * @param string $tag      The IMAP tag of the current command.
     * @param boolean $ignore  If true, don't throw errors.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _parseResponse($tag, $ignore)
    {
        while ($ob = $this->_getLine()) {
            if (($ob['type'] == 'tagged') && ($ob['tag'] == $tag)) {
                // Here we know there isn't an untagged response, so directly
                // call _parseStatusResponse().
                $this->_parseStatusResponse($ob);

                // Now that any status response has been processed, we can
                // throw errors if appropriate.
                switch ($ob['response']) {
                case 'BAD':
                case 'NO':
                    if ($ignore) {
                        return;
                    }

                    if (empty($this->_temp['parsestatuserr'])) {
                        $errcode = 0;
                        $errstr = empty($ob['line']) ? '[No error message returned by server.]' : $ob['line'];
                    } else {
                        list($errcode, $errstr) = $this->_temp['parsestatuserr'];
                    }
                    $this->_temp['parseresperr'] = $ob;

                    if ($ob['response'] == 'BAD') {
                        $this->_exception('Bad IMAP request: ' . $errstr, $errcode);
                    }

                    $this->_exception('IMAP error: ' . $errstr, $errcode);
                }

                /* Update the cache, if needed. */
                $tmp = $this->_temp['fetchresp'];
                if (!empty($tmp['uid'])) {
                    $this->_updateCache($tmp['uid']);
                } elseif (!empty($tmp['seq'])) {
                    $this->_updateCache($tmp['seq'], array(
                        'seq' => true
                    ));
                }
                break;
            }
            $this->_parseServerResponse($ob);
        }
    }

    /**
     * Handle unilateral server responses - untagged data not returned from an
     * explicit server call (see RFC 3501 [2.2.2]).
     *
     * @param array  An array returned from self::_getLine().
     */
    protected function _parseServerResponse($ob)
    {
        if (!empty($ob['response'])) {
            $this->_parseStatusResponse($ob);
        } elseif ($ob['token']) {
            // First, catch all untagged responses where the name appears
            // first on the line.
            switch (strtoupper($ob['token'][0])) {
            case 'CAPABILITY':
                $this->_parseCapability(array_slice($ob['token'], 1));
                break;

            case 'LIST':
            case 'LSUB':
                $this->_parseList($ob['token'], 1);
                break;

            case 'STATUS':
                // Parse a STATUS response (RFC 3501 [7.2.4]).
                $this->_parseStatus($ob['token'][1], $ob['token'][2]);
                break;

            case 'SEARCH':
            case 'SORT':
                // Parse a SEARCH/SORT response (RFC 3501 [7.2.5] &
                // RFC 5256 [4]).
                $this->_parseSearch(array_slice($ob['token'], 1));
                break;

            case 'ESEARCH':
                // Parse an ESEARCH response (RFC 4466 [2.6.2]).
                $this->_parseEsearch(array_slice($ob['token'], 1));
                break;

            case 'FLAGS':
                $this->_temp['mailbox']['flags'] = array_map('strtolower', $ob['token'][1]);
                break;

            case 'QUOTA':
                $this->_parseQuota(array_slice($ob['token'], 1));
                break;

            case 'QUOTAROOT':
                // Ignore this line - we can get this information from
                // the untagged QUOTA responses.
                break;

            case 'NAMESPACE':
                $this->_parseNamespace(array_slice($ob['token'], 1));
                break;

            case 'THREAD':
                foreach (array_slice($ob['token'], 1) as $val) {
                    $this->_parseThread($val);
                }
                break;

            case 'ACL':
                $this->_parseACL(array_slice($ob['token'], 1));
                break;

            case 'LISTRIGHTS':
                $this->_parseListRights(array_slice($ob['token'], 1));
                break;

            case 'MYRIGHTS':
                $this->_parseMyRights(array_slice($ob['token'], 1));
                break;

            case 'ID':
                // ID extension (RFC 2971)
                $this->_parseID(array_slice($ob['token'], 1));
                break;

            case 'ENABLED':
                // ENABLE extension (RFC 5161)
                $this->_parseEnabled(array_slice($ob['token'], 1));
                break;

            case 'LANGUAGE':
                // LANGUAGE extension (RFC 5255 [3.2])
                $this->_parseLanguage(array_slice($ob['token'], 1));
                break;

            case 'COMPARATOR':
                // I18NLEVEL=2 extension (RFC 5255 [4.7])
                $this->_parseComparator(array_slice($ob['token'], 1));
                break;

            case 'VANISHED':
                // QRESYNC extension (RFC 5162 [3.6])
                $this->_parseVanished(array_slice($ob['token'], 1));
                break;

            case 'ANNOTATION':
            case 'METADATA':
                // Parse a ANNOTATEMORE/METADATA response.
                $this->_parseMetadata($ob['token']);
                break;

            default:
                // Next, look for responses where the keywords occur second.
                $type = strtoupper($ob['token'][1]);
                switch ($type) {
                case 'EXISTS':
                case 'RECENT':
                    // RECENT response - RFC 3501 [7.3.1]
                    // EXISTS response - RFC 3501 [7.3.2]
                    $this->_temp['mailbox'][$type == 'RECENT' ? 'recent' : 'messages'] = $ob['token'][0];
                    break;

                case 'EXPUNGE':
                    // EXPUNGE response - RFC 3501 [7.4.1]
                    $this->_parseExpunge($ob['token'][0]);
                    break;

                case 'FETCH':
                    // FETCH response - RFC 3501 [7.4.2]
                    $rest = array_slice($ob['token'], 2);
                    $this->_parseFetch($ob['token'][0], reset($rest));
                    break;
                }
                break;
            }
        }
    }

    /**
     * Handle status responses (see RFC 3501 [7.1]).
     *
     * @param array  An array returned from self::_getLine().
     */
    protected function _parseStatusResponse($ob)
    {
        if ($ob['line'][0] != '[') {
            return;
        }

        $pos = strpos($ob['line'], ' ', 2);
        $end_pos = strpos($ob['line'], ']', 2);
        if ($pos > $end_pos) {
            $code = strtoupper(substr($ob['line'], 1, $end_pos - 1));
            $data = trim(substr($ob['line'], $end_pos + 1));
        } else {
            $code = strtoupper(substr($ob['line'], 1, $pos - 1));
            $data = substr($ob['line'], $pos + 1, $end_pos - $pos - 1);
        }

        $this->_temp['parsestatuserr'] = null;

        switch ($code) {
        case 'ALERT':
        // Defined by RFC 5530 [3] - Treat as an alert for now.
        case 'CONTACTADMIN':
            if (!isset($this->_temp['alerts'])) {
                $this->_temp['alerts'] = array();
            }
            $this->_temp['alerts'][] = $data;
            break;

        case 'BADCHARSET':
            /* @todo Store the list of search charsets supported by the server
             * (this is a MAY response, not a MUST response) */
            $this->_temp['parsestatuserr'] = array(
                'BADCHARSET',
                substr($ob['line'], $end_pos + 2)
            );
            break;

        case 'CAPABILITY':
            $this->_tokenizeData($data);
            $this->_parseCapability($this->_temp['token']['out']);
            $this->_temp['token'] = null;
            break;

        case 'PARSE':
            $this->_temp['parsestatuserr'] = array(
                'PARSEERROR',
                substr($ob['line'], $end_pos + 2)
            );
            break;

        case 'READ-ONLY':
        case 'READ-WRITE':
            // Ignore - openMailbox() takes care of this for us
            break;

        case 'TRYCREATE':
            // RFC 3501 [7.1]
            $this->_temp['trycreate'] = true;
            break;

        case 'PERMANENTFLAGS':
            $this->_tokenizeData($data);
            $this->_temp['mailbox']['permflags'] = array_map('strtolower', reset($this->_temp['token']['out']));
            $this->_temp['token'] = null;
            break;

        case 'UIDNEXT':
        case 'UIDVALIDITY':
            $this->_temp['mailbox'][strtolower($code)] = $data;
            break;

        case 'UNSEEN':
            /* This is different from the STATUS UNSEEN response - this item,
             * if defined, returns the first UNSEEN message in the mailbox. */
            $this->_temp['mailbox']['firstunseen'] = $data;
            break;

        case 'REFERRAL':
            // Defined by RFC 2221
            $this->_temp['referral'] = $this->utils->parseUrl($data);
            break;

        case 'UNKNOWN-CTE':
            // Defined by RFC 3516
            $this->_temp['parsestatuserr'] = array(
                'UNKNOWNCTE',
                substr($ob['line'], $end_pos + 2)
            );
            break;

        case 'APPENDUID':
        case 'COPYUID':
            // Defined by RFC 4315
            // APPENDUID: [0] = UIDVALIDITY, [1] = UID(s)
            // COPYUID: [0] = UIDVALIDITY, [1] = UIDFROM, [2] = UIDTO
            $parts = explode(' ', $data);

            if (($this->_selected == $this->_temp['uidplusmbox']) &&
                ($this->_temp['mailbox']['uidvalidity'] != $parts[0])) {
                $this->_temp['mailbox'] = array('uidvalidity' => $parts[0]);
                $this->_temp['searchnotsaved'] = true;
            }

            /* Check for cache expiration (see RFC 4549 [4.1]). */
            $this->_updateCache(array(), array(
                'mailbox' => $this->_temp['uidplusmbox'],
                'uidvalid' => $parts[0]
            ));

            if ($code == 'APPENDUID') {
                $this->_temp['appenduid'] = array_merge($this->_temp['appenduid'], $this->utils->fromSequenceString($parts[1]));
            } else {
                $this->_temp['copyuid'] = array_combine($this->utils->fromSequenceString($parts[1]), $this->utils->fromSequenceString($parts[2]));
            }
            break;

        case 'UIDNOTSTICKY':
            // Defined by RFC 4315 [3]
            $this->_temp['mailbox']['uidnotsticky'] = true;
            break;

        case 'BADURL':
            // Defined by RFC 4469 [4.1]
            $this->_temp['parsestatuserr'] = array(
                'CATENATE_BADURL',
                substr($ob['line'], $end_pos + 2)
            );
            break;

        case 'TOOBIG':
            // Defined by RFC 4469 [4.2]
            $this->_temp['parsestatuserr'] = array(
                'CATENATE_TOOBIG',
                substr($ob['line'], $end_pos + 2)
            );
            break;

        case 'HIGHESTMODSEQ':
            // Defined by RFC 4551 [3.1.1]
            $this->_temp['mailbox']['highestmodseq'] = $data;
            break;

        case 'NOMODSEQ':
            // Defined by RFC 4551 [3.1.2]
            $this->_temp['mailbox']['highestmodseq'] = 0;

            // Delete cache for mailbox, if it exists.
            if ($this->_initCache()) {
                $this->cache->deleteMailbox($this->_selected);
            }
            break;

        case 'MODIFIED':
            // Defined by RFC 4551 [3.2]
            $this->_temp['modified'] = new Horde_Imap_Client_Ids($data);
            break;

        case 'CLOSED':
            // Defined by RFC 5162 [3.7]
            if (isset($this->_temp['qresyncmbox'])) {
                $this->_temp['mailbox'] = array('name' => $this->_temp['qresyncmbox']);
                $this->_selected = $this->_temp['qresyncmbox'];
            }
            break;

        case 'NOTSAVED':
            // Defined by RFC 5182 [2.5]
            $this->_temp['searchnotsaved'] = true;
            break;

        case 'BADCOMPARATOR':
            // Defined by RFC 5255 [4.9]
            $this->_temp['parsestatuserr'] = array(
                'BADCOMPARATOR',
                substr($ob['line'], $end_pos + 2)
            );
            break;

        case 'UNAVAILABLE':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = 'LOGIN_UNAVAILABLE';
            break;

        case 'AUTHENTICATIONFAILED':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = 'LOGIN_AUTHENTICATIONFAILED';
            break;

        case 'AUTHORIZATIONFAILED':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = 'LOGIN_AUTHORIZATIONFAILED';
            break;

        case 'EXPIRED':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = 'LOGIN_EXPIRED';
            break;

        case 'PRIVACYREQUIRED':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = 'LOGIN_PRIVACYREQUIRED';
            break;

        case 'NOPERM':
            // Defined by RFC 5530 [3]
            break;

        case 'INUSE':
            // Defined by RFC 5530 [3]
            break;

        case 'EXPUNGEISSUED':
            // Defined by RFC 5530 [3]
            break;

        case 'CORRUPTION':
            // Defined by RFC 5530 [3]
            break;

        case 'SERVERBUG':
        case 'CLIENTBUG':
        case 'CANNOT':
            // Defined by RFC 5530 [3]
            if ($this->_debug) {
                fwrite($this->_debug, "*** Problem with IMAP command. ***\n");
            }
            break;

        case 'LIMIT':
            // Defined by RFC 5530 [3]
            break;

        case 'OVERQUOTA':
            // Defined by RFC 5530 [3]
            break;

        case 'ALREADYEXISTS':
            // Defined by RFC 5530 [3]
            break;

        case 'NONEXISTENT':
            // Defined by RFC 5530 [3]
            break;

        case 'XPROXYREUSE':
            // The proxy connection was reused, so no need to do login tasks.
            $this->_temp['proxyreuse'] = true;
            break;

        default:
            // Unknown response codes SHOULD be ignored - RFC 3501 [7.1]
            break;
        }
    }

}

<?php
/**
 * An interface to an IMAP4rev1 server (RFC 3501) using built-in PHP features.
 *
 * Implements the following IMAP-related RFCs (see
 * http://www.iana.org/assignments/imap4-capabilities):
 *   - RFC 2086/4314: ACL
 *   - RFC 2087: QUOTA
 *   - RFC 2088: LITERAL+
 *   - RFC 2195: AUTH=CRAM-MD5
 *   - RFC 2221: LOGIN-REFERRALS
 *   - RFC 2342: NAMESPACE
 *   - RFC 2595/4616: TLS & AUTH=PLAIN
 *   - RFC 2831: DIGEST-MD5 authentication mechanism (obsoleted by RFC 6331)
 *   - RFC 2971: ID
 *   - RFC 3348: CHILDREN
 *   - RFC 3501: IMAP4rev1 specification
 *   - RFC 3502: MULTIAPPEND
 *   - RFC 3516: BINARY
 *   - RFC 3691: UNSELECT
 *   - RFC 4315: UIDPLUS
 *   - RFC 4422: SASL Authentication (for DIGEST-MD5)
 *   - RFC 4466: Collected extensions (updates RFCs 2088, 3501, 3502, 3516)
 *   - RFC 4469/5550: CATENATE
 *   - RFC 4551: CONDSTORE
 *   - RFC 4731: ESEARCH
 *   - RFC 4959: SASL-IR
 *   - RFC 5032: WITHIN
 *   - RFC 5161: ENABLE
 *   - RFC 5162: QRESYNC
 *   - RFC 5182: SEARCHRES
 *   - RFC 5255: LANGUAGE/I18NLEVEL
 *   - RFC 5256: THREAD/SORT
 *   - RFC 5258: LIST-EXTENDED
 *   - RFC 5267: ESORT; PARTIAL search return option
 *   - RFC 5464: METADATA
 *   - RFC 5530: IMAP Response Codes
 *   - RFC 5819: LIST-STATUS
 *   - RFC 5957: SORT=DISPLAY
 *   - RFC 6154: SPECIAL-USE/CREATE-SPECIAL-USE
 *   - RFC 6203: SEARCH=FUZZY
 *
 * Implements the following non-RFC extensions:
 * <ul>
 *  <li>draft-ietf-morg-inthread-01: THREAD=REFS</li>
 *  <li>XIMAPPROXY
 *   <ul>
 *    <li>Requires imapproxy v1.2.7-rc1 or later</li>
 *    <li>
 *     See http://lists.andrew.cmu.edu/pipermail/imapproxy-info/2008-October/000771.html and
 *     http://lists.andrew.cmu.edu/pipermail/imapproxy-info/2008-October/000772.html
 *    </li>
 *   </ul>
 *  </li>
 * </ul>
 *
 * TODO (or not necessary?):
 * <ul>
 *  <li>RFC 2177: IDLE
 *   <ul>
 *    <li>
 *     Probably not necessary due to the limited connection time of each
 *     HTTP/PHP request
 *    </li>
 *   </ul>
 *  <li>RFC 2193: MAILBOX-REFERRALS</li>
 *  <li>
 *   RFC 4467/5092/5524/5550/5593: URLAUTH, URLAUTH=BINARY, URL-PARTIAL
 *  </li>
 *  <li>RFC 4978: COMPRESS=DEFLATE
 *   <ul>
 *    <li>See: http://bugs.php.net/bug.php?id=48725</li>
 *   </ul>
 *  </li>
 *  <li>RFC 5257: ANNOTATE (Experimental)</li>
 *  <li>RFC 5259: CONVERT</li>
 *  <li>RFC 5267: CONTEXT=SEARCH; CONTEXT=SORT</li>
 *  <li>RFC 5465: NOTIFY</li>
 *  <li>RFC 5466: FILTERS</li>
 *  <li>RFC 5738: UTF8 (Very limited support currently)</li>
 *  <li>RFC 6237: MULTISEARCH</li>
 *  <li>draft-ietf-morg-inthread-01: SEARCH=INTHREAD
 *   <ul>
 *    <li>Appears to be dead</li>
 *   </ul>
 *  </li>
 *  <li>draft-krecicki-imap-move-01.txt: MOVE
 *   <ul>
 *    <li>Appears to be dead</li>
 *   </ul>
 *  </li>
 * </ul>
 *
 * Originally based on code from:
 *   - auth.php (1.49)
 *   - imap_general.php (1.212)
 *   - imap_messages.php (revision 13038)
 *   - strings.php (1.184.2.35)
 * from the Squirrelmail project.
 * Copyright (c) 1999-2007 The SquirrelMail Project Team
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
     *                       Additional parameters to base driver:
     *   - debug_literal: (boolean) If true, will output the raw text of
     *                    literal responses to the debug stream. Otherwise,
     *                    outputs a summary of the literal response.
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

        return isset($this->_init['capability'])
            ? $this->_init['capability']
            : array();
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

        /* RFC 5162 [1] - QRESYNC implies CONDSTORE and ENABLE, even if they
         * are not listed as capabilities. */
        if (isset($c['QRESYNC'])) {
            $c['CONDSTORE'] = true;
            $c['ENABLE'] = true;
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
        if (!$this->queryCapability('NAMESPACE')) {
            return array();
        }

        $this->_sendLine('NAMESPACE');
        return $this->_temp['namespace'];
    }

    /**
     * Parse a NAMESPACE response (RFC 2342 [5] & RFC 5255 [3.4]).
     *
     * @param array $data  The NAMESPACE data.
     */
    protected function _parseNamespace($data)
    {
        $namespace_array = array(
            Horde_Imap_Client::NS_PERSONAL,
            Horde_Imap_Client::NS_OTHER,
            Horde_Imap_Client::NS_SHARED
        );

        $c = &$this->_temp['namespace'];
        $c = array();

        // Per RFC 2342, response from NAMESPACE command is:
        // (PERSONAL NAMESPACES) (OTHER_USERS NAMESPACE) (SHARED NAMESPACES)
        foreach ($namespace_array as $i => $val) {
            if (($entry = $this->_getString($data[$i], true)) === null) {
                continue;
            }
            reset($data[$i]);
            while (list(,$v) = each($data[$i])) {
                $ob = Horde_Imap_Client_Mailbox::get($this->_getString($v[0]), true);

                $c[strval($ob)] = array(
                    'delimiter' => $v[1],
                    'hidden' => false,
                    'name' => strval($ob),
                    'translation' => '',
                    'type' => $val
                );

                // RFC 4466: NAMESPACE extensions
                for ($j = 2; isset($v[$j]); $j += 2) {
                    switch (strtoupper($v[$j])) {
                    case 'TRANSLATION':
                        // RFC 5255 [3.4] - TRANSLATION extension
                        $c[strval($ob)]['translation'] = reset($v[$j + 1]);
                        break;
                    }
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
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Server does not support TLS connections."),
                    Horde_Imap_Client_Exception::LOGIN_TLSFAILURE
                );
            }

            // Switch over to a TLS connection.
            // STARTTLS returns no untagged response.
            $this->_sendLine('STARTTLS');

            if (@stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                $this->logout();
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Could not open secure TLS connection to the IMAP server."),
                    Horde_Imap_Client_Exception::LOGIN_TLSFAILURE
                );
            }

            if ($first_login) {
                // Expire cached CAPABILITY information (RFC 3501 [6.2.1])
                $this->_setInit('capability');

                // Reset language (RFC 5255 [3.1])
                $this->_setInit('lang');
            }

            // Set language if using imapproxy
            if (!empty($this->_init['imapproxy'])) {
                $this->setLanguage();
            }

            $this->_isSecure = true;
        }

        if ($first_login) {
            $imap_auth_mech = array();

            $auth_methods = $this->queryCapability('AUTH');
            if (!empty($auth_methods)) {
                // Add SASL methods. Prefer CRAM-MD5 over DIGEST-MD5, as the
                // latter has been obsoleted (RFC 6331).
                $imap_auth_mech = array_intersect(array('CRAM-MD5', 'DIGEST-MD5'), $auth_methods);

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
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("No supported IMAP authentication method could be found."),
                    Horde_Imap_Client_Exception::LOGIN_NOAUTHMETHOD
                );
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
        $t['loginerr'] = new Horde_Imap_Client_Exception(
            Horde_Imap_Client_Translation::t("Mail server denied authentication."),
            Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED
        );

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

        $ex = $t['loginerr'];

        /* Try again from scratch if authentication failed in an established,
         * previously-authenticated object. */
        if (!empty($this->_init['authmethod'])) {
            $this->_setInit('authmethod');
            try {
                return $this->login();
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        throw $ex;
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
            throw new InvalidArgumentException('Secure connections require the PHP openssl extension.');
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
            $e = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Error connecting to mail server."),
                Horde_Imap_Client_Exception::SERVER_CONNECT
            );
            $e->details = sprintf("[%u] %s", $error_number, $error_string);
            throw $e;
        }

        stream_set_timeout($this->_stream, $this->_params['timeout']);

        // If we already have capability information, don't re-set with
        // (possibly) limited information sent in the inital banner.
        if (isset($this->_init['capability'])) {
            $this->_temp['no_cap'] = true;
        }

        // Get greeting information.  This is untagged so we need to specially
        // deal with it here.  A BYE response will be caught and thrown in
        // _getLine().
        $ob = $this->_getLine();
        switch ($ob['response']) {
        case 'BAD':
            // Server is rejecting our connection.
            throw new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("Server rejected connection."),
                Horde_Imap_Client_Exception::SERVER_CONNECT,
                'BAD',
                $ob['line']
            );

        case 'PREAUTH':
            // The user was pre-authenticated.
            $this->_temp['preauth'] = true;
            break;
        }
        $this->_parseServerResponse($ob);

        // Check for IMAP4rev1 support
        if (!$this->queryCapability('IMAP4REV1')) {
            throw new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("The mail server does not support IMAP4rev1 (RFC 3501)."),
                Horde_Imap_Client_Exception::SERVER_CONNECT
            );
        }

        // Set language if NOT using imapproxy
        if (empty($this->_init['imapproxy'])) {
            if ($this->queryCapability('XIMAPPROXY')) {
                $this->_setInit('imapproxy', true);
            } else {
                $this->setLanguage();
            }
        }

        // If pre-authenticated, we need to do all login tasks now.
        if (!empty($this->_temp['preauth'])) {
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
        case 'CRAM-SHA1':
        case 'CRAM-SHA256':
            // RFC 2195: CRAM-MD5
            // CRAM-SHA1 & CRAM-SHA256 supported by Courier SASL library
            $ob = $this->_sendLine(array(
                'AUTHENTICATE',
                array('t' => Horde_Imap_Client::DATA_ATOM, 'v' => $method)
            ), array(
                'noparse' => true
            ));

            $response = base64_encode($this->_params['username'] . ' ' . hash_hmac(strtolower(substr($method, 5)), $this->getParam('password'), base64_decode($ob['line']), true));
            $this->_sendLine($response, array(
                'debug' => '[' . $method . ' Response]',
                'notag' => true
            ));
            break;

        case 'DIGEST-MD5':
            // RFC 2831/4422; obsoleted by RFC 6331
            $ob = $this->_sendLine(array(
                'AUTHENTICATE',
                array('t' => Horde_Imap_Client::DATA_ATOM, 'v' => $method)
            ), array(
                'noparse' => true
            ));

            $response = base64_encode(new Horde_Imap_Client_Auth_DigestMD5(
                $this->_params['username'],
                $this->getParam('password'),
                base64_decode($ob['line']),
                $this->_params['hostspec'],
                'imap'
            ));
            $ob = $this->_sendLine($response, array(
                'debug' => '[DIGEST-MD5 Response]',
                'noparse' => true,
                'notag' => true
            ));
            $response = base64_decode($ob['line']);
            if (strpos($response, 'rspauth=') === false) {
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Unexpected response from server when authenticating."),
                    Horde_Imap_Client_Exception::SERVER_CONNECT
                );
            }
            $this->_sendLine('', array(
                'notag' => true
            ));
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

        default:
            throw new Horde_Imap_Client_Exception(
                sprintf(Horde_Imap_Client_Translation::t("Unknown authentication method: %s"), $method),
                Horde_Imap_Client_Exception::SERVER_CONNECT
            );
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
                $this->_enable(array('QRESYNC'));
            } elseif ($this->queryCapability('CONDSTORE')) {
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
                try {
                    $this->_sendLine('LOGOUT');
                } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
                    // Ignore server errors
                }
            }
            unset($this->_temp['logout']);
            @fclose($this->_stream);
            $this->_stream = null;
        }

        unset($this->_temp['proxyreuse']);
    }

    /**
     */
    protected function _sendID($info)
    {
        $cmd = array('ID');

        if (empty($info)) {
            $cmd[] = array('t' => Horde_Imap_Client::DATA_NSTRING, 'v' => null);
        } else {
            $tmp = array();
            foreach ($info as $key => $val) {
                $tmp[] = array('t' => Horde_Imap_Client::DATA_STRING, 'v' => strtolower($key));
                $tmp[] = array('t' => Horde_Imap_Client::DATA_NSTRING, 'v' => $val);
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
            for ($i = 0; isset($d[$i]); $i += 2) {
                if (($id = $this->_getString($d[$i + 1])) !== null) {
                    $this->_temp['id'][$this->_getString($d[$i])] = $id;
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
     * Parse a LANGUAGE response (RFC 5255 [3.3]).
     *
     * @param array $data  The server response.
     */
    protected function _parseLanguage($data)
    {
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
        if ($this->queryCapability('ENABLE')) {
            // Only enable non-enabled extensions
            $exts = array_diff($exts, array_keys($this->_init['enabled']));
            if (!empty($exts)) {
                $this->_sendLine(array_merge(array('ENABLE'), $exts));
            }
        }
    }

    /**
     * Parse an ENABLED response (RFC 5161 [3.2]).
     *
     * @param array $data  The server response.
     */
    protected function _parseEnabled($data)
    {
        $enabled = array_flip($data);

        if (in_array('QRESYNC', $data)) {
            $enabled['CONDSTORE'] = true;
        }

        $this->_setInit('enabled', array_merge($this->_init['enabled'], $enabled));
    }

    /**
     */
    protected function _openMailbox(Horde_Imap_Client_Mailbox $mailbox, $mode)
    {
        $condstore = false;
        $qresync = isset($this->_init['enabled']['QRESYNC']);

        /* Don't sync mailbox if we are reopening R/W - we would catch any
         * mailbox changes from an untagged request. */
        $reopen = $mailbox->equals($this->_selected);

        /* Let the 'CLOSE' response code handle mailbox switching if QRESYNC
         * is active. */
        if (empty($this->_temp['mailbox']['name']) ||
            (!$qresync && ($mailbox != $this->_temp['mailbox']['name']))) {
            $this->_temp['mailbox'] = array('name' => clone($mailbox));
            $this->_selected = clone($mailbox);
        } elseif ($qresync) {
            $this->_temp['qresyncmbox'] = clone($mailbox);
        }

        $cmd = array(
            (($mode == Horde_Imap_Client::OPEN_READONLY) ? 'EXAMINE' : 'SELECT'),
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap)
        );

        /* If QRESYNC is available, synchronize the mailbox. */
        if (!$reopen && $qresync) {
            $this->_initCache();
            $metadata = $this->_cache->getMetaData($mailbox, null, array(self::CACHE_MODSEQ, 'uidvalid'));

            if (isset($metadata[self::CACHE_MODSEQ])) {
                $uids = $this->_cache->get($mailbox);
                if (!empty($uids)) {
                    /* This command may cause several things to happen.
                     * 1. UIDVALIDITY may have changed.  If so, we need
                     * to expire the cache immediately (done below).
                     * 2. NOMODSEQ may have been returned. We can keep current
                     * message cache data but won't be able to do flag
                     * caching.
                     * 3. VANISHED/FETCH information was returned. These
                     * responses will have already been handled by those
                     * response handlers.
                     * TODO: Use 4th parameter (useful if we keep a sequence
                     * number->UID lookup in the future). */
                    $cmd[] = array(
                        'QRESYNC',
                        array(
                            $metadata['uidvalid'],
                            $metadata[self::CACHE_MODSEQ],
                            $this->utils->toSequenceString($uids)
                        )
                    );
                }
            }
        } elseif (!$reopen &&
                  !isset($this->_init['enabled']['CONDSTORE']) &&
                  $this->_initCache() &&
                  $this->queryCapability('CONDSTORE')) {
            /* Activate CONDSTORE now if ENABLE is not available. */
            $cmd[] = array('CONDSTORE');
            $condstore = true;
        }

        try {
            $this->_sendLine($cmd);
        } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
            // An EXAMINE/SELECT failure with a return of 'NO' will cause the
            // current mailbox to be unselected.
            if ($e->response == 'NO') {
                $this->_selected = null;
                $this->_mode = 0;
                if (!$e->getCode()) {
                    throw new Horde_Imap_Client_Exception(
                        sprintf(Horde_Imap_Client_Translation::t("Could not open mailbox \"%s\"."), $mailbox),
                        Horde_Imap_Client_Exception::MAILBOX_NOOPEN
                    );
                }
            }
            throw $e;
        }

        if ($condstore) {
            $this->_parseEnabled(array('CONDSTORE'));
        }
    }

    /**
     */
    protected function _createMailbox(Horde_Imap_Client_Mailbox $mailbox, $opts)
    {
        $cmd = array(
            'CREATE',
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap)
        );

        if (!empty($opts['special_use'])) {
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
    protected function _deleteMailbox(Horde_Imap_Client_Mailbox $mailbox)
    {
        // Some IMAP servers will not allow a delete of a currently open
        // mailbox.
        if ($mailbox->equals($this->_selected)) {
            $this->close();
        }

        try {
            // DELETE returns no untagged information (RFC 3501 [6.3.4])
            $this->_sendLine(array(
                'DELETE',
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap)
            ));
        } catch (Horde_Imap_Client_Exception $e) {
            // Some IMAP servers won't allow a mailbox delete unless all
            // messages in that mailbox are deleted.
            if (!empty($this->_temp['deleteretry'])) {
                unset($this->_temp['deleteretry']);
                throw $e;
            }

            $this->store($mailbox, array('add' => array(Horde_Imap_Client::FLAG_DELETED)));
            $this->expunge($mailbox);

            $this->_temp['deleteretry'] = true;
            $this->deleteMailbox($mailbox);
        }

        unset($this->_temp['deleteretry']);
    }

    /**
     */
    protected function _renameMailbox(Horde_Imap_Client_Mailbox $old,
                                      Horde_Imap_Client_Mailbox $new)
    {
        // RENAME returns no untagged information (RFC 3501 [6.3.5])
        $this->_sendLine(array(
            'RENAME',
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $old->utf7imap),
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $new->utf7imap)
        ));
    }

    /**
     */
    protected function _subscribeMailbox(Horde_Imap_Client_Mailbox $mailbox,
                                         $subscribe)
    {
        // SUBSCRIBE/UNSUBSCRIBE returns no untagged information (RFC 3501
        // [6.3.6 & 6.3.7])
        $this->_sendLine(array(
            ($subscribe ? 'SUBSCRIBE' : 'UNSUBSCRIBE'),
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap)
        ));
    }

    /**
     */
    protected function _listMailboxes($pattern, $mode, $options)
    {
        // RFC 5258 [3.1]: Use LSUB for MBOX_SUBSCRIBED if no other server
        // return options are specified.
        if (($mode == Horde_Imap_Client::MBOX_SUBSCRIBED) &&
            empty($options['attributes']) &&
            empty($options['children']) &&
            empty($options['recursivematch']) &&
            empty($options['remote']) &&
            empty($options['special_use']) &&
            empty($options['status'])) {
            return $this->_getMailboxList(
                $pattern,
                Horde_Imap_Client::MBOX_SUBSCRIBED,
                array(
                    'delimiter' => !empty($options['delimiter']),
                    'flat' => !empty($options['flat']),
                    'no_listext' => true
                )
            );
        }

        // Get the list of subscribed/unsubscribed mailboxes. Since LSUB is
        // not guaranteed to have correct attributes, we must use LIST to
        // ensure we receive the correct information.
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
     * @param array $options     Additional options. 'no_listext' will skip
     *                           using the LIST-EXTENDED capability.
     * @param array $subscribed  A list of subscribed mailboxes.
     *
     * @return array  See listMailboxes(().
     *
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
            'subscribed' => ($check ? array_flip(array_map('strval', $subscribed)) : null)
        );
        $t['listresponse'] = array();
        $return_opts = array();

        if ($this->queryCapability('LIST-EXTENDED') &&
            empty($options['no_listext'])) {
            $cmd = array('LIST');
            $t['mailboxlist']['ext'] = true;

            $select_opts = array();

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

            if (!empty($options['special_use'])) {
                $return_opts[] = 'SPECIAL-USE';
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

        /* LIST-STATUS does NOT depend on LIST-EXTENDED. */
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
                $val_utf8 = Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($val);
                if (isset($t['listresponse'][$val_utf8]) &&
                    isset($t['status'][$val_utf8])) {
                    $t['listresponse'][$val_utf8]['status'] = $t['status'][$val_utf8];
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
        $mbox = Horde_Imap_Client_Mailbox::get($data[3], true);

        if ($ml['check'] &&
            $ml['subexist'] &&
            !isset($ml['subscribed'][$mbox->utf7imap])) {
            return;
        } elseif ((!$ml['check'] && $ml['subexist']) ||
                  (empty($mlo['flat']) && !empty($mlo['attributes']))) {
            $attr = array_flip(array_map('strtolower', $data[1]));
            if ($ml['subexist'] &&
                !$ml['check'] &&
                isset($attr['\\nonexistent'])) {
                return;
            }
        }

        if (empty($mlo['flat'])) {
            $tmp = array(
                'mailbox' => $mbox
            );

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
            $lr[strval($mbox)] = $tmp;
        } else {
            $lr[] = $mbox;
        }
    }

    /**
     */
    protected function _status(Horde_Imap_Client_Mailbox $mailbox, $flags)
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
         * Use queryCapability('CONDSTORE') here because we may not have
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
        }

        foreach ($items as $key => $val) {
            if ($key & $flags) {
                if ($mailbox->equals($this->_selected)) {
                    if (isset($this->_temp['mailbox'][$val])) {
                        $data[$val] = $this->_temp['mailbox'][$val];
                    } elseif ($key == Horde_Imap_Client::STATUS_UIDNEXT) {
                        /* UIDNEXT is not strictly required on mailbox open.
                         * See RFC 3501 [6.3.1]. */
                        $data[$val] = 0;
                    } elseif ($key == Horde_Imap_Client::STATUS_UIDNOTSTICKY) {
                        /* In the absence of uidnotsticky information, or
                         * if UIDPLUS is not supported, we assume the UIDs
                         * are sticky. */
                        $data[$val] = false;
                    } elseif ($key == Horde_Imap_Client::STATUS_PERMFLAGS) {
                        /* If PERMFLAGS is not returned by server, must assume
                         * that all flags can be changed permanently. See
                         * RFC 3501 [6.3.1]. */
                        $data[$val] = isset($this->_temp['mailbox'][$items[Horde_Imap_Client::STATUS_FLAGS]])
                            ? $this->_temp['mailbox'][$items[Horde_Imap_Client::STATUS_FLAGS]]
                            : array();
                        $data[$val][] = "\\*";
                    } elseif (in_array($key, array(Horde_Imap_Client::STATUS_FIRSTUNSEEN, Horde_Imap_Client::STATUS_UNSEEN))) {
                        /* If we already know there are no messages in the
                         * current mailbox, we know there is no firstunseen
                         * and unseen info also. */
                        if (empty($this->_temp['mailbox']['messages'])) {
                            $data[$val] = ($key == Horde_Imap_Client::STATUS_FIRSTUNSEEN) ? null : 0;
                        } else {
                            /* RFC 3501 [6.3.1] - FIRSTUNSEEN information is
                             * not mandatory. If missing in EXAMINE/SELECT
                             * results, we need to do a search. An UNSEEN
                             * count also requires a search. */
                            if (is_null($search)) {
                                $search_query = new Horde_Imap_Client_Search_Query();
                                $search_query->flag(Horde_Imap_Client::FLAG_SEEN, false);
                                $search = $this->search($mailbox, $search_query, array('results' => array(($key == Horde_Imap_Client::STATUS_FIRSTUNSEEN) ? Horde_Imap_Client::SEARCH_RESULTS_MIN : Horde_Imap_Client::SEARCH_RESULTS_COUNT), 'sequence' => true));
                            }

                            $data[$val] = $search[($key == Horde_Imap_Client::STATUS_FIRSTUNSEEN) ? 'min' : 'count'];
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
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap),
            array_map('strtoupper', $query)
        ));

        return $this->_temp['status'][strval($mailbox)];
    }

    /**
     * Parse a STATUS response (RFC 3501 [7.2.4], RFC 4551 [3.6])
     *
     * @param string $mailbox  The mailbox name (UTF7-IMAP).
     * @param array $data      The server response.
     */
    protected function _parseStatus($mailbox, $data)
    {
        $mailbox = Horde_Imap_Client_Mailbox::get($mailbox, true);

        $this->_temp['status'][strval($mailbox)] = array();

        for ($i = 0; isset($data[$i]); $i += 2) {
            $item = strtolower($data[$i]);
            $this->_temp['status'][strval($mailbox)][$item] = $data[$i + 1];
        }
    }

    /**
     */
    protected function _append(Horde_Imap_Client_Mailbox $mailbox, $data,
                               $options)
    {
        // Check for MULTIAPPEND extension (RFC 3502)
        if ((count($data) > 1) && !$this->queryCapability('MULTIAPPEND')) {
            $result = $this->getIdsOb();
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
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap)
        );

        foreach (array_keys($data) as $key) {
            if (!empty($data[$key]['flags'])) {
                $tmp = array();
                foreach ($data[$key]['flags'] as $val) {
                    /* Ignore recent flag. RFC 3501 [9]: flag definition */
                    if (strcasecmp($val, Horde_Imap_Client::FLAG_RECENT) !== 0) {
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
            switch ($e->getCode()) {
            case $e::CATENATE_BADURL:
            case $e::CATENATE_TOOBIG:
                /* Cyrus 2.4 (at least as of .14) has a broken CATENATE (see
                 * Bug #11111). Regardless, if CATENATE is broken, we can try
                 * to fallback to APPEND. */
                $cap = $this->capability();
                unset($cap['CATENATE']);
                $this->_setInit('capability', $cap);

                return $this->_append($mailbox, $data, $options);
            }

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
            : $this->getIdsOb($t['appenduid']);
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
                try {
                    $this->_sendLine('SELECT ""');
                } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
                    // Ignore error; it is expected.
                }
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
        $mailbox = clone($this->_selected);
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
                $results = array(Horde_Imap_Client::SEARCH_RESULTS_MATCH);
                if ($this->queryCapability('SEARCHRES')) {
                    $results[] = Horde_Imap_Client::SEARCH_RESULTS_SAVE;
                }
                $s_res = $this->search($mailbox, null, array(
                    'results' => $results
                ));
                $uid_string = (in_array(Horde_Imap_Client::SEARCH_RESULTS_SAVE, $results) && !empty($s_res['save']))
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
            $search_query->flag(Horde_Imap_Client::FLAG_DELETED, true);
            if ($options['ids']->search_res) {
                $search_query->previousSearch(true);
            } else {
                $search_query->ids($options['ids'], true);
            }

            $res = $this->search($mailbox, $search_query);

            $this->store($mailbox, array(
                'ids' => $res['match'],
                'remove' => array(Horde_Imap_Client::FLAG_DELETED)
            ));

            $unflag = $res['match'];
        }

        $list_msgs = !empty($options['list']);
        $tmp = &$this->_temp;
        $tmp['expunge'] = $tmp['vanished'] = array();

        /* We need to get sequence num -> UID lookup table if we are caching.
         * There is no guarantee that if we are using QRESYNC that we will get
         * VANISHED responses, so this is unfortunately necessary. */
        if (is_null($s_res) && ($list_msgs || $use_cache)) {
            $s_res = $uidplus
                ? $this->_getSeqUidLookup($options['ids'], true)
                : $this->_getSeqUidLookup($this->getIdsOb(Horde_Imap_Client_Ids::ALL, true));
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
                'add' => array(Horde_Imap_Client::FLAG_DELETED),
                'ids' => $unflag
            ));
        }

        if (!$use_cache && !$list_msgs) {
            return null;
        }

        $expunged = array();

        if (!empty($tmp['vanished'])) {
            $expunged = $tmp['vanished'];
        } elseif (!empty($tmp['expunge'])) {
            $lookup = $s_res['lookup'];

            /* Expunge responses can come in any order. Thus, we need to
             * reindex anytime we have an index that appears equal to or
             * after a previously seen index. If an IMAP server is smart,
             * it will expunge in reverse order instead. */
            foreach ($tmp['expunge'] as &$val) {
                $found = false;
                $tmp2 = array();

                foreach (array_keys($lookup) as $i => $seq) {
                    if ($found) {
                        $tmp2[$seq - 1] = $lookup[$seq];
                    } elseif ($seq == $val) {
                        $expunged[] = $lookup[$seq];
                        $tmp2 = array_slice($lookup, 0, $i, true);
                        $found = true;
                    }
                }

                $lookup = $tmp2;
            }
        }

        if (empty($expunged)) {
            return null;
        }

        if ($use_cache) {
            $this->_deleteMsgs($mailbox, $expunged);
        }

        /* Update MODSEQ if active for mailbox. */
        if (!empty($this->_temp['mailbox']['highestmodseq'])) {
            if (isset($this->_init['enabled']['QRESYNC'])) {
                $this->_updateMetaData($mailbox, array(
                    self::CACHE_MODSEQ => $this->_temp['mailbox']['highestmodseq']
                ), isset($this->_temp['mailbox']['uidvalidity']) ? $this->_temp['mailbox']['uidvalidity'] : null);
            } else {
                /* Unfortunately, RFC 4551 does not provide any method to
                 * obtain the HIGHESTMODSEQ after an EXPUNGE is completed.
                 * Instead, unselect the mailbox - if we need to reselect the
                 * mailbox, the HIGHESTMODSEQ info will appear in the
                 * EXAMINE/SELECT HIGHESTMODSEQ response. */
                $this->close();
            }
        }

        return $list_msgs
            ? $this->getIdsOb($expunged, $options['ids']->sequence)
            : null;
    }

    /**
     * Parse an EXPUNGE response (RFC 3501 [7.4.1]).
     *
     * @param integer $seq  The message sequence number.
     */
    protected function _parseExpunge($seq)
    {
        $this->_temp['expunge'][] = $seq;

        /* Bug #9915: Decrement the message list here because some broken
         * IMAP servers will send an unneeded EXISTS response after the
         * EXPUNGE list is processed (see RFC 3501 [7.4.1]). */
        --$this->_temp['mailbox']['messages'];
        $this->_temp['mailbox']['lookup'] = array();
    }

    /**
     * Parse a VANISHED response (RFC 5162 [3.6]).
     *
     * @param array $data  The response data.
     */
    protected function _parseVanished($data)
    {
        $vanished = array();

        /* There are two forms of VANISHED.  VANISHED (EARLIER) will be sent
         * in a FETCH (VANISHED) or SELECT/EXAMINE (QRESYNC) call.
         * If this is the case, we can go ahead and update the cache
         * immediately (we know we are caching or else QRESYNC would not be
         * enabled). HIGHESTMODSEQ information will be grabbed at the end in
         * the tagged response. */
        if (is_array($data[0])) {
            if (strtoupper(reset($data[0])) == 'EARLIER') {
                /* Caching is guaranteed to be active if we are using
                 * QRESYNC. */
                $vanished = $this->utils->fromSequenceString($data[1]);
                $this->_deleteMsgs($this->_temp['mailbox']['name'], $vanished);
            }
        } else {
            /* The second form is just VANISHED. This is returned from an
             * EXPUNGE command and will be processed in _expunge(). */
            $vanished = $this->utils->fromSequenceString($data[0]);
            $this->_temp['mailbox']['messages'] -= count($vanished);
            $this->_temp['mailbox']['lookup'] = array();
        }

        $this->_temp['vanished'] = $vanished;
    }

    /**
     * Search a mailbox.  This driver supports all IMAP4rev1 search criteria
     * as defined in RFC 3501.
     */
    protected function _search($query, $options)
    {
        /* RFC 4551 [3.1] - trying to do a MODSEQ SEARCH on a mailbox that
         * doesn't support it will return BAD. Catch that here and throw
         * an exception. */
        if (in_array('CONDSTORE', $options['_query']['exts']) &&
            empty($this->_temp['mailbox']['highestmodseq'])) {
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Mailbox does not support mod-sequences."),
                    Horde_Imap_Client_Exception::MBOXNOMODSEQ
                );
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
            Horde_Imap_Client::SORT_RELEVANCY => 'RELEVANCY',
            // This is a bogus entry to allow the sort options check to
            // correctly work below.
            Horde_Imap_Client::SORT_SEQUENCE => 'SEQUENCE',
            Horde_Imap_Client::SORT_SIZE => 'SIZE',
            Horde_Imap_Client::SORT_SUBJECT => 'SUBJECT',
            Horde_Imap_Client::SORT_TO => 'TO'
        );

        $results_criteria = array(
            Horde_Imap_Client::SEARCH_RESULTS_COUNT => 'COUNT',
            Horde_Imap_Client::SEARCH_RESULTS_MATCH => 'ALL',
            Horde_Imap_Client::SEARCH_RESULTS_MAX => 'MAX',
            Horde_Imap_Client::SEARCH_RESULTS_MIN => 'MIN',
            Horde_Imap_Client::SEARCH_RESULTS_RELEVANCY => 'RELEVANCY',
            Horde_Imap_Client::SEARCH_RESULTS_SAVE => 'SAVE'
        );

        // Check if the server supports sorting (RFC 5256).
        $esearch = $return_sort = $server_seq_sort = $server_sort = false;
        if (!empty($options['sort'])) {
            /* Make sure sort options are correct. If not, default to no
             * sort. */
            if (count(array_intersect($options['sort'], array_keys($sort_criteria))) === 0) {
                unset($options['sort']);
            } else {
                $return_sort = true;

                if ($server_sort = $this->queryCapability('SORT')) {
                    /* Make sure server supports DISPLAYFROM & DISPLAYTO. */
                    $server_sort =
                        !array_intersect($options['sort'], array(Horde_Imap_Client::SORT_DISPLAYFROM, Horde_Imap_Client::SORT_DISPLAYTO)) ||
                        (is_array($server_sort) &&
                         in_array('DISPLAY', $server_sort));
                }

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

        $charset = is_null($options['_query']['charset'])
            ? 'US-ASCII'
            : $options['_query']['charset'];

        if ($server_sort) {
            $cmd[] = 'SORT';
            $results = array();

            // Use ESEARCH (RFC 4466) response if server supports.
            $esearch = false;

            // Check for ESORT capability (RFC 5267)
            if ($this->queryCapability('ESORT')) {
                foreach ($options['results'] as $val) {
                    if (isset($results_criteria[$val]) &&
                        ($val != Horde_Imap_Client::SEARCH_RESULTS_SAVE)) {
                        $results[] = $results_criteria[$val];
                    }
                }
                $esearch = true;
            }

            // Add PARTIAL limiting (RFC 5267 [4.4])
            if ((!$esearch || !empty($options['partial'])) &&
                ($cap = $this->queryCapability('CONTEXT')) &&
                in_array('SORT', $cap)) {
                /* RFC 5267 indicates RFC 4466 ESEARCH support,
                 * notwithstanding RFC 4731 support. */
                $esearch = true;

                if (!empty($options['partial'])) {
                    /* Can't have both ALL and PARTIAL returns. */
                    $results = array_diff($results, array('ALL'));

                    $results[] = 'PARTIAL';
                    $results[] = strval($this->getIdsOb($options['partial']));
                }
            }

            if ($esearch && empty($this->_init['noesearch'])) {
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
            $cmd[] = $charset;
        } else {
            $esearch = false;
            $results = array();

            $cmd[] = 'SEARCH';

            // Check if the server supports ESEARCH (RFC 4731).
            if ($this->queryCapability('ESEARCH')) {
                foreach ($options['results'] as $val) {
                    if (isset($results_criteria[$val])) {
                        $results[] = $results_criteria[$val];
                    }
                }
                $esearch = true;
            }

            // Add PARTIAL limiting (RFC 5267 [4.4]).
            if ((!$esearch || !empty($options['partial'])) &&
                ($cap = $this->queryCapability('CONTEXT')) &&
                in_array('SEARCH', $cap)) {
                /* RFC 5267 indicates RFC 4466 ESEARCH support,
                 * notwithstanding RFC 4731 support. */
                $esearch = true;

                if (!empty($options['partial'])) {
                    // Can't have both ALL and PARTIAL returns.
                    $results = array_diff($results, array('ALL'));

                    $results[] = 'PARTIAL';
                    $results[] = strval($this->getIdsOb($options['partial']));
                }
            }

            if ($esearch && empty($this->_init['noesearch'])) {
                // Always use ESEARCH if available because it returns results
                // in a more compact sequence-set list
                $cmd[] = 'RETURN';
                $cmd[] = $results;
            }

            // Charset is optional for SEARCH (RFC 3501 [6.4.4]).
            if ($charset != 'US-ASCII') {
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

        try {
            $this->_sendLine($cmd);
        } catch (Horde_Imap_Client_Exception $e) {
            if (($e instanceof Horde_Imap_Client_Exception_ServerResponse) &&
                ($e->response == 'NO') &&
                ($charset != 'US-ASCII')) {
                /* RFC 3501 [6.4.4]: BADCHARSET response code is only a
                 * SHOULD return. If it doesn't exist, need to check for
                 * command status of 'NO'. List of supported charsets in
                 * the BADCHARSET response has already been parsed and stored
                 * at this point. */
                $s_charset = $this->_init['s_charset'];
                $s_charset[$charset] = false;
                $this->_setInit('s_charset', $s_charset);
                $e->setCode(Horde_Imap_Client_Exception::BADCHARSET);
            }

            if (empty($this->_temp['search_retry'])) {
                $this->_temp['search_retry'] = true;

                /* Bug #9842: Workaround broken Cyrus servers (as of
                 * 2.4.7). */
                if ($esearch && ($charset != 'US-ASCII')) {
                    $cap = $this->capability();
                    unset($cap['ESEARCH']);
                    $this->_setInit('capability', $cap);
                    $this->_setInit('noesearch', true);

                    try {
                        return $this->_search($query, $options);
                    } catch (Horde_Imap_Client_Exception $e) {}
                }

                /* Try to convert charset. */
                if (($e->getCode() == Horde_Imap_Client_Exception::BADCHARSET) &&
                    ($charset != 'US-ASCII')) {
                    foreach (array_merge(array_keys(array_filter($this->_init['s_charset'])), array('US-ASCII')) as $val) {
                        $this->_temp['search_retry'] = 1;
                        $new_query = clone($query);
                        try {
                            $new_query->charset($val);
                            $options['_query'] = $new_query->build($this->capability());
                            return $this->_search($new_query, $options);
                        } catch (Horde_Imap_Client_Exception $e) {}
                    }
                }

                unset($this->_temp['search_retry']);
            }

            throw $e;
        }

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
            case Horde_Imap_Client::SEARCH_RESULTS_COUNT:
                $ret['count'] = $esearch ? $er['count'] : count($sr);
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_MATCH:
                $ret['match'] = $this->getIdsOb($sr, !empty($options['sequence']));
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_MAX:
                $ret['max'] = $esearch ? (isset($er['max']) ? $er['max'] : null) : (empty($sr) ? null : max($sr));
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_MIN:
                $ret['min'] = $esearch ? (isset($er['min']) ? $er['min'] : null) : (empty($sr) ? null : min($sr));
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_RELEVANCY:
                $ret['relevancy'] = ($esearch && isset($er['relevancy'])) ? $er['relevancy'] : array();
                break;

            case Horde_Imap_Client::SEARCH_RESULTS_SAVE:
                $ret['save'] = $esearch ? empty($this->_temp['searchnotsaved']) : false;
                break;
            }
        }

        // Add modseq data, if needed.
        if (!empty($er['modseq'])) {
            $ret['modseq'] = $er['modseq'];
        }

        unset($this->_temp['search_retry']);

        /* Check for EXPUNGEISSUED (RFC 2180 [4.3]/RFC 5530 [3]). */
        if (!empty($this->_temp['expungeissued'])) {
            unset($this->_temp['expungeissued']);
            $this->noop();
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
        /* More than one search response may be sent. */
        $this->_temp['searchresp'] = array_merge($this->_temp['searchresp'], $data);
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

        // This catches the case of an '(ALL)' ESEARCH with no results
        if ($i == $len) {
            return;
        }

        for (; $i < $len; $i += 2) {
            $val = $data[$i + 1];
            $tag = strtoupper($data[$i]);
            switch ($tag) {
            case 'ALL':
                $this->_parseSearch($this->utils->fromSequenceString($val));
                break;

            case 'COUNT':
            case 'MAX':
            case 'MIN':
            case 'MODSEQ':
            case 'RELEVANCY':
                $this->_temp['esearchresp'][strtolower($tag)] = $val;
                break;

            case 'PARTIAL':
                $this->_parseSearch($this->utils->fromSequenceString(end($val)));
                break;
            }
        }
    }

    /**
     * If server does not support the SORT IMAP extension (RFC 5256), we need
     * to do sorting on the client side.
     *
     * @param array $res   The search results.
     * @param array $opts  The options to _search().
     *
     * @return array  The sort results.
     *
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
            case Horde_Imap_Client::SORT_DISPLAYFROM:
            case Horde_Imap_Client::SORT_DISPLAYTO:
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

        if (count($query)) {
            $fetch_res = $this->fetch($this->_selected, $query, array(
                'ids' => $this->getIdsOb($res, !empty($opts['sequence']))
            ));
            $res = $this->_clientSortProcess($res, $fetch_res, $opts['sort']);
        }

        return $res;
    }

    /**
     */
    protected function _clientSortProcess($res, $fetch_res, $sort)
    {
        /* The initial sort is on the entire set. */
        $slices = array(0 => $res);
        $reverse = false;

        foreach ($sort as $val) {
            if ($val == Horde_Imap_Client::SORT_REVERSE) {
                $reverse = true;
                continue;
            }

            $slices_list = $slices;
            $slices = array();

            foreach ($slices_list as $slice_start => $slice) {
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
                    $field = ($val == Horde_Imap_Client::SORT_DISPLAYFROM)
                        ? 'from'
                        : 'to';

                    foreach ($slice as $num) {
                        $env = $fetch_res[$num]->getEnvelope();

                        if (empty($env->$field)) {
                            $sorted[$num] = null;
                        } else {
                            $addr_ob = reset($env->$field);
                            $sorted[$num] = empty($addr_ob['personal'])
                                ? $addr_ob['mailbox']
                                : $addr_ob['personal'];
                        }
                    }

                    asort($sorted, SORT_LOCALE_STRING);
                    break;

                case Horde_Imap_Client::SORT_CC:
                case Horde_Imap_Client::SORT_FROM:
                case Horde_Imap_Client::SORT_TO:
                    if ($val == Horde_Imap_Client::SORT_CC) {
                        $field = 'cc';
                    } elseif ($val == Horde_Imap_Client::SORT_FROM) {
                        $field = 'from';
                    } else {
                        $field = 'to';
                    }

                    foreach ($slice as $num) {
                        $tmp = $fetch_res[$num]->getEnvelope()->$field;
                        $sorted[$num] = empty($tmp)
                            ? null
                            : $tmp[0]['mailbox'];
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
                                $slices[array_search($start, $res)] = array_slice($sorted, array_search($start, $sorted), $i + 1);
                                $i = 0;
                            }
                            $last = $v;
                            $start = $k;
                        } else {
                            ++$i;
                        }
                    }
                    if ($i) {
                        $slices[array_search($start, $res)] = array_slice($sorted, array_search($start, $sorted), $i + 1);
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
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
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
                    $ids = $this->getIdsOb(Horde_Imap_Client_Ids::ALL, !empty($options['sequence']));
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
                throw new Horde_Imap_Client_Exception_NoSupportExtension(
                    'THREAD',
                    sprintf('Server does not support "%s" thread sort.', $tsort)
                );
            }
        }

        $charset = 'US-ASCII';
        if (empty($options['search'])) {
            $search = array('ALL');
        } else {
            $search_query = $options['search']->build();
            if (!is_null($search_query['charset'])) {
                $charset = $search_query['charset'];
            }
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
        $dates = $this->_getSentDates($data, $data->ids());
        $level = $sorted = $tsort = array();
        $this->_temp['threadparse'] = array('base' => null, 'resp' => array());

        foreach ($data as $k => $v) {
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
    protected function _fetch(Horde_Imap_Client_Fetch_Results $results,
                              Horde_Imap_Client_Fetch_Query $query,
                              $options)
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
                        throw new Horde_Imap_Client_Exception(
                            Horde_Imap_Client_Translation::t("Mailbox does not support mod-sequences."),
                            Horde_Imap_Client_Exception::MBOXNOMODSEQ
                        );
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
            $seq
        );

        if (empty($options['changedsince'])) {
            $cmd[] = $fetch;
        } else {
            if (empty($this->_temp['mailbox']['highestmodseq'])) {
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Mailbox does not support mod-sequences."),
                    Horde_Imap_Client_Exception::MBOXNOMODSEQ
                );
            }

            $fetch_opts = array(
                'CHANGEDSINCE',
                array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $options['changedsince'])
            );

            /* We might just want the list of UIDs changed since a given
             * modseq. In that case, we don't have any other FETCH attributes,
             * but RFC 3501 requires at least one attribute to be
             * specified. */
            $cmd[] = empty($fetch)
                ? 'UID'
                : $fetch;
            $cmd[] = $fetch_opts;
        }

        try {
            $this->_sendLine($cmd, array(
                'fetch' => $results
            ));
        } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
            // A NO response, when coupled with a sequence FETCH, most likely
            // means that messages were expunged. RFC 2180 [4.1]
            if ($options['ids']->sequence && ($e->response == 'NO')) {
                $this->_temp['expungeissued'] = true;
            }
        }

        /* Check for EXPUNGEISSUED (RFC 2180 [4.1]/RFC 5530 [3]). */
        if (!empty($this->_temp['expungeissued'])) {
            unset($this->_temp['expungeissued']);
            $this->noop();
        }
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
        $i = 0;
        $uid = null;

        /* At this point, we don't have access to the UID of the entry. Thus,
         * need to cache data locally until we reach the end. */
        $ob = new $this->_fetchDataClass();
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
                $this->_temp['mailbox']['lookup'][$id] = $uid;
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
                            if (($tmp = $this->_getString($data[++$i], true)) !== null) {
                                $ob->setFullMsg($tmp);
                            }
                        } elseif (is_numeric(substr($tag, -1))) {
                            // BODY[MIMEID] request
                            if (($tmp = $this->_getString($data[++$i], true)) !== null) {
                                $ob->setBodyPart($tag, $tmp);
                            }
                        } else {
                            // BODY[HEADER|TEXT|MIME] request
                            if (($last_dot = strrpos($tag, '.')) === false) {
                                $mime_id = 0;
                            } else {
                                $mime_id = substr($tag, 0, $last_dot);
                                $tag = substr($tag, $last_dot + 1);
                            }

                            if (($tmp = $this->_getString($data[++$i], true)) !== null) {
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

        if (is_null($this->_temp['fetchresp'])) {
            $this->_temp['fetchresp'] = new Horde_Imap_Client_Fetch_Results($this->_fetchDataClass, is_null($uid) ? Horde_Imap_Client_Fetch_Results::SEQUENCE : Horde_Imap_Client_Fetch_Results::UID);
        }

        $this->_temp['fetchresp']->get(is_null($uid) ? $id : $uid)->merge($ob);
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
            for ($i = 0; isset($data[$i]) && is_array($data[$i]); ++$i) {
                $ob->addPart($this->_parseBodystructure($data[$i]));
            }

            // The first string entry after an array entry gives us the
            // subpart type.
            $ob->setType('multipart/' . $this->_getString($data[$i]));

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
                $ob->setDisposition($this->_getString($data[$i][0]));

                foreach ($this->_parseStructureParams($data[$i][1], 'content-disposition') as $key => $val) {
                    $ob->setDispositionParameter($key, $val);
                }
            }

            // This is language information. It is either a single value or
            // a list of values.
            if (isset($data[++$i])) {
                $ob->setLanguage($this->_getString($data[$i]));
            }

            // Ignore: location (RFC 2557)
            // There can be further information returned in the future, but
            // for now we are done.
        } else {
            $ob->setType($this->_getString($data[0]) . '/' . $this->_getString($data[1]));

            foreach ($this->_parseStructureParams($data[2], 'content-type') as $key => $val) {
                $ob->setContentTypeParameter($key, $val);
            }

            if (($tmp = $this->_getString($data[3], true)) !== null) {
                $ob->setContentId($tmp);
            }

            if (($tmp = $this->_getString($data[4], true)) !== null) {
                $ob->setDescription(Horde_Mime::decode($tmp));
            }

            if (($tmp = $this->_getString($data[5], true)) !== null) {
                $ob->setTransferEncoding($tmp);
            }

            $ob->setBytes($data[6]);

            // If the type is 'message/rfc822' or 'text/*', several extra
            // fields are included
            $i = 7;
            switch ($ob->getPrimaryType()) {
            case 'message':
                if ($ob->getSubType() == 'rfc822') {
                    // Ignore: envelope
                    $ob->addPart($this->_parseBodystructure($data[8]));
                    // Ignore: lines
                    $i = 10;
                }
                break;

            case 'text':
                // Ignore: lines
                $i = 8;
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
                $ob->setLanguage($this->_getString($data[$i]));
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
            for ($i = 0; isset($data[$i]); $i += 2) {
                $params[strtolower($data[$i])] = $this->_getString($data[$i + 1]);
            }
        }

        $ret = Horde_Mime::decodeParam($type, $params);

        return $ret['params'];
    }

    /**
     * Helper function to validate/parse string data.
     *
     * @param mixed $data       The token item.
     * @param boolean $nstring  True if this element is an nstring.
     *
     * @return string  The string value, or null if this is an nstring and the
     *                 data value is NIL.
     */
    protected function _getString($data, $nstring = false)
    {
        if (is_array($data)) {
            return array_map(array($this, '_getString'), $data, array_fill(0, count($data), $nstring));
        }

        if (is_resource($data)) {
            rewind($data);
            return stream_get_contents($data);
        }

        return ($nstring && (strcasecmp($data, 'NIL') === 0))
            ? null
            : $data;
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
        // 'route', the 2nd element, is deprecated by RFC 2822.
        $addr_structure = array(
            0 => 'personal',
            2 => 'mailbox',
            3 => 'host'
        );
        $env_data = array(
            0 => 'date',
            1 => 'subject',
            2 => 'from',
            3 => 'sender',
            4 => 'reply_to',
            5 => 'to',
            6 => 'cc',
            7 => 'bcc',
            8 => 'in_reply_to',
            9 => 'message_id'
        );

        $ret = new Horde_Imap_Client_Data_Envelope();

        foreach ($data as $key => $val) {
            if (!isset($env_data[$key]) ||
                ($val = $this->_getString($val, true)) === null) {
                continue;
            }

            if (is_string($val)) {
                // These entries are text fields.
                $ret->$env_data[$key] = $val;
            } else {
                // These entries are address structures.
                $group = null;
                $tmp = new Horde_Mail_Rfc822_List();

                foreach ($val as $a_val) {
                    // RFC 3501 [7.4.2]: Group entry when host is NIL.
                    // Group end when mailbox is NIL; otherwise, this is
                    // mailbox name.
                    if (is_null($a_val[3])) {
                        $group = new Horde_Mail_Rfc822_Group();

                        if (is_null($a_val[2])) {
                            $group = null;
                        } else {
                            $group->groupname = $a_val[2];
                            $tmp->add($group);
                        }
                    } else {
                        $addr = new Horde_Mail_Rfc822_Address();

                        foreach ($addr_structure as $add_key => $add_val) {
                            if (!is_null($a_val[$add_key])) {
                                $addr->$add_val = $a_val[$add_key];
                            }
                        }

                        if ($group) {
                            $group->addresses->add($addr);
                        } else {
                            $tmp->add($addr);
                        }
                    }
                }

                $ret->$env_data[$key] = $tmp;
            }
        }

        return $ret;
    }

    /**
     */
    protected function _vanished($modseq, Horde_Imap_Client_Ids $ids)
    {
        if (empty($this->_temp['mailbox']['highestmodseq'])) {
            throw new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Mailbox does not support mod-sequences."),
                Horde_Imap_Client_Exception::MBOXNOMODSEQ
            );
        }

        $this->_temp['vanished'] = array();

        $this->_sendLine(array(
            'UID FETCH',
            $ids->all ? '1:*' : strval($ids),
            array(),
            array(
                'VANISHED',
                'CHANGEDSINCE',
                array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => intval($modseq))
            )
        ));

        return $this->getIdsOb($this->_temp['vanished']);
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

        if (!empty($this->_temp['mailbox']['highestmodseq'])) {
            $ucsince = empty($options['unchangedsince'])
                /* If CONDSTORE is enabled, we need to verify UNCHANGEDSINCE
                 * added to ensure we get MODSEQ updated information. */
                ? $this->_temp['mailbox']['highestmodseq']
                : intval($options['unchangedsince']);

            if ($ucsince) {
                $cmd[] = array(
                    'UNCHANGEDSINCE',
                    array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => $ucsince)
                );
            }
        } elseif (!empty($options['unchangedsince'])) {
            /* RFC 4551 [3.1] - trying to do a UNCHANGEDSINCE STORE on a
             * mailbox that doesn't support it will return BAD. Catch that
             * here and throw an exception. */
            throw new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Mailbox does not support mod-sequences."),
                Horde_Imap_Client_Exception::MBOXNOMODSEQ
            );
        }

        $this->_temp['modified'] = $this->getIdsOb();

        if (!empty($options['replace'])) {
            $cmd[] = 'FLAGS' . ($this->_debug ? '' : '.SILENT');
            foreach ($options['replace'] as $val) {
                $cmd[] = array('t' => Horde_Imap_Client::DATA_ATOM, 'v' => $val);
            }

            try {
                $this->_sendLine($cmd);
            } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
                // A NO response, when coupled with a sequence STORE and
                // non-SILENT behavior, most likely means that messages were
                // expunged. RFC 2180 [4.2]
                if (!empty($options['sequence']) &&
                    !$this->_debug &&
                    ($e->response == 'NO')) {
                    $this->_temp['expungeissued'] = true;
                }
            }

            $this->_storeUpdateCache('replace', $options['replace']);
        } else {
            foreach (array('add' => '+', 'remove' => '-') as $k => $v) {
                if (!empty($options[$k])) {
                    $cmdtmp = $cmd;
                    $cmdtmp[] = $v . 'FLAGS' . ($this->_debug ? '' : '.SILENT');
                    foreach ($options[$k] as $val) {
                        $cmdtmp[] = array('t' => Horde_Imap_Client::DATA_ATOM, 'v' => $val);
                    }

                    try {
                        $this->_sendLine($cmdtmp);
                    } catch (Horde_Imap_Client_Exception_ServerResponse $e) {
                        // A NO response, when coupled with a sequence STORE
                        // and non-SILENT behavior, most likely means that
                        // messages were expunged. RFC 2180 [4.2]
                        if (!empty($options['sequence']) &&
                            !$this->_debug &&
                            ($e->response == 'NO')) {
                            $this->_temp['expungeissued'] = true;
                        }
                    }

                    $this->_storeUpdateCache($k, $options[$k]);
                }
            }
        }

        $ret = $this->_temp['modified'];

        /* Check for EXPUNGEISSUED (RFC 2180 [4.2]/RFC 5530 [3]). */
        if (!empty($this->_temp['expungeissued'])) {
            unset($this->_temp['expungeissued']);
            $this->noop();
        }

        return $ret;
    }

    /**
     * Update the flags in the cache. Only update if STORE was successful and
     * flag information was not returned.
     */
    protected function _storeUpdateCache($type, $update_flags)
    {
        if (!isset($this->_init['enabled']['CONDSTORE']) ||
            empty($this->_temp['mailbox']['highestmodseq']) ||
            !count($this->_temp['fetchresp'])) {
            return;
        }

        $fr = $this->_temp['fetchresp'];
        $tocache = new Horde_Imap_Client_Fetch_Results();
        $uids = array();

        switch ($fr->key_type) {
        case $fr::SEQUENCE:
            $seq_res = $this->_getSeqUidLookup($this->getIdsOb($fr->ids(), true));
            break;

        case $fr::UID:
            $seq_res = null;
            break;
        }

        foreach ($fr as $key => $val) {
            if (!$val->exists(Horde_Imap_Client::FETCH_FLAGS)) {
                $uids[$key] = is_null($seq_res)
                    ? $key
                    : $seq_res['lookup'][$key];
            }
        }

        /* Get the list of flags from the cache. */
        switch ($type) {
        case 'add':
        case 'remove':
            /* Caching is guaranteed to be active if CONDSTORE is active. */
            $data = $this->_cache->get($this->_selected, array_values($uids), array('HICflags'), $this->_temp['mailbox']['uidvalidity']);

            foreach ($uids as $key => $uid) {
                $flags = isset($data[$uid]['HICflags'])
                    ? $data[$uid]['HICflags']
                    : array();
                if ($type == 'add') {
                    $flags = array_merge($flags, $update_flags);
                } else {
                    $flags = array_diff($flags, $update_flags);
                }

                $tocache[$uid] = $fr[$key];
                $tocache[$uid]->setFlags(array_keys(array_flip($flags)));
            }
            break;

        case 'update':
            foreach ($uids as $uid) {
                $tocache[$uid] = $fr[$key];
                $tocache[$uid]->setFlags($update_flags);
            }
            break;
        }

        if (count($tocache)) {
            $this->_updateCache($tocache, array(
                'fields' => array(
                    Horde_Imap_Client::FETCH_FLAGS
                )
            ));
        }
    }

    /**
     */
    protected function _copy(Horde_Imap_Client_Mailbox $dest, $options)
    {
        $this->_temp['copyuid'] = $this->_temp['copyuidvalid'] = $this->_temp['trycreate'] = null;
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
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $dest->utf7imap)
            ));
        } catch (Horde_Imap_Client_Exception $e) {
            if (!empty($options['create']) && $this->_temp['trycreate']) {
                $this->createMailbox($dest);
                unset($options['create']);
                return $this->_copy($dest, $options);
            }
            throw $e;
        }

        /* UIDPLUS (RFC 4315) allows easy determination of the UID of the
         * copied messages. If UID not returned, then destination mailbox
         * does not support persistent UIDs.
         * Use UIDPLUS information to move cached data to new mailbox (see
         * RFC 4549 [4.2.2.1]). */
        if (!is_null($this->_temp['copyuid'])) {
            $this->_moveCache($this->_selected, $dest, $this->_temp['copyuid'], $this->_temp['copyuidvalid']);
        }

        // If moving, delete the old messages now.
        if (!empty($options['move'])) {
            $opts = array('ids' => $options['ids']);
            $this->store($this->_selected, array_merge(array(
                'add' => array(Horde_Imap_Client::FLAG_DELETED)
            ), $opts));
            $this->expunge($this->_selected, $opts);
        }

        return is_null($this->_temp['copyuid'])
            ? true
            : $this->_temp['copyuid'];
    }

    /**
     */
    protected function _setQuota(Horde_Imap_Client_Mailbox $root, $resources)
    {
        $limits = array();

        foreach ($resources as $key => $val) {
            $limits[] = strtoupper($key);
            $limits[] = array('t' => Horde_Imap_Client::DATA_NUMBER, 'v' => intval($val));
        }

        $this->_sendLine(array(
            'SETQUOTA',
            array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $root->utf7imap),
            $limits
        ));
    }

    /**
     */
    protected function _getQuota(Horde_Imap_Client_Mailbox $root)
    {
        $this->_temp['quotaresp'] = array();
        $this->_sendLine(array(
            'GETQUOTA',
            array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $root->utf7imap)
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

        for ($i = 0; isset($data[1][$i]); $i += 3) {
            if (count($data[1][$i])) {
                $c[$root][strtolower($data[1][$i])] = array(
                    'limit' => $data[1][$i + 2],
                    'usage' => $data[1][$i + 1]
                );
            }
        }
    }

    /**
     */
    protected function _getQuotaRoot(Horde_Imap_Client_Mailbox $mailbox)
    {
        $this->_temp['quotaresp'] = array();
        $this->_sendLine(array(
            'GETQUOTAROOT',
            array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $mailbox->utf7imap)
        ));
        return $this->_temp['quotaresp'];
    }

    /**
     */
    protected function _setACL(Horde_Imap_Client_Mailbox $mailbox, $identifier,
                               $options)
    {
        // SETACL/DELETEACL returns no untagged information (RFC 4314 [3.1 &
        // 3.2]).
        if (empty($options['rights']) && !empty($options['remove'])) {
            $this->_sendLine(array(
                'DELETEACL',
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap),
                array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $identifier)
            ));
        } else {
            $this->_sendLine(array(
                'SETACL',
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap),
                array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $identifier),
                array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $options['rights'])
            ));
        }
    }

    /**
     */
    protected function _getACL(Horde_Imap_Client_Mailbox $mailbox)
    {
        $this->_temp['getacl'] = array();
        $this->_sendLine(array(
            'GETACL',
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap)
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
        for ($i = 1; isset($data[$i]); $i += 2) {
            $acl[$data[$i]] = ($data[$i][0] == '-')
                ? new Horde_Imap_Client_Data_AclNegative($data[$i + 1])
                : new Horde_Imap_Client_Data_Acl($data[$i + 1]);
        }
    }

    /**
     */
    protected function _listACLRights(Horde_Imap_Client_Mailbox $mailbox,
                                      $identifier)
    {
        unset($this->_temp['listaclrights']);
        $this->_sendLine(array(
            'LISTRIGHTS',
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap),
            array('t' => Horde_Imap_Client::DATA_ASTRING, 'v' => $identifier)
        ));

        return isset($this->_temp['listaclrights'])
            ? $this->_temp['listaclrights']
            : new Horde_Imap_Client_Data_AclRights();
    }

    /**
     * Parse a LISTRIGHTS response (RFC 4314 [3.7]).
     *
     * @param array $data  The server response.
     */
    protected function _parseListRights($data)
    {
        // Ignore mailbox and identifier arguments
        $this->_temp['listaclrights'] = new Horde_Imap_Client_Data_AclRights(
            str_split($data[2]),
            array_slice($data, 3)
        );
    }

    /**
     */
    protected function _getMyACLRights(Horde_Imap_Client_Mailbox $mailbox)
    {
        unset($this->_temp['myrights']);
        $this->_sendLine(array(
            'MYRIGHTS',
            array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap)
        ));

        return isset($this->_temp['myrights'])
            ? $this->_temp['myrights']
            : new Horde_Imap_Client_Data_Acl();
    }

    /**
     * Parse a MYRIGHTS response (RFC 4314 [3.8]).
     *
     * @param array $data  The server response.
     */
    protected function _parseMyRights($data)
    {
        $this->_temp['myrights'] = new Horde_Imap_Client_Data_Acl($data[1]);
    }

    /**
     */
    protected function _getMetadata(Horde_Imap_Client_Mailbox $mailbox,
                                    $entries, $options)
    {
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
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap),
                (empty($cmd_options) ? null : $cmd_options),
                $queries
            ));

            return $this->_temp['metadata'];
        }

        if (!$this->queryCapability('ANNOTATEMORE') &&
            !$this->queryCapability('ANNOTATEMORE2')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('METADATA');
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
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap),
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
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getAnnotateMoreEntry($name)
    {
        if (substr($name, 0, 7) == '/shared') {
            return array(substr($name, 7), 'value.shared');
        } else if (substr($name, 0, 8) == '/private') {
            return array(substr($name, 8), 'value.priv');
        }

        throw new Horde_Imap_Client_Exception(
            sprintf(Horde_Imap_Client_Translation::t("Invalid METADATA entry: \"%s\"."), $name),
            Horde_Imap_Client_Exception::METADATA_INVALID
        );
    }

    /**
     */
    protected function _setMetadata(Horde_Imap_Client_Mailbox $mailbox, $data)
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
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap),
                $data_elts
            ));

            return;
        }

        if (!$this->queryCapability('ANNOTATEMORE') &&
            !$this->queryCapability('ANNOTATEMORE2')) {
            throw new Horde_Imap_Client_Exception_NoSupportExtension('METADATA');
        }

        foreach ($data as $md_entry => $value) {
            list($entry, $type) = $this->_getAnnotateMoreEntry($md_entry);

            $this->_sendLine(array(
                'SETANNOTATION',
                array('t' => Horde_Imap_Client::DATA_MAILBOX, 'v' => $mailbox->utf7imap),
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
                    throw new Horde_Imap_Client_Exception(
                        sprintf(Horde_Imap_Client_Translation::t("Invalid METADATA value type \"%s\"."), $type),
                        Horde_Imap_Client_Exception::METADATA_INVALID
                    );
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

    /* Overriden methods. */

    /**
     */
    protected function _getSeqUidLookup(Horde_Imap_Client_Ids $ids,
                                        $reverse = false)
    {
        $ob = array(
            'lookup' => array(),
            'uids' => $this->getIdsOb()
        );

        if (!empty($this->_temp['mailbox']['lookup']) &&
            count($ids) &&
            ($ids->sequence || $reverse)) {
            $need = $this->getIdsOb(null, $ids->sequence);
            $t = $this->_temp['mailbox']['lookup'];

            foreach ($ids as $val) {
                if ($ids->sequence) {
                    if (isset($t[$val])) {
                        $ob['lookup'][$val] = $t[$val];
                        $ob['uids']->add($t[$val]);
                    } else {
                        $need->add($val);
                    }
                } else {
                    if (($key = array_search($val, $t)) !== false) {
                        $ob['lookup'][$key] = $val;
                        $ob['uids']->add($val);
                    } else {
                        $need->add($val);
                    }
                }
            }

            if (!count($need)) {
                return $ob;
            }

            $ids = $need;
        }

        $res = parent::_getSeqUidLookup($ids, $reverse);

        if (!empty($res['lookup'])) {
            $ob['lookup'] = $ob['lookup'] + $res['lookup'];
        }
        if (isset($res['uids'])) {
            $ob['uids']->add($res['uids']);
        }

        return $ob;
    }

    /**
     */
    protected function _getSearchCache($type, $mailbox, $options)
    {
        /* Search caching requires MODSEQ, which may not be active for a
         * mailbox. */
        return empty($this->_temp['mailbox']['highestmodseq'])
            ? null
            : parent::_getSearchCache($type, $mailbox, $options);
    }

    /* Internal functions. */

    /**
     * Perform a command on the IMAP server. A connection to the server must
     * have already been made.
     *
     * RFC 3501 allows the sending of multiple commands at once. For
     * simplicity of implementation, we will execute commands one at a time.
     * This allows us to easily determine data meant for a command while
     * scanning for untagged responses unilaterally sent by the server.
     * The only advantage of pipelining commands is to reduce the (small)
     * amount of overhead needed to send commands. Modern IMAP servers do not
     * meaningfully optimize response order internally, so that is not a
     * worthwhile reason to implement pipelining. Even the IMAP gurus admit
     * that pipelining is probably more trouble than it is worth.
     *
     * @param mixed $data    The IMAP command to execute. If string output as
     *                       is. If array, parsed via parseCommandArray(). If
     *                       resource, output directly to server.
     * @param array $options  Additional options:
     *   - binary: (boolean) Does $data contain binary data?  If so, and the
     *             'BINARY' extension is available on the server, the data
     *             will be sent in literal8 format. If not available, an
     *             exception will be returned. 'binary' requires literal to
     *             be defined.
     *             DEFAULT: Sends literals in a non-binary compliant method.
     *   - debug: (string) When debugging, send this string instead of the
     *            actual command/data sent.
     *            DEFAULT: Raw data output to debug stream.
     *   - fetch: (array) Use this as the initial value of the fetch results.
     *            DEFAULT: Fetch result is empty
     *   - literal: (integer) Send the command followed by a literal. The value
     *              of 'literal' is the length of the literal data.
     *              Will attempt to use LITERAL+ capability if possible.
     *              DEFAULT: Do not send literal
     *   - literaldata: (boolean) Is this literal data?
     *                  DEFAULT: Not literal data
     *   - noparse: (boolean) Don't parse the response and instead return the
     *              server response.
     *              DEFAULT: Parses the response
     *   - notag: (boolean) Don't prepend an IMAP tag (i.e. for a continuation
     *            response).
     *            DEFAULT: false
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _sendLine($data, array $options = array())
    {
        $out = '';

        if (empty($options['notag'])) {
            $out = ++$this->_tag . ' ';
            $this->_temp['fetchresp'] = isset($options['fetch'])
                ? $options['fetch']
                : null;
        }

        if (is_array($data)) {
            if (!empty($options['debug'])) {
                $this->_temp['sendnodebug'] = true;
            }
            $out = rtrim($this->utils->parseCommandArray($data, array($this, 'parseCommandArrayCallback'), $out));
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
                    throw new Horde_Imap_Client_Exception_NoSupport(
                        'BINARY',
                        'Cannot send binary data to server that does not support it.'
                    );
                }
                $out .= '~';
            }

            $out .= '{' . $options['literal'] . ($literalplus ? '+' : '') . '}';
        }

        if ($this->_debug && empty($this->_temp['sendnodebug'])) {
            if (is_resource($data)) {
                if (empty($this->_params['debug_literal'])) {
                    fseek($data, 0, SEEK_END);
                    $this->writeDebug('[LITERAL DATA - ' . ftell($data) . ' bytes]' . "\n", Horde_Imap_Client::DEBUG_CLIENT);
                } else {
                    rewind($data);
                    $this->writeDebug('', Horde_Imap_Client::DEBUG_CLIENT);
                    while (!feof($data)) {
                        $this->writeDebug(fread($data, 8192));
                    }
                }
            } else {
                $this->writeDebug((empty($options['debug']) ? $out : $options['debug']) . "\n", Horde_Imap_Client::DEBUG_CLIENT);
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
                $this->writeDebug("ERROR: Unexpected response from server while waiting for a continuation request.\n", Horde_Imap_Client::DEBUG_INFO);
                $e = new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Error when communicating with the mail server."),
                    'SERVER_READERROR'
                );
                $e->details = $ob['line'];
                throw $e;
            }
        } elseif (empty($options['noparse'])) {
            $this->_parseResponse($this->_tag);
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
            while (!feof($data)) {
                if (strpos(fread($data, 4096), "\0") !== false) {
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
     *   - line: (string) The server response text (set for all but an
     *           untagged response with no response code).
     *   - response: (string) Either 'OK', 'NO', 'BAD', 'PREAUTH', or ''.
     *   - tag: (string) If tagged response, the tag string.
     *   - token: (array) The tokenized response (set if an untagged response
     *            with no response code).
     *   - type: (string) Either 'tagged', 'untagged', or 'continuation'.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getLine()
    {
        $ob = array(
            'line' => '',
            'response' => '',
            'tag' => '',
            'token' => ''
        );

        $data = $this->_readData();
        $tag = $data->getToChar(' ');

        switch ($tag) {
        /* Continuation response. */
        case '+':
            $ob['line'] = $data->getString();
            $ob['type'] = 'continuation';
            break;

        /* Untagged response. */
        case '*':
            $ob['type'] = 'untagged';

            $data_pos = ftell($data->stream);
            $response = strtoupper($data->getToChar(' '));
            if ($response == 'BYE') {
                if (empty($this->_temp['logout'])) {
                    $this->_temp['logout'] = true;
                    $this->logout();
                    $e = new Horde_Imap_Client_Support(
                        Horde_Imap_Client_Translation::t("IMAP Server closed the connection."),
                        'DISCONNECT'
                    );
                    $e->details = $data->getString();
                    throw $e;
                }

                /* A BYE response received as part of a logout cmd should
                 * be treated like a regular command. A client MUST
                 * process the entire command until logging out. RFC 3501
                 * [3.4]. */
                $ob['response'] = 'BYE';
                $ob['line'] = $data->getString();
            }

            if (in_array($response, array('OK', 'NO', 'BAD', 'PREAUTH'))) {
                $ob['response'] = $response;
                $ob['line'] = $data->getString();
            } else {
                /* Tokenize response. */
                do {
                    $binary = false;
                    $literal_len = null;

                    fseek($data->stream, -1, SEEK_END);
                    if ($data->peek() == '}') {
                        $literal_data = $data->getString($data->search('{', true) - 1);
                        $literal_len = substr($literal_data, 2, -1);
                        if (!is_numeric($literal_len)) {
                            $literal_len = null;
                        } elseif ($literal_data[0] == '~') {
                            $binary = true;
                        }
                    }

                    fseek($data->stream, is_null($data_pos) ? 0 : $data_pos);
                    $data_pos = null;

                    $this->_tokenizeData(
                        $data->getString(null, is_null($literal_len) ? null : (strlen($literal_data) * -1))
                    );

                    if (is_null($literal_len)) {
                        break;
                    }

                    $this->_temp['token']->ptr[$this->_temp['token']->paren][] = $this->_readData($literal_len, $binary)->getString();
                    $data = $this->_readData();
                } while (true);

                $ob['token'] = $this->_temp['token']->out;
                unset($this->_temp['token']);
            }
            break;

        /* Tagged response. */
        default:
            $ob['type'] = 'tagged';
            $ob['response'] = $data->getToChar(' ');
            $ob['line'] = $data->getString();
            $ob['tag'] = $tag;
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
     * @return Horde_Stream_Temp  The data stream.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _readData($len = null, $binary = false)
    {
        if (feof($this->_stream)) {
            $this->_temp['logout'] = true;
            $this->logout();
            $this->writeDebug("ERROR: Server closed the connection.\n", Horde_Imap_Client::DEBUG_INFO);
            throw new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Mail server closed the connection unexpectedly."),
                Horde_Imap_Client_Exception::DISCONNECT
            );
        }

        $data = new Horde_Stream_Temp();
        $got_data = false;

        if (is_null($len)) {
            do {
                /* Can't do a straight fgets() because extremely large lines
                 * will result in read errors. */
                if ($in = fgets($this->_stream, 8192)) {
                    $got_data = true;
                    if (!isset($in[8190]) || ($in[8190] == "\n")) {
                        fwrite($data->stream, rtrim($in));
                        break;
                    }
                    fwrite($data->stream, $in);
                }
            } while ($in !== false);
        } elseif (!$len) {
            // Skip 0-length literal data
            return $data;
        } else {
            $old_len = $len;

            while ($len && !feof($this->_stream)) {
                $in = fread($this->_stream, min($len, 8192));
                fwrite($data->stream, $in);

                $got_data = true;

                $in_len = strlen($in);
                if ($in_len > $len) {
                    break;
                }
                $len -= $in_len;
            }
        }

        if (!$got_data) {
            $this->writeDebug("ERROR: IMAP read/timeout error.\n", Horde_Imap_Client::DEBUG_INFO);
            $this->logout();
            throw new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Error when communicating with the mail server."),
                Horde_Imap_Client_Exception::SERVER_READERROR
            );
        }

        if ($this->_debug) {
            if ($binary) {
                $this->writeDebug('[BINARY DATA - ' . $old_len . ' bytes]' . "\n", Horde_Imap_Client::DEBUG_SERVER);
            } elseif (!is_null($len) &&
                      empty($this->_params['debug_literal'])) {
                $this->writeDebug('[LITERAL DATA - ' . $old_len . ' bytes]' . "\n", Horde_Imap_Client::DEBUG_SERVER);
            } else {
                $this->writeDebug($data->getString(0) . "\n", Horde_Imap_Client::DEBUG_SERVER);
            }
        }

        rewind($data->stream);

        return $data;
    }

    /**
     * Tokenize IMAP data. Handles quoted strings and parentheses.
     *
     * @param string $line  The raw IMAP data.
     */
    protected function _tokenizeData($line)
    {
        if (empty($this->_temp['token'])) {
            $c = $this->_temp['token'] = new stdClass;
            $c->in_quote = false;
            $c->out = array();
            $c->paren = 0;
            $c->ptr = array(&$c->out);
        } else {
            $c = $this->_temp['token'];
        }

        $tmp = '';

        for ($i = 0, $len = strlen($line); $i < $len; ++$i) {
            $char = $line[$i];
            switch ($char) {
            case '"':
                if ($c->in_quote) {
                    if ($i && ($line[$i - 1] != '\\')) {
                        $c->in_quote = false;
                        $c->ptr[$c->paren][] = stripcslashes($tmp);
                        $tmp = '';
                    } else {
                        $tmp .= $char;
                    }
                } else {
                    $c->in_quote = true;
                }
                break;

            default:
                if ($c->in_quote) {
                    $tmp .= $char;
                    break;
                }

                switch ($char) {
                case '(':
                    $c->ptr[$c->paren][] = array();
                    $c->ptr[$c->paren + 1] = &$c->ptr[$c->paren][count($c->ptr[$c->paren]) - 1];
                    ++$c->paren;
                    break;

                case ')':
                    if (strlen($tmp)) {
                        $c->ptr[$c->paren][] = $tmp;
                        $tmp = '';
                    }
                    --$c->paren;
                    break;

                case ' ':
                    if (strlen($tmp)) {
                        $c->ptr[$c->paren][] = $tmp;
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
            $c->ptr[$c->paren][] = $tmp;
        }
    }

    /**
     * Parse all untagged and tagged responses for a given command.
     *
     * @param string $tag  The IMAP tag of the current command.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _parseResponse($tag)
    {
        while ($ob = $this->_getLine()) {
            if (($ob['type'] == 'tagged') && ($ob['tag'] == $tag)) {
                // Here we know there isn't an untagged response, so directly
                // call _parseStatusResponse().
                $e = $this->_parseStatusResponse($ob);

                // Now that any status response has been processed, we can
                // throw errors if appropriate.
                switch ($ob['response']) {
                case 'BAD':
                case 'NO':
                    if (is_null($e)) {
                        throw new Horde_Imap_Client_Exception_ServerResponse(
                            Horde_Imap_Client_Translation::t("IMAP error reported by server."),
                            0,
                            $ob['response'],
                            trim($ob['line'])
                        );
                    } else {
                        $e->response = $ob['response'];
                    }

                    throw $e;
                }

                /* Update the cache, if needed. */
                if (!is_null($this->_temp['fetchresp'])) {
                    $this->_updateCache($this->_temp['fetchresp']);
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
     *
     * @return Horde_Imap_Client_Exception_ServerResponse  Exception if error
     *                                                     status response is
     *                                                     found.
     */
    protected function _parseStatusResponse($ob)
    {
        $response = $this->_parseResponseText($ob['line']);
        if (!isset($response->code)) {
            return null;
        }

        switch ($response->code) {
        case 'ALERT':
        // Defined by RFC 5530 [3] - Treat as an alert for now.
        case 'CONTACTADMIN':
            if (!isset($this->_temp['alerts'])) {
                $this->_temp['alerts'] = array();
            }
            $this->_temp['alerts'][] = $response->text;
            break;

        case 'BADCHARSET':
            $this->_tokenizeData($response->data);

            /* Store valid search charsets if returned by server. */
            if (!empty($this->_temp['token']->out)) {
                $s_charset = $this->_init['s_charset'];
                foreach ($this->_temp['token']->out as $val) {
                    $s_charset[$val] = true;
                }
                $this->_setInit('s_charset', $s_charset);
            }

            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("Charset used in search query is not supported on the mail server."),
                Horde_Imap_Client_Exception::BADCHARSET,
                null,
                $response->text
            );

        case 'CAPABILITY':
            $this->_tokenizeData($response->data);
            $this->_parseCapability($this->_temp['token']->out);
            unset($this->_temp['token']);
            break;

        case 'PARSE':
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The mail server was unable to parse the contents of the mail message."),
                Horde_Imap_Client_Exception::PARSEERROR,
                null,
                $response->text
            );

        case 'READ-ONLY':
            $this->_mode = Horde_Imap_Client::OPEN_READONLY;
            break;

        case 'READ-WRITE':
            $this->_mode = Horde_Imap_Client::OPEN_READWRITE;
            break;

        case 'TRYCREATE':
            // RFC 3501 [7.1]
            $this->_temp['trycreate'] = true;
            break;

        case 'PERMANENTFLAGS':
            $this->_tokenizeData($response->data);
            $this->_temp['mailbox']['permflags'] = array_map('strtolower', reset($this->_temp['token']->out));
            unset($this->_temp['token']);
            break;

        case 'UIDNEXT':
        case 'UIDVALIDITY':
            $this->_temp['mailbox'][strtolower($response->code)] = $response->data;
            break;

        case 'UNSEEN':
            /* This is different from the STATUS UNSEEN response - this item,
             * if defined, returns the first UNSEEN message in the mailbox. */
            $this->_temp['mailbox']['firstunseen'] = $response->data;
            break;

        case 'REFERRAL':
            // Defined by RFC 2221
            $this->_temp['referral'] = $this->utils->parseUrl($response->data);
            break;

        case 'UNKNOWN-CTE':
            // Defined by RFC 3516
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The mail server was unable to parse the contents of the mail message."),
                Horde_Imap_Client_Exception::UNKNOWNCTE,
                null,
                $response->text
            );

        case 'APPENDUID':
        case 'COPYUID':
            // Defined by RFC 4315
            // APPENDUID: [0] = UIDVALIDITY, [1] = UID(s)
            // COPYUID: [0] = UIDVALIDITY, [1] = UIDFROM, [2] = UIDTO
            $parts = explode(' ', $response->data);

            if ($this->_temp['uidplusmbox']->equals($this->_selected) &&
                ($this->_temp['mailbox']['uidvalidity'] != $parts[0])) {
                $this->_temp['mailbox'] = array('uidvalidity' => $parts[0]);
                $this->_temp['searchnotsaved'] = true;
            }

            /* Check for cache expiration (see RFC 4549 [4.1]). */
            $this->_updateCache(new Horde_Imap_Client_Fetch_Results(), array(
                'mailbox' => $this->_temp['uidplusmbox'],
                'uidvalid' => $parts[0]
            ));

            if ($response->code == 'APPENDUID') {
                $this->_temp['appenduid'] = array_merge($this->_temp['appenduid'], $this->utils->fromSequenceString($parts[1]));
            } else {
                $this->_temp['copyuid'] = array_combine($this->utils->fromSequenceString($parts[1]), $this->utils->fromSequenceString($parts[2]));
                $this->_temp['copyuidvalid'] = $parts[0];
            }
            break;

        case 'UIDNOTSTICKY':
            // Defined by RFC 4315 [3]
            $this->_temp['mailbox']['uidnotsticky'] = true;
            break;

        case 'BADURL':
            // Defined by RFC 4469 [4.1]
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("Could not save message on server."),
                Horde_Imap_Client_Exception::CATENATE_BADURL,
                null,
                $response->text
            );

        case 'TOOBIG':
            // Defined by RFC 4469 [4.2]
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("Could not save message data because it is too large."),
                Horde_Imap_Client_Exception::CATENATE_TOOBIG,
                null,
                $response->text
            );

        case 'HIGHESTMODSEQ':
            // Defined by RFC 4551 [3.1.1]
            $this->_temp['mailbox']['highestmodseq'] = $response->data;
            break;

        case 'NOMODSEQ':
            // Defined by RFC 4551 [3.1.2]
            $this->_temp['mailbox']['highestmodseq'] = 0;
            break;

        case 'MODIFIED':
            // Defined by RFC 4551 [3.2]
            $this->_temp['modified']->add($response->data);
            break;

        case 'CLOSED':
            // Defined by RFC 5162 [3.7]
            if (isset($this->_temp['qresyncmbox'])) {
                $this->_temp['mailbox'] = array(
                    'name' => $this->_temp['qresyncmbox']
                );
                $this->_selected = $this->_temp['qresyncmbox'];
            }
            break;

        case 'NOTSAVED':
            // Defined by RFC 5182 [2.5]
            $this->_temp['searchnotsaved'] = true;
            break;

        case 'BADCOMPARATOR':
            // Defined by RFC 5255 [4.9]
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The comparison algorithm was not recognized by the server."),
                Horde_Imap_Client_Exception::BADCOMPARATOR,
                null,
                $response->text
            );

        case 'METADATA':
            $this->_tokenizeData($response->data);

            switch (reset($this->_temp['token']->out)) {
            case 'LONGENTRIES':
                // Defined by RFC 5464 [4.2.1]
                $this->_temp['metadata']['*longentries'] = intval(end($this->_temp['token']->out));
                break;

            case 'MAXSIZE':
                // Defined by RFC 5464 [4.3]
                return new Horde_Imap_Client_Exception_ServerResponse(
                    Horde_Imap_Client_Translation::t("The metadata item could not be saved because it is too large."),
                    Horde_Imap_Client_Exception::METADATA_MAXSIZE,
                    null,
                    intval(end($this->_temp['token']->out))
                );

            case 'NOPRIVATE':
                // Defined by RFC 5464 [4.3]
                return new Horde_Imap_Client_Exception_ServerResponse(
                    Horde_Imap_Client_Translation::t("The metadata item could not be saved because the server does not support private annotations."),
                    Horde_Imap_Client_Exception::METADATA_NOPRIVATE,
                    null,
                    $response->text
                );

            case 'TOOMANY':
                // Defined by RFC 5464 [4.3]
                return new Horde_Imap_Client_Exception_ServerResponse(
                    Horde_Imap_Client_Translation::t("The metadata item could not be saved because the maximum number of annotations has been exceeded."),
                    Horde_Imap_Client_Exception::METADATA_TOOMANY,
                    null,
                    $response->text
                );
            }
            break;

        case 'UNAVAILABLE':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Remote server is temporarily unavailable."),
                Horde_Imap_Client_Exception::LOGIN_UNAVAILABLE
            );
            break;

        case 'AUTHENTICATIONFAILED':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Authentication failed."),
                Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED
            );
            break;

        case 'AUTHORIZATIONFAILED':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Authentication was successful, but authorization failed."),
                Horde_Imap_Client_Exception::LOGIN_AUTHORIZATIONFAILED
            );
            break;

        case 'EXPIRED':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Authentication credentials have expired."),
                Horde_Imap_Client_Exception::LOGIN_EXPIRED
            );
            break;

        case 'PRIVACYREQUIRED':
            // Defined by RFC 5530 [3]
            $this->_temp['loginerr'] = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Operation failed due to a lack of a secure connection."),
                Horde_Imap_Client_Exception::LOGIN_PRIVACYREQUIRED
            );
            break;

        case 'NOPERM':
            // Defined by RFC 5530 [3]
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("You do not have adequate permissions to carry out this operation."),
                Horde_Imap_Client_Exception::NOPERM,
                null,
                $response->text
            );

        case 'INUSE':
            // Defined by RFC 5530 [3]
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("There was a temporary issue when attempting this operation. Please try again later."),
                Horde_Imap_Client_Exception::INUSE,
                null,
                $response->text
            );

        case 'EXPUNGEISSUED':
            // Defined by RFC 5530 [3]
            $this->_temp['expungeissued'] = true;
            break;

        case 'CORRUPTION':
            // Defined by RFC 5530 [3]
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The mail server is reporting corrupt data in your mailbox."),
                Horde_Imap_Client_Exception::CORRUPTION,
                null,
                $response->text
            );

        case 'SERVERBUG':
        case 'CLIENTBUG':
        case 'CANNOT':
            // Defined by RFC 5530 [3]
            $this->writeDebug("ERROR: mail server explicitly reporting an error.\n", Horde_Imap_Client::DEBUG_INFO);
            break;

        case 'LIMIT':
            // Defined by RFC 5530 [3]
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The mail server has denied the request."),
                Horde_Imap_Client_Exception::LIMIT,
                null,
                $response->text
            );

        case 'OVERQUOTA':
            // Defined by RFC 5530 [3]
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The operation failed because the quota has been exceeded on the mail server."),
                Horde_Imap_Client_Exception::OVERQUOTA,
                null,
                $response->text
            );

        case 'ALREADYEXISTS':
            // Defined by RFC 5530 [3]
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The object could not be created because it already exists."),
                Horde_Imap_Client_Exception::ALREADYEXISTS,
                null,
                $response->text
            );

        case 'NONEXISTENT':
            // Defined by RFC 5530 [3]
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The object could not be deleted because it does not exist."),
                Horde_Imap_Client_Exception::NONEXISTENT,
                null,
                $response->text
            );

        case 'USEATTR':
            // Defined by RFC 6154 [3]
            return new Horde_Imap_Client_Exception_ServerResponse(
                Horde_Imap_Client_Translation::t("The special-use attribute requested for the mailbox is not supported."),
                Horde_Imap_Client_Exception::USEATTR,
                null,
                $response->text
            );

        case 'XPROXYREUSE':
            // The proxy connection was reused, so no need to do login tasks.
            $this->_temp['proxyreuse'] = true;
            break;

        default:
            // Unknown response codes SHOULD be ignored - RFC 3501 [7.1]
            break;
        }

        return null;
    }

}

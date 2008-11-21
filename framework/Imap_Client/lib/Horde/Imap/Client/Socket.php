<?php
/**
 * Horde_Imap_Client_Socket:: provides an interface to an IMAP4rev1 server
 * (RFC 3501) using PHP functions.
 *
 * Optional Parameters: NONE
 *
 * This driver implements the following IMAP-related RFCs:
 *   RFC 2086/4314 - ACL
 *   RFC 2087 - QUOTA
 *   RFC 2088 - LITERAL+
 *   RFC 2195 - AUTH=CRAM-MD5
 *   RFC 2221 - LOGIN-REFERRALS
 *   RFC 2342 - NAMESPACE
 *   RFC 2595/4616 - AUTH=PLAIN
 *   RFC 2831 - DIGEST-MD5 authentication mechanism.
 *   RFC 2971 - ID
 *   RFC 3501 - IMAP4rev1 specification
 *   RFC 3502 - MULTIAPPEND
 *   RFC 3516 - BINARY
 *   RFC 3691 - UNSELECT
 *   RFC 4315 - UIDPLUS
 *   RFC 4422 - SASL Authentication (for DIGEST-MD5)
 *   RFC 4466 - Collected extensions (updates RFCs 2088, 3501, 3502, 3516)
 *   RFC 4551 - CONDSTORE
 *   RFC 4731 - ESEARCH
 *   RFC 4959 - SASL-IR
 *   RFC 5032 - WITHIN
 *   RFC 5161 - ENABLE
 *   RFC 5162 - QRESYNC
 *   RFC 5182 - SEARCHRES
 *   RFC 5255 - LANGUAGE/I18NLEVEL
 *   RFC 5256 - THREAD/SORT
 *   RFC 5267 - ESORT
 *
 *   [NO RFC] - XIMAPPROXY
 *              + Requires imapproxy v1.2.7-rc1 or later
 *              + See http://lists.andrew.cmu.edu/pipermail/imapproxy-info/2008-October/000771.html and
 *                http://lists.andrew.cmu.edu/pipermail/imapproxy-info/2008-October/000772.html
 *
 * TODO (or not necessary?):
 *   RFC 2177 - IDLE (probably not necessary due to the limited connection
 *                    time by each HTTP/PHP request)
 *   RFC 2193 - MAILBOX-REFERRALS
 *   RFC 4467/5092 - URLAUTH
 *   RFC 4469 - CATENATE
 *   RFC 4978 - COMPRESS=DEFLATE
 *   RFC 3348/5258 - LIST-EXTENDED
 *   RFC 5257 - ANNOTATE
 *   RFC 5259 - CONVERT
 *   RFC 5267 - CONTEXT
 *
 * Originally based on code from:
 *   + auth.php (1.49)
 *   + imap_general.php (1.212)
 *   + imap_messages.php (revision 13038)
 *   + strings.php (1.184.2.35)
 *   from the Squirrelmail project.
 *   Copyright (c) 1999-2007 The SquirrelMail Project Team
 *
 * Copyright 2005-2008 The Horde Project (http://www.horde.org/)
 *
 * $Horde: framework/Imap_Client/lib/Horde/Imap/Client/Socket.php,v 1.99 2008/10/29 05:13:00 slusarz Exp $
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @package  Horde_Imap_Client
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
     * Temp array (destroyed at end of process).
     *
     * @var array
     */
    protected $_temp = array();

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->logout();
        parent::__destruct();
    }

    /**
     * Do cleanup prior to serialization and provide a list of variables
     * to serialize.
     */
    function __sleep()
    {
        $this->logout();
        $this->_temp = array();
        $this->_tag = 0;
        parent::__sleep();
        return array_diff(array_keys(get_class_vars(__CLASS__)), array('encryptKey'));
    }

    /**
     * Get CAPABILITY info from the IMAP server.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @return array  The capability array.
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

        $c = &$this->_init['capability'];
        $c = array();

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

        if (!empty($this->_temp['in_login'])) {
            $this->_temp['logincapset'] = true;
        }
    }

    /**
     * Send a NOOP command.
     * Throws a Horde_Imap_Client_Exception on error.
     */
    protected function _noop()
    {
        // NOOP doesn't return any specific response
        $this->_sendLine('NOOP');
    }

    /**
     * Get the NAMESPACE information from the IMAP server (RFC 2342).
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @return array  An array of namespace information.
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
     * Return a list of alerts that MUST be presented to the user.
     *
     * @return array  An array of alert messages.
     */
    public function alerts()
    {
        return empty($this->_temp['alerts']) ? array() : $this->_temp['alerts'];
    }

    /**
     * Login to the IMAP server.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @return boolean  Return true if global login tasks should be run.
     */
    protected function _login()
    {
        if (!empty($this->_temp['preauth'])) {
            return $this->_loginTasks();
        }

        $this->_connect();

        $t = &$this->_temp;

        // Switch to secure channel if using TLS.
        if (!$this->_isSecure &&
            ($this->_params['secure'] == 'tls')) {
            if (!$this->queryCapability('STARTTLS')) {
                // We should never hit this - STARTTLS is required pursuant
                // to RFC 3501 [6.2.1].
                throw new Horde_Imap_Client_Exception('Server does not support TLS connections.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
            }

            // Switch over to a TLS connection.
            // STARTTLS returns no untagged response.
            $this->_sendLine('STARTTLS');

            $old_error = error_reporting(0);
            $res = stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            error_reporting($old_error);

            if (!$res) {
                $this->logout();
                throw new Horde_Imap_Client_Exception('Could not open secure TLS connection to the IMAP server.');
            }

            // Expire cached CAPABILITY information (RFC 3501 [6.2.1])
            unset($this->_init['capability']);

            // Reset language (RFC 5255 [3.1])
            unset($this->_init['lang']);

            // Set language if not using imapproxy
            if ($this->_init['imapproxy']) {
                $this->setLanguage();
            }

            $this->_isSecure = true;
        }

        if (empty($this->_init['authmethod'])) {
            $first_login = true;
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
                throw new Horde_Imap_Client_Exception('No supported IMAP authentication method could be found.');
            }

            /* Use MD5 authentication first, if available. But no need to use
             * use special authentication if we are already using an
             * encrypted connection. */
            if ($this->_isSecure) {
                $imap_auth_mech = array_reverse($imap_auth_mech);
            }
        } else {
            $first_login = false;
            $imap_auth_mech = array($this->_init['authmethod']);
        }

        foreach ($imap_auth_mech as $method) {
            $t['referral'] = null;

            /* Set a flag indicating whether we have received a CAPABILITY
             * response after we successfully login. Since capabilities may
             * be different after login, this is the value we should end up
             * caching if the object is eventually serialized. */
            $this->_temp['in_login'] = true;

            try {
                $this->_tryLogin($method);
                $success = true;
                $this->_init['authmethod'] = $method;
                unset($t['referralcount']);
            } catch (Horde_Imap_Client_Exception $e) {
                $success = false;
                if (!empty($this->_init['authmethod'])) {
                    unset($this->_init['authmethod']);
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
                    $this->_init['authmethod'] = $t['referral']['auth'];
                }

                if (!isset($t['referralcount'])) {
                    $t['referralcount'] = 0;
                }

                // RFC 2221 [3] - Don't follow more than 10 levels of referral
                // without consulting the user.
                if (++$t['referralcount'] < 10) {
                    $this->logout();
                    unset($this->_init['capability']);
                    $this->_init['namespace'] = array();
                    return $this->login();
                }

                unset($t['referralcount']);
            }

            if ($success) {
                return $this->_loginTasks($first_login);
            }
        }

        throw new Horde_Imap_Client_Exception('IMAP server denied authentication.');
    }

    /**
     * Connects to the IMAP server.
     * Throws a Horde_Imap_Client_Exception on error.
     */
    protected function _connect()
    {
        if (!is_null($this->_stream)) {
            return;
        }

        if (!empty($this->_params['secure']) && !extension_loaded('openssl')) {
            throw new Horde_Imap_Client_Exception('Secure connections require the PHP openssl extension.');
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
            throw new Horde_Imap_Client_Exception('Error connecting to IMAP server: [' . $error_number . '] ' . $error_string);
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
            throw new Horde_Imap_Client_Exception('Server rejected connection: ' . $ob['line']);

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
            throw new Horde_Imap_Client_Exception('This server does not support IMAP4rev1 (RFC 3501).');
        }

        // Set language if not using imapproxy
        if (empty($this->_init['imapproxy'])) {
            $this->_init['imapproxy'] = $this->queryCapability('XIMAPPROXY');
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
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $method  IMAP login method.
     */
    protected function _tryLogin($method)
    {
        switch ($method) {
        case 'CRAM-MD5':
        case 'DIGEST-MD5':
            $this->_sendLine('AUTHENTICATE ' . $method);

            switch ($method) {
            case 'CRAM-MD5':
                // RFC 2195
                $auth_sasl = Auth_SASL::factory('crammd5');
                $response = base64_encode($auth_sasl->getResponse($this->_params['username'], $this->_params['password'], base64_decode($ob['line'])));
                $this->_sendLine($response, array('debug' => '[CRAM-MD5 Response]', 'notag' => true));
                break;

            case 'DIGEST-MD5':
                $auth_sasl = Auth_SASL::factory('digestmd5');
                $response = base64_encode($auth_sasl->getResponse($this->_params['username'], $this->_params['password'], base64_decode($ob['line']), $this->_params['hostspec'], 'imap'));
                $ob = $this->_sendLine($response, array('debug' => '[DIGEST-MD5 Response]', 'noparse' => true, 'notag' => true));
                $response = base64_decode($ob['line']);
                if (strpos($response, 'rspauth=') === false) {
                    throw new Horde_Imap_Client_Exception('Unexpected response from server to Digest-MD5 response.');
                }
                $this->_sendLine('', array('notag' => true));
                break;
            }
            break;

        case 'LOGIN':
            $this->_sendLine('LOGIN ' . $this->escape($this->_params['username']) . ' ' . $this->escape($this->_params['password']), array('debug' => '[LOGIN Command]'));
            break;

        case 'PLAIN':
            // RFC 2595/4616 - PLAIN SASL mechanism
            $auth = base64_encode(implode("\0", array($this->_params['username'], $this->_params['username'], $this->_params['password'])));
            if ($this->queryCapability('SASL-IR')) {
                // IMAP Extension for SASL Initial Client Response (RFC 4959)
                $this->_sendLine('AUTHENTICATE PLAIN ' . $auth, array('debug' => '[SASL-IR AUTHENTICATE Command]'));
            } else {
                $this->_sendLine('AUTHENTICATE PLAIN');
                $this->_sendLine($auth, array('debug' => '[AUTHENTICATE Command]', 'notag' => true));
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

        $this->_init['enabled'] = array();

        /* If we logged in for first time, and server did not return
         * capability information, we need to grab it now. */
        if ($firstlogin && empty($this->_temp['logincapset'])) {
            unset($this->_init['capability']);
        }
        $this->setLanguage();

        /* Only active QRESYNC/CONDSTORE if caching is enabled. */
        if ($this->_initCacheOb()) {
            if ($this->queryCapability('QRESYNC')) {
                /* QRESYNC REQUIRES ENABLE, so we just need to send one ENABLE
                 * QRESYNC call to enable both QRESYNC && CONDSTORE. */
                $this->_enable(array('QRESYNC'));
                $this->_init['enabled']['CONDSTORE'] = true;
            } elseif ($this->queryCapability('CONDSTORE')) {
                /* CONDSTORE may be available, but ENABLE may not be. */
                if ($this->queryCapability('ENABLE')) {
                    $this->_enable(array('CONDSTORE'));
                }
            }
        }

        return true;
    }

    /**
     * Log out of the IMAP session.
     */
    protected function _logout()
    {
        if (!is_null($this->_stream)) {
            if (empty($this->_temp['logout'])) {
                $this->_temp['logout'] = true;
                try {
                    $this->_sendLine('LOGOUT');
                } catch (Horde_Imap_Client_Exception $e) {}
            }
            unset($this->_temp['logout']);
            fclose($this->_stream);
            $this->_stream = null;
        }
    }

    /**
     * Send ID information to the IMAP server (RFC 2971).
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param array $info  The information to send to the server.
     */
    protected function _sendID($info)
    {
        if (empty($info)) {
            $cmd = 'NIL';
        } else {
            $cmd = '(';
            foreach ($info as $key => $val) {
                $cmd .= $this->escape(strtolower($key)) . ' ' . $this->escape($val);
            }
            $cmd .= ')';
        }

        $this->_sendLine('ID ' . $cmd);
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
                $this->_temp['id'][$d[$i]] = $d[$i + 1];
            }
        }
    }

    /**
     * Return ID information from the IMAP server (RFC 2971).
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @return array  An array of information returned, with the keys as the
     *                'field' and the values as the 'value'.
     */
    protected function _getID()
    {
        if (!isset($this->_temp['id'])) {
            $this->sendID();
        }
        return $this->_temp['id'];
    }

    /**
     * Sets the preferred language for server response messages (RFC 5255).
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param array $info  The preferred list of languages.
     *
     * @return string  The language accepted by the server, or null if the
     *                 default language is used.
     */
    protected function _setLanguage($langs)
    {
        $cmd = array();
        foreach ($langs as $val) {
            $cmd[] = $this->escape($val);
        }

        try {
            $this->_sendLine('LANGUAGE ' . implode(' ', $cmd));
        } catch (Horde_Imap_Client_Exception $e) {
            $this->_init['lang'] = null;
            return null;
        }

        return $this->_init['lang'];
    }

    /**
     * Gets the preferred language for server response messages (RFC 5255).
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param array $list  If true, return the list of available languages.
     *
     * @return mixed  If $list is true, the list of languages available on the
     *                server (may be empty). If false, the language used by
     *                the server, or null if the default language is used.
     */
    protected function _getLanguage($list)
    {
        if (!$list) {
            return empty($this->_init['lang']) ? null : $this->_init['lang'];
        }

        if (!isset($this->_init['langavail'])) {
            try {
                $this->_sendLine('LANGUAGE');
            } catch (Horde_Imap_Client_Exception $e) {
                $this->_init['langavail'] = array();
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
            $this->_init['lang'] = reset($data[0]);
        } else {
            // These are the languages that are available.
            $this->_init['langavail'] = $data[0];
        }
    }

    /**
     * Enable an IMAP extension (see RFC 5161).
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param array $exts  The extensions to enable.
     */
    protected function _enable($exts)
    {
        // Only enable non-enabled extensions
        $exts = array_diff($exts, array_keys($this->_init['enabled']));
        if (!empty($exts)) {
            $this->_sendLine('ENABLE ' . implode(' ', array_map('strtoupper', $exts)));
        }
    }

    /**
     * Parse an ENABLED response (RFC 5161 [3.2])
     *
     * @param array $data  The server response.
     */
    protected function _parseEnabled($data)
    {
        $this->_init['enabled'] = array_merge($this->_init['enabled'], array_flip($data));
    }

    /**
     * Open a mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $mailbox  The mailbox to open (UTF7-IMAP).
     * @param integer $mode    The access mode.
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

        $cmd = (($mode == self::OPEN_READONLY) ? 'EXAMINE' : 'SELECT') . ' ' . $this->escape($mailbox);

        /* If QRESYNC is available, synchronize the mailbox. */
        if ($qresync) {
            $this->_initCacheOb();
            $metadata = $this->_cacheOb->getMetaData($mailbox, array('HICmodseq', 'uidvalid'));
            if (isset($metadata['HICmodseq'])) {
                $uids = $this->_cacheOb->get($mailbox);
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
                    $cmd .= ' (QRESYNC (' . $metadata['uidvalid'] . ' ' . $metadata['HICmodseq'] . ' ' . $this->toSequenceString($uids) . '))';
                }
            }
        } elseif (!isset($this->_init['enabled']['CONDSTORE']) &&
                  $this->_initCacheOb() &&
                  $this->queryCapability('CONDSTORE')) {
            /* Activate CONDSTORE now if ENABLE is not available. */
            $cmd .= ' (CONDSTORE)';
            $condstore = true;
        }

        try {
            $this->_sendLine($cmd);
        } catch (Horde_Imap_Client_Exception $e) {
            // An EXAMINE/SELECT failure with a return of 'NO' will cause the
            // current mailbox to be unselected.
            if ($this->_temp['parseresperr']['response'] == 'NO') {
                $this->_selected = null;
                $this->_mode = 0;
            }
            throw $e;
        }

        if ($qresync && isset($metadata['uidvalid'])) {
            if (is_null($this->_temp['mailbox']['highestmodseq']) ||
                ($this->_temp['mailbox']['uidvalidity'] != $metadata['uidvalid'])) {
                $this->_cacheOb->deleteMailbox($mailbox);
            } else {
                /* We know the mailbox has been updated, so update the
                 * highestmodseq metadata in the cache. */
                $this->_cacheOb->setMetaData($mailbox, array('HICmodseq' => $this->_temp['mailbox']['highestmodseq']));
            }
        } elseif ($condstore) {
            $this->_init['enabled']['CONDSTORE'] = true;
        }
    }

    /**
     * Create a mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $mailbox  The mailbox to create (UTF7-IMAP).
     */
    protected function _createMailbox($mailbox)
    {
        $this->login();

        // CREATE returns no untagged information (RFC 3501 [6.3.3])
        $this->_sendLine('CREATE ' . $this->escape($mailbox));
    }

    /**
     * Delete a mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $mailbox  The mailbox to delete (UTF7-IMAP).
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
            $this->_sendLine('DELETE ' . $this->escape($mailbox));
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
     * Rename a mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $old     The old mailbox name (UTF7-IMAP).
     * @param string $new     The new mailbox name (UTF7-IMAP).
     */
    protected function _renameMailbox($old, $new)
    {
        $this->login();

        // RENAME returns no untagged information (RFC 3501 [6.3.5])
        $this->_sendLine('RENAME ' . $this->escape($old) . ' ' . $this->escape($new));
    }

    /**
     * Manage subscription status for a mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $mailbox     The mailbox to [un]subscribe to (UTF7-IMAP).
     * @param boolean $subscribe  True to subscribe, false to unsubscribe.
     */
    protected function _subscribeMailbox($mailbox, $subscribe)
    {
        $this->login();

        // SUBSCRIBE/UNSUBSCRIBE returns no untagged information (RFC 3501
        // [6.3.6 & 6.3.7])
        $this->_sendLine(($subscribe ? '' : 'UN') . 'SUBSCRIBE ' . $this->escape($mailbox));
    }

    /**
     * Obtain a list of mailboxes matching a pattern.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $pattern  The mailbox search pattern.
     * @param integer $mode    Which mailboxes to return.
     * @param array $options   Additional options.
     *
     * @return array  See self::listMailboxes().
     */
    protected function _listMailboxes($pattern, $mode, $options)
    {
        $this->login();

        // Get the list of subscribed/unsubscribed mailboxes. Since LSUB is
        // not guaranteed to have correct attributes, we must use LIST to
        // ensure we receive the correct information.
        if ($mode != self::MBOX_ALL) {
            $subscribed = $this->_getMailboxList($pattern, self::MBOX_SUBSCRIBED, array('flat' => true));
            // If mode is subscribed, and 'flat' option is true, we can
            // return now.
            if (($mode == self::MBOX_SUBSCRIBED) && !empty($options['flat'])) {
                return $subscribed;
            }
        } else {
            $subscribed = null;
        }

        return $this->_getMailboxList($pattern, $mode, $options, $subscribed);
    }

    /**
     * Obtain a list of mailboxes matching a pattern.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $pattern    The mailbox search pattern.
     * @param integer $mode      Which mailboxes to return.
     * @param array $options     Additional options.
     * @param array $subscribed  A list of subscribed mailboxes.
     *
     * @return array  See self::listMailboxes(().
     */
    protected function _getMailboxList($pattern, $mode, $options,
                                       $subscribed = null)
    {
        $check = (($mode != self::MBOX_ALL) && !is_null($subscribed));

        // Setup cache entry for use in _parseList()
        $t = &$this->_temp;
        $t['mailboxlist'] = array(
            'check' => $check,
            'subscribed' => $check ? array_flip($subscribed) : null,
            'options' => $options
        );
        $t['listresponse'] = array();

        $this->_sendLine((($mode == self::MBOX_SUBSCRIBED) ? 'LSUB' : 'LIST') . ' "" ' . $this->escape($pattern));

        return (empty($options['flat'])) ? $t['listresponse'] : array_values($t['listresponse']);
    }

    /**
     * Parse a LIST/LSUB response (RFC 3501 [7.2.2 & 7.2.3]).
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param array $data  The server response (includes type as first
     *                     element).
     */
    protected function _parseList($data)
    {
        $ml = $this->_temp['mailboxlist'];
        $mlo = $ml['options'];
        $lr = &$this->_temp['listresponse'];

        $mode = strtoupper($data[0]);
        $mbox = $data[3];

        /* If dealing with [un]subscribed mailboxes, check to make sure
         * this mailbox is in the correct category. */
        if ($ml['check'] &&
            ((($mode == 'LIST') && isset($ml['subscribed'][$mbox])) ||
             (($mode == 'LSUB') && !isset($ml['subscribed'][$mbox])))) {
            return;
        }

        if (!empty($mlo['utf8'])) {
            $mbox = Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($mbox);
        }

        if (empty($mlo['flat'])) {
            $tmp = array('mailbox' => $mbox);
            if (!empty($mlo['attributes'])) {
                $tmp['attributes'] = array_map('strtolower', $data[1]);
            }
            if (!empty($mlo['delimiter'])) {
                $tmp['delimiter'] = $data[2];
            }
            $lr[$mbox] = $tmp;
        } else {
            $lr[] = $mbox;
        }
    }

    /**
     * Obtain status information for a mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $mailbox  The mailbox to query (UTF7-IMAP).
     * @param string $flags    A bitmask of information requested from the
     *                         server.
     *
     * @return array  See Horde_Imap_Client_Base::status().
     */
    protected function _status($mailbox, $flags)
    {
        $data = $query = array();
        $search = null;

        $items = array(
            self::STATUS_MESSAGES => 'messages',
            self::STATUS_RECENT => 'recent',
            self::STATUS_UIDNEXT => 'uidnext',
            self::STATUS_UIDVALIDITY => 'uidvalidity',
            self::STATUS_UNSEEN => 'unseen',
            self::STATUS_FIRSTUNSEEN => 'firstunseen',
            self::STATUS_FLAGS => 'flags',
            self::STATUS_PERMFLAGS => 'permflags',
            self::STATUS_UIDNOTSTICKY => 'uidnotsticky',
        );

        /* Don't include 'highestmodseq' return if server does not support it.
         * OK to use queryCapability('CONDSTORE') here because we may not have
         * yet sent an enabling command. */
        if ($this->queryCapability('CONDSTORE')) {
            $items[self::STATUS_HIGHESTMODSEQ] = 'highestmodseq';
        }

        /* If FLAGS/PERMFLAGS/UIDNOTSTICKY/FIRSTUNSEEN are needed, we must do
         * a SELECT/EXAMINE to get this information (data will be caught in
         * the code below). */
        if (($flags & self::STATUS_FIRSTUNSEEN) ||
            ($flags & self::STATUS_FLAGS) ||
            ($flags & self::STATUS_PERMFLAGS) ||
            ($flags & self::STATUS_UIDNOTSTICKY)) {
            $this->openMailbox($mailbox);
        } else {
            $this->login();
        }

        foreach ($items as $key => $val) {
            if ($key & $flags) {
                if ($mailbox == $this->_selected) {
                    if (isset($this->_temp['mailbox'][$val])) {
                        $data[$val] = $this->_temp['mailbox'][$val];
                    } else {
                        if ($key == self::STATUS_UIDNOTSTICKY) {
                            /* In the absence of uidnotsticky information, or
                             * if UIDPLUS is not supported, we assume the UIDs
                             * are sticky. */
                            $data[$val] = false;
                        } elseif (in_array($key, array(self::STATUS_FIRSTUNSEEN, self::STATUS_UNSEEN))) {
                            /* If we already know there are no messages in the
                             * current mailbox, we know there is no
                             * firstunseen and unseen info also. */
                            if (empty($this->_temp['mailbox']['messages'])) {
                                $data[$val] = ($key == self::STATUS_FIRSTUNSEEN) ? null : 0;
                            } else {
                                /* RFC 3501 [6.3.1] - FIRSTUNSEEN information
                                 * is not mandatory. If missing EXAMINE/SELECT
                                 * we need to do a search. An UNSEEN count
                                 * also requires a search. */
                                if (is_null($search)) {
                                    $search_query = new Horde_Imap_Client_Search_Query();
                                    $search_query->flag('\\seen', false);
                                    $search = $this->search($mailbox, $search_query, array('results' => array(($key == self::STATUS_FIRSTUNSEEN) ? self::SORT_RESULTS_MIN : self::SORT_RESULTS_COUNT), 'sequence' => true));
                                }

                                $data[$val] = $search[($key == self::STATUS_FIRSTUNSEEN) ? 'min' : 'count'];
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

        $this->_temp['status'] = array();
        $this->_sendLine('STATUS ' . $this->escape($mailbox) . ' (' . implode(' ', array_map('strtoupper', $query)) . ')');

        return $this->_temp['status'];
    }

    /**
     * Parse a STATUS response (RFC 3501 [7.2.4], RFC 4551 [3.6])
     *
     * @param array $data  The server response.
     */
    protected function _parseStatus($data)
    {
        for ($i = 0, $len = count($data); $i < $len; $i += 2) {
            $item = strtolower($data[$i]);
            $val = $data[$i + 1];
            if (!$val && ($item == 'highestmodseq')) {
                $val = null;
            }
            $this->_temp['status'][$item] = $val;
        }
    }

    /**
     * Append message(s) to a mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $mailbox  The mailbox to append the message(s) to
     *                         (UTF7-IMAP).
     * @param array $data      The message data.
     * @param array $options   Additional options.
     *
     * @return mixed  An array of the UIDs of the appended messages (if server
     *                supports UIDPLUS extension) or true.
     */
    protected function _append($mailbox, $data, $options)
    {
        $this->login();

        // If the mailbox is currently selected read-only, we need to close
        // because some IMAP implementations won't allow an append.
        if (($this->_selected == $mailbox) &&
            ($this->_mode == Horde_Imap_Client::OPEN_READONLY)) {
            $this->close();
        }

        // Check for MULTIAPPEND extension (RFC 3502)
        $multiappend = $this->queryCapability('MULTIAPPEND');

        $t = &$this->_temp;
        $t['appenduid'] = array();
        $t['trycreate'] = null;
        $t['uidplusmbox'] = $mailbox;
        $cnt = count($data);
        $i = 0;
        $notag = false;
        $literaldata = true;

        reset($data);
        while (list(,$m_data) = each($data)) {
            if (!$i++ || !$multiappend) {
                $cmd = 'APPEND ' . $this->escape($mailbox);
            } else {
                $cmd = '';
                $notag = true;
            }

            if (!empty($m_data['flags'])) {
                $cmd .= ' (' . implode(' ', $m_data['flags']) . ')';
            }

            if (!empty($m_data['internaldate'])) {
                $cmd .= ' ' . $this->escape($m_data['internaldate']->format('j-M-Y H:i:s O'));
            }

            /* @todo There is no way I am aware of to determine the length of
             * a stream. Having a user pass in the length of a stream is
             * cumbersome, and they would most likely have to do just as much
             * work to get the length of the stream as we have to do here. So
             * for now, simply grab the contents of the stream and do a
             * strlen() call to determine the literal size to send to the
             * IMAP server. */
            $text = $this->removeBareNewlines(is_resource($m_data['data']) ? stream_get_contents($m_data['data']) : $m_data['data']);
            $datalength = strlen($text);

            /* RFC 3516/4466 says we should be able to append binary data
             * using literal8 "~{#} format", but it doesn't seem to work in
             * all servers tried (UW-IMAP/Cyrus). However, there is no other
             * way to append null data, so try anyway. */
            $binary = (strpos($text, null) !== false);

            /* Need to add 2 additional characters (we send CRLF at the end of
             * a line) to literal count for multiappend messages to ensure the
             * server will accept the next line of information, which contains
             * the next append request. */
            if ($multiappend) {
                if ($i == $cnt) {
                    $literaldata = false;
                } else {
                    $datalength += 2;
                }
            } else {
                $literaldata = false;
            }

            try {
                $this->_sendLine($cmd, array('binary' => $binary, 'literal' => $datalength, 'notag' => $notag));
            } catch (Horde_Imap_Client_Exception $e) {
                if (!empty($options['create']) && $this->_temp['trycreate']) {
                    $this->createMailbox($mailbox);
                    unset($options['create']);
                    return $this->_append($mailbox, $data, $options);
                }
                throw $e;
            }

            // Send data.
            $this->_sendLine($text, array('literaldata' => $literaldata, 'notag' => true));
        }

        /* If we reach this point and have data in $_temp['appenduid'],
         * UIDPLUS (RFC 4315) has done the dirty work for us. */
        return empty($t['appenduid']) ? true : $t['appenduid'];
    }

    /**
     * Request a checkpoint of the currently selected mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     */
    protected function _check()
    {
        // CHECK returns no untagged information (RFC 3501 [6.4.1])
        $this->_sendLine('CHECK');
    }

    /**
     * Close the connection to the currently selected mailbox, optionally
     * expunging all deleted messages (RFC 3501 [6.4.2]).
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param array $options  Additional options.
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
                } catch (Horde_Imap_Client_Exception $e) {
                    // Ignore - we are expecting a NO return.
                }
            }
        } else {
            // If caching, we need to know the UIDs being deleted, so call
            // expunge() before calling close().
            if ($this->_initCacheOb()) {
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
     * Expunge deleted messages from the given mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param array $options  Additional options.
     */
    protected function _expunge($options)
    {
        $unflag = array();
        $mailbox = $this->_selected;
        $seq = !empty($options['sequence']);
        $s_res = null;
        $uidplus = $this->queryCapability('UIDPLUS');
        $use_cache = $this->_initCacheOb();

        if (empty($options['ids'])) {
            $uid_string = '1:*';
        } elseif ($uidplus) {
            /* UID EXPUNGE command needs UIDs. */
            if (reset($options['ids']) === self::USE_SEARCHRES) {
                $uid_string = '$';
            } elseif ($seq) {
                $results = array(self::SORT_RESULTS_MATCH);
                if ($this->queryCapability('SEARCHRES')) {
                    $results[] = self::SORT_RESULTS_SAVE;
                }
                $s_res = $this->search($mailbox, null, array('results' => $results));
                $uid_string = (in_array(self::SORT_RESULTS_SAVE, $results) && !empty($s_res['save']))
                    ? '$'
                    : $this->toSequenceString($s_res['match']);
            } else {
                $uid_string = $this->toSequenceString($options['ids']);
            }
        } else {
            /* Without UIDPLUS, need to temporarily unflag all messages marked
             * as deleted but not a part of requested IDs to delete. Use NOT
             * searches to accomplish this goal. */
            $search_query = new Horde_Imap_Client_Search_Query();
            $search_query->flag('\\deleted', true);
            if (reset($options['ids']) === self::USE_SEARCHRES) {
                $search_query->previousSearch(true);
            } else {
                $search_query->sequence($options['ids'], $seq, true);
            }

            $res = $this->search($mailbox, $search_query);
            $unflag = $res['match'];

            $this->store($mailbox, array('ids' => $unflag, 'remove' => array('\\deleted')));
        }

        /* We need to get Msgno -> UID lookup table if we are caching.
         * Apparently, there is no guarantee that if we are using QRESYNC that
         * we will get VANISHED responses, so we need to do this. */
        if ($use_cache && is_null($s_res)) {
            /* Keys in $s_res['sort'] start at 0, not 1. */
            $s_res = $this->search($mailbox, null, array('sort' => array(self::SORT_ARRIVAL)));
        }

        $tmp = &$this->_temp;
        $tmp['expunge'] = $tmp['vanished'] = array();

        /* Always use UID EXPUNGE if available. */
        if ($uidplus) {
            $this->_sendLine('UID EXPUNGE ' . $uid_string);
        } elseif ($use_cache) {
            $this->_sendLine('EXPUNGE');
        } else {
            /* This is faster than an EXPUNGE because the server will not
             * return untagged EXPUNGE responses. We can only do this if
             * we are not updating cache information. */
            $this->close(array('expunge' => true));
        }

        if (!empty($unflag)) {
            $this->store($mailbox, array('add' => array('\\deleted'), 'ids' => $unflag));
        }

        if ($use_cache) {
            if (!empty($tmp['vanished'])) {
                $i = count($tmp['vanished']);
                $expunged = $tmp['vanished'];
            } elseif (!empty($tmp['expunge'])) {
                $expunged = array();
                $i = 0;
                $t = $s_res['sort'];

                foreach ($tmp['expunge'] as $val) {
                    $expunged[] = $t[$val - 1 + $i++];
                }
            }

            if (!empty($expunged)) {
                $this->_cacheOb->deleteMsgs($mailbox, $expunged);
                $tmp['mailbox']['messages'] -= $i;
            }

            if (isset($this->_init['enabled']['QRESYNC'])) {
                $this->_cacheOb->setMetaData($mailbox, array('HICmodseq' => $this->_temp['mailbox']['highestmodseq']));
            }
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
                $this->_cacheOb->deleteMsgs($this->_temp['mailbox']['name'], $this->fromSequenceString($data[1]));
            }
        } else {
            /* The second form is just VANISHED. This is returned from an
             * EXPUNGE command and will be processed in _expunge() (since
             * we need to adjust message counts in the current mailbox). */
            $this->_temp['vanished'] = $this->fromSequenceString($data[0]);
        }
    }

    /**
     * Search a mailbox.  This driver supports all IMAP4rev1 search criteria
     * as defined in RFC 3501.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param object $query   The search query.
     * @param array $options  Additional options. The '_query' key contains
     *                        the value of $query->build().
     *
     * @return array  An array of UIDs (default) or an array of message
     *                sequence numbers (if 'sequence' is true).
     */
    protected function _search($query, $options)
    {
        // Check for IMAP extensions needed
        foreach ($query->extensionsNeeded() as $val) {
            if (!$this->queryCapability($val)) {
                throw new Horde_Imap_Client_Exception('IMAP Server does not support sorting extension ' . $val . '.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
            }

            /* RFC 4551 [3.1] - trying to do a MODSEQ SEARCH on a mailbox that
             * doesn't support it will return BAD. Catch that here and thrown
             * an exception. */
            if (($val == 'CONDSTORE') &&
                is_null($this->_temp['mailbox']['highestmodseq']) &&
                (strpos($options['_query']['query'], 'MODSEQ ') !== false)) {
                throw new Horde_Imap_Client_Exception('Mailbox does not support mod-sequences.', Horde_Imap_Client_Exception::MBOXNOMODSEQ);
            }
        }

        $cmd = '';
        if (empty($options['sequence'])) {
            $cmd = 'UID ';
        }

        $sort_criteria = array(
            self::SORT_ARRIVAL => 'ARRIVAL',
            self::SORT_CC => 'CC',
            self::SORT_DATE => 'DATE',
            self::SORT_FROM => 'FROM',
            self::SORT_REVERSE => 'REVERSE',
            self::SORT_SIZE => 'SIZE',
            self::SORT_SUBJECT => 'SUBJECT',
            self::SORT_TO => 'TO'
        );

        $results_criteria = array(
            self::SORT_RESULTS_COUNT => 'COUNT',
            self::SORT_RESULTS_MATCH => 'ALL',
            self::SORT_RESULTS_MAX => 'MAX',
            self::SORT_RESULTS_MIN => 'MIN',
            self::SORT_RESULTS_SAVE => 'SAVE'
        );

        // Check if the server supports server-side sorting (RFC 5256).
        $esearch = $server_sort = $return_sort = false;
        if (!empty($options['sort'])) {
            $return_sort = true;
            $server_sort = $this->queryCapability('SORT');

            /* Make sure sort options are correct. If not, default to ARRIVAL
             * sort. */
            if (count(array_intersect($options['sort'], array_keys($sort_criteria))) === 0) {
                $options['sort'] = array(self::SORT_ARRIVAL);
            }
        }

        if ($server_sort) {
            // Check for ESORT capability (RFC 5267)
            if ($this->queryCapability('ESORT')) {
                $results = array();
                foreach ($options['results'] as $val) {
                    if (isset($results_criteria[$val]) &&
                        ($val != self::SORT_RESULTS_SAVE)) {
                        $results[] = $results_criteria[$val];
                    }
                }
                $cmd .= 'SORT RETURN ( ' . implode(' ', $results) . ') (';
            } else {
                $cmd .= 'SORT (';
            }

            foreach ($options['sort'] as $val) {
                if (isset($sort_criteria[$val])) {
                    $cmd .= $sort_criteria[$val] . ' ';
                }
            }
            $cmd = rtrim($cmd) . ') ';
        } else {
            // Check if the server supports ESEARCH (RFC 4731).
            $esearch = $this->queryCapability('ESEARCH');

            if ($esearch) {
                // Always use ESEARCH if available because it returns results
                // in a more compact sequence-set list
                $results = array();
                foreach ($options['results'] as $val) {
                    if (isset($results_criteria[$val])) {
                        $results[] = $results_criteria[$val];
                    }
                }
                $cmd .= 'SEARCH RETURN (' . implode(' ', $results) . ') CHARSET ';
            } else {
                $cmd .= 'SEARCH CHARSET ';
            }

            // SEARCHRES requires ESEARCH
            unset($this->_temp['searchnotsaved']);
        }

        $er = &$this->_temp['esearchresp'];
        $sr = &$this->_temp['searchresp'];
        $er = $sr = array();

        $this->_sendLine($cmd . $options['_query']['charset'] . ' ' . $options['_query']['query']);

        if ($return_sort && !$server_sort) {
            $sr = array_values($this->_clientSort($sr, $options));
        }

        $ret = array();
        foreach ($options['results'] as $val) {
            switch ($val) {
            case self::SORT_RESULTS_COUNT:
                $ret['count'] = $esearch ? $er['count'] : count($sr);
                break;

            case self::SORT_RESULTS_MATCH:
                $ret[$return_sort ? 'sort' : 'match'] = $sr;
                break;

            case self::SORT_RESULTS_MAX:
                $ret['max'] = $esearch ? (isset($er['max']) ? $er['max'] : null) : (empty($sr) ? null : max($sr));
                break;

            case self::SORT_RESULTS_MIN:
                $ret['min'] = $esearch ? (isset($er['min']) ? $er['min'] : null) : (empty($sr) ? null : min($sr));
                break;

            case self::SORT_RESULTS_SAVE:
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
                $this->_temp['searchresp'] = $this->fromSequenceString($val);
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
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param array $res   The search results.
     * @param array $opts  The options to search().
     *
     * @return array  The sort results.
     */
    protected function _clientSort($res, $opts)
    {
        if (empty($res)) {
            return $res;
        }

        /* Generate the FETCH command needed. */
        $criteria = array();
        foreach ($opts['sort'] as $val) {
            switch ($val) {
            case self::SORT_DATE:
                $criteria[self::FETCH_DATE] = true;
                // Fall through

            case self::SORT_CC:
            case self::SORT_FROM:
            case self::SORT_SUBJECT:
            case self::SORT_TO:
                $criteria[self::FETCH_ENVELOPE] = true;
                break;

            case self::SORT_SIZE:
                $criteria[self::FETCH_SIZE] = true;
                break;
            }
        }

        /* Get the FETCH results now. */
        if (!empty($criteria)) {
            $fetch_res = $this->fetch($this->_selected, $criteria, array('ids' => $res, 'sequence' => $opts['sequence']));
        }

        /* The initial sort is on the entire set. */
        $slices = array(0 => $res);

        $reverse = false;
        foreach ($opts['sort'] as $val) {
            if ($val == self::SORT_REVERSE) {
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
                case self::SORT_ARRIVAL:
                    /* There is no requirement that IDs be returned in
                     * sequence order (see RFC 4549 [4.3.1]). So we must sort
                     * ourselves. */
                    $sorted = $slice;
                    sort($sorted, SORT_NUMERIC);
                    break;

                case self::SORT_SIZE:
                    foreach ($slice as $num) {
                        $sorted[$num] = $fetch_res[$num]['size'];
                    }
                    asort($sorted, SORT_NUMERIC);
                    break;

                case self::SORT_CC:
                case self::SORT_FROM:
                case self::SORT_TO:
                    if ($val == self::SORT_CC) {
                        $field = 'cc';
                    } elseif ($val = self::SORT_FROM) {
                        $field = 'from';
                    } else {
                        $field = 'to';
                    }

                    foreach ($slice as $num) {
                        $sorted[$num] = empty($fetch_res[$num]['envelope'][$field])
                            ? null
                            : $fetch_res[$num]['envelope'][$field][0]['mailbox'];
                    }
                    asort($sorted, SORT_LOCALE_STRING);
                    break;

                case self::SORT_DATE:
                    // Date sorting rules in RFC 5256 [2.2]
                    $sorted = $this->_getSentDates($fetch_res, $slice);
                    asort($sorted, SORT_NUMERIC);
                    break;

                case self::SORT_SUBJECT:
                    // Subject sorting rules in RFC 5256 [2.1]
                    foreach ($slice as $num) {
                        $sorted[$num] = empty($fetch_res[$num]['envelope']['subject'])
                            ? ''
                            : $this->getBaseSubject($fetch_res[$num]['envelope']['subject']);
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
     * @param array $data  Data returned from fetch() that includes both the
     *                     'envelope' and 'date' keys.
     * @param array $ids   The IDs to process.
     *
     * @return array  A mapping of IDs -> UNIX timestamps.
     */
    protected function _getSentDates($data, $ids)
    {
        $dates = array();

        $tz = new DateTimeZone('UTC');
        foreach ($ids as $num) {
            if (empty($data[$num]['envelope']['date'])) {
                $dt = $data[$num]['date'];
                $dt->setTimezone($tz);
            } else {
                $dt = new DateTime($data[$num]['envelope']['date'], $tz);
            }
            $dates[$num] = $dt->format('U');
        }

        return $dates;
    }

    /**
     * Set the comparator to use for searching/sorting (RFC 5255).
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $comparator  The comparator string (see RFC 4790 [3.1] -
     *                            "collation-id" - for format). The reserved
     *                            string 'default' can be used to select
     *                            the default comparator.
     */
    protected function _setComparator($comparator)
    {
        $this->_login();

        $cmd = array();
        foreach (explode(' ', $comparator) as $val) {
            $cmd[] = $this->escape($val);
        }

        $this->_sendLine('COMPARATOR ' . implode(' ', $cmd));
    }

    /**
     * Get the comparator used for searching/sorting (RFC 5255).
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @return mixed  Null if the default comparator is being used, or an
     *                array of comparator information (see RFC 5255 [4.8]).
     */
    protected function _getComparator()
    {
        $this->_login();

        $this->_sendLine('COMPARATOR');

        return isset($this->_temp['comparator']) ? $this->_temp['comparator'] : null;
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
     * Thread sort a given list of messages (RFC 5256).
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param array $options  Additional options.
     *
     * @return array  See Horde_Imap_Client_Base::_thread().
     */
    protected function _thread($options)
    {
        $thread_criteria = array(
            self::THREAD_ORDEREDSUBJECT => 'ORDEREDSUBJECT',
            self::THREAD_REFERENCES => 'REFERENCES'
        );

        $tsort = (isset($options['criteria']))
            ? (is_string($options['criteria']) ? strtoupper($options['criteria']) : $thread_criteria[$options['criteria']])
            : 'REFERENCES';

        $cap = $this->queryCapability('THREAD');
        if (!$cap || !in_array($tsort, $cap)) {
            if ($tsort == 'ORDEREDSUBJECT') {
                if (empty($options['search'])) {
                    $ids = array();
                } else {
                    $search_res = $this->search($this->_selected, $options['search'], array('sequence' => !empty($options['sequence'])));
                    $ids = $search_res['match'];
                }

                /* Do client-side ORDEREDSUBJECT threading. */
                $fetch_res = $this->fetch($this->_selected, array(self::FETCH_ENVELOPE => true, self::FETCH_DATE => true), array('ids' => $ids, 'sequence' => !empty($options['sequence'])));
                return $this->_clientThreadOrderedsubject($fetch_res);
            } else {
                throw new Horde_Imap_Client_Exception('Server does not support REFERENCES thread sort.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
            }
        }

        if (empty($options['search'])) {
            $charset = 'US-ASCII';
            $search = 'ALL';
        } else {
            $search_query = $options['search']->build();
            $charset = $search_query['charset'];
            $search = $search_query['query'];
        }

        $this->_temp['threadresp'] = array();
        $this->_sendLine((empty($options['sequence']) ? 'UID ' : '') . 'THREAD ' . $tsort . ' ' . $charset . ' ' . $search);

        return $this->_temp['threadresp'];
    }

    /**
     * Parse a THREAD response (RFC 5256 [4]).
     *
     * @param array $data      An array of thread token data.
     * @param boolean $islast  Is this the last item in the level?
     * @param integer $level   The current tree level.
     */
    protected function _parseThread($data, $level = 0, $islast = true)
    {
        $tb = &$this->_temp['threadbase'];
        $tr = &$this->_temp['threadresp'];

        if (!$level) {
            $tb = null;
        }
        $cnt = count($data) - 1;

        reset($data);
        while (list($key, $val) = each($data)) {
            if (is_array($val)) {
                $this->_parseThread($val, $level, $cnt == $key);
            } else {
                if (is_null($tb) && ($level || $cnt)) {
                    $tb = $val;
                }
                $tr[$val] = array(
                    'base' => $tb,
                    'last' => $islast,
                    'level' => $level++,
                    'id' => $val
                );
            }
        }
    }

    /**
     * If server does not support the THREAD IMAP extension (RFC 5256), do
     * ORDEREDSUBJECT threading on the client side.
     *
     * @param array $res   The search results.
     * @param array $opts  The options to search().
     *
     * @return array  The sort results.
     */
    protected function _clientThreadOrderedsubject($data)
    {
        $dates = $this->_getSentDates($data, array_keys($data));
        $level = $sorted = $tsort = array();
        $this->_temp['threadresp'] = array();

        reset($data);
        while(list($k, $v) = each($data)) {
            $subject = empty($v['envelope']['subject'])
                ? ''
                : $this->getBaseSubject($v['envelope']['subject']);
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

        return $this->_temp['threadresp'];
    }

    /**
     * Fetch message data.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @todo Provide a function that would allow streaming of large data
     *       items like bodytext.
     *
     * @param array $criteria  The fetch criteria.
     * @param array $options   Additional options.
     *
     * @return array  See self::fetch().
     */
    protected function _fetch($criteria, $options)
    {
        $t = &$this->_temp;
        $t['fetchparams'] = array();
        $fp = &$t['fetchparams'];
        $fetch = array();

        /* Build an IMAP4rev1 compliant FETCH query. We handle the following
         * criteria:
         *   BINARY[.PEEK][<section #>]<<partial>> (RFC 3516)
         *     see BODY[] response
         *   BINARY.SIZE[<section #>] (RFC 3516)
         *   BODY
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
         *   FAST macro => (FLAGS INTERNALDATE RFC822.SIZE)
         *   FULL macro => (FLAGS INTERNALDATE RFC822.SIZE ENVELOPE BODY)
         *   RFC822 => BODY[]
         *   RFC822.HEADER => BODY[HEADER]
         *   RFC822.TEXT => BODY[TEXT]
         */
        reset($criteria);
        while (list($type, $c_val) = each($criteria)) {
            if (!is_array($c_val)) {
                $c_val = array();
            }

            switch ($type) {
            case self::FETCH_STRUCTURE:
                $fp['parsestructure'] = !empty($c_val['parse']);
                $fetch[] = !empty($c_val['noext']) ? 'BODY' : 'BODYSTRUCTURE';
                break;

            case self::FETCH_FULLMSG:
                if (empty($c_val['peek'])) {
                    $this->openMailbox($this->_selected, self::OPEN_READWRITE);
                }
                $fetch[] = 'BODY' .
                    (!empty($c_val['peek']) ? '.PEEK' : '') .
                    '[]' .
                    (isset($c_val['start']) && !empty($c_val['length']) ? ('<' . $c_val['start'] . '.' . $c_val['length'] . '>') : '');
                break;

            case self::FETCH_HEADERTEXT:
            case self::FETCH_BODYTEXT:
            case self::FETCH_MIMEHEADER:
            case self::FETCH_BODYPART:
            case self::FETCH_HEADERS:
                foreach ($c_val as $val) {
                    $main_cmd = 'BODY';

                    if (empty($val['id'])) {
                        $cmd = '';
                    } else {
                        $cmd = $val['id'] . '.';
                    }

                    switch ($type) {
                    case self::FETCH_HEADERTEXT:
                        $fp['parseheadertext'] = !empty($val['parse']);
                        $cmd .= 'HEADER';
                        break;

                    case self::FETCH_BODYTEXT:
                        $cmd .= 'TEXT';
                        break;

                    case self::FETCH_MIMEHEADER:
                        if (empty($val['id'])) {
                            throw new Horde_Imap_Client_Exception('Need a MIME ID when retrieving a MIME header.');
                        }
                        $cmd .= 'MIME';
                        break;

                    case self::FETCH_BODYPART:
                        if (empty($val['id'])) {
                            throw new Horde_Imap_Client_Exception('Need a MIME ID when retrieving a MIME body part.');
                        }
                        // Remove the last dot from the string.
                        $cmd = substr($cmd, 0, -1);

                        if (!empty($val['decode']) &&
                            $this->queryCapability('BINARY')) {
                            $main_cmd = 'BINARY';
                        }
                        break;

                    case self::FETCH_HEADERS:
                        if (empty($val['label'])) {
                            throw new Horde_Imap_Client_Exception('Need a unique label when doing a headers field search.');
                        }
                        if (empty($val['headers'])) {
                            throw new Horde_Imap_Client_Exception('Need headers to query when doing a headers field search.');
                        }
                        $fp['parseheaders'] = !empty($val['parse']);

                        $cmd .= 'HEADER.FIELDS';
                        if (!empty($val['notsearch'])) {
                            $cmd .= '.NOT';
                        }
                        $cmd .= ' (' . implode(' ', array_map('strtoupper', $val['headers'])) . ')';

                        // Maintain a command -> label lookup so we can put
                        // the results in the proper location.
                        if (!isset($fp['hdrfields'])) {
                            $fp['hdrfields'] = array();
                        }
                        $fp['hdrfields'][$cmd] = $val['label'];
                    }

                    if (empty($c_val['peek'])) {
                        $this->openMailbox($this->_selected, self::OPEN_READWRITE);
                    }

                    $fetch[] = $main_cmd .
                        (!empty($c_val['peek']) ? '.PEEK' : '') .
                        '[' . $cmd . ']' .
                        (isset($c_val['start']) && !empty($c_val['length']) ? ('<' . $c_val['start'] . '.' . $c_val['length'] . '>') : '');
                }
                break;

            case self::FETCH_BODYPARTSIZE:
                foreach ($c_val as $val) {
                    if (empty($val['id'])) {
                        throw new Horde_Imap_Client_Exception('Need a MIME ID when retrieving unencoded MIME body part size.');
                    }
                    $fetch[] = 'BINARY.SIZE[' . $val['id'] . ']';
                }
                break;

            case self::FETCH_ENVELOPE:
                $fetch[] = 'ENVELOPE';
                break;

            case self::FETCH_FLAGS:
                $fetch[] = 'FLAGS';
                break;

            case self::FETCH_DATE:
                $fetch[] = 'INTERNALDATE';
                break;

            case self::FETCH_SIZE:
                $fetch[] = 'RFC822.SIZE';
                break;

            case self::FETCH_UID:
                $fetch[] = 'UID';
                break;

            case self::FETCH_SEQ:
                // Nothing we need to add to fetch criteria.
                break;

            case self::FETCH_MODSEQ:
                /* RFC 4551 [3.1] - trying to do a FETCH of MODSEQ on a
                 * mailbox that doesn't support it will return BAD. Catch that
                 * here and throw an exception. */
                if (is_null($this->_temp['mailbox']['highestmodseq'])) {
                    throw new Horde_Imap_Client_Exception('Mailbox does not support mod-sequences.', Horde_Imap_Client_Exception::MBOXNOMODSEQ);
                }
                $fetch[] = 'MODSEQ';
                break;
            }
        }

        $seq = empty($options['ids'])
            ? '1:*'
            : ((reset($options['ids']) === self::USE_SEARCHRES)
                 ? '$'
                 : $this->toSequenceString($options['ids']));
        $use_seq = !empty($options['sequence']);

        $cmd = ($use_seq ? '' : 'UID ') . 'FETCH ' . $seq . ' (' . implode(' ', $fetch) . ')';

        if (!empty($options['changedsince'])) {
            if (is_null($this->_temp['mailbox']['highestmodseq'])) {
                throw new Horde_Imap_Client_Exception('Mailbox does not support mod-sequences.', Horde_Imap_Client_Exception::MBOXNOMODSEQ);
            }
            $cmd .= ' (CHANGEDSINCE ' . intval($options['changedsince']) . ')';
        }

        $this->_sendLine($cmd);

        return $t['fetchresp'][$use_seq ? 'seq' : 'uid'];
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
        $section_storage = array(
            'HEADER' => 'headertext',
            'TEXT' => 'bodytext',
            'MIME' => 'mimeheader'
        );

        $i = 0;
        $cnt = count($data);

        if (isset($this->_temp['fetchresp']['seq'][$id])) {
            $tmp = $this->_temp['fetchresp']['seq'][$id];
            $uid = isset($tmp['uid']) ? $tmp['uid'] : null;
        } else {
            $tmp = array('seq' => $id);
            $uid = null;
        }

        while ($i < $cnt) {
            $tag = strtoupper($data[$i]);
            switch ($tag) {
            case 'BODY':
            case 'BODYSTRUCTURE':
                // Only care about these if doing a FETCH command.
                $tmp['structure'] = empty($this->_temp['fetchparams']['parsestructure'])
                    ? $this->_parseBodystructure($data[++$i])
                    : Horde_Mime_Message::parseStructure($this->_parseBodystructure($data[++$i]));
                break;

            case 'ENVELOPE':
                $tmp['envelope'] = $this->_parseEnvelope($data[++$i]);
                break;

            case 'FLAGS':
                $tmp['flags'] = array_map('strtolower', $data[++$i]);
                break;

            case 'INTERNALDATE':
                $tmp['date'] = new DateTime($data[++$i]);
                break;

            case 'RFC822.SIZE':
                $tmp['size'] = $data[++$i];
                break;

            case 'UID':
                $uid = $tmp['uid'] = $data[++$i];
                break;

            case 'MODSEQ':
                $tmp['modseq'] = reset($data[++$i]);
                break;

            default:
                // Catch BODY[*]<#> responses
                if (strpos($tag, 'BODY[') === 0) {
                    // Remove the beginning 'BODY['
                    $tag = substr($tag, 5);

                    // BODY[HEADER.FIELDS] request
                    if (!empty($this->_temp['fetchparams']['hdrfields']) &&
                        (strpos($tag, 'HEADER.FIELDS') !== false)) {
                        if (!isset($tmp['headers'])) {
                            $tmp['headers'] = array();
                        }

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

                        $tmp['headers'][$this->_temp['fetchparams']['hdrfields'][$sig]] = empty($this->_temp['fetchparams']['parseheaders'])
                            ? $data[++$i]
                            : Horde_Mime_Headers::parseHeaders($data[++$i]);
                    } else {
                        // Remove trailing bracket and octet start info
                        $tag = substr($tag, 0, strrpos($tag, ']'));

                        if (!strlen($tag)) {
                            // BODY[] request
                            $tmp['fullmsg'] = $data[++$i];
                        } elseif (is_numeric(substr($tag, -1))) {
                            // BODY[MIMEID] request
                            if (!isset($tmp['bodypart'])) {
                                $tmp['bodypart'] = array();
                            }
                            $tmp['bodypart'][$tag] = $data[++$i];
                        } else {
                            // BODY[HEADER|TEXT|MIME] request
                            if (($last_dot = strrpos($tag, '.')) === false) {
                                $mime_id = 0;
                            } else {
                                $mime_id = substr($tag, 0, $last_dot);
                                $tag = substr($tag, $last_dot + 1);
                            }

                            $label = $section_storage[$tag];

                            if (!isset($tmp[$label])) {
                                $tmp[$label] = array();
                            }
                            $tmp[$label][$mime_id] = empty($this->_temp['fetchparams']['parseheadertext'])
                                ? $data[++$i]
                                : Horde_Mime_Headers::parseHeaders($data[++$i]);
                        }
                    }
                } elseif (strpos($tag, 'BINARY[') === 0) {
                    // Catch BINARY[*]<#> responses
                    // Remove the beginning 'BINARY[' and the trailing bracket
                    // and octet start info
                    $tag = substr($tag, 7, strrpos($tag, ']') - 7);
                    if (!isset($tmp['bodypart'])) {
                        $tmp['bodypart'] = $tmp['bodypartdecode'] = array();
                    }
                    $tmp['bodypart'][$tag] = $data[++$i];
                    $tmp['bodypartdecode'][$tag] = !empty($this->_temp['literal8']) ? 'binary': '8bit';
                } elseif (strpos($tag, 'BINARY.SIZE[') === 0) {
                    // Catch BINARY.SIZE[*] responses
                    // Remove the beginning 'BINARY.SIZE[' and the trailing
                    // bracket and octet start info
                    $tag = substr($tag, 12, strrpos($tag, ']') - 12);
                    if (!isset($tmp['bodypartsize'])) {
                        $tmp['bodypartsize'] = array();
                    }
                    $tmp['bodypartsize'][$tag] = $data[++$i];
                }
                break;
            }

            ++$i;
        }

        $this->_temp['fetchresp']['seq'][$id] = $tmp;
        if (!is_null($uid)) {
            $this->_temp['fetchresp']['uid'][$uid] = $tmp;
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
        // If index 0 is an array, this is a multipart part.
        if (is_array($data[0])) {
            $ret = array(
                'parts' => array(),
                'type' => 'multipart'
            );

            // Keep going through array values until we find a non-array.
            for ($i = 0, $cnt = count($data); $i < $cnt; ++$i) {
                if (!is_array($data[$i])) {
                    break;
                }
                $ret['parts'][] = $this->_parseBodystructure($data[$i]);
            }

            // The first string entry after an array entry gives us the
            // subpart type.
            $ret['subtype'] = strtolower($data[$i]);

            // After the subtype is further extension information. This
            // information won't be present if this is a BODY request, and
            // MAY not appear for BODYSTRUCTURE requests.

            // This is parameter information.
            if (isset($data[++$i]) && is_array($data[$i])) {
                $ret['parameters'] = $this->_parseStructureParams($data[$i]);
            }

            // This is disposition information.
            if (isset($data[++$i]) && is_array($data[$i])) {
                $ret['disposition'] = strtolower($data[$i][0]);
                $ret['dparameters'] = $this->_parseStructureParams($data[$i][1]);
            }

            // This is body language information.
            if (isset($data[++$i])) {
                if (is_array($data[$i])) {
                    $ret['language'] = $data[$i];
                } elseif ($data[$i] != 'NIL') {
                    $ret['language'] = array($data[$i]);
                }
            }

            // This is body location information
            if (isset($data[++$i]) && ($data[$i] != 'NIL')) {
                $ret['location'] = $data[$i];
            }

            // There can be further information returned in the future, but
            // for now we are done.
        } else {
            $ret = array(
                'type' => strtolower($data[0]),
                'subtype' => strtolower($data[1]),
                'parameters' => $this->_parseStructureParams($data[2]),
                'id' => ($data[3] == 'NIL') ? null : $data[3],
                'description' => ($data[4] == 'NIL') ? null : $data[4],
                'encoding' => ($data[5] == 'NIL') ? null : strtolower($data[5]),
                'size' => ($data[6] == 'NIL') ? null : $data[6]
            );

            // If the type is 'message/rfc822' or 'text/*', several extra
            // fields are included
            switch ($ret['type']) {
            case 'message':
                if ($ret['subtype'] == 'rfc822') {
                    $ret['envelope'] = $this->_parseEnvelope($data[7]);
                    $ret['structure'] = $this->_parseBodystructure($data[8]);
                    $ret['lines'] = $data[9];
                    $i = 10;
                } else {
                    $i = 7;
                }
                break;

            case 'text':
                $ret['lines'] = $data[7];
                $i = 8;
                break;

            default:
                $i = 7;
                break;
            }

            // After the subtype is further extension information. This
            // information won't be present if this is a BODY request, and
            // MAY not appear for BODYSTRUCTURE requests.

            // This is MD5 information
            if (isset($data[$i]) && ($data[$i] != 'NIL')) {
                $ret['md5'] = $data[$i];
            }

            // This is disposition information
            if (isset($data[++$i]) && is_array($data[$i])) {
                $ret['disposition'] = strtolower($data[$i][0]);
                $ret['dparameters'] = $this->_parseStructureParams($data[$i][1]);
            }

            // This is body language information.
            if (isset($data[++$i])) {
                if (is_array($data[$i])) {
                    $ret['language'] = $data[$i];
                } elseif ($data[$i] != 'NIL') {
                    $ret['language'] = array($data[$i]);
                }
            }

            // This is body location information
            if (isset($data[++$i]) && ($data[$i] != 'NIL')) {
                $ret['location'] = $data[$i];
            }
        }

        return $ret;
    }

    /**
     * Helper function to parse a parameters-like tokenized array.
     *
     * @param array $data  The tokenized data.
     *
     * @return array  The parameter array.
     */
    protected function _parseStructureParams($data)
    {
        $ret = array();

        if (is_array($data)) {
            for ($i = 0, $cnt = count($data); $i < $cnt; ++$i) {
                $ret[strtolower($data[$i])] = $data[++$i];
            }
        }

        return $ret;
    }

    /**
     * Parse ENVELOPE data from a FETCH return (see RFC 3501 [7.4.2]).
     *
     * @param array $data  The tokenized information from the server.
     *
     * @return array  The array of envelope information.
     */
    protected function _parseEnvelope($data)
    {
        $addr_structure = array(
            'personal', 'adl', 'mailbox', 'host'
        );
        $env_data = array(
            0 => 'date',
            1 => 'subject',
            8 => 'in-reply-to',
            9 => 'message-id'
        );
        $env_data_array = array(
            2 => 'from',
            3 => 'sender',
            4 => 'reply-to',
            5 => 'to',
            6 => 'cc',
            7 => 'bcc'
        );

        $ret = array();

        foreach ($env_data as $key => $val) {
            $ret[$val] = (strtoupper($data[$key]) == 'NIL') ? null : $data[$key];
        }

        // These entries are address structures.
        foreach ($env_data_array as $key => $val) {
            $ret[$val] = array();
            // Check for 'NIL' value here.
            if (is_array($data[$key])) {
                reset($data[$key]);
                while (list(,$a_val) = each($data[$key])) {
                    $tmp_addr = array();
                    foreach ($addr_structure as $add_key => $add_val) {
                        if (strtoupper($a_val[$add_key]) != 'NIL') {
                            $tmp_addr[$add_val] = $a_val[$add_key];
                        }
                    }
                    $ret[$val][] = $tmp_addr;
                }
            }
        }

        return $ret;
    }

    /**
     * Store message flag data.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param array $options  Additional options.
     *
     * @return array  See Horde_Imap_Client::store().
     */
    protected function _store($options)
    {
        $seq = empty($options['ids'])
            ? '1:*'
            : ((reset($options['ids']) === self::USE_SEARCHRES)
                 ? '$'
                 : $this->toSequenceString($options['ids']));

        $cmd_prefix = (empty($options['sequence']) ? 'UID ' : '') .
                      'STORE ' . $seq . ' ';
        $ucsince = !empty($options['unchangedsince']);

        if ($ucsince) {
            /* RFC 4551 [3.1] - trying to do a UNCHANGEDSINCE STORE on a
             * mailbox that doesn't support it will return BAD. Catch that
             * here and throw an exception. */
            if (is_null($this->_temp['mailbox']['highestmodseq'])) {
                throw new Horde_Imap_Client_Exception('Mailbox does not support mod-sequences.', Horde_Imap_Client_Exception::MBOXNOMODSEQ);
            }

            $cmd .= '(UNCHANGEDSINCE ' . intval($options['unchangedsince']) . ') ';
        }

        $this->_temp['modified'] = array();

        if (!empty($options['replace'])) {
            $this->_sendLine($cmd_prefix . 'FLAGS' . ($this->_debug ? '.SILENT' : '') . ' (' . implode(' ', $options['replace']) . ')');
        } else {
            foreach (array('add' => '+', 'remove' => '-') as $k => $v) {
                if (!empty($options[$k])) {
                    $this->_sendLine($cmd_prefix . $v . 'FLAGS' . ($this->_debug ? '.SILENT' : '') . ' (' . implode(' ', $options[$k]) . ')');
                }
            }
        }

        /* Update the flags in the cache. Only update if store was successful
         * and flag information was not returned. */
        if (!empty($this->_temp['fetchresp']) &&
            isset($this->_init['enabled']['CONDSTORE'])) {
            $fr = $this->_temp['fetchresp'];
            $out = $uids = array();

            if (empty($fr['uid'])) {
                $res = $fr['seq'];
                $seq_res = $this->_getSeqUIDLookup(array_keys($res), true);
            } else {
                $res = $fr['uid'];
                $seq_res = null;
            }

            foreach (array_keys($res) as $key) {
                if (!isset($res[$key]['flags'])) {
                    $uids[is_null($seq_res) ? $key : $seq_res['lookup'][$key]] = $res[$key]['modseq'];
                }
            }

            /* Get the list of flags from the cache. */
            if (empty($options['replace'])) {
                $data = $this->_cacheOb->get($this->_selected, array_keys($uids), array('HICflags'), $this->_temp['mailbox']['uidvalidity']);

                foreach ($uids as $uid => $modseq) {
                    $flags = isset($data[$uid]['HICflags']) ? $data[$uid]['HICflags'] : array();
                    if (!empty($options['add'])) {
                        $flags = array_merge($flags, $options['add']);
                    }
                    if (!empty($options['remove'])) {
                        $flags = array_diff($flags, $options['remove']);
                    }
                    $out[$uid] = array('modseq' => $uids[$uid], 'flags' => array_keys(array_flip($flags)));
                }
            } else {
                foreach ($uids as $uid => $modseq) {
                    $out[$uid] = array('modseq' => $uids[$uid], 'flags' => $options['replace']);
                }
            }

            $this->_updateCache($out);
        }

        return $this->_temp['modified'];
    }

    /**
     * Copy messages to another mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $dest    The destination mailbox (UTF7-IMAP).
     * @param array $options  Additional options.
     *
     * @return mixed  An array mapping old UIDs (keys) to new UIDs (values) on
     *                success (if the IMAP server and/or driver support the
     *                UIDPLUS extension) or true.
     */
    protected function _copy($dest, $options)
    {
        $this->_temp['copyuid'] = $this->_temp['trycreate'] = null;
        $this->_temp['uidplusmbox'] = $dest;

        $seq = empty($options['ids'])
            ? '1:*'
            : ((reset($options['ids']) === self::USE_SEARCHRES)
                 ? '$'
                 : $this->toSequenceString($options['ids']));

        // COPY returns no untagged information (RFC 3501 [6.4.7])
        try {
            $this->_sendLine((empty($options['sequence']) ? 'UID ' : '') . 'COPY ' . $seq . ' ' . $this->escape($dest));
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
            $opts = array('ids' => empty($options['ids']) ? array() : $options['ids'], 'sequence' => !empty($options['sequence']));
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
     * Set quota limits.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $root    The quota root (UTF7-IMAP).
     * @param array $options  Additional options.
     */
    protected function _setQuota($root, $options)
    {
        $this->login();

        $limits = array();
        if (isset($options['messages'])) {
            $limits[] = 'MESSAGE ' . $options['messages'];
        }
        if (isset($options['storage'])) {
            $limits[] = 'STORAGE ' . $options['storage'];
        }

        $this->_sendLine('SETQUOTA ' . $this->escape($root) . ' (' . implode(' ', $limits) . ')');
    }

    /**
     * Get quota limits.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $root  The quota root (UTF7-IMAP).
     *
     * @return mixed  An array with these possible keys: 'messages' and
     *                'storage'; each key holds an array with 2 values:
     *                'limit' and 'usage'.
     */
    protected function _getQuota($root)
    {
        $this->login();

        $this->_temp['quotaresp'] = array();
        $this->_sendLine('GETQUOTA ' . $this->escape($root));
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
     * Get quota limits for a mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     *
     * @return mixed  An array with the keys being the quota roots. Each key
     *                holds an array with two possible keys: 'messages' and
     *                'storage'; each of these keys holds an array with 2
     *                values: 'limit' and 'usage'.
     */
    protected function _getQuotaRoot($mailbox)
    {
        $this->login();

        $this->_temp['quotaresp'] = array();
        $this->_sendLine('GETQUOTAROOT ' . $this->escape($mailbox));
        return $this->_temp['quotaresp'];
    }

    /**
     * Set ACL rights for a given mailbox/identifier.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $mailbox     A mailbox (UTF7-IMAP).
     * @param string $identifier  The identifier to alter (UTF7-IMAP).
     * @param array $options      Additional options.
     */
    protected function _setACL($mailbox, $identifier, $options)
    {
        $this->login();

        // SETACL/DELETEACL returns no untagged information (RFC 4314 [3.1 &
        // 3.2]).
        if (empty($options['rights']) && !empty($options['remove'])) {
            $this->_sendLine('DELETEACL ' . $this->escape($mailbox) . ' ' . $identifier);
        } else {
            $this->_sendLine('SETACL ' . $this->escape($mailbox) . ' ' . $identifier . ' ' . $options['rights']);
        }
    }

    /**
     * Get ACL rights for a given mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     *
     * @return array  An array with identifiers as the keys and an array of
     *                rights as the values.
     */
    protected function _getACL($mailbox)
    {
        $this->login();

        $this->_temp['getacl'] = array();
        $this->_sendLine('GETACL ' . $this->escape($mailbox));
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
     * Get ACL rights for a given mailbox/identifier.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $mailbox     A mailbox (UTF7-IMAP).
     * @param string $identifier  The identifier (US-ASCII).
     *
     * @return array  An array of rights (keys: 'required' and 'optional').
     */
    protected function _listACLRights($mailbox, $identifier)
    {
        $this->login();

        $this->_temp['listaclrights'] = array();
        $this->_sendLine('LISTRIGHTS ' . $this->escape($mailbox) . ' ' . $identifier);
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
        $this->_temp['myrights'] = array(
            'required' => str_split($data[2]),
            'optional' => array_slice($data, 3)
        );
    }

    /**
     * Get the ACL rights for the current user for a given mailbox.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     *
     * @return array  An array of rights.
     */
    protected function _getMyACLRights($mailbox)
    {
        $this->login();

        $this->_temp['myrights'] = array();
        $this->_sendLine('MYRIGHTS ' . $this->escape($mailbox));
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

    /* Internal functions. */

    /**
     * Perform a command on the IMAP server. A connection to the server must
     * have already been made.
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @todo RFC 3501 allows the sending of multiple commands at once. For
     *       simplicity of implementation at this time, we will execute
     *       commands one at a time. This allows us to easily determine data
     *       meant for a command while scanning for untagged responses
     *       unilaterally sent by the server.
     *
     * @param string $query   The IMAP command to execute.
     * @param array $options  Additional options:
     * <pre>
     * 'binary' - (boolean) Does $query contain binary data?  If so, and the
     *            'BINARY' extension is available on the server, the data
     *            will be sent in literal8 format. If not available, an
     *            exception will be returned. 'binary' requires literal to
     *            be defined.
     *            DEFAULT: Sends literals in a non-binary compliant method.
     * 'debug' - (string) When debugging, send this string instead of the
     *           actual command/data sent.
     *           DEFAULT: Raw data output to debug stream.
     * 'literal' - (integer) Send the command followed by a literal. The value
     *             of 'literal' is the length of the literal data.
     *             Will attempt to use LITERAL+ capability if possible.
     *             DEFAULT: Do not send literal
     * 'literaldata' - (boolean) Is this literal data?  If so, will parse the
     *                 server response based on the existence of LITERAL+.
     *                 DEFAULT: Server specific.
     * 'noparse' - (boolean) Don't parse the response and instead return the
     *             server response.
     *             DEFAULT: Parses the response
     * 'notag' - (boolean) Don't prepend an IMAP tag (i.e. for a continuation
     *           response).
     *           DEFAULT: false
     * </pre>
     */
    protected function _sendLine($query, $options = array())
    {
        if (empty($options['notag'])) {
            $query = ++$this->_tag . ' ' . $query;

            /* Catch all FETCH responses until a tagged response. */
            $this->_temp['fetchresp'] = array('seq' => array(), 'uid' => array());
        }

        $continuation = $literalplus = false;

        if (!empty($options['literal']) || !empty($options['literaldata'])) {
            if ($this->queryCapability('LITERAL+')) {
                /* RFC 2088 - If LITERAL+ is available, saves a roundtrip
                 * from the server. */
                $literalplus = true;
            } else {
                $continuation = true;
            }

            if (!empty($options['literal'])) {
                $query .= ' ';

                // RFC 3516 - Send literal8 if we have binary data.
                if (!empty($options['binary'])) {
                    if (!$this->queryCapability('BINARY')) {
                        throw new Horde_Imap_Client_Exception('Can not send binary data to server that does not support it.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
                    }
                    $query .= '~';
                }

                $query .= '{' . $options['literal'] . ($literalplus ? '+' : '') . '}';
            }
        }

        if ($this->_debug) {
            fwrite($this->_debug, 'C: ' . (empty($options['debug']) ? $query : $options['debug']) . "\n");
        }

        fwrite($this->_stream, $query . "\r\n");

        if ($literalplus) {
            return;
        }

        if ($continuation) {
            $ob = $this->_getLine();
            if ($ob['type'] != 'continuation') {
                throw new Horde_Imap_Client_Exception('Unexpected response from IMAP server while waiting for a continuation request: ' . $ob['line']);
            }
        } elseif (empty($options['noparse'])) {
            $this->_parseResponse($this->_tag);
        } else {
            return $this->_getLine();
        }
    }

    /**
     * Gets a line from the IMAP stream and parses it.
     * Throws a Horde_Imap_Client_Exception on error.
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
     */
    protected function _getLine()
    {
        $ob = array('line' => '', 'response' => '', 'tag' => '', 'token' => '');

        if (feof($this->_stream)) {
            $this->_temp['logout'] = true;
            $this->logout();
            throw new Horde_Imap_Client_Exception('IMAP Server closed the connection unexpectedly.', Horde_Imap_Client_Exception::IMAP_DISCONNECT);
        }

        $read = rtrim(fgets($this->_stream));
        if (empty($read)) {
            return;
        }

        if ($this->_debug) {
            fwrite($this->_debug, 'S: ' . $read . "\n");
        }

        $read = explode(' ', $read, 3);

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
                    throw new Horde_Imap_Client_Exception('IMAP Server closed the connection: ' . implode(' ', array_slice($read, 1)), Horde_Imap_Client_Exception::IMAP_DISCONNECT);
                }
            }

            if (in_array($read[1], array('OK', 'NO', 'BAD', 'PREAUTH'))) {
                $ob['response'] = $read[1];
                $ob['line'] = implode(' ', array_slice($read, 2));
            } else {
                /* Tokenize response. */
                $line = implode(' ', array_slice($read, 1));
                $binary = $literal = false;
                $this->_temp['token'] = null;
                $this->_temp['literal8'] = array();

                do {
                    $literal_len = null;

                    if (!$literal && (substr($line, -1) == '}')) {
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

                    if ($literal) {
                        $this->_temp['token']['ptr'][$this->_temp['token']['paren']][] = $line;
                    } else {
                        $this->_tokenizeData($line);
                    }

                    if (is_null($literal_len)) {
                        if (!$literal) {
                            break;
                        }
                        $binary = $literal = false;
                        $line = rtrim(fgets($this->_stream));
                    } else {
                        $literal = true;
                        $line = '';
                        while ($literal_len) {
                            $data_read = fread($this->_stream, min($literal_len, 8192));
                            $literal_len -= strlen($data_read);
                            $line .= $data_read;
                        }
                    }

                    if ($this->_debug) {
                        $debug_line = $binary
                            ? "[BINARY DATA - $literal_len bytes]"
                            : $line;
                        fwrite($this->_debug, 'S: ' . $debug_line . "\n");
                    }
                } while (true);

                $ob['token'] = $this->_temp['token']['out'];
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
     * Tokenize IMAP data. Handles quoted strings and parantheses.
     *
     * @param string $line  The raw IMAP data.
     */
    protected function _tokenizeData($line)
    {
        if (is_null($this->_temp['token'])) {
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
                    if ($i && ($line[$i - 1] != '//')) {
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
     * Throws a Horde_Imap_Client_Exception on error.
     *
     * @param string $tag  The IMAP tag of the current command.
     */
    protected function _parseResponse($tag)
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
                    if (empty($this->_temp['parsestatuserr'])) {
                        $errcode = 0;
                        $errstr = empty($ob['line']) ? '[No error message returned by server.]' : $ob['line'];
                    } else {
                        list($errcode, $errstr) = $this->_temp['parsestatuserr'];
                    }
                    $this->_temp['parseresperr'] = $ob;

                    if ($ob['response'] == 'BAD') {
                        throw new Horde_Imap_Client_Exception('Bad IMAP request: ' . $errstr, $errcode);
                    } else {
                        throw new Horde_Imap_Client_Exception('IMAP error: ' . $errstr, $errcode);
                    }
                }

                /* Update the cache, if needed. */
                $tmp = $this->_temp['fetchresp'];
                if (!empty($tmp['uid'])) {
                    $this->_updateCache($tmp['uid']);
                } elseif (!empty($tmp['seq'])) {
                    $this->_updateCache($tmp['seq'], array('seq' => true));
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
        } else {
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
                $this->_parseStatus($ob['token'][2]);
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
                    $this->_parseFetch($ob['token'][0], reset(array_slice($ob['token'], 2)));
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
            $data = null;
        } else {
            $code = strtoupper(substr($ob['line'], 1, $pos - 1));
            $data = substr($ob['line'], $pos + 1, $end_pos - $pos - 1);
        }

        $this->_temp['parsestatuserr'] = null;

        switch ($code) {
        case 'ALERT':
            if (!isset($this->_temp['alerts'])) {
                $this->_temp['alerts'] = array();
            }
            $this->_temp['alerts'][] = $data;
            break;

        case 'BADCHARSET':
            /* @todo Store the list of search charsets supported by the server
             * (this is a MAY response, not a MUST response) */
            $this->_temp['parsestatuserr'] = array(
                Horde_Imap_Client_Exception::BADCHARSET,
                substr($ob['line'], $end_pos + 2)
            );
            break;

        case 'CAPABILITY':
            $this->_temp['token'] = null;
            $this->_tokenizeData($data);
            $this->_parseCapability($this->_temp['token']['out']);
            break;

        case 'PARSE':
            $this->_temp['parsestatuserr'] = array(
                Horde_Imap_Client_Exception::PARSEERROR,
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
            $this->_temp['token'] = null;
            $this->_tokenizeData($data);
            $this->_temp['mailbox']['permflags'] = array_map('strtolower', reset($this->_temp['token']['out']));
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
            $this->_temp['referral'] = $this->parseImapURL($data);
            break;

        case 'UNKNOWN-CTE':
            // Defined by RFC 3516
            $this->_temp['parsestatuserr'] = array(
                Horde_Imap_Client_Exception::UNKNOWNCTE,
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
            $this->_updateCache(array(), array('mailbox' => $this->_temp['uidplusmbox'], 'uidvalid' => $parts[0]));

            if ($code == 'APPENDUID') {
                $this->_temp['appenduid'] = array_merge($this->_temp['appenduid'], $this->fromSequenceString($parts[1]));
            } else {
                $this->_temp['copyuid'] = array_combine($this->fromSequenceString($parts[1]), $this->fromSequenceString($parts[2]));
            }
            break;

        case 'UIDNOTSTICKY':
            // Defined by RFC 4315 [3]
            $this->_temp['mailbox']['uidnotsticky'] = true;
            break;

        case 'HIGHESTMODSEQ':
        case 'NOMODSEQ':
            // Defined by RFC 4551 [3.1.1 & 3.1.2]
            $this->_temp['mailbox']['highestmodseq'] = ($code == 'HIGHESTMODSEQ') ? $data : null;
            break;

        case 'MODIFIED':
            // Defined by RFC 4551 [3.2]
            $this->_temp['modified'] = $this->fromSequenceString($data);
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
                Horde_Imap_Client_Exception::BADCOMPARATOR,
                substr($ob['line'], $end_pos + 2)
            );
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

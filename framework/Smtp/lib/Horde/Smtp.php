<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 */

/**
 * An interface to an SMTP server (RFC 5321).
 *
 * Implements the following SMTP-related RFCs:
 * <pre>
 *   - RFC 1870/STD 10: Message Size Declaration
 *   - RFC 2034: Enhanced-Status-Codes
 *   - RFC 2195: CRAM-MD5 (SASL Authentication)
 *   - RFC 2595/4616: TLS & PLAIN (SASL Authentication)
 *   - RFC 2831: DIGEST-MD5 authentication mechanism (obsoleted by RFC 6331)
 *   - RFC 2920/STD 60: Pipelining
 *   - RFC 3207: Secure SMTP over TLS
 *   - RFC 3463: Enhanced Mail System Status Codes
 *   - RFC 4422: SASL Authentication (for DIGEST-MD5)
 *   - RFC 4954: Authentication
 *   - RFC 5321: Simple Mail Transfer Protocol
 *   - RFC 6152/STD 71: 8bit-MIMEtransport
 *   - RFC 6409/STD 72: Message Submission for Mail
 *   - RFC 6531: Internationalized Email
 *
 *   - XOAUTH2: https://developers.google.com/gmail/xoauth2_protocol
 * </pre>
 *
 * TODO:
 * <pre>
 *   - RFC 1845: CHECKPOINT
 *   - RFC 2852: DELIVERYBY
 *   - RFC 3030: BINARYMIME/CHUNKING
 *   - RFC 3461: DSN
 *   - RFC 3865: NO-SOLICITING
 *   - RFC 3885: MTRK
 *   - RFC 4141: CONPERM/CONNEG
 *   - RFC 4405: SUBMITTER
 *   - RFC 4468: BURL
 *   - RFC 4865: FUTURERELEASE
 *   - RFC 6710: MT-PRIORITY
 *   - RFC 7293: RRVS
 * </pre>
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 *
 * @property-read boolean $data_8bit  Does server support sending 8-bit MIME
 *                                    data?
 * @property-read boolean $data_intl  Does server support sending
 *                                    internationalized (UTF-8) header data?
 *                                    (@since 1.6.0)
 * @property-read integer $size  The maximum message size supported (in
 *                               bytes) or null if this cannot be determined.
 */
class Horde_Smtp implements Serializable
{
    /**
     * Connection to the SMTP server.
     *
     * @var Horde_Smtp_Connection
     */
    protected $_connection;

    /**
     * The debug object.
     *
     * @var Horde_Smtp_Debug
     */
    protected $_debug;

    /**
     * The hello command to use for extended SMTP support.
     *
     * @var string
     */
    protected $_ehlo = 'EHLO';

    /**
     * The list of ESMTP extensions.
     * If this value is null, we have not connected to server yet.
     *
     * @var array
     */
    protected $_extensions = null;

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * List of required ESMTP extensions.
     *
     * @var array
     */
    protected $_requiredExts = array();

    /**
     * Constructor.
     *
     * @param array $params   Configuration parameters:
     * <ul>
     *  <li>
     *   debug: (string) If set, will output debug information to the stream
     *          provided. The value can be any PHP supported wrapper that
     *          can be opened via fopen().
     *          DEFAULT: No debug output
     *  </li>
     *  <li>
     *   host: (string) The SMTP server.
     *         DEFAULT: localhost
     *  </li>
     *  <li>
     *   password: (mixed) The SMTP password or a Horde_Smtp_Password object
     *             (since 1.1.0).
     *             DEFAULT: NONE
     *  </li>
     *  <li>
     *   port: (string) The SMTP port.
     *         DEFAULT: 587 (See RFC 6409/STD 72)
     *  </li>
     *  <li>
     *   secure: (string) Use SSL or TLS to connect.
     *           DEFAULT: true (use 'tls' option, if available)
     *   <ul>
     *    <li>false (No encryption)</li>
     *    <li>'ssl' (Auto-detect SSL version)</li>
     *    <li>'sslv2' (Force SSL version 3)</li>
     *    <li>'sslv3' (Force SSL version 2)</li>
     *    <li>'tls' (TLS; started via protocol-level negotation over
     *    unencrypted channel; RECOMMENDED way of initiating secure
     *    connection)
     *    <li>'tlsv1' (TLS direct version 1.x connection to server) [@since
     *    1.3.0]</li>
     *    <li>true (Use TLS, if available) [@since 1.2.0]</li>
     *   </ul>
     *  </li>
     *  <li>
     *   timeout: (integer) Connection timeout, in seconds.
     *            DEFAULT: 30 seconds
     *  </li>
     *  <li>
     *   username: (string) The SMTP username.
     *             DEFAULT: NONE
     *  </li>
     *  <li>
     *   xoauth2_token: (string) If set, will authenticate via the XOAUTH2
     *                  mechanism (if available) with this token. Either a
     *                  string or a Horde_Smtp_Password object (since 1.1.0).
     *  </li>
     * </ul>
     */
    public function __construct(array $params = array())
    {
        // Default values.
        $params = array_merge(array(
            'host' => 'localhost',
            'port' => 587,
            'secure' => true,
            'timeout' => 30
        ), array_filter($params));

        foreach ($params as $key => $val) {
            $this->setParam($key, $val);
        }

        $this->_initOb();
    }

    /**
     * Get encryption key.
     *
     * @deprecated
     *
     * @return string  The encryption key.
     */
    protected function _getEncryptKey()
    {
        if (is_callable($ekey = $this->getParam('password_encrypt'))) {
            return call_user_func($ekey);
        }

        throw new InvalidArgumentException('password_encrypt parameter is not a valid callback.');
    }

    /**
     * Do initialization tasks.
     */
    protected function _initOb()
    {
        register_shutdown_function(array($this, 'shutdown'));
        $this->_debug = ($debug = $this->getParam('debug'))
            ? new Horde_Smtp_Debug($debug)
            : new Horde_Support_Stub();
    }

    /**
     * Shutdown actions.
     */
    public function shutdown()
    {
        $this->logout();
    }

    /**
     * This object can not be cloned.
     */
    public function __clone()
    {
        throw new LogicException('Object cannot be cloned.');
    }

    /**
     */
    public function serialize()
    {
        return serialize($this->_params);
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_params = @unserialize($data);
        $this->_initOb();
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'data_8bit':
            // RFC 6152
            return $this->queryExtension('8BITMIME');

        case 'data_intl':
            // RFC 6531
            return $this->queryExtension('SMTPUTF8');

        case 'size':
            // RFC 1870
            return $this->queryExtension('SIZE') ?: null;
        }
    }

    /**
     * Returns a value from the internal params array.
     *
     * @param string $key  The param key.
     *
     * @return mixed  The param value, or null if not found.
     */
    public function getParam($key)
    {
        /* Passwords may be stored encrypted. */
        switch ($key) {
        case 'password':
        case 'xoauth2_token':
            if (isset($this->_params[$key]) &&
                ($this->_params[$key] instanceof Horde_Smtp_Password)) {
                return $this->_params[$key]->getPassword();
            }

            // DEPRECATED
            if (($key == 'password') &&
                !empty($this->_params['_passencrypt'])) {
                try {
                    $secret = new Horde_Secret();
                    return $secret->read($this->_getEncryptKey(), $this->_params['password']);
                } catch (Exception $e) {
                    return null;
                }
            }
            break;
        }

        return isset($this->_params[$key])
            ? $this->_params[$key]
            : null;
    }

    /**
     * Sets a configuration parameter value.
     *
     * @param string $key  The param key.
     * @param mixed $val   The param value.
     */
    public function setParam($key, $val)
    {
        switch ($key) {
        case 'password':
            if ($val instanceof Horde_Smtp_Password) {
                break;
            }

            // Encrypt password. DEPRECATED
            try {
                $encrypt_key = $this->_getEncryptKey();
                if (strlen($encrypt_key)) {
                    $secret = new Horde_Secret();
                    $val = $secret->write($encrypt_key, $val);
                    $this->_params['_passencrypt'] = true;
                }
            } catch (Exception $e) {}
            break;
        }

        $this->_params[$key] = $val;
    }

    /**
     * Returns whether the SMTP server supports the given extension.
     *
     * @param string $ext  The extension to query.
     *
     * @return mixed  False if the server doesn't support the extension;
     *                otherwise, the extension value (returns true if the
     *                extension only supports existence).
     */
    public function queryExtension($ext)
    {
        try {
            $this->login();
        } catch (Horde_Smtp_Exception $e) {
            return false;
        }

        $ext = strtoupper($ext);

        return isset($this->_extensions[$ext])
            ? $this->_extensions[$ext]
            : false;
    }

    /**
     * Display if connection to the server has been secured via TLS or SSL.
     *
     * @return boolean  True if the SMTP connection is secured.
     */
    public function isSecureConnection()
    {
        return ($this->_connection && $this->_connection->secure);
    }

    /**
     * Connect/login to the SMTP server.
     *
     * @throws Horde_Smtp_Exception
     */
    public function login()
    {
        if (!is_null($this->_extensions)) {
            return;
        }

        if (!$this->_connection) {
            try {
                $this->_connection = new Horde_Smtp_Connection(
                    $this->getParam('host'),
                    $this->getParam('port'),
                    $this->getParam('timeout'),
                    $this->getParam('secure'),
                    array(
                        'debug' => $this->_debug
                    )
                );
            } catch (Horde\Socket\Client\Exception $e) {
                $e2 = new Horde_Smtp_Exception(
                    Horde_Smtp_Translation::r("Error connecting to SMTP server."),
                    Horde_Smtp_Exception::SERVER_CONNECT
                );
                $e2->details = $e->details;
                throw $e2;
            }

            $this->_debug->info(sprintf(
                'Connection to: smtp://%s:%s',
                $this->getParam('host'),
                $this->getParam('port')
            ));

            // Get initial line (RFC 5321 [3.1]).
            $this->_getResponse(220, 'logout');
        }

        $this->_hello();

        if ($this->_startTls()) {
            $this->_extensions = null;
            $this->login();
            return;
        }

        /* Check for required ESMTP extensions. */
        foreach ($this->_requiredExts as $val) {
            if (!$this->queryExtension($val)) {
                throw new Horde_Smtp_Exception(
                    sprintf(
                        Horde_Smtp_Translation::r("Server does not support a necessary server extension: %s."),
                        $val
                    ),
                    Horde_Smtp_Exception::LOGIN_MISSINGEXTENSION
                );
            }
        }

        /* If we reached this point and don't have a secure connection, then
         * a secure connections is not available. */
        if (!$this->isSecureConnection() &&
            ($this->getParam('secure') === true)) {
            $this->setParam('secure', false);
        }

        if (!strlen($this->getParam('username')) ||
            !($auth = $this->queryExtension('AUTH'))) {
            return;
        }

        $auth = array_flip(array_map('trim', explode(' ', $auth)));

        // XOAUTH2
        if (isset($auth['XOAUTH2'])) {
            unset($auth['XOAUTH2']);
            if ($this->getParam('xoauth2_token')) {
                $auth = array('XOAUTH2' => true) + $auth;
            }
        }

        foreach (array_keys($auth) as $method) {
            try {
                $this->_auth($method);
                return;
            } catch (Horde_Smtp_Exception $e) {}
        }

        $this->logout();
        throw new Horde_Smtp_Exception(
            Horde_Smtp_Translation::r("Server denied authentication."),
            Horde_Smtp_Exception::LOGIN_AUTHENTICATIONFAILED
        );
    }

    /**
     * Logout from the SMTP server.
     */
    public function logout()
    {
        if (!is_null($this->_extensions) && $this->_connection->connected) {
            try {
                // See RFC 5321 [4.1.1.10]
                $this->_connection->write('QUIT');
                $this->_getResponse(221);
            } catch (Exception $e) {}

            $this->_connection->close();
        }

        unset($this->_connection);
        $this->_extensions = null;
    }

    /**
     * Send a message.
     *
     * @param mixed $from  The from address. Either a
     *                     Horde_Mail_Rfc822_Address object or a string.
     * @param mixed $to    The to (recipient) addresses. Either a
     *                     Horde_Mail_Rfc822_List object, a string, or an
     *                     array of addresses.
     * @param mixed $data  The data to send. Either a stream or a string.
     * @param array $opts  Additional options:
     * <pre>
     *   - 8bit: (boolean) If true, $data is a MIME message with arbitrary
     *           octet content (i.e. 8-bit encoding).
     *           DEFAULT: false
     *   - intl: (boolean) If true, $data contains internationalized header
     *           content (UTF-8). (@since 1.6.0)
     *           DEFAULT: false
     * </pre>
     *
     * @return array  If no receipients were successful, a
     *                Horde_Smtp_Exception will be thrown. If at least one
     *                recipient was successful, an array with the following
     *                format is returned: (@since 1.5.0)
     *   - KEYS: Recipient addresses ($to addresses).
     *   - VALUES: Boolean true (if message was accepted for this recpieint)
     *             or a Horde_Smtp_Exception (if messages was not accepted).
     *
     * @throws Horde_Smtp_Exception
     */
    public function send($from, $to, $data, array $opts = array())
    {
        $this->login();

        if (!($from instanceof Horde_Mail_Rfc822_Address)) {
            $from = new Horde_Mail_Rfc822_Address($from);
        }

        /* RFC 6531 */
        if (!empty($opts['intl'])) {
            if (!$this->data_intl) {
                throw new InvalidArgumentException(
                    'Server does not support sending internationalized header data.'
                );
            }

            /* RFC 6531[1.2] requires 8BITMIME to be available. */
            $opts['8bit'] = true;
        }

        /* RFC 6152[3] */
        if (!empty($opts['8bit']) && !$this->data_8bit) {
            throw new InvalidArgumentException(
                'Server does not support sending 8-bit data.'
            );
        }

        $mailcmd = 'MAIL FROM:<' .
            (empty($opts['intl']) ? $from->bare_address_idn : $from->bare_address) .
            '>';

        // RFC 1870[6]
        if ($this->queryExtension('SIZE')) {
            if (is_resource($data)) {
                fseek($data, 0, SEEK_END);
                $size = ftell($data);
            } else {
                $size = strlen($data);
            }

            $mailcmd .= ' SIZE=' . intval($size);
        }

        // RFC 6531[3.4]
        if (!empty($opts['intl'])) {
            $mailcmd .= ' SMTPUTF8';
        }

        // RFC 6152[3]
        if (!empty($opts['8bit'])) {
            $mailcmd .= ' BODY=8BITMIME';
        } elseif ($this->_debug->active && $this->data_8bit) {
            /* Only output extended 7bit command if debug is active (it is
             * default and does not need to be explicitly declared). */
            $mailcmd .= ' BODY=7BIT';
        }

        $cmds = array($mailcmd);

        if (!($to instanceof Horde_Mail_Rfc822_List)) {
            $to = new Horde_Mail_Rfc822_List($to);
        }

        $recipients = empty($opts['intl'])
            ? $to->bare_addresses_idn
            : $to->bare_addresses;
        foreach ($recipients as $val) {
            $cmds[] = 'RCPT TO:<' . $val . '>';
        }

        if ($this->queryExtension('PIPELINING')) {
            $this->_connection->write($cmds);

            $error = null;
            foreach ($cmds as $val) {
                try {
                    $this->_getResponse(array(250, 251));
                } catch (Horde_Smtp_Exception $e) {
                    if (is_null($error)) {
                        $error = $e;
                    }
                }
            }

            /* Can't pipeline DATA since we want to throw an exception if
             * ANY of the recipients are bad. */
            if (!is_null($error)) {
                $this->resetCmd();
                throw $error;
            }
        } else {
            foreach ($cmds as $val) {
                $this->_connection->write($val);
                $this->_getResponse(array(250, 251), 'reset');
            }
        }

        $this->_connection->write('DATA');

        try {
            $this->_getResponse(354, 'reset');
        } catch (Horde_Smtp_Exception $e) {
            /* This is the place where a STARTTLS 530 error would occur. If
             * so, explicitly use STARTTLS and try again. */
            switch ($e->getSmtpCode()) {
            case 530:
                if (!$this->isSecureConnection()) {
                    $this->logout();
                    $this->setParam('secure', 'tls');
                    $this->send($from, $to, $data, $opts);
                    return;
                }
                break;
            }

            throw $e;
        }

        if (!is_resource($data)) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $data);
            $data = $stream;
        }
        rewind($data);

        // Add SMTP escape filter.
        stream_filter_register('horde_smtp_data', 'Horde_Smtp_Filter_Data');
        $res = stream_filter_append($data, 'horde_smtp_data', STREAM_FILTER_READ);

        $this->_connection->write($data);
        stream_filter_remove($res);

        $this->_connection->write('.');

        return $this->_processData($recipients);
    }

    /**
     * Send a reset command.
     *
     * @throws Horde_Smtp_Exception
     */
    public function resetCmd()
    {
        $this->login();

        // See RFC 5321 [4.1.1.5].
        // RSET only useful if we are already authenticated.
        if (!is_null($this->_extensions)) {
            $this->_connection->write('RSET');
            $this->_getResponse(250);
        }
    }

    /**
     * Send a NOOP command.
     *
     * @throws Horde_Smtp_Exception
     */
    public function noop()
    {
        $this->login();

        // See RFC 5321 [4.1.1.9].
        // NOOP only useful if we are already authenticated.
        if (!is_null($this->_extensions)) {
            $this->_connection->write('NOOP');
            $this->_getResponse(250);
        }
    }

    /* Internal methods. */

    /**
     * Send "Hello" command to the server.
     *
     * @throws Horde_Smtp_Exception
     */
    protected function _hello()
    {
        $ehlo = $host = gethostname();
        if ($host === false) {
            $ehlo = $_SERVER['SERVER_ADDR'];
            $host = 'localhost';
        }

        $this->_connection->write($this->_ehlo . ' ' . $ehlo);
        try {
            $resp = $this->_getResponse(250);

            foreach ($resp as $val) {
                $tmp = explode(' ', $val, 2);
                $this->_extensions[$tmp[0]] = empty($tmp[1])
                    ? true
                    : $tmp[1];
            }
        } catch (Horde_Smtp_Exception $e) {
            switch ($e->getSmtpCode()) {
            case 502:
                // Old server - doesn't support EHLO
                $this->_connection->write('HELO ' . $host);
                try {
                    $this->_getResponse(250);
                } catch (Horde_Smtp_Exception $e2) {
                    $this->logout();
                    throw $e;
                }
                $this->_extensions = array();
                break;

            default:
                $this->logout();
                throw $e;
            }
        }
    }

    /**
     * Starts the TLS connection to the server, if necessary.  See RFC 3207.
     *
     * @return boolean  True if TLS was started.
     *
     * @throws Horde_Smtp_Exception
     */
    protected function _startTls()
    {
        $secure = $this->getParam('secure');

        if ($this->isSecureConnection() ||
            (($secure !== true) && ($secure !== 'tls'))) {
            return false;
        }

        if (!$this->queryExtension('STARTTLS')) {
            if ($secure === true) {
                return false;
            }

            throw new Horde_Smtp_Exception(
                Horde_Smtp_Translation::r("Server does not support TLS connections."),
                Horde_Smtp_Exception::LOGIN_TLSFAILURE
            );
        }

        $this->_connection->write('STARTTLS');
        $this->_getResponse(220, 'logout');

        if (!$this->_connection->startTls()) {
            $this->logout();
            $e = new Horde_Smtp_Exception();
            $e->setSmtpCode(454);
            throw $e;
        }

        $this->_debug->info('Successfully completed TLS negotiation.');

        $this->setParam('secure', 'tls');

        return true;
    }

    /**
     * Authenticate user to server for a given method.
     *
     * @param string $method  Authentication method.
     *
     * @throws Horde_Smtp_Exception
     */
    protected function _auth($method)
    {
        $user = $this->getParam('username');
        $pass = $this->getParam('password');

        $debug = sprintf("[AUTH Command - method: %s; username: %s]\n", $method, $user);

        switch ($method) {
        case 'CRAM-MD5':
        case 'CRAM-SHA1':
        case 'CRAM-SHA256':
            // RFC 2195: CRAM-MD5
            // CRAM-SHA1 & CRAM-SHA256 supported by Courier SASL library
            $this->_connection->write('AUTH ' . $method);
            $resp = $this->_getResponse(334);

            $this->_debug->active = false;
            $this->_connection->write(
                base64_encode($user . ' ' . hash_hmac(strtolower(substr($method, 5)), base64_decode(reset($resp)), $pass, false))
            );
            $this->_debug->active = true;

            $this->_debug->raw($debug);
            break;

        case 'DIGEST-MD5':
            // RFC 2831/4422; obsoleted by RFC 6331
            // Since this is obsolete, will only attempt if
            // Horde_Imap_Client is also present on the system.
            if (!class_exists('Horde_Imap_Client_Auth_DigestMD5')) {
                throw new Horde_Smtp_Exception('DIGEST-MD5 not supported');
            }

            $this->_connection->write('AUTH ' . $method);
            $resp = $this->_getResponse(334);

            $this->_debug->active = false;
            $this->_connection->write(
                base64_encode(new Horde_Imap_Client_Auth_DigestMD5(
                    $user,
                    $pass,
                    base64_decode(reset($resp)),
                    $this->getParam('hostspec'),
                    'smtp'
                ))
            );
            $this->_debug->active = true;
            $this->_debug->raw($debug);

            $this->_getResponse(334);
            $this->_connection->write('');
            break;

        case 'LOGIN':
            $this->_connection->write('AUTH ' . $method);
            $this->_getResponse(334);
            $this->_connection->write(base64_encode($user));
            $this->_getResponse(334);
            $this->_debug->active = false;
            $this->_connection->write(base64_encode($pass));
            $this->_debug->active = true;
            $this->_debug->raw($debug);
            break;

        case 'PLAIN':
            // RFC 2595/4616 - PLAIN SASL mechanism
            $auth = base64_encode(implode("\0", array(
                $user,
                $user,
                $pass
            )));
            $this->_debug->active = false;
            $this->_connection->write('AUTH ' . $method . ' ' . $auth);
            $this->_debug->active = true;
            $this->_debug->raw($debug);
            break;

        case 'XOAUTH2':
            // Google XOAUTH2
            $this->_debug->active = false;
            $this->_connection->write(
                'AUTH ' . $method . ' ' . $this->getParam('xoauth2_token')
            );
            $this->_debug->active = true;
            $this->_debug->raw($debug);

            try {
                $this->_getResponse(235);
                return;
            } catch (Horde_Smtp_Exception $e) {
                switch ($e->getSmtpCode()) {
                case 334:
                    $this->_connection->write('');
                    break;
                }
            }
            break;

        default:
            throw new Horde_Smtp_Exception(sprintf('Authentication method %s not supported', $method));
        }

        $this->_getResponse(235);
    }

    /**
     * Gets a line from the incoming stream and parses it.
     *
     * @param mixed $code    Expected reply code(s) (integer or array).
     * @param string $error  On error, 'logout' or 'reset'?
     *
     * @return array  An array with the response text.
     * @throws Horde_Smtp_Exception
     */
    protected function _getResponse($code, $error = null)
    {
        $text = array();

        while ($read = $this->_connection->read()) {
            $read = trim(rtrim($read, "\r\n"));
            $replycode = intval(substr($read, 0, 3));
            $text[] = ltrim(substr($read, 4));
            if ($read[3] != '-') {
                break;
            }
        }

        if (!is_array($code)) {
            $code = array($code);
        }

        if (in_array($replycode, $code)) {
            return $text;
        }

        /* Check for enhanced status codes (RFC 2034). */
        $details = reset($text);
        if (!is_null($this->_extensions) &&
            $this->queryExtension('ENHANCEDSTATUSCODES')) {
            list($enhanced, $details) = explode(' ', $details, 2);
        } else {
            $enhanced = null;
        }

        $e = new Horde_Smtp_Exception($details);
        $e->details = $details;
        $e->setSmtpCode($replycode);
        $e->setEnhancedSmtpCode($enhanced);

        switch ($error) {
        case 'logout':
            $this->logout();
            break;

        case 'reset':
            /* RFC 3207: If we see 530, no need to send reset command. */
            if ($code != 530) {
                $this->resetCmd();
            }
            break;
        }

        throw $e;
    }

    /**
     * Process the return from the DATA command.
     *
     * @see _send()
     *
     * @param array $recipients  The list of message recipients.
     *
     * @return array  See _send().
     * @throws Horde_Smtp_Exception
     */
    protected function _processData($recipients)
    {
        $this->_getResponse(250, 'reset');
        return array_fill_keys($recipients, true);
    }

}

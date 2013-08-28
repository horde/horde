<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
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
 *
 *   - XOAUTH2: https://developers.google.com/gmail/xoauth2_protocol
 * </pre>
 *
 * TODO:
 * <pre>
 *   - RFC 3030: BINARYMIME/CHUNKING
 * </pre>
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 *
 * @property-read boolean $data_8bit  Does server support sending 8-bit MIME
 *                                    data?
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
     * The list of extensions.
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
     *         DEFAULT: 25
     *  </li>
     *  <li>
     *   secure: (string) Use SSL or TLS to connect.
     *           DEFAULT: No encryption
     *   <ul>
     *    <li>false (No encryption)</li>
     *    <li>'ssl' (Auto-detect SSL version)</li>
     *    <li>'sslv2' (Force SSL version 3)</li>
     *    <li>'sslv3' (Force SSL version 2)</li>
     *    <li>'tls' (TLS)</li>
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
            'port' => 25,
            'secure' => false,
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
            if ($this->_params[$key] instanceof Horde_Smtp_Password) {
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
            $this->_connection = new Horde_Smtp_Connection($this, $this->_debug);

            // Get initial line (RFC 5321 [3.1]).
            $this->_getResponse(220, 'logout');
        }

        $ehlo = $host = gethostname();
        if ($host === false) {
            $ehlo = $_SERVER['SERVER_ADDR'];
            $host = 'localhost';
        }

        $this->_connection->write('EHLO ' . $ehlo);
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
                $this->_extensions = array();
                break;

            default:
                $this->logout();
                throw $e;
            }
        }

        if ($this->_startTls()) {
            $this->_extensions = null;
            $this->login();
            return;
        }

        if (!strlen($this->getParam('username')) ||
            !($auth = $this->queryExtension('AUTH'))) {
            return;
        }

        $auth = array_flip(array_map('trim', explode(' ', $auth)));

        // XOAUTH2
        if (isset($auth['XOAUTH2']) && $this->getParam('xoauth2_token')) {
            unset($auth['XOAUTH2']);
            $auth = array('XOAUTH2' => true) + $auth;
        }

        foreach (array_keys($auth) as $method) {
            try {
                $this->_auth($method);
                return;
            } catch (Horde_Smtp_Exception $e) {}
        }

        $this->logout();
        throw new Horde_Smtp_Exception(
            Horde_Smtp_Translation::t("Server denied authentication."),
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
     * </pre>
     *
     * @throws Horde_Smtp_Exception
     */
    public function send($from, $to, $data, array $opts = array())
    {
        $this->login();

        if (!($from instanceof Horde_Mail_Rfc822_Address)) {
            $from = new Horde_Mail_Rfc822_Address($from);
        }

        $mailcmd = 'MAIL FROM:<' . $from->bare_address_idn . '>';

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

        // RFC 6152[3]
        if (!empty($opts['8bit'])) {
            $mailcmd .= ' BODY=8BITMIME';
        }

        $cmds = array($mailcmd);

        if (!($to instanceof Horde_Mail_Rfc822_List)) {
            $to = new Horde_Mail_Rfc822_List($to);
        }

        foreach ($to->bare_addresses_idn as $val) {
            $cmds[] = 'RCPT TO:<' . $val . '>';
        }

        $error = null;

        if ($this->queryExtension('PIPELINING')) {
            $this->_connection->write(array_merge($cmds, array('DATA')));

            foreach ($cmds as $val) {
                try {
                    $this->_getResponse(array(250, 251));
                } catch (Horde_Smtp_Exception $e) {
                    if (is_null($error)) {
                        $error = $e;
                    }
                }
            }
        } else {
            foreach ($cmds as $val) {
                $this->_connection->write($val);
                $this->_getResponse(array(250, 251), 'reset');
            }

            $this->_connection->write('DATA');
        }

        try {
            $this->_getResponse(354, 'reset');
        } catch (Horde_Smtp_Exception $e) {
            throw ($error ? $error : $e);
        }

        if (!$error) {
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
        }

        $this->_connection->write('.');

        try {
            $this->_getResponse(250, 'reset');
        } catch (Horde_Smtp_Exception $e) {
            throw ($error ? $error : $e);
        }
    }

    /**
     * Send a RESET command.
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
     * Starts the TLS connection to the server, if necessary.  See RFC 3207.
     *
     * @return boolean  True if TLS was started.
     *
     * @throws Horde_Smtp_Exception
     */
    protected function _startTls()
    {
        if ($this->isSecureConnection() ||
            ($this->getParam('secure') != 'tls')) {
            return false;
        }

        if (!$this->queryExtension('STARTTLS')) {
            throw new Horde_Smtp_Exception(
                Horde_Smtp_Translation::t("Server does not support TLS connections."),
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
            list($enhanced, $details) = explode(' '. $details, 2);
        } else {
            $enhanced = null;
        }

        $e = new Horde_Smtp_Exception($details);
        $e->details = $details;
        $e->setSmtpCode($code);
        $e->setEnhancedSmtpCode($enhanced);

        switch ($error) {
        case 'logout':
            $this->logout();
            break;

        case 'reset':
            $this->resetCmd();
            break;
        }

        throw $e;
    }

}

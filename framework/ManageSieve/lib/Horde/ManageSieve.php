<?php
/**
 * Copyright 2002-2003 Richard Heyes
 * Copyright 2006-2008 Anish Mistry
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @package   ManageSieve
 * @author    Richard Heyes <richard@phpguru.org>
 * @author    Damian Fernandez Sosa <damlists@cnba.uba.ar>
 * @author    Anish Mistry <amistry@am-productions.biz>
 * @author    Jan Schneider <jan@horde.org>
 * @license   http://www.horde.org/licenses/bsd BSD
 */

namespace Horde;
use Horde\ManageSieve;
use Horde\ManageSieve\Exception;

/**
 * This class implements the ManageSieve protocol (RFC 5804).
 *
 * @package   ManageSieve
 * @author    Richard Heyes <richard@phpguru.org>
 * @author    Damian Fernandez Sosa <damlists@cnba.uba.ar>
 * @author    Anish Mistry <amistry@am-productions.biz>
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2002-2003 Richard Heyes
 * @copyright 2006-2008 Anish Mistry
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @link      http://tools.ietf.org/html/rfc5804 RFC 5804 A Protocol for
 *            Remotely Managing Sieve Scripts
 */
class ManageSieve
{
    /**
     * Client is disconnected.
     */
    const STATE_DISCONNECTED = 1;

    /**
     * Client is connected but not authenticated.
     */
    const STATE_NON_AUTHENTICATED = 2;

    /**
     * Client is authenticated.
     */
    const STATE_AUTHENTICATED = 3;

    /**
     * The authentication methods this class supports.
     *
     * Can be overwritten if having problems with certain methods.
     *
     * @var array
     */
    public $supportedAuthMethods = array(
        'DIGEST-MD5',
        'CRAM-MD5',
        'EXTERNAL',
        'PLAIN',
        'LOGIN',
    );

    /**
     * SASL authentication methods that require Auth_SASL.
     *
     * @var array
     */
    public $supportedSASLAuthMethods = array(
        'DIGEST-MD5',
        'CRAM-MD5',
    );

    /**
     * The socket handle.
     *
     * @var Net_Socket
     */
    protected $_sock;

    /**
     * Parameters and connection information.
     *
     * @var array
     */
    protected $_data;

    /**
     * Current state of the connection.
     *
     * One of the STATE_* constants.
     *
     * @var integer
     */
    protected $_state = self::STATE_DISCONNECTED;

    /**
     * Whether to enable debugging.
     *
     * @var boolean
     */
    protected $_debug = false;

    /**
     * Debug output handler.
     *
     * This has to be a valid callback.
     *
     * @var string|array
     */
    protected $_debug_handler = null;

    /**
     * Whether to pick up an already established connection.
     *
     * @var boolean
     */
    protected $_bypassAuth = false;

    /**
     * Whether to use TLS if available.
     *
     * @var boolean
     */
    protected $_useTLS = true;

    /**
     * Additional options for stream_context_create().
     *
     * @var array
     */
    protected $_options = null;

    /**
     * Maximum number of referral loops
     *
     * @var array
     */
    protected $_maxReferralCount = 15;

    /**
     * Constructor.
     *
     * Sets up the object, connects to the server and logs in.
     *
     * @param string  $user       Login username.
     * @param string  $pass       Login password.
     * @param string  $host       Hostname of server.
     * @param string  $port       Port of server.
     * @param string  $logintype  Type of login to perform (see
     *                            $supportedAuthMethods).
     * @param string  $euser      Effective user. If authenticating as an
     *                            administrator, login as this user.
     * @param boolean $debug      Whether to enable debugging (@see setDebug()).
     * @param string  $bypassAuth Skip the authentication phase. Useful if the
     *                            socket is already open.
     * @param boolean $useTLS     Use TLS if available.
     * @param array   $options    Additional options for
     *                            stream_context_create().
     * @param mixed   $handler    A callback handler for the debug output.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    public function __construct($user = null, $pass  = null, $host = 'localhost',
                       $port = 2000, $logintype = '', $euser = '',
                       $debug = false, $bypassAuth = false, $useTLS = true,
                       $options = null, $handler = null)
    {
        $this->_data['user']      = $user;
        $this->_data['pass']      = $pass;
        $this->_data['host']      = $host;
        $this->_data['port']      = $port;
        $this->_data['logintype'] = $logintype;
        $this->_data['euser']     = $euser;
        $this->_sock              = new \Net_Socket();
        $this->_bypassAuth        = $bypassAuth;
        $this->_useTLS            = $useTLS;
        $this->_options           = $options;
        $this->setDebug($debug, $handler);

        /* Try to include the Auth_SASL package.  If the package is not
         * available, we disable the authentication methods that depend upon
         * it. */
        if (!class_exists('Auth_SASL')) {
            $this->_debug('Auth_SASL not present');
            $this->supportedAuthMethods = array_diff(
                $this->supportedAuthMethods,
                $this->supportedSASLAuthMethods
            );
        }

        if (strlen($user) && strlen($pass)) {
            $this->_handleConnectAndLogin();
        }
    }

    /**
     * Sets the debug state and handler function.
     *
     * @param boolean $debug   Whether to enable debugging.
     * @param string  $handler A custom debug handler. Must be a valid callback.
     */
    public function setDebug($debug = true, $handler = null)
    {
        $this->_debug = $debug;
        $this->_debug_handler = $handler;
    }

    /**
     * Connects to the server and logs in.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _handleConnectAndLogin()
    {
        $this->connect($this->_data['host'], $this->_data['port'], $this->_options, $this->_useTLS);
        if ($this->_bypassAuth === false) {
            $this->login($this->_data['user'], $this->_data['pass'], $this->_data['logintype'], $this->_data['euser'], $this->_bypassAuth);
        }
    }

    /**
     * Handles connecting to the server and checks the response validity.
     *
     * @param string  $host    Hostname of server.
     * @param string  $port    Port of server.
     * @param array   $options List of options to pass to
     *                         stream_context_create().
     * @param boolean $useTLS  Use TLS if available.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    public function connect($host, $port, $options = null, $useTLS = true)
    {
        $this->_data['host'] = $host;
        $this->_data['port'] = $port;
        $this->_useTLS       = $useTLS;
        if (is_array($options)) {
            $this->_options = array_merge($this->_options, $options);
        }

        if (self::STATE_DISCONNECTED != $this->_state) {
            throw new NotDisconnected();
        }

        $res = $this->_sock->connect($host, $port, false, 5, $options);
        if (is_a($res, 'PEAR_Error')) {
            throw new Exception($res);
        }

        if ($this->_bypassAuth) {
            $this->_state = self::STATE_AUTHENTICATED;
        } else {
            $this->_state = self::STATE_NON_AUTHENTICATED;
            $this->_doCmd();
        }

        // Explicitly ask for the capabilities in case the connection is
        // picked up from an existing connection.
        try {
            $this->_cmdCapability();
        } catch (Exception $e) {
            throw new ConnectionFailed($e);
        }

        // Check if we can enable TLS via STARTTLS.
        if ($useTLS && !empty($this->_capability['starttls'])
            && function_exists('stream_socket_enable_crypto')
        ) {
            $this->_startTLS();
        }
    }

    /**
     * Disconnect from the Sieve server.
     *
     * @param boolean $sendLogoutCMD Whether to send LOGOUT command before
     *                               disconnecting.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    public function disconnect($sendLogoutCMD = true)
    {
        $this->_cmdLogout($sendLogoutCMD);
    }

    /**
     * Logs into server.
     *
     * @param string  $user       Login username.
     * @param string  $pass       Login password.
     * @param string  $logintype  Type of login method to use.
     * @param string  $euser      Effective UID (perform on behalf of $euser).
     * @param boolean $bypassAuth Do not perform authentication.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    public function login($user, $pass, $logintype = null, $euser = '', $bypassAuth = false)
    {
        $this->_data['user']      = $user;
        $this->_data['pass']      = $pass;
        $this->_data['logintype'] = $logintype;
        $this->_data['euser']     = $euser;
        $this->_bypassAuth        = $bypassAuth;

        if (self::STATE_DISCONNECTED == $this->_state) {
            throw new NotConnected();
        }
        if (self::STATE_AUTHENTICATED == $this->_state) {
            throw new Exception('Already authenticated');
        }

        if (!$bypassAuth ) {
            $this->_cmdAuthenticate($user, $pass, $logintype, $euser);
        }
        $this->_state = self::STATE_AUTHENTICATED;
    }

    /**
     * Returns an indexed array of scripts currently on the server.
     *
     * @return array  Indexed array of scriptnames.
     */
    public function listScripts()
    {
        if (is_array($scripts = $this->_cmdListScripts())) {
            return $scripts[0];
        } else {
            return $scripts;
        }
    }

    /**
     * Returns the active script.
     *
     * @return string  The active scriptname.
     */
    public function getActive()
    {
        if (is_array($scripts = $this->_cmdListScripts())) {
            return $scripts[1];
        }
    }

    /**
     * Sets the active script.
     *
     * @param string $scriptname The name of the script to be set as active.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    public function setActive($scriptname)
    {
        $this->_cmdSetActive($scriptname);
    }

    /**
     * Retrieves a script.
     *
     * @param string $scriptname The name of the script to be retrieved.
     *
     * @throws \Horde\ManageSieve\Exception
     * @return string  The script.
    */
    public function getScript($scriptname)
    {
        return $this->_cmdGetScript($scriptname);
    }

    /**
     * Adds a script to the server.
     *
     * @param string  $scriptname Name of the script.
     * @param string  $script     The script content.
     * @param boolean $makeactive Whether to make this the active script.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    public function installScript($scriptname, $script, $makeactive = false)
    {
        $this->_cmdPutScript($scriptname, $script);
        if ($makeactive) {
            $this->_cmdSetActive($scriptname);
        }
    }

    /**
     * Removes a script from the server.
     *
     * @param string $scriptname Name of the script.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    public function removeScript($scriptname)
    {
        $this->_cmdDeleteScript($scriptname);
    }

    /**
     * Checks if the server has space to store the script by the server.
     *
     * @param string  $scriptname The name of the script to mark as active.
     * @param integer $size       The size of the script.
     *
     * @throws \Horde\ManageSieve\Exception
     * @return boolean  True if there is space.
     */
    public function hasSpace($scriptname, $size)
    {
        if (self::STATE_AUTHENTICATED != $this->_state) {
            throw new NotAuthenticated();
        }

        try {
            $this->_doCmd(sprintf('HAVESPACE %s %d', $this->_escape($scriptname), $size));
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns the list of extensions the server supports.
     *
     * @throws \Horde\ManageSieve\Exception
     * @return array  List of extensions.
     */
    public function getExtensions()
    {
        if (self::STATE_DISCONNECTED == $this->_state) {
            throw new NotConnected();
        }
        return $this->_capability['extensions'];
    }

    /**
     * Returns whether the server supports an extension.
     *
     * @param string $extension The extension to check.
     *
     * @throws \Horde\ManageSieve\Exception
     * @return boolean  Whether the extension is supported.
     */
    public function hasExtension($extension)
    {
        if (self::STATE_DISCONNECTED == $this->_state) {
            throw new NotConnected();
        }

        $extension = trim($this->_toUpper($extension));
        if (is_array($this->_capability['extensions'])) {
            foreach ($this->_capability['extensions'] as $ext) {
                if ($ext == $extension) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the list of authentication methods the server supports.
     *
     * @throws \Horde\ManageSieve\Exception
     * @return array  List of authentication methods.
     */
    public function getAuthMechs()
    {
        if (self::STATE_DISCONNECTED == $this->_state) {
            throw new NotConnected();
        }
        return $this->_capability['sasl'];
    }

    /**
     * Returns whether the server supports an authentication method.
     *
     * @param string $method The method to check.
     *
     * @throws \Horde\ManageSieve\Exception
     * @return boolean  Whether the method is supported.
     */
    public function hasAuthMech($method)
    {
        if (self::STATE_DISCONNECTED == $this->_state) {
            throw new NotConnected();
        }

        $method = trim($this->_toUpper($method));
        if (is_array($this->_capability['sasl'])) {
            foreach ($this->_capability['sasl'] as $sasl) {
                if ($sasl == $method) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Handles the authentication using any known method.
     *
     * @param string $uid        The userid to authenticate as.
     * @param string $pwd        The password to authenticate with.
     * @param string $userMethod The method to use. If empty, the class chooses
     *                           the best (strongest) available method.
     * @param string $euser      The effective uid to authenticate as.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _cmdAuthenticate($uid, $pwd, $userMethod = null, $euser = '')
    {
        $method = $this->_getBestAuthMethod($userMethod);

        switch ($method) {
        case 'DIGEST-MD5':
            $this->_authDigestMD5($uid, $pwd, $euser);
            return;
        case 'CRAM-MD5':
            $this->_authCRAMMD5($uid, $pwd, $euser);
            break;
        case 'LOGIN':
            $this->_authLOGIN($uid, $pwd, $euser);
            break;
        case 'PLAIN':
            $this->_authPLAIN($uid, $pwd, $euser);
            break;
        case 'EXTERNAL':
            $this->_authEXTERNAL($uid, $pwd, $euser);
            break;
        default :
            throw new Exception(
                $method . ' is not a supported authentication method'
            );
            break;
        }

        $this->_doCmd();

        // Query the server capabilities again now that we are authenticated.
        try {
            $this->_cmdCapability();
        } catch (Exception $e) {
            throw new ConnectionFailed($e);
        }
    }

    /**
     * Authenticates the user using the PLAIN method.
     *
     * @param string $user  The userid to authenticate as.
     * @param string $pass  The password to authenticate with.
     * @param string $euser The effective uid to authenticate as.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _authPLAIN($user, $pass, $euser)
    {
        return $this->_sendCmd(
            sprintf(
                'AUTHENTICATE "PLAIN" "%s"',
                base64_encode($euser . chr(0) . $user . chr(0) . $pass)
            )
        );
    }

    /**
     * Authenticates the user using the LOGIN method.
     *
     * @param string $user  The userid to authenticate as.
     * @param string $pass  The password to authenticate with.
     * @param string $euser The effective uid to authenticate as. Not used.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _authLOGIN($user, $pass, $euser)
    {
        $this->_sendCmd('AUTHENTICATE "LOGIN"');
        $this->_doCmd('"' . base64_encode($user) . '"', true);
        $this->_doCmd('"' . base64_encode($pass) . '"', true);
    }

    /**
     * Authenticates the user using the CRAM-MD5 method.
     *
     * @param string $user  The userid to authenticate as.
     * @param string $pass  The password to authenticate with.
     * @param string $euser The effective uid to authenticate as. Not used.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _authCRAMMD5($user, $pass, $euser)
    {
        $challenge = $this->_doCmd('AUTHENTICATE "CRAM-MD5"', true);
        $challenge = base64_decode(trim($challenge));
        $cram = Auth_SASL::factory('crammd5');
        $response = $cram->getResponse($user, $pass, $challenge);
        if (is_a($response, 'PEAR_Error')) {
            throw new Exception($response);
        }
        $this->_sendStringResponse(base64_encode($response));
    }

    /**
     * Authenticates the user using the DIGEST-MD5 method.
     *
     * @param string $user  The userid to authenticate as.
     * @param string $pass  The password to authenticate with.
     * @param string $euser The effective uid to authenticate as.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _authDigestMD5($user, $pass, $euser)
    {
        $challenge = $this->_doCmd('AUTHENTICATE "DIGEST-MD5"', true);
        $challenge = base64_decode(trim($challenge));
        $digest = Auth_SASL::factory('digestmd5');
        // @todo Really 'localhost'?
        $response = $digest->getResponse($user, $pass, $challenge, 'localhost', 'sieve', $euser);
        if (is_a($response, 'PEAR_Error')) {
            throw new Exception($response);
        }

        $this->_sendStringResponse(base64_encode($response));
        $this->_doCmd('', true);
        if ($this->_toUpper(substr($result, 0, 2)) == 'OK') {
            return;
        }

        /* We don't use the protocol's third step because SIEVE doesn't allow
         * subsequent authentication, so we just silently ignore it. */
        $this->_sendStringResponse('');
        $this->_doCmd();
    }

    /**
     * Authenticates the user using the EXTERNAL method.
     *
     * @param string $user  The userid to authenticate as.
     * @param string $pass  The password to authenticate with.
     * @param string $euser The effective uid to authenticate as.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _authEXTERNAL($user, $pass, $euser)
    {
        $cmd = sprintf(
            'AUTHENTICATE "EXTERNAL" "%s"',
            base64_encode(strlen($euser) ? $euser : $user)
        );
        return $this->_sendCmd($cmd);
    }

    /**
     * Removes a script from the server.
     *
     * @param string $scriptname Name of the script to delete.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _cmdDeleteScript($scriptname)
    {
        if (self::STATE_AUTHENTICATED != $this->_state) {
            throw new NotAuthenticated();
        }
        $this->_doCmd(sprintf('DELETESCRIPT %s', $this->_escape($scriptname)));
    }

    /**
     * Retrieves the contents of the named script.
     *
     * @param string $scriptname Name of the script to retrieve.
     *
     * @throws \Horde\ManageSieve\Exception
     * @return string  The script.
     */
    protected function _cmdGetScript($scriptname)
    {
        if (self::STATE_AUTHENTICATED != $this->_state) {
            throw new NotAuthenticated();
        }

        $res = $this->_doCmd(sprintf('GETSCRIPT %s', $this->_escape($scriptname)));

        return preg_replace('/^{[0-9]+}\r\n/', '', $res);
    }

    /**
     * Sets the active script, i.e. the one that gets run on new mail by the
     * server.
     *
     * @param string $scriptname The name of the script to mark as active.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _cmdSetActive($scriptname)
    {
        if (self::STATE_AUTHENTICATED != $this->_state) {
            throw new NotAuthenticated();
        }
        $this->_doCmd(sprintf('SETACTIVE %s', $this->_escape($scriptname)));
    }

    /**
     * Returns the list of scripts on the server.
     *
     * @throws \Horde\ManageSieve\Exception
     * @return array  An array with the list of scripts in the first element
     *                and the active script in the second element.
     */
    protected function _cmdListScripts()
    {
        if (self::STATE_AUTHENTICATED != $this->_state) {
            throw new NotAuthenticated();
        }

        $res = $this->_doCmd('LISTSCRIPTS');

        $scripts = array();
        $activescript = null;
        $res = explode("\r\n", $res);
        foreach ($res as $value) {
            if (preg_match('/^"(.*)"( ACTIVE)?$/i', $value, $matches)) {
                $script_name = stripslashes($matches[1]);
                $scripts[] = $script_name;
                if (!empty($matches[2])) {
                    $activescript = $script_name;
                }
            }
        }

        return array($scripts, $activescript);
    }

    /**
     * Adds a script to the server.
     *
     * @param string $scriptname Name of the new script.
     * @param string $scriptdata The new script.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _cmdPutScript($scriptname, $scriptdata)
    {
        if (self::STATE_AUTHENTICATED != $this->_state) {
            throw new NotAuthenticated();
        }

        $command = sprintf(
            "PUTSCRIPT %s {%d+}\r\n%s",
            $this->_escape($scriptname),
            strlen($scriptdata),
            $scriptdata
        );

        $this->_doCmd($command);
    }

    /**
     * Logs out of the server and terminates the connection.
     *
     * @param boolean $sendLogoutCMD Whether to send LOGOUT command before
     *                               disconnecting.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _cmdLogout($sendLogoutCMD = true)
    {
        if (self::STATE_DISCONNECTED == $this->_state) {
            throw new NotConnected();
        }

        if ($sendLogoutCMD) {
            $this->_doCmd('LOGOUT');
        }

        $this->_sock->disconnect();
        $this->_state = self::STATE_DISCONNECTED;
    }

    /**
     * Sends the CAPABILITY command
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _cmdCapability()
    {
        if (self::STATE_DISCONNECTED == $this->_state) {
            throw new NotConnected();
        }
        $res = $this->_doCmd('CAPABILITY');
        $this->_parseCapability($res);
    }

    /**
     * Parses the response from the CAPABILITY command and stores the result
     * in $_capability.
     *
     * @param string $data The response from the capability command.
     */
    protected function _parseCapability($data)
    {
        // Clear the cached capabilities.
        $this->_capability = array('sasl' => array(),
                                   'extensions' => array());

        $data = preg_split('/\r?\n/', $this->_toUpper($data), -1, PREG_SPLIT_NO_EMPTY);

        for ($i = 0; $i < count($data); $i++) {
            if (!preg_match('/^"([A-Z]+)"( "(.*)")?$/', $data[$i], $matches)) {
                continue;
            }
            switch ($matches[1]) {
            case 'IMPLEMENTATION':
                $this->_capability['implementation'] = $matches[3];
                break;

            case 'SASL':
                $this->_capability['sasl'] = preg_split('/\s+/', $matches[3]);
                break;

            case 'SIEVE':
                $this->_capability['extensions'] = preg_split('/\s+/', $matches[3]);
                break;

            case 'STARTTLS':
                $this->_capability['starttls'] = true;
                break;
            }
        }
    }

    /**
     * Sends a command to the server
     *
     * @param string $cmd The command to send.
     */
    protected function _sendCmd($cmd)
    {
        $status = $this->_sock->getStatus();
        if (is_a($status, 'PEAR_Error') || $status['eof']) {
            throw new Exception('Failed to write to socket: connection lost');
        }
        $error = $this->_sock->write($cmd . "\r\n");
        if (is_a($error, 'PEAR_Error')) {
            throw new Exception(
                'Failed to write to socket: ' . $error->getMessage()
            );
        }
        $this->_debug("C: $cmd");
    }

    /**
     * Sends a string response to the server.
     *
     * @param string $str The string to send.
     */
    protected function _sendStringResponse($str)
    {
        return $this->_sendCmd('{' . strlen($str) . "+}\r\n" . $str);
    }

    /**
     * Receives a single line from the server.
     *
     * @return string  The server response line.
     */
    protected function _recvLn()
    {
        $lastline = $this->_sock->gets(8192);
        if (is_a($lastline, 'PEAR_Error')) {
            throw new Exception(
                'Failed to read from socket: ' . $lastline->getMessage()
            );
        }

        $lastline = rtrim($lastline);
        $this->_debug("S: $lastline");

        if ($lastline === '') {
            throw new Exception('Failed to read from socket');
        }

        return $lastline;
    }

    /**
     * Receives a number of bytes from the server.
     *
     * @param integer $length  Number of bytes to read.
     *
     * @return string  The server response.
     */
    protected function _recvBytes($length)
    {
        $response = '';
        $response_length = 0;
        while ($response_length < $length) {
            $response .= $this->_sock->read($length - $response_length);
            $response_length = strlen($response);
        }
        $this->_debug('S: ' . rtrim($response));
        return $response;
    }

    /**
     * Send a command and retrieves a response from the server.
     *
     * @param string $cmd   The command to send.
     * @param boolean $auth Whether this is an authentication command.
     *
     * @throws \Horde\ManageSieve\Exception if a NO response.
     * @return string  Reponse string if an OK response.
     *                            
     */
    protected function _doCmd($cmd = '', $auth = false)
    {
        $referralCount = 0;
        while ($referralCount < $this->_maxReferralCount) {
            if (strlen($cmd)) {
                $this->_sendCmd($cmd);
            }

            $response = '';
            while (true) {
                $line = $this->_recvLn();

                if (preg_match('/^(OK|NO)/i', $line, $tag)) {
                    // Check for string literal message.
                    if (preg_match('/{([0-9]+)}$/', $line, $matches)) {
                        $line = substr($line, 0, -(strlen($matches[1]) + 2))
                            . str_replace(
                                "\r\n", ' ', $this->_recvBytes($matches[1] + 2)
                            );
                    }

                    if ('OK' == $this->_toUpper($tag[1])) {
                        $response .= $line;
                        return rtrim($response);
                    }

                    throw new Exception(trim($response . substr($line, 2)), 3);
                }

                if (preg_match('/^BYE/i', $line)) {
                    try {
                        $this->disconnect(false);
                    } catch (Exception $e) {
                        throw new Exception(
                            'Cannot handle BYE, the error was: '
                            . $e->getMessage(),
                            4
                        );
                    }
                    // Check for referral, then follow it.  Otherwise, carp an
                    // error.
                    if (preg_match('/^bye \(referral "(sieve:\/\/)?([^"]+)/i', $line, $matches)) {
                        // Replace the old host with the referral host
                        // preserving any protocol prefix.
                        $this->_data['host'] = preg_replace(
                            '/\w+(?!(\w|\:\/\/)).*/', $matches[2],
                            $this->_data['host']
                        );
                        try {
                            $this->_handleConnectAndLogin();
                        } catch (Exception $e) {
                            throw new Referral(
                                'Cannot follow referral to '
                                . $this->_data['host'] . ', the error was: '
                                . $e->getMessage()
                            );
                        }
                        break;
                    }
                    throw new Exception(trim($response . $line), 6);
                }

                if (preg_match('/^{([0-9]+)}/', $line, $matches)) {
                    // Matches literal string responses.
                    $line = $this->_recvBytes($matches[1] + 2);
                    if (!$auth) {
                        // Receive the pending OK only if we aren't
                        // authenticating since string responses during
                        // authentication don't need an OK.
                        $this->_recvLn();
                    }
                    return $line;
                }

                if ($auth) {
                    // String responses during authentication don't need an
                    // OK.
                    $response .= $line;
                    return rtrim($response);
                }

                $response .= $line . "\r\n";
                $referralCount++;
            }
        }

        throw new Referral('Max referral count (' . $referralCount . ') reached.');
    }

    /**
     * Returns the name of the best authentication method that the server
     * has advertised.
     *
     * @param string $userMethod Only consider this method as available.
     *
     * @throws \Horde\ManageSieve\Exception
     * @return string  The name of the best supported authentication method.
     */
    protected function _getBestAuthMethod($userMethod = null)
    {
        if (!isset($this->_capability['sasl'])) {
            throw new Exception('This server doesn\'t support any authentication methods. SASL problem?');
        }
        if (!$this->_capability['sasl']) {
            throw new Exception('This server doesn\'t support any authentication methods.');
        }

        if ($userMethod) {
            if (in_array($userMethod, $this->_capability['sasl'])) {
                return $userMethod;
            }
            throw new Exception(
                sprintf('No supported authentication method found. The server supports these methods: %s, but we want to use: %s',
                        implode(', ', $this->_capability['sasl']),
                        $userMethod));
        }

        foreach ($this->supportedAuthMethods as $method) {
            if (in_array($method, $this->_capability['sasl'])) {
                return $method;
            }
        }

        throw new Exception(
            sprintf('No supported authentication method found. The server supports these methods: %s, but we only support: %s',
                    implode(', ', $this->_capability['sasl']),
                    implode(', ', $this->supportedAuthMethods)));
    }

    /**
     * Starts a TLS connection.
     *
     * @throws \Horde\ManageSieve\Exception
     */
    protected function _startTLS()
    {
        $this->_doCmd('STARTTLS');
        if (!stream_socket_enable_crypto($this->_sock->fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception('Failed to establish TLS connection', 2);
        }

        $this->_debug('STARTTLS negotiation successful');

        // The server should be sending a CAPABILITY response after
        // negotiating TLS. Read it, and ignore if it doesn't.
        // Unfortunately old Cyrus versions are broken and don't send a
        // CAPABILITY response, thus we would wait here forever. Parse the
        // Cyrus version and work around this broken behavior.
        if (!preg_match('/^CYRUS TIMSIEVED V([0-9.]+)/', $this->_capability['implementation'], $matches) ||
            version_compare($matches[1], '2.3.10', '>=')) {
            $this->_doCmd();
        }

        // Query the server capabilities again now that we are under
        // encryption.
        try {
            $this->_cmdCapability();
        } catch (Exception $e) {
            throw new ConnectionFailed($e);
        }
    }

    /**
     * Locale independant strtoupper() implementation.
     *
     * @param string $string The string to convert to lowercase.
     *
     * @return string  The lowercased string, based on ASCII encoding.
     */
    protected function _toUpper($string)
    {
        $language = setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, 'C');
        $string = strtoupper($string);
        setlocale(LC_CTYPE, $language);
        return $string;
    }

    /**
     * Converts strings into RFC's quoted-string or literal-c2s form.
     *
     * @param string $string  The string to convert.
     *
     * @return string  Result string.
     */
    protected function _escape($string)
    {
        // Some implementations don't allow UTF-8 characters in quoted-string,
        // use literal-c2s.
        if (preg_match('/[^\x01-\x09\x0B-\x0C\x0E-\x7F]/', $string)) {
            return sprintf("{%d+}\r\n%s", strlen($string), $string);
        }

        return '"' . addcslashes($string, '\\"') . '"';
    }

    /**
     * Write debug text to the current debug output handler.
     *
     * @param string $message Debug message text.
     */
    protected function _debug($message)
    {
        if ($this->_debug) {
            if ($this->_debug_handler) {
                call_user_func_array($this->_debug_handler, array($this, $message));
            } else {
                echo "$message\n";
            }
        }
    }
}

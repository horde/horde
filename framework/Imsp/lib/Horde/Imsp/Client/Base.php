<?php
/**
 * The Horde_Imsp_Client base class.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Imsp
 */
abstract class Horde_Imsp_Client_Base
{

    const OCTET_COUNT = '/({)([0-9]{1,})(\}$)/';
    const MUST_USE_LITERAL = '/[\x80-\xFF\\r\\n\"\\\\]/';
    const MUST_QUOTE = '/[\W]/i';

    /**
     * String containing name/IP address of IMSP host.
     *
     * @var string
     */
    public $host = 'localhost';

    /**
     * String containing port for IMSP server.
     *
     * @var string
     */
    public $port = '406';

    /**
     * String buffer containing the last raw NO or BAD response from the
     * server.
     *
     * @var string
     */
    public $lastRawError;

    /**
     * Current command prefix
     *
     * @var string
     */
    protected $_commandPrefix = 'A';

    /**
     * Current command count
     *
     * @var integer
     */
    protected $_commandCount = 1;

    /**
     * Currently in-use command tag
     *
     * @var string
     */
    protected $_tag;

    /**
     * Command tag last used.
     *
     * @var string
     */
    protected $_lastCommandTag = 'undefined';

    /**
     * Logger
     *
     * @var Horde_Log_Logger
     */
    public $_logger;

    /**
     * The auth object
     *
     * @var Horde_Imsp_Auth
     */
    protected $_authObj;

    /**
     * Constructor function.
     * Required parameters:
     *<pre>
     *  authObj  <Horde_Imsp_Auth>  The object to handle the authentication
     *</pre>
     *
     * Optional parameters:
     *<pre>
     *  server   <string>           The IMSP host
     *  port     <string>           The port the IMSP server listens on
     *  logger  <Horde_Log_Logger>  The logger.
     *</pre>
     * @param array $params Hash containing server parameters.
     */
    public function __construct(array $params)
    {
        if (empty($params['authObj'])) {
            throw new InvalidArgumentException('Missing required AuthObj');
        }
        $this->_authObj = $params['authObj'];
        if (!empty($params['server'])) {
            $this->host = $params['server'];
        }
        if (!empty($params['port'])) {
            $this->port = $params['port'];
        }
        if (!empty($params['logger'])) {
            $this->_logger = $params['logger'];
        } else {
            $this->_logger = new Horde_Support_Stub();
        }
    }

    /**
     * Determines if a string needs to be quoted before sending to the server.
     *
     * @param string $string  String to be tested.
     *
     * @return string Original string, quoted if needed.
     */
    static public function quoteSpacedString($string)
    {
        if (strpos($string, ' ') !== false ||
            preg_match(Horde_Imsp::MUST_QUOTE, $string)) {
            return '"' . $string . '"';
        } else {
            return $string;
        }
    }

    /**
     * Increments the IMSP command tag token.
     *
     * @return string Next command tag.
     */
    protected function _getNextCommandTag()
    {
        $this->_lastCommandTag = $this->_tag ? $this->_tag : 'undefined';
        return $this->_commandPrefix . sprintf('%04d', $this->_commandCount++);
    }

    /**
     * Close connection and logout from IMSP server.
     */
    abstract public function logout();

    /**
     * Returns the raw capability response from the server.
     *
     * @return string  The raw capability response.
     * @throws Horde_Imsp_Exception
     */
    abstract public function capability();


    /**
     * Attempts to send a command to the server.
     *
     * @param string  $commandText   Text to send to the server.
     * @param boolean $includeTag    Determines if command tag is prepended.
     * @param boolean  $sendCRLF     Determines if CRLF is appended.
     * @param boolean $continuation  Expect a command continuation response.
     *
     * @throws Horde_Imsp_Exception
     */
    abstract public function send($commandText, $includeTag = true, $sendCRLF = true, $continuation = false);

    /**
     * Receives a single CRLF terminated server response string
     *
     * @return mixed 'NO', 'BAD', 'OK', raw response.
     * @throws Horde_Imsp_Exception
     */
    abstract public function receive();

    /**
     * Retrieves CRLF terminated response from server and splits it into
     * an array delimited by a <space>.
     *
     * @return array The exploded string
     */
    abstract public function getServerResponseChunks();

    /**
     * Receives fixed number of bytes from IMSP socket. Used when server returns
     * a string literal.
     *
     * @param integer $length  Number of bytes to read from socket.
     *
     * @return string  Text of string literal.
     */
    abstract public function receiveStringLiteral($length);

    /**
     * Attempts to login to IMSP server.
     *
     * @param boolean $login   Should we remain logged in after auth?
     *
     * @return boolean
     */
    abstract public function authenticate($login = true);

}
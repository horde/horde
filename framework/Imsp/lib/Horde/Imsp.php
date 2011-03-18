<?php
/**
 * The Horde_Imsp class provides a common interface to an IMSP server .
 *
 * Required parameters:<pre>
 *   'server'  Hostname of IMSP server.
 *   'port'    Port of IMSP server.</pre>
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Imsp
 */
class Horde_Imsp
{
    const OCTET_COUNT = '/({)([0-9]{1,})(\}$)/';
    const MUST_USE_LITERAL = '/[\x80-\xFF\\r\\n\"\\\\]/';
    const MUST_QUOTE = '/[\W]/i';

    /**
     * String containing name/IP address of IMSP host.
     *
     * @var string
     */
    public $imsp_server = 'localhost';

    /**
     * String containing port for IMSP server.
     *
     * @var string
     */
    public $imsp_port = '406';

    /**
     * String buffer containing the last raw NO or BAD response from the
     * server.
     *
     * @var string
     */
    public $lastRawError;

    protected $_commandPrefix = 'A';
    protected $_commandCount = 1;
    protected $_tag;
    protected $_stream;
    protected $_lastCommandTag = 'undefined';

    /**
     *
     * @var Horde_Log_Logger
     */
    public $_logger;

    /**
     * Constructor function.
     *
     * @param array $params Hash containing server parameters.
     */
    public function __construct(array $params)
    {
        if (!empty($params['server'])) {
            $this->imsp_server = $params['server'];
        }
        if (!empty($params['port'])) {
            $this->imsp_port = $params['port'];
        }
        if (!empty($params['logger'])) {
            $this->_logger = $params['logger'];
        } else {
            $this->_logger = new Horde_Support_Stub();
        }

        $this->_imspOpen();
        $this->_logger->debug('Initializing Horde_Imsp object.');
    }

    /**
     * Logs out of the server and closes the IMSP stream
     */
    public function logout()
    {
        $this->_logger->debug('Closing IMSP Connection.');
        $command_string = 'LOGOUT';
        $this->imspSend($command_string);
        fclose($this->_stream);
    }

    /**
     * Returns the raw capability response from the server.
     *
     * @return string  The raw capability response.
     * @throws Horde_Imsp_Exception
     */
    public function capability()
    {
        $command_string = 'CAPABILITY';
        $this->imspSend($command_string);
        $server_response = $this->imspReceive();
        if (preg_match("/^\* CAPABILITY/", $server_response)) {
            $capability = preg_replace("/^\* CAPABILITY/", '', $server_response);
            $server_response = $this->imspReceive(); //OK
            if (!$server_response == 'OK') {
                $this->_logger->err('Did not receive the expected response from the server.');
                throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
            } else {
                $this->_logger->debug('CAPABILITY completed OK');
                return $capability;
            }
        }
    }

    /**
     * Attempts to open an IMSP socket with the server.
     *
     * @throws Horde_Imsp_Exception
     */
    protected function _imspOpen()
    {
        $fp = @fsockopen($this->imsp_server, $this->imsp_port);
        if (!$fp) {
            $this->_logger->err('Connection to IMSP host failed.');
            throw new Horde_Imsp_Exception('Connection to IMSP host failed.');
        }
        $this->_stream = $fp;
        $server_response = $this->imspReceive();
        if (!preg_match("/^\* OK/", $server_response)) {
            fclose($fp);
            $this->_logger->err('Did not receive the expected response from the server.');
        }
    }

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
    public function imspSend($commandText, $includeTag = true, $sendCRLF = true, $continuation = false)
    {
        $command_text = '';

        if (!$this->_stream) {
            throw new Horde_Imsp_Exception('No IMSP connection in place');
        }

        if ($includeTag) {
            $this->_tag = $this->_getNextCommandTag();
            $command_text = "$this->_tag ";
        }
        $command_text .= $commandText;

        if ($sendCRLF) {
            $command_text .= "\r\n";
        }

        $this->_logger->debug('C: ' . $command_text);

        if (!fputs($this->_stream, $command_text)) {
            $this->_logger->err('Connection to IMSP host failed.');
            fclose($this->_stream);
            throw new Horde_Imsp_Exception('Connection to IMSP host failed');
        }

        if ($continuation && !preg_match("/^\+/", $this->imspReceive())) {
            $this->_logger->err('Did not receive expected command continuation response from IMSP server.');
            throw new Horde_Imsp_Exception('Did not receive expected command continuation response from IMSP server.');
        }
    }

    /**
     * Receives a single CRLF terminated server response string
     *
     * @return mixed 'NO', 'BAD', 'OK', raw response.
     * @throws Horde_Imsp_Exception
     */
    public function imspReceive()
    {
        if (!$this->_stream) {
            throw new Horde_Imsp_Exception('No IMSP connection in place.');
        }
        $result = fgets($this->_stream, 512);
        if (!$result) {
            $this->_logger->err('Did not receive the expected response from the server.');
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server.');
        }
        $meta = stream_get_meta_data($this->_stream);
        if ($meta['timed_out']) {
            $this->_logger->err('Connection timed out.');
            throw new Horde_Imsp_Exception(Horde_Imsp_Translation::t('Connection timed out!'));
        }

        $server_response = trim($result);
        $this->_logger->debug('S: ' . $server_response);

        /* Parse out the response:
         * First make sure that this is not for a previous command.
         * If it is, it means we did not read all the server responses from
         * the last command...read them now, but throw an error. */
        while (preg_match("/^" . $this->_lastCommandTag . "/", $server_response)) {
            $server_response = trim(fgets($this->_stream, 512));
            throw new Horde_Imsp_Exception('Did not receive the expected response from the server: ' . $server_response);
        }

        $currentTag = $this->_tag;
        if (preg_match("/^" . $currentTag . " NO/", $server_response)) {
            $this->lastRawError = $server_response;
            return 'NO';
        }

        if (preg_match("/^" . $currentTag . " BAD/", $server_response)) {
            $this->_logger->err('The IMSP server did not understand your request.');
            $this->lastRawError = $server_response;
            return 'BAD';
        }

        if (preg_match("/^" . $currentTag . " OK/", $server_response)) {
            return 'OK';
        }

        /* If it was not a 'NO', 'BAD' or 'OK' response,
         * then it's up to the calling function to decide
         * what to do with it. */
        return $server_response;
    }

    /**
     * Retrieves CRLF terminated response from server and splits it into
     * an array delimited by a <space>.
     *
     * @return array The exploded string
     */
    public function getServerResponseChunks()
    {
        $server_response = trim(fgets($this->_stream, 512));
        $chunks = explode(' ', $server_response);

        return $chunks;
    }

    /**
     * Receives fixed number of bytes from IMSP socket. Used when server returns
     * a string literal.
     *
     * @param integer $length  Number of bytes to read from socket.
     *
     * @return string  Text of string literal.
     */
    public function receiveStringLiteral($length)
    {
        $literal = '';
        do {
            $temp = fread($this->_stream, $length);
            $length -= strlen($temp);
            $literal .= $temp;
        } while ($length > 0 && strlen($temp));
        $this->_logger->debug('From{}: ' . $literal);

        return $literal;
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
     * Determines if a string needs to be quoted before sending to the server.
     *
     * @param string $string  String to be tested.
     *
     * @return string Original string, quoted if needed.
     */
    function quoteSpacedString($string)
    {
        if (strpos($string, ' ') !== false ||
            preg_match(Horde_Imsp::MUST_QUOTE, $string)) {
            return '"' . $string . '"';
        } else {
            return $string;
        }
    }

    /**
     * Attempts to create a Horde_Imsp object based on $driver.
     * Must be called as $imsp = &Horde_Imsp::factory($driver, $params);
     *
     * @param  string $driver Type of Horde_Imsp object to return.
     * @param  mixed  $params  Any parameters needed by the Horde_Imsp object.
     *
     * @return mixed  The requested Horde_Imsp object.
     * @throws Horde_Exception
     */
    public function factory($driver, $params)
    {
        $driver = basename($driver);
        if (empty($driver) || $driver == 'none') {
            return new Horde_Imsp($params);
        }
        $class = 'Horde_Imsp_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

         throw new Horde_Exception(sprintf(Horde_Imsp_Translation::t("Unable to load the definition of %s."), $class));
    }

    /**
     * Attempts to return a Horde_Imsp object based on $driver.  Only
     * creates a new object if one with the same parameters already
     * doesn't exist.
     * Must be called as $imsp = &Horde_Imsp::singleton($driver, $params);
     *
     * @TODO: Move to injector factory
     * @param  string $driver Type of Horde_Imsp object to return.
     * @param  mixed  $params Any parameters needed by the Horde_Imsp object.
     * @return mixed  Reference to the Horde_Imsp object or PEAR_Error on failure.
     */
    public function &singleton($driver, $params)
    {
        static $instances = array();

        $signature = serialize(array($driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = self::factory($driver, $params);
        }

        return $instances[$signature];
    }

}

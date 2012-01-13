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
class Horde_Imsp_Client_Socket extends Horde_Imsp_Client_Base
{
    /**
     * Stream handle
     *
     * @var resource
     */
    protected $_stream;

    /**
     *
     * @var Horde_Imsp_Auth_Base
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
        parent::__construct($params);
        $this->_imspOpen();
        $this->_logger->debug('Initializing Horde_Imsp object.');
    }

    /**
     * Attempts to login to IMSP server.
     *
     * @param boolean $login   Should we remain logged in after auth?
     *
     * @return boolean
     */
    public function authenticate($login = true)
    {
        if (!$this->_authObj->authenticate($this, $login)) {
            return false;
        }

        return true;
    }

    /**
     * Logs out of the server and closes the IMSP stream
     */
    public function logout()
    {
        $this->_logger->debug('Closing IMSP Connection.');
        $command_string = 'LOGOUT';
        $this->send($command_string);
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
        $this->send($command_string);
        $server_response = $this->receive();
        if (preg_match("/^\* CAPABILITY/", $server_response)) {
            $capability = preg_replace("/^\* CAPABILITY/", '', $server_response);
            $server_response = $this->receive(); //OK
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
     * Attempts to send a command to the server.
     *
     * @param string  $commandText   Text to send to the server.
     * @param boolean $includeTag    Determines if command tag is prepended.
     * @param boolean  $sendCRLF     Determines if CRLF is appended.
     * @param boolean $continuation  Expect a command continuation response.
     *
     * @throws Horde_Imsp_Exception
     */
    public function send($commandText, $includeTag = true, $sendCRLF = true, $continuation = false)
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

        if ($continuation && !preg_match("/^\+/", $this->receive())) {
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
    public function receive()
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
     * Attempts to open an IMSP socket with the server.
     *
     * @throws Horde_Imsp_Exception
     */
    protected function _imspOpen()
    {
        $fp = @fsockopen($this->host, $this->port);
        if (!$fp) {
            $this->_logger->err('Connection to IMSP host failed.');
            throw new Horde_Imsp_Exception('Connection to IMSP host failed.');
        }
        $this->_stream = $fp;
        $server_response = $this->receive();
        if (!preg_match("/^\* OK/", $server_response)) {
            fclose($fp);
            $this->_logger->err('Did not receive the expected response from the server.');
        }
    }

}
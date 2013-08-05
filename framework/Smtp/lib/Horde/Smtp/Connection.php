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
 * PHP stream connection to a SMTP server.
 *
 * NOTE: This class is NOT intended to be accessed outside of the package.
 * There is NO guarantees that the API of this class will not change across
 * versions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 *
 * @property-read boolean $connected  Is there a connection to the server?
 * @property-read boolean $secure  Is the connection secure?
 */
class Horde_Smtp_Connection
{
    /**
     * Is there a connection to the server?
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * Debug object.
     *
     * @var object
     */
    protected $_debug;

    /**
     * Is the connection secure?
     *
     * @var boolean
     */
    protected $_secure = false;

    /**
     * Constructor.
     *
     * @param Horde_Smtp $client  The client object.
     * @param object $debug     The debug handler.
     *
     * @throws Horde_Smtp_Exception
     */
    public function __construct(Horde_Smtp $client, $debug)
    {
        if (($secure = $client->getParam('secure')) &&
            !extension_loaded('openssl')) {
            throw new InvalidArgumentException('Secure connections require the PHP openssl extension.');
        }

        $this->_debug = $debug;

        switch ($secure) {
        case 'ssl':
        case 'sslv2':
        case 'sslv3':
            $conn = $secure . '://';
            $this->_secure = true;
            break;

        case 'tls':
        default:
            $conn = 'tcp://';
            break;
        }

        $timeout = $client->getParam('timeout');

        $this->_stream = @stream_socket_client(
            $conn . $client->getParam('host') . ':' . $client->getParam('port'),
            $error_number,
            $error_string,
            $timeout
        );

        if ($this->_stream === false) {
            $e = new Horde_Smtp_Exception(
                Horde_Smtp_Translation::t("Error connecting to SMTP server."),
                Horde_Smtp_Exception::SERVER_CONNECT
            );
            $e->details = sprintf("[%u] %s", $error_number, $error_string);
            throw $e;
        }

        stream_set_timeout($this->_stream, $timeout);

        if (function_exists('stream_set_read_buffer')) {
            stream_set_read_buffer($this->_stream, 0);
        }
        stream_set_write_buffer($this->_stream, 0);

        $this->_connected = true;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'connected':
            return $this->_connected;

        case 'secure':
            return $this->_secure;
        }
    }

    /**
     * This object can not be cloned.
     */
    public function __clone()
    {
        throw new LogicException('Object cannot be cloned.');
    }

    /**
     * This object can not be serialized.
     */
    public function __sleep()
    {
        throw new LogicException('Object can not be serialized.');
    }

    /**
     * Start a TLS connection to the server.
     *
     * @return boolean  Whether TLS was successfully started.
     */
    public function startTls()
    {
        if ($this->connected &&
            !$this->secure &&
            (@stream_socket_enable_crypto($this->_stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) === true)) {
            $this->_secure = true;
            return true;
        }

        return false;
    }

    /**
     * Writes data to the output stream.
     *
     * @param string $data    String data.
     * @param boolean $debug  Output debug data?
     *
     * @throws Horde_Smtp_Exception
     */
    public function write($data, $debug = true)
    {
        if (fwrite($this->_stream, $data . "\r\n") === false) {
            throw new Horde_Smtp_Exception(
                Horde_Smtp_Translation::t("Server write error."),
                Horde_Smtp_Exception::SERVER_WRITEERROR
            );
        }

        if ($debug) {
            $this->_debug->client($data);
        }
    }

    /**
     * Read data from incoming stream.
     *
     * @return string  Line of data.
     *
     * @throws Horde_Smtp_Exception
     */
    public function read()
    {
        if (feof($this->_stream)) {
            $this->close();
            $this->_debug->info("ERROR: Server closed the connection.");
            throw new Horde_Smtp_Exception(
                Horde_Smtp_Translation::t("Server closed the connection unexpectedly."),
                Horde_Smtp_Exception::DISCONNECT
            );
        }

        if (($read = fgets($this->_stream)) === false) {
            $this->_debug->info("ERROR: Server read/timeout error.");
            throw new Horde_Smtp_Exception(
                Horde_Smtp_Translation::t("Error when communicating with the server."),
                Horde_Smtp_Exception::SERVER_READERROR
            );
        }

        $this->_debug->server(rtrim($read, "\r\n"));

        return $read;
    }

    /**
     * Close the connection to the server.
     */
    public function close()
    {
        if ($this->connected) {
            @fclose($this->_stream);
            $this->_connected = $this->_secure = false;
            $this->_stream = null;
        }
    }

}

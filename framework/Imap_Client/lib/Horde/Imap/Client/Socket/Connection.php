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
 * @package   Imap_Client
 */

/**
 * PHP stream connection to a server.
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
 * @package   Imap_Client
 */
class Horde_Imap_Client_Socket_Connection
extends Horde_Imap_Client_Base_Connection
{
    /**
     * Sending buffer.
     *
     * @var string
     */
    protected $_buffer = '';

    /**
     * The stream connection to the IMAP server.
     *
     * @var resource
     */
    protected $_stream = null;

    /**
     * Constructor.
     *
     * @param Horde_Imap_Client_Base $base  The base client object.
     * @param object $debug                 The debug handler.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function __construct(Horde_Imap_Client_Base $base, $debug)
    {
        parent::__construct($base, $debug);

        switch ($secure = $base->getParam('secure')) {
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

        $timeout = $base->getParam('timeout');

        $this->_stream = @stream_socket_client(
            $conn . $base->getParam('hostspec') . ':' . $base->getParam('port'),
            $error_number,
            $error_string,
            $timeout
        );

        if ($this->_stream === false) {
            $e = new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Error connecting to mail server."),
                Horde_Imap_Client_Exception::SERVER_CONNECT
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

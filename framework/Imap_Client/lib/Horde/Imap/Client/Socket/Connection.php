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
 * PHP stream connection to the IMAP server.
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

    /**
     * Writes data to the IMAP output stream.
     *
     * @param string $data  String data.
     * @param boolean $eol  Append EOL?
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function write($data, $eol = false)
    {
        if ($eol) {
            $buffer = $this->_buffer;
            $this->_buffer = '';

            if (fwrite($this->_stream, $buffer . $data . ($eol ? "\r\n" : '')) === false) {
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Server write error."),
                    Horde_Imap_Client_Exception::SERVER_WRITEERROR
                );
            }

            $this->_debug->client($buffer . $data);
        } else {
            $this->_buffer .= $data;
        }
    }

    /**
     * Writes literal data to the IMAP output stream.
     *
     * @param mixed $data      Either a stream resource, or Horde_Stream
     *                         object.
     * @param integer $length  The literal length.
     * @param boolean $binary  If true, this is binary data.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function writeLiteral($data, $length, $binary = false)
    {
        $this->_buffer = '';

        if ($data instanceof Horde_Stream) {
            $data = $data->stream;
        }

        rewind($data);
        while (!feof($data)) {
            if (fwrite($this->_stream, fread($data, 8192)) === false) {
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Server write error."),
                    Horde_Imap_Client_Exception::SERVER_WRITEERROR
                );
            }
        }

        if ($this->_debugliteral) {
            rewind($data);
            while (!feof($data)) {
                $this->_debug->raw(fread($data, 8192));
            }
        } else {
            $this->_debug->client('[' . ($binary ? 'BINARY' : 'LITERAL') . ' DATA: ' . $length . ' bytes]');
        }
    }

    /**
     * Read data from incoming IMAP stream.
     *
     * @return Horde_Imap_Client_Tokenize  The tokenized data.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function read()
    {
        $got_data = false;
        $literal_len = null;
        $token = new Horde_Imap_Client_Tokenize();

        do {
            if (feof($this->_stream)) {
                $this->close();
                $this->_debug->info("ERROR: Server closed the connection.");
                throw new Horde_Imap_Client_Exception(
                    Horde_Imap_Client_Translation::t("Mail server closed the connection unexpectedly."),
                    Horde_Imap_Client_Exception::DISCONNECT
                );
            }

            if (is_null($literal_len)) {
                $buffer = '';

                while (($in = fgets($this->_stream)) !== false) {
                    $got_data = true;

                    if (substr($in, -1) == "\n") {
                        $in = rtrim($in);
                        $this->_debug->server($buffer . $in);
                        $token->add($in);
                        break;
                    }

                    $buffer .= $in;
                    $token->add($in);
                }

                /* Check for literal data. */
                if (!is_null($len = $token->getLiteralLength())) {
                    if ($len['length']) {
                        $binary = $len['binary'];
                        $literal_len = $len['length'];
                    } else {
                        // Skip 0-length literal data.
                        $literal_len = null;
                    }
                    continue;
                }
                break;
            }

            $old_len = $literal_len;

            while ($literal_len && !feof($this->_stream)) {
                $in = fread($this->_stream, min($literal_len, 8192));
                $token->add($in);
                if ($this->_debugliteral) {
                    $this->_debug->raw($in);
                }

                $got_data = true;

                $in_len = strlen($in);
                if ($in_len > $literal_len) {
                    break;
                }
                $literal_len -= $in_len;
            }

            $literal_len = null;

            if (!$this->_debugliteral) {
                $this->_debug->server('[' . ($binary ? 'BINARY' : 'LITERAL') . ' DATA: ' . $old_len . ' bytes]');
            }
        } while (true);

        if (!$got_data) {
            $this->_debug->info("ERROR: IMAP read/timeout error.");
            throw new Horde_Imap_Client_Exception(
                Horde_Imap_Client_Translation::t("Error when communicating with the mail server."),
                Horde_Imap_Client_Exception::SERVER_READERROR
            );
        }

        return $token;
    }

}

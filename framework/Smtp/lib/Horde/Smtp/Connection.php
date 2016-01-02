<?php
/**
 * Copyright 2013-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2016 Horde LLC
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
 * @copyright 2013-2016 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Smtp
 */
class Horde_Smtp_Connection extends Horde\Socket\Client
{
    /**
     * Writes data to the output stream.
     *
     * @param mixed $data  String data (or array of string data), or a
     *                     resource.
     * @param mixed $size  If set, the maximum number of octets to send.
     *
     * @throws Horde_Smtp_Exception
     */
    public function write($data, $size = null)
    {
        if (is_resource($data)) {
            $this->_params['debug']->client('', false);

            while (!feof($data) && (is_null($size) || ($size > 0))) {
                if (is_null($size)) {
                    $c_size = 65536;
                } else {
                    $c_size = min($size, 65536);
                    $size -= $c_size;
                }

                $chunk = fread($data, $c_size);
                $this->_params['debug']->raw($chunk);

                try {
                    $this->_write($chunk);
                } catch (Horde_Smtp_Exception $e) {
                    $this->_params['debug']->raw("\n");
                    throw $e;
                }
            }

            $this->_write("\r\n");
            $this->_params['debug']->raw("\n");
        } else {
            if (!is_array($data)) {
                $data = array($data);
            }

            foreach ($data as $val) {
                $this->_params['debug']->client($val);
            }

            $chunk = implode("\r\n", $data);
            if (!is_null($size)) {
                $chunk = substr($chunk, 0, $size);
            }

            $this->_write($chunk . "\r\n");
        }
    }

    /**
     * Writes data to the output stream.
     *
     * @param string $data  String data.
     *
     * @throws Horde_Smtp_Exception
     */
    protected function _write($data)
    {
        if (fwrite($this->_stream, $data) === false) {
            throw new Horde_Smtp_Exception(
                Horde_Smtp_Translation::r("Server write error."),
                Horde_Smtp_Exception::SERVER_WRITEERROR
            );
        }
    }

    /**
     * Read data from incoming stream.
     *
     * @param integer $size  UNUSED: The number of bytes to read from the
     *                       socket.
     *
     * @return string  Line of data.
     *
     * @throws Horde_Smtp_Exception
     */
    public function read($size = null)
    {
        if (feof($this->_stream)) {
            $this->close();
            $this->_params['debug']->info("ERROR: Server closed the connection.");
            throw new Horde_Smtp_Exception(
                Horde_Smtp_Translation::r("Server closed the connection unexpectedly."),
                Horde_Smtp_Exception::DISCONNECT
            );
        }

        if (($read = fgets($this->_stream)) === false) {
            $this->_params['debug']->info("ERROR: Server read/timeout error.");
            throw new Horde_Smtp_Exception(
                Horde_Smtp_Translation::r("Error when communicating with the server."),
                Horde_Smtp_Exception::SERVER_READERROR
            );
        }

        $this->_params['debug']->server(rtrim($read, "\r\n"));

        return $read;
    }

}

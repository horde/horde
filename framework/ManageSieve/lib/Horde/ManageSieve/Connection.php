<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @package   ManageSieve
 * @author    Jan Schneider <jan@horde.org>
 * @license   http://www.horde.org/licenses/bsd BSD
 */

namespace Horde\ManageSieve;
use Horde\Socket\Client;
use Horde\ManageSieve\Exception;

/**
 * This class extends \Horde\Socket\Client for usage in \Horde\ManageSieve
 *
 * @package   ManageSieve
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 */
class Connection extends Client
{
    /**
     * Returns information about the socket resource.
     *
     * Currently returns four entries in the result array:
     *  - timed_out (bool): The socket timed out waiting for data
     *  - blocked (bool): The socket was blocked
     *  - eof (bool): Indicates EOF event
     *  - unread_bytes (int): Number of bytes left in the socket buffer
     *
     * @throws Horde\ManageSieve\Exception
     * @return array  Information about existing socket resource.
     */
    public function getStatus()
    {
        $this->_checkStream();
        return stream_get_meta_data($this->_stream);
    }

    /**
     * Returns a line of data.
     *
     * @param int $size  Reading ends when $size - 1 bytes have been read,
     *                   or a newline or an EOF (whichever comes first).
     *
     * @throws Horde\ManageSieve\Exception
     * @return string  $size bytes of data from the socket
     */
    public function gets($size)
    {
        $this->_checkStream();
        $data = @fgets($this->_stream, $size);
        if ($data === false) {
            throw new Horde\ManageSieve\Exception('Error reading data from socket');
        }
        return $data;
    }

    /**
     * Returns a specified amount of data.
     *
     * @param integer $size  The number of bytes to read from the socket.
     *
     * @throws Horde\ManageSieve\Exception
     * @return string  $size bytes of data from the socket.
     */
    public function read($size)
    {
        $this->_checkStream();
        $data = @fread($this->_stream, $size);
        if ($data === false) {
            throw new Horde\ManageSieve\Exception('Error reading data from socket');
        }
        return $data;
    }

    /**
     * Writes data to the stream.
     *
     * @param string $data  Data to write.
     *
     * @throws Horde\ManageSieve\Exception
     */
    public function write($data)
    {
        $this->_checkStream();
        if (!@fwrite($this->_stream, $data)) {
            $meta_data = $this->getStatus();
            if (!empty($meta_data['timed_out'])) {
                throw new Horde\ManageSieve\Exception('Timed out writing data to socket');
            }
            throw new Horde\ManageSieve\Exception('Error writing data to socket');
        }
    }

    /**
     * Throws an exception is the stream is not a resource.
     *
     * @throws Horde\ManageSieve\Exception
     */
    protected function _checkStream()
    {
        if (!is_resource($this->_stream)) {
            throw new Horde\ManageSieve\Exception('Not connected');
        }
    }
}

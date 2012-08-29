<?php
/**
 * This object allows easy access to parsing mbox data (RFC 4155).
 *
 * See:
 * http://homepage.ntlworld.com./jonathan.deboynepollard/FGA/mail-mbox-formats
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mbox_Parse implements ArrayAccess, Countable, Iterator
{
    /**
     * Data stream.
     *
     * @var resource
     */
    protected $_data;

    /**
     * Parsed boundaries.
     *
     * @var array
     */
    protected $_parsed;

    /**
     * Constructor.
     *
     * @param mixed $data  The mbox data. Either a resource or a filename
     *                     as interpreted by fopen() (string).
     *
     * @throws IMP_Exception
     */
    public function __construct($data)
    {
        $this->_data = is_resource($data)
            ? $data
            : @fopen($data, 'r');

        if ($this->_data === false) {
            throw new IMP_Exception('Could not open mbox data.');
        }

        $this->_init();
    }

    /**
     */
    protected function _init()
    {
        if (!isset($this->_parsed)) {
            $this->_parsed = array();
            rewind($this->_data);

            $curr = $last_line = null;

            while (!feof($this->_data)) {
                $line = fgets($this->_data);

                if (strpos($line, 'From ') === 0) {
                    if (is_null($curr) || (trim($last_line) == '')) {
                        $this->_parsed[] = ftell($this->_data);
                    }
                }

                $last_line = $line;
            }
        }
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        return isset($this->_parsed[$offset]);
    }

    /**
     */
    public function offsetGet($offset)
    {
        if (isset($this->_parsed[$offset])) {
            $end = isset($this->_parsed[$offset + 1])
                ? $this->_parsed[$offset + 1]
                : null;
            $fd = fopen('php://temp', 'w+');

            fseek($this->_data, $this->_parsed[$offset]);
            while (!feof($this->_data)) {
                $line = fgets($this->_data);
                if (ftell($this->_data) == $end) {
                    break;
                }

                if (strpos($line, '>From ') === 0) {
                    fwrite($fd, substr($line, 1));
                } else {
                    fwrite($fd, $line);
                }
            }

            rewind($fd);

            return $fd;
        }

        if (($offset == 0) && !count($this)) {
            $fd = fopen('php://temp', 'w+');
            rewind($this->_data);
            stream_copy_to_stream($this->_data, $fd);
            rewind($fd);

            return $fd;
        }

        return null;
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        // NOOP
    }

    /**
     */
    public function offsetUnset($offset)
    {
        // NOOP
    }

    /* Countable methods. */

    /**
     * Index count.
     *
     * @return integer  The number of messages.
     */
    public function count()
    {
        return count($this->_parsed);
    }

    /* Magic methods. */

    /**
     * String representation of the object.
     *
     * @return string  String representation.
     */
    public function __toString()
    {
        rewind($this->_data);
        return stream_get_contents($this->_data);
    }

    /* Iterator methods. */

    public function current()
    {
        $key = $this->key();

        return is_null($key)
            ? null
            : $this[$key];
    }

    public function key()
    {
        return key($this->_parsed);
    }

    public function next()
    {
        if ($this->valid()) {
            next($this->_parsed);
        }
    }

    public function rewind()
    {
        reset($this->_parsed);
    }

    public function valid()
    {
        return !is_null($this->key());
    }

}

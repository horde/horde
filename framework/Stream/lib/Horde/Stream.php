<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Stream
 */

/**
 * Object that adds convenience/utility methods to interacting with PHP
 * streams.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Stream
 */
class Horde_Stream implements Serializable
{
    /**
     * Stream resource.
     *
     * @deprecated  Will be removed in 2.0.0
     *
     * @var resource
     */
    public $stream;

    /**
     * Parse character as UTF-8 instead of single byte.
     *
     * @since 1.4.0
     *
     * @var boolean
     */
    public $utf8_char = false;

    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor.
     *
     * @param array $opts  Configuration options.
     */
    public function __construct(array $opts = array())
    {
        $this->_params = $opts;
        $this->_init();
    }

    /**
     * Initialization method.
     */
    protected function _init()
    {
        // Sane default: read-write, 0-length stream.
        if (!$this->stream) {
            $this->stream = @fopen('php://temp', 'r+');
        }
    }

    /**
     */
    public function __clone()
    {
        $data = strval($this);
        $this->stream = null;
        $this->_init();
        $this->add($data);
    }

    /**
     * String representation of object.
     *
     * @since 1.1.0
     *
     * @return string  The full stream converted to a string.
     */
    public function __toString()
    {
        return $this->getString(0);
    }

    /**
     * Adds data to the stream.
     *
     * @param mixed $data     Data to add to the stream. Can be a resource,
     *                        Horde_Stream object, or a string(-ish) value.
     * @param boolean $reset  Reset stream pointer to initial position after
     *                        adding?
     */
    public function add($data, $reset = false)
    {
        if ($reset) {
            $pos = $this->pos();
        }

        if (is_resource($data)) {
            $dpos = ftell($data);
            while (!feof($data)) {
                $this->add(fread($data, 8192));
            }
            fseek($data, $dpos);
        } elseif ($data instanceof Horde_Stream) {
            $dpos = $data->pos();
            while (!$data->eof()) {
                $this->add($data->getString(null, $data->pos() + 65536));
            }
            $data->seek($dpos, false);
        } else {
            fwrite($this->stream, $data);
        }

        if ($reset) {
            $this->seek($pos, false);
        }
    }

    /**
     * Returns the length of the data. Does not change the stream position.
     *
     * @param boolean $utf8  If true, determines the UTF-8 length of the
     *                       stream (as of 1.4.0). If false, determines the
     *                       byte length of the stream.
     *
     * @return integer  Stream size.
     *
     * @throws Horde_Stream_Exception
     */
    public function length($utf8 = false)
    {
        $pos = $this->pos();

        if ($utf8 && $this->utf8_char) {
            $this->rewind();
            $len = 0;
            while ($this->getChar() !== false) {
                ++$len;
            }
        } else {

            if (!$this->end()) {
                throw new Horde_Stream_Exception('ERROR');
            }

            $len = $this->pos();
        }

        if (!$this->seek($pos, false)) {
            throw new Horde_Stream_Exception('ERROR');
        }

        return $len;
    }

    /**
     * Get a string up to a certain character (or EOF).
     *
     * @param string $end  The character to stop reading at. As of 1.4.0,
     *                     $char can be a multi-character string.
     *
     * @return string  The string up to $end (stream is positioned after the
     *                 end character(s), all of which are stripped from the
                       return data).
     */
    public function getToChar($end)
    {
        $res = $this->search($end);

        if (is_null($res)) {
            return $this->getString();
        }

        $len = strlen($end);
        $out = substr($this->getString(null, $res + $len - 1), 0, $len * -1);

        /* Remove all further characters also. */
        while ($this->peek($len) == $end) {
            $this->seek($len);
        }

        return $out;
    }

    /**
     * Return the current character(s) without moving the pointer.
     *
     * @param integer $length  The peek length (since 1.4.0).
     *
     * @return string  The current character.
     */
    public function peek($length = 1)
    {
        $out = '';

        for ($i = 0; $i < $length; ++$i) {
            if (($c = $this->getChar()) === false) {
                break;
            }
            $out .= $c;
        }

        $this->seek(strlen($out) * -1);

        return $out;
    }

    /**
     * Search for character(s) and return its position.
     *
     * @param string $char      The character to search for. As of 1.4.0,
     *                          $char can be a multi-character string.
     * @param boolean $reverse  Do a reverse search?
     * @param boolean $reset    Reset the pointer to the original position?
     *
     * @return mixed  The start position of the search string (integer), or
     *                null if character not found.
     */
    public function search($char, $reverse = false, $reset = true)
    {
        $found_pos = null;

        if ($len = strlen($char)) {
            do {
                $pos = $this->pos();

                if ($reverse) {
                    for ($i = $pos - 1; $i >= 0; --$i) {
                        $this->seek($i, false);
                        $c = $this->peek();
                        if ($c == substr($char, 0, strlen($c))) {
                            $found_pos = $i;
                            break;
                        }
                    }
                } else {
                    while (($c = $this->getChar()) !== false) {
                        if ($c == substr($char, 0, strlen($c))) {
                            $found_pos = $this->pos() - strlen($c);
                            break;
                        }
                    }
                }

                if (is_null($found_pos) ||
                    ($len == 1) ||
                    ($this->getString($found_pos, $found_pos + $len - 1) == $char)) {
                    break;
                }

                $this->seek($found_pos + ($reverse ? 0 : 1), false);
                $found_pos = null;
            } while (true);

            $this->seek(
                ($reset || is_null($found_pos)) ? $pos : $found_pos,
                false
            );
        }

        return $found_pos;
    }

    /**
     * Returns the stream (or a portion of it) as a string. Position values
     * are the byte position in the stream.
     *
     * @param integer $start  The starting position. If positive, start from
     *                        this position. If negative, starts this length
     *                        back from the current position. If null, starts
     *                        from the current position.
     * @param integer $end    The ending position relative to the starting
     *                        position (if positive). If negative, end this
     *                        length back from the end of the stream. If null,
     *                        reads to the end of the stream.
     *
     * @return string  A string.
     */
    public function getString($start = null, $end = null)
    {
        if (!is_null($start)) {
            $this->seek($start, ($start < 0));
        }

        if (is_null($end)) {
            $len = 8192;
        } else {
            $end = ($end >= 0)
                ? $end - $this->pos() + 1
                : $this->length() - $this->pos() + $end;
            $len = max($end, 0);
        }

        $out = '';

        while (!feof($this->stream) && (is_null($end) || $len)) {
            $read = fread($this->stream, $len);
            $out .= $read;
            if (!is_null($end)) {
                $len -= strlen($read);
            }
        }

        return $out;
    }

    /**
     * Auto-determine the EOL string.
     *
     * @since 1.3.0
     *
     * @return string  The EOL string, or null if no EOL found.
     */
    public function getEOL()
    {
        $pos = $this->pos();

        $this->rewind();
        $pos2 = $this->search("\n", false, false);
        if ($pos2) {
            $this->seek(-1);
            $eol = (fgetc($this->stream) == "\r")
                ? "\r\n"
                : "\n";
        } else {
            $eol = is_null($pos2)
                ? null
                : "\n";
        }

        $this->seek($pos, false);

        return $eol;
    }

    /**
     * Return a character from the string.
     *
     * @since 1.4.0
     *
     * @return string  Character (single byte, or UTF-8 character if
     *                 $utf8_char is true).
     */
    public function getChar()
    {
        $char = fgetc($this->stream);
        if (!$this->utf8_char) {
            return $char;
        }

        $c = ord($char);
        if ($c < 0x80) {
            return $char;
        }

        if ($c < 0xe0) {
            $n = 1;
        } elseif ($c < 0xf0) {
            $n = 2;
        } elseif ($c < 0xf8) {
            $n = 3;
        } else {
            throw new Horde_Stream_Exception('ERROR');
        }

        for ($i = 0; $i < $n; ++$i) {
            if (($c = fgetc($this->stream)) === false) {
                throw new Horde_Stream_Exception('ERROR');
            }
            $char .= $c;
        }

        return $char;
    }

    /**
     * Return the current stream pointer position.
     *
     * @since 1.4.0
     *
     * @return mixed  The current position (integer), or false.
     */
    public function pos()
    {
        return ftell($this->stream);
    }

    /**
     * Rewind the internal stream to the beginning.
     *
     * @since 1.4.0
     *
     * @return boolean  True if successful.
     */
    public function rewind()
    {
        return rewind($this->stream);
    }

    /**
     * Move internal pointer.
     *
     * @since 1.4.0
     *
     * @param boolean $curr  If true, offset is from current position. If
     *                       false, offset is from beginning of stream.
     *
     * @return boolean  True if successful.
     */
    public function seek($offset = 0, $curr = true)
    {
        return (fseek($this->stream, $offset, $curr ? SEEK_CUR : SEEK_SET) === 0);
    }

    /**
     * Move internal pointer to the end of the stream.
     *
     * @since 1.4.0
     *
     * @param integer $offset  Move this offset from the end.
     *
     * @return boolean  True if successful.
     */
    public function end($offset = 0)
    {
        return (fseek($this->stream, $offset, SEEK_END) === 0);
    }

    /**
     * Has the end of the stream been reached?
     *
     * @since 1.4.0
     *
     * @return boolean  True if the end of the stream has been reached.
     */
    public function eof()
    {
        return feof($this->stream);
    }

    /**
     * Close the stream.
     *
     * @since 1.4.0
     */
    public function close()
    {
        if ($this->stream) {
            fclose($this->stream);
        }
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        $this->_params['_pos'] = $this->pos();

        return json_encode(array(
            strval($this),
            $this->_params
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_init();

        $data = json_decode($data, true);
        $this->add($data[0]);
        $this->seek($data[1]['_pos'], false);
        unset($data[1]['_pos']);
        $this->_params = $data[1];
    }

}

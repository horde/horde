<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Stream
 */

/**
 * Implementation of Horde_Stream that uses a PHP native string variable
 * for the internal storage.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Stream
 * @since     1.6.0
 */
class Horde_Stream_String extends Horde_Stream
{
    /**
     * Current string pointer.
     *
     * @var integer
     */
    protected $_ptr = 0;

    /**
     * String data.
     *
     * @var string
     */
    protected $_str = '';

    /**
     */
    protected function _init()
    {
        /* Don't initialize stream by default. */
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'stream':
            $stream = new Horde_Support_StringStream($this->_str);
            return $stream->fopen();
        }

        return parent::__get($name);
    }

    /**
     */
    public function __clone()
    {
        /* Overrides parent class. */
    }

    /**
     */
    public function __toString()
    {
        return $this->_str;
    }

    /**
     */
    public function add($data, $reset = false)
    {
        if (!is_string($data)) {
            return parent::add($data, $reset);
        }

        if ($this->_ptr === (strlen($this->_str) - 1)) {
            $this->_str .= $data;
        } else {
            $this->_str = substr_replace(
                $this->_str,
                $data,
                $this->_ptr,
                strlen($data)
            );
        }

        if (!$reset) {
            $this->_ptr += strlen($data);
        }
    }

    /**
     */
    public function length($utf8 = false)
    {
        return ($utf8 && $this->_utf8_char)
            ? Horde_String::length($this->_str)
            : strlen($this->_str);
    }

    /**
     */
    public function peek($length = 1)
    {
        return $this->_utf8_char
            ? Horde_String::substr($this->_str, $this->_posUtf8(), $length)
            : substr($this->_str, $this->_ptr, $length);
    }

    /**
     */
    public function search($char, $reverse = false, $reset = true)
    {
        $char = strval($char);
        if (!strlen($char)) {
            return null;
        }

        $pos = $this->pos();

        if ($this->_utf8_char) {
            $found_pos = $reverse
                ? Horde_String::rpos($this->_str, $char, $this->length(true) - $this->_posUtf8())
                : Horde_String::pos($this->_str, $char, $this->_posUtf8());
            if ($found_pos) {
                $found_pos = $this->_posUtf8($found_pos);
            }
        } else {
            $found_pos = $reverse
                ? strrpos($this->_str, $char, $this->length() - $pos)
                : strpos($this->_str, $char, $pos);
        }

        $this->seek(
            ($reset || ($found_pos === false)) ? $pos : $found_pos,
            false
        );

        return ($found_pos === false)
            ? null
            : $found_pos;
    }

    /**
     */
    public function substring($start = 0, $length = null, $char = false)
    {
        if ($start !== 0) {
            $this->seek($start, true, $char);
        }

        if ($this->eof()) {
            return '';
        }

        if (is_null($length)) {
            $out = substr($this->_str, $this->_ptr);
            $this->end();
            return $out;
        }

        $out = $char
            ? Horde_String::substr($this->_str, $this->_posUtf8(), $length)
            : substr($this->_str, $this->_ptr, $length);
        $this->seek(max($length, strlen($out)));

        return $out;
    }

    /**
     */
    public function getChar()
    {
        $char = $this->peek();

        if ($len = strlen($char)) {
            $this->seek($len);
        } else {
            $this->_ptr = false;
        }

        return $char;
    }

    /**
     */
    public function pos()
    {
        return $this->_ptr;
    }

    /**
     * Determines the current UTF-8 aware position in the stream.
     *
     * @param integer $utf8  If set, will convert from utf8 position to
     *                       byte position.
     *
     * @return integer  Position.
     */
    protected function _posUtf8($utf8 = null)
    {
        return is_null($utf8)
            ? Horde_String::length(substr($this->_str, 0, $this->_ptr))
            : strlen(Horde_String::substr($this->_str, 0, $utf8));
    }

    /**
     */
    public function rewind()
    {
        $this->_ptr = 0;
        return true;
    }

    /**
     */
    public function seek($offset = 0, $curr = true, $char = false)
    {
        if (!$offset) {
            return (bool)$curr ?: $this->rewind();
        }

        /* Optimizations if offset is negative. */
        if ($offset < 0) {
            if (!$curr) {
                return true;
            } elseif (abs($offset) > $this->_ptr) {
                return $this->rewind();
            }
        }

        if ($char) {
            if ($curr) {
                if ($offset > 0) {
                    $this->substring(0, $offset, true);
                } else {
                    while (--$this->_ptr && ($offset < 0)) {
                        $offset += strlen(Horde_String::substr($this->_str, $this->_ptr, 1));
                    }
                }
            } else {
                $this->_ptr = $this->_posUtf8($offset);
            }
        } elseif ($curr) {
            $this->_ptr += $offset;
        } else {
            $this->_ptr = $offset;
        }

        if ($this->_ptr < 0) {
            $this->_ptr = 0;
        } elseif ($this->_ptr > $this->length()) {
            $this->_ptr = false;
        }

        return true;
    }

    /**
     */
    public function end($offset = 0)
    {
        $this->_ptr = $this->length();
        if ($offset) {
            $this->seek($offset, true, $this->_utf8_char);
        }

        return true;
    }

    /**
     */
    public function eof()
    {
        return ($this->_ptr === false);
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return json_encode(array(
            $this->_ptr,
            $this->_str,
            $this->_params
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        $data = json_decode($data, true);
        $this->_ptr = $data[0];
        $this->_str = $data[1];
        $this->_params = $data[2];
    }

}

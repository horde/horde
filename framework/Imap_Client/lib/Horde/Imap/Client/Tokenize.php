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
 * @package   Imap_Client
 */

/**
 * Tokenization of an IMAP data stream.
 *
 * NOTE: This class is NOT intended to be accessed outside of this package.
 * There is NO guarantees that the API of this class will not change across
 * versions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 *
 * @property-read boolean $eos  Has the end of the stream been reached?
 */
class Horde_Imap_Client_Tokenize implements Iterator
{
    /**
     * Current data.
     *
     * @var mixed
     */
    protected $_current = false;

    /**
     * Current key.
     *
     * @var integer
     */
    protected $_key = false;

    /**
     * Sublevel.
     *
     * @var integer
     */
    protected $_level = false;

    /**
     * Data stream.
     *
     * @var Horde_Stream
     */
    protected $_stream;

    /**
     * Constructor.
     *
     * @param mixed $data  Data to add (string, resource, or Horde_Stream
     *                     object).
     */
    public function __construct($data = null)
    {
        $this->_stream = new Horde_Stream_Temp();

        if (!is_null($data)) {
            $this->add($data);
        }
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'eos':
            return feof($this->_stream->stream);
        }
    }

    /**
     */
    public function __sleep()
    {
        throw new LogicException('Object can not be serialized.');
    }

    /**
     */
    public function __toString()
    {
        $pos = ftell($this->_stream->stream);
        $out = $this->_current . ' ' . $this->_stream->getString();
        fseek($this->_stream->stream, $pos);
        return $out;
    }

    /**
     * Add data to buffer.
     *
     * @param mixed $data  Data to add (string, resource, or Horde_Stream
     *                     object).
     */
    public function add($data)
    {
        $this->_stream->add($data);
    }

    /**
     * Flush the remaining entries left in the iterator.
     *
     * @param boolean $return    If true, return entries. Only returns entries
     *                           on the current level.
     * @param boolean $sublevel  Only flush items in current sublevel?
     *
     * @return array  The entries if $return is true.
     */
    public function flushIterator($return = true, $sublevel = true)
    {
        $out = array();

        if ($return) {
            $level = $sublevel ? $this->_level : 0;
            do {
                $curr = $this->next();
                if ($this->_level < $level) {
                    break;
                }

                if (!is_bool($curr) && ($level === $this->_level)) {
                    $out[] = $curr;
                }
            } while (($curr !== false) || $this->_level || !$this->eos);
        } elseif ($sublevel && $this->_level) {
            $level = $this->_level;
            while ($level <= $this->_level) {
                $this->next();
            }
        } else {
            fseek($this->_stream->stream, 0, SEEK_END);
            fgetc($this->_stream->stream);
            $this->_current = $this->_key = $this->_level = false;
        }

        return $out;
    }

    /**
     * Return literal length data located at the end of the stream.
     *
     * @return mixed  Null if no literal data found, or an array with these
     *                keys:
     *   - binary: (boolean) True if this is a literal8.
     *   - length: (integer) Length of the literal.
     */
    public function getLiteralLength()
    {
        fseek($this->_stream->stream, -1, SEEK_END);
        if ($this->_stream->peek() === '}') {
            $literal_data = $this->_stream->getString($this->_stream->search('{', true) - 1);
            $literal_len = substr($literal_data, 2, -1);

            if (is_numeric($literal_len)) {
                return array(
                    'binary' => ($literal_data[0] === '~'),
                    'length' => intval($literal_len)
                );
            }
        }

        return null;
    }

    /* Iterator methods. */

    /**
     */
    public function current()
    {
        return $this->_current;
    }

    /**
     */
    public function key()
    {
        return $this->_key;
    }

    /**
     * @return mixed  Either a string, boolean (true for open paren, false for
     *                close paren/EOS), or null.
     */
    public function next()
    {
        if ((($this->_current = $this->_parseStream()) === false) &&
            $this->eos) {
            $this->_key = $this->_level = false;
        } else {
            ++$this->_key;
        }

        return $this->_current;
    }

    /**
     */
    public function rewind()
    {
        fseek($this->_stream->stream, 0);
        $this->_current = false;
        $this->_key = -1;
        $this->_level = 0;
    }

    /**
     */
    public function valid()
    {
        return ($this->_level !== false);
    }

    /**
     * Returns the next token and increments the internal stream pointer.
     *
     * @see next()
     */
    protected function _parseStream()
    {
        $in_quote = false;
        $stream = $this->_stream->stream;
        $text = '';

        while (($c = fgetc($stream)) !== false) {
            switch ($c) {
            case '\\':
                $text .= $in_quote
                    ? fgetc($stream)
                    : $c;
                break;

            case '"':
                if ($in_quote) {
                    return $text;
                } else {
                    $in_quote = true;
                }
                break;

            default:
                if ($in_quote) {
                    $text .= $c;
                    break;
                }

                switch ($c) {
                case '(':
                    ++$this->_level;
                    return true;

                case ')':
                    if (strlen($text)) {
                        fseek($stream, -1, SEEK_CUR);
                        break 3;
                    }
                    --$this->_level;
                    return false;

                case '~':
                    // Ignore binary string identifier. PHP strings are
                    // binary-safe.
                    break;

                case '{':
                    return stream_get_contents($stream, $this->_stream->getToChar('}'));

                case ' ':
                    if (strlen($text)) {
                        break 3;
                    }
                    break;

                default:
                    $text .= $c;
                    break;
                }
                break;
            }
        }

        switch (strlen($text)) {
        case 0:
            return false;

        case 3:
            if (strcasecmp($text, 'NIL') === 0) {
                return null;
            }
            // Fall-through

        default:
            return $text;
        }
    }

}

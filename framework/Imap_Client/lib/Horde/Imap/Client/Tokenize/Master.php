<?php
/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */

/**
 * Tokenization of an IMAP data stream: contains master data stream.
 *
 * NOTE: This class is NOT intended to be accessed outside of this package.
 * There is NO guarantees that the API of this class will not change across
 * versions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */
class Horde_Imap_Client_Tokenize_Master extends Horde_Imap_Client_Tokenize
{
    /**
     * No need to return data when flushing iterator.
     *
     * @var boolean
     */
    public $noreturn = false;

    /**
     * Data stream.
     *
     * @var Horde_Stream
     */
    public $stream;

    /**
     * Current sub level.
     *
     * @var integer
     */
    protected $_sub = 0;

    /**
     * Constructor.
     *
     * @param mixed $data  Data to add (string, resource, or Horde_Stream
     *                     object).
     */
    public function __construct($data = null)
    {
        $this->stream = new Horde_Stream_Temp();

        if (!is_null($data)) {
            $this->add($data);
        }
    }

    /**
     */
    public function __toString()
    {
        $pos = ftell($this->stream->stream);
        $out = $this->_current . ' ' . $this->stream->getString();
        fseek($this->stream->stream, $pos);
        return $out;
    }

    /**
     */
    public function flushIterator($return_entry = true)
    {
        if ($return_entry) {
            return $this->_flushIterator(true);
        }

        fseek($this->stream->stream, 0, SEEK_END);
        $this->_current = $this->_key = false;
        $this->_sub = 0;

        return array();
    }

    /**
     * Add data to buffer.
     *
     * @param mixed $data  Data to add (string, resource, or Horde_Stream
     *                     object).
     */
    public function add($data)
    {
        $this->stream->add($data);
    }

    /**
     */
    public function next()
    {
        $this->_current = $this->parseStream();
        $this->_key = ($this->_current === false)
            ? false
            : (($this->_key === false) ? 0 : ($this->_key + 1));

        return $this->_current;
    }

    /**
     */
    public function rewind()
    {
        fseek($this->stream->stream, 0);
        $this->_key = false;
        $this->_sub = 0;

        return $this->next();
    }

    /**
     * Returns the next token and increments the internal stream pointer.
     *
     * @param integer $level  Sublevel to return.
     *
     * @return mixed  Either a string, array, true, false, or null.
     */
    public function parseStream($level = 0)
    {
        while (($this->_sub > $level) &&
               ($this->_parseStream($level) !== false)) {
            // No-op
        }
        return $this->_parseStream($level);
    }

    /**
     * Returns the next token and increments the internal stream pointer.
     *
     * @param integer $level  Sublevel to return.
     *
     * @return mixed  Either a string, array, true, false, or null.
     */
    protected function _parseStream($level)
    {
        $in_quote = false;
        $stream = $this->stream->stream;
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
                    ++$this->_sub;
                    return $this->noreturn
                        ? true
                        : new Horde_Imap_Client_Tokenize_List($this, $this->_sub);

                case ')':
                    if (strlen($text)) {
                        fseek($stream, -1, SEEK_CUR);
                        break 3;
                    }
                    return ($level != $this->_sub--);

                case '~':
                    // Ignore binary string identifier. PHP strings are
                    // binary-safe.
                    break;

                case '{':
                    $literal_len = $this->stream->getToChar('}');
                    if ($this->noreturn) {
                        fseek($stream, $literal_len, SEEK_CUR);
                        return true;
                    }
                    return stream_get_contents($stream, $literal_len);

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

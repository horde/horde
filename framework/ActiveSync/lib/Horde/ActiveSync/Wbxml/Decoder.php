<?php
/**
 * Horde_ActiveSync_Wbxml_Decoder::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   © Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * ActiveSync specific WBXML decoder.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Wbxml_Decoder extends Horde_ActiveSync_Wbxml
{
    /**
     * Store the wbxml version value. Used to verify we have a valid wbxml
     * input stream.
     *
     * @todo H6 Make this (and most of the other) properties protected.
     *
     * @var integer
     */
    public $version;

    public $publicid;
    public $publicstringid;
    public $charsetid;
    public $stringtable;

    /**
     * Temporary string buffer
     *
     * @var stream
     */
    protected $_buffer;

    /**
     * Flag to indicate we have a valid wbxml input stream
     *
     * @var boolean
     */
    protected $_isWbxml;

    protected $_attrcp = 0;
    protected $_ungetbuffer;
    protected $_readHeader = false;

    /**
     * Cache the last successfully fetched start tag array. Used to be able
     * to easily detected emtpy nodes after the element was already fetched.
     *
     * @var array
     */
    protected $_lastStartElement;

    /**
     * Start reading the wbxml stream, pulling off the initial header and
     * populate the properties.
     */
    public function readWbxmlHeader()
    {
        $this->_readHeader = true;
        $this->_readVersion();
        if ($this->version != self::WBXML_VERSION) {
            // Not Wbxml - save the byte we already read.
            $this->_getTempStream();
            $this->_buffer->add(chr($this->version));
            $this->_isWbxml = false;
            return;
        } else {
            $this->_isWbxml = true;
        }

        $this->publicid = $this->_getMBUInt();
        if ($this->publicid == 0) {
            $this->publicstringid = $this->_getMBUInt();
        }
        $this->charsetid = $this->_getMBUInt();
        $this->stringtable = $this->_getStringTable();
    }

    /**
     * Check that the input stream contains wbxml. Basically looks for a valid
     * WBXML_VERSION header. self::readWbxmlHeader MUST have been called already.
     *
     * @return boolean
     */
    public function isWbxml()
    {
        if (!$this->_readHeader) {
            throw new Horde_ActiveSync_Exception('Failed to read WBXML header prior to calling isWbxml()');
        }

        return $this->_isWbxml;
    }

    /**
     * Return the full, raw, input stream. Used for things like SendMail request
     * where we don't have wbxml to parse. The calling code is responsible for
     * closing the stream.
     *
     * @return resource
     */
    public function getFullInputStream()
    {
        // Ensure the buffer was created
        $this->_getTempStream();
        $this->_buffer->add($this->_stream);
        $this->_buffer->rewind();
        return $this->_buffer->stream;
    }

    /**
     * Returns either start, content or end, and auto-concatenates successive
     * content.
     *
     * @return mixed  The element false on failure.
     */
    public function getElement()
    {
        $element = $this->getToken();

        switch ($element[self::EN_TYPE]) {
        case self::EN_TYPE_STARTTAG:
            return $element;
        case self::EN_TYPE_ENDTAG:
            return $element;
        case self::EN_TYPE_CONTENT:
            while (1) {
                $next = $this->getToken();
                if ($next == false) {
                    return false;
                } elseif ($next[self::EN_TYPE] == self::EN_CONTENT) {
                    $element[self::EN_CONTENT] .= $next[self::EN_CONTENT];
                } else {
                    $this->_ungetElement($next);
                    break;
                }
            }
            return $element;
        }

        return false;
    }

    /**
     * Returns whether or not the passed in element array represents an
     * empty tag.
     *
     * @param  array $el  The element array.
     *
     * @return boolean  True if $el is an empty start tag, otherwise false.
     */
    public function isEmptyElement($el)
    {
        if (!is_array($el)) {
            return false;
        }
        switch ($el[self::EN_TYPE]) {
        case self::EN_TYPE_STARTTAG:
            return !($el[self::EN_FLAGS] & self::EN_FLAGS_CONTENT);
        default:
            // Not applicable.
            return false;
        }
    }

    /**
     * Returns the last element array fetched using getElementStartTag()
     *
     * @return array|boolean  The element array, or false if none available.
     */
    public function getLastStartElement()
    {
        return $this->_lastStartElement;
    }

    /**
     * Peek at the next element in the stream.
     *
     * @return array  The next element in the stream.
     */
    public function peek()
    {
        $element = $this->getElement();
        $this->_ungetElement($element);

        return $element;
    }

    /**
     * Get the next tag, which is assumed to be a start tag.
     *
     * @param string $tag  The element that this should be a start tag for.
     *
     * @return array|boolean  The start tag array | false on failure.
     */
    public function getElementStartTag($tag)
    {
        $element = $this->getToken();

        if ($element[self::EN_TYPE] == self::EN_TYPE_STARTTAG &&
            $element[self::EN_TAG] == $tag) {

            $this->_lastStartElement = $element;
            return $element;
        } else {
            $this->_lastStartElement = false;
            $this->_ungetElement($element);
        }

        return false;
    }

    /**
     * Get the next tag, which is assumed to be an end tag.
     *
     * @return array|boolean The element array | false on failure.
     */
    public function getElementEndTag()
    {
        $element = $this->getToken();
        if ($element[self::EN_TYPE] == self::EN_TYPE_ENDTAG) {
            return $element;
        } else {
            $this->_logger->err(sprintf(
                '[%s] Unmatched end tag:',
                $this->_procid));
            $this->_logger->err(print_r($element, true));
            $this->_ungetElement($element);
        }

        return false;
    }

    /**
     * Get the element contents
     *
     * @return mixed  The content of the current element | false on failure.
     */
    public function getElementContent()
    {
        $element = $this->getToken();
        if ($element[self::EN_TYPE] == self::EN_TYPE_CONTENT) {
            return $element[self::EN_CONTENT];
        }
        $this->_ungetElement($element);

        return false;
    }

    /**
     * Get the next [start | content | end] tag.
     *
     * @return array  The next, complete, token array.
     */
    public function getToken()
    {
        // See if there's something in the ungetBuffer
        if ($this->_ungetbuffer) {
            $element = $this->_ungetbuffer;
            $this->_ungetbuffer = false;
            return $element;
        }

        $el = $this->_getToken();
        $this->_logToken($el);

        return $el;
    }

    /**
     * Log the token.
     *
     * @param array  The element array.
     *
     * @return void
     */
    protected function _logToken($el)
    {
        switch ($el[self::EN_TYPE]) {
        case self::EN_TYPE_STARTTAG:
            if ($el[self::EN_FLAGS] & self::EN_FLAGS_CONTENT) {
                $spaces = str_repeat(' ', count($this->_logStack));
                $this->_logStack[] = $el[self::EN_TAG];
                $this->_logger->debug(sprintf(
                    '[%s] I %s <%s>',
                    $this->_procid,
                    $spaces,
                    $el[self::EN_TAG]));
            } else {
                $spaces = str_repeat(' ', count($this->_logStack));
                $this->_logger->debug(sprintf(
                    '[%s] I %s <%s />',
                    $this->_procid,
                    $spaces,
                    $el[self::EN_TAG]));
            }
            break;
        case self::EN_TYPE_ENDTAG:
            $tag = array_pop($this->_logStack);
            $spaces = str_repeat(' ', count($this->_logStack));
            $this->_logger->debug(sprintf(
                '[%s] I %s </%s>',
                $this->_procid,
                $spaces,
                $tag));
            break;
        case self::EN_TYPE_CONTENT:
            $spaces = str_repeat(' ', count($this->_logStack) + 1);
            if ($this->_logLevel == self::LOG_PROTOCOL &&
                ($l = Horde_String::length($el[self::EN_CONTENT])) > self::LOG_MAXCONTENT) {
                $this->_logger->debug(sprintf(
                    '[%s] I %s %s',
                    $this->_procid,
                    $spaces,
                    sprintf('[%d bytes of content]', $l)
                  ));
            } else {
                $this->_logger->debug(sprintf(
                    '[%s] I %s %s',
                    $this->_procid,
                    $spaces,
                    $el[self::EN_CONTENT])
                );
            }
            break;
        }
    }

    /**
     * Get the next start tag, content or end tag
     *
     * @return array  The element array.
     */
   protected function _getToken() {

        // Get the data from the input stream
        $element = array();

        while (1) {
            $byte = $this->_getByte();

            if (!isset($byte)) {
                break;
            }

            switch ($byte) {
            case self::SWITCH_PAGE:
                $this->_tagcp = $this->_getByte();
                continue;

            case self::END:
                $element[self::EN_TYPE] = self::EN_TYPE_ENDTAG;
                return $element;

            case self::ENTITY:
                $entity = $this->_getMBUInt();
                $element[self::EN_TYPE] = self::EN_TYPE_CONTENT;
                $element[self::EN_CONTENT] = $this->entityToCharset($entity);
                return $element;

            case self::STR_I:
                $element[self::EN_TYPE] = self::EN_TYPE_CONTENT;
                $element[self::EN_CONTENT] = $this->_getTermStr();
                return $element;

            case self::LITERAL:
                $element[self::EN_TYPE] = self::EN_TYPE_STARTTAG;
                $element[self::EN_TAG] = $this->_getStringTableEntry($this->_getMBUInt());
                $element[self::EN_FLAGS] = 0;
                return $element;

            case self::EXT_I_0:
            case self::EXT_I_1:
            case self::EXT_I_2:
                $this->_getTermStr();
                // Ignore extensions
                continue;

            case self::PI:
                // Ignore PI
                $this->_getAttributes();
                continue;

            case self::LITERAL_C:
                $element[self::EN_TYPE] = self::EN_TYPE_STARTTAG;
                $element[self::EN_TAG] = $this->_getStringTableEntry($this->_getMBUInt());
                $element[self::EN_FLAGS] = self::EN_FLAGS_CONTENT;
                return $element;

            case self::EXT_T_0:
            case self::EXT_T_1:
            case self::EXT_T_2:
                $this->_getMBUInt();
                // Ingore extensions;
                continue;

            case self::STR_T:
                $element[self::EN_TYPE] = self::EN_TYPE_CONTENT;
                $element[self::EN_CONTENT] = $this->_getStringTableEntry($this->_getMBUInt());
                return $element;

            case self::LITERAL_A:
                $element[self::EN_TYPE] = self::EN_TYPE_STARTTAG;
                $element[self::EN_TAG] = $this->_getStringTableEntry($this->_getMBUInt());
                $element[self::EN_ATTRIBUTES] = $this->_getAttributes();
                $element[self::EN_FLAGS] = self::EN_FLAGS_ATTRIBUTES;
                return $element;
            case self::EXT_0:
            case self::EXT_1:
            case self::EXT_2:
                continue;

            case self::OPAQUE:
                $length = $this->_getMBUInt();
                $element[self::EN_TYPE] = self::EN_TYPE_CONTENT;
                $element[self::EN_CONTENT] = $this->_getOpaque($length);
                return $element;

            case self::LITERAL_AC:
                $element[self::EN_TYPE] = self::EN_TYPE_STARTTAG;
                $element[self::EN_TAG] = $this->_getStringTableEntry($this->_getMBUInt());
                $element[self::EN_ATTRIBUTES] = $this->_getAttributes();
                $element[self::EN_FLAGS] = self::EN_FLAGS_ATTRIBUTES | self::EN_FLAGS_CONTENT;
                return $element;

            default:
                $element[self::EN_TYPE] = self::EN_TYPE_STARTTAG;
                $element[self::EN_TAG] = $this->_getMapping($this->_tagcp, $byte & 0x3f);
                $element[self::EN_FLAGS] = ($byte & 0x80 ? self::EN_FLAGS_ATTRIBUTES : 0) | ($byte & 0x40 ? self::EN_FLAGS_CONTENT : 0);
                if ($byte & 0x80) {
                    $element[self::EN_ATTRIBUTES] = $this->_getAttributes();
                }
                return $element;
            }
        }
    }

    /**
     * Unget the specified element from the stream. Places the element into
     * the unget buffer.
     *
     * @param array $element  The element array to unget.
     *
     * @return void
     */
    public function _ungetElement($element)
    {
        if ($this->_ungetbuffer) {
            $this->_logger->err('Double unget!');
        }
        $this->_ungetbuffer = $element;
    }

    /**
     * Read the Wbxml version header byte, and buffer the input incase we
     * need the full stream later.
     */
    protected function _readVersion()
    {
        $b = $this->_getByte();
        if ($b != NULL) {
            $this->version = $b;
        }
    }

    /**
     * Get the element attributes
     *
     * @return mixed  The value of the element's attributes.
     */
    protected function _getAttributes()
    {
        $attributes = array();
        $attr = '';

        while (1) {
            $byte = $this->_getByte();
            if (count($byte) == 0) {
                break;
            }

            switch($byte) {
            case self::SWITCH_PAGE:
                $this->_attrcp = $this->_getByte();
                break;

            case self::END:
                if ($attr != '') {
                    $attributes += $this->_splitAttribute($attr);
                }
                return $attributes;

            case self::ENTITY:
                $entity = $this->_getMBUInt();
                $attr .= $this->entityToCharset($entity);
                return $element;

            case self::STR_I:
                $attr .= $this->_getTermStr();
                return $element;

            case self::LITERAL:
                if ($attr != '') {
                    $attributes += $this->_splitAttribute($attr);
                }
                $attr = $this->_getStringTableEntry($this->_getMBUInt());
                return $element;

            case self::EXT_I_0:
            case self::EXT_I_1:
            case self::EXT_I_2:
                $this->_getTermStr();
                continue;

            case self::PI:
            case self::LITERAL_C:
                // Invalid
                return false;

            case self::EXT_T_0:
            case self::EXT_T_1:
            case self::EXT_T_2:
                $this->_getMBUInt();
                continue;

            case self::STR_T:
                $attr .= $this->_getStringTableEntry($this->_getMBUInt());
                return $element;

            case self::LITERAL_A:
                return false;

            case self::EXT_0:
            case self::EXT_1:
            case self::EXT_2:
                continue;

            case self::OPAQUE:
                $length = $this->_getMBUInt();
                $attr .= $this->_getOpaque($length);
                return $element;

            case self::LITERAL_AC:
                return false;

            default:
                if ($byte < 128) {
                    if ($attr != '') {
                        $attributes += $this->_splitAttribute($attr);
                        $attr = '';
                    }
                }

                $attr .= $this->_getMapping($this->_attrcp, $byte);
                break;
            }
        }
    }

    /**
     * Parses an attribute string
     *
     * @param string $attr  The raw attribute value.
     *
     * @return array  The attribute hash
     */
    protected function _splitAttribute($attr)
    {
        $attributes = array();
        $pos = strpos($attr,chr(61)); // equals sign
        if ($pos) {
            $attributes[substr($attr, 0, $pos)] = substr($attr, $pos+1);
        } else {
            $attributes[$attr] = null;
        }

        return $attributes;
    }

    /**
     * Get a null terminated string from the stream.
     *
     * @return string  The string
     */
    protected function _getTermStr()
    {
        $str = '';
        while(1) {
            $in = $this->_getByte();

            if ($in == 0) {
                break;
            } else {
                $str .= chr($in);
            }
        }

        return $str;
    }

    /**
     * Get an opaque value from the stream of the specified length.
     *
     * @param integer $len  The length of the data to fetch.
     *
     * @return string  A string of bytes representing the opaque value.
     */
    protected function _getOpaque($len)
    {
        // See http://php.net/fread for why we can't simply use a single fread()
        // here. Bottom line, for buffered network streams it may be possible
        // that fread will only return a portion of the stream if chunk
        // is smaller then $len, so we use a loop to reach $len.
        $d = '';
        while (1) {
            $l = (($len - strlen($d)) > 8192) ? 8192 : ($len - strlen($d));
            if ($l > 0) {
                $data = $this->_stream->substring(0, $l);
                // Stream ends prematurely on instable connections and big mails
                if ($data === false || $this->_stream->eof()) {
                    throw new Horde_ActiveSync_Exception(sprintf(
                        'Connection unavailable while trying to read %d bytes from stream. Aborting after %d bytes read.',
                        $len,
                        strlen($d)));
                } else {
                    $d .= $data;
                }
            }
            if (strlen($d) >= $len) {
                break;
            }
        }

        return $d;
    }

    /**
     * Fetch a single byte from the stream.
     *
     * @return string  The single byte.
     */
    protected function _getByte()
    {
        $ch = $this->_stream->getChar();
        if (strlen($ch) > 0) {
            $ch = ord($ch);
            return $ch;
        } else {
            return;
        }
    }

    /**
     * Get an MBU integer
     *
     * @return integer
     */
    protected function _getMBUInt()
    {
        $uint = 0;
        while (1) {
          $byte = $this->_getByte();
          $uint |= $byte & 0x7f;
          if ($byte & 0x80) {
              $uint = $uint << 7;
          } else {
              break;
          }
        }

        return $uint;
    }

    /**
     * Fetch the string table. Don't think we use the results anywhere though.
     *
     * @return string  The string table.
     */
    protected function _getStringTable()
    {
        $stringtable = '';
        $length = $this->_getMBUInt();
        if ($length > 0) {
            $stringtable = $this->_stream->substring(0, $length);
        }

        return $stringtable;
    }

    /**
     * Really don't know for sure what this method is supposed to do, it is
     * called from numerous places in this class, but the original zpush code
     * did not contain this method...so, either it's completely broken, or
     * normal use-cases do not reach the calling code. Either way, it needs to
     * eventually be fixed.
     *
     * @param integer $id  The entry to return??
     *
     * @return string
     */
    protected function _getStringTableEntry($id)
    {
        throw new Horde_ActiveSync_Exception('Not implemented');
    }

    /**
     * Get a dtd mapping
     *
     * @param integer $cp  The codepage to use.
     * @param integer $id  The property.
     *
     * @return mixed  The mapped value.
     */
    protected function _getMapping($cp, $id)
    {
        if (!isset($this->_dtd['codes'][$cp]) || !isset($this->_dtd['codes'][$cp][$id])) {
            return false;
        } else {
            if (isset($this->_dtd['namespaces'][$cp])) {
                return $this->_dtd['namespaces'][$cp] . ':' . $this->_dtd['codes'][$cp][$id];
            } else {
                return $this->_dtd['codes'][$cp][$id];
            }
        }
    }

    /**
     * Return the temporary buffer stream.
     *
     * @return stream
     */
    protected function _getTempStream()
    {
        if (!isset($this->_buffer)) {
            $this->_buffer = new Horde_Stream_Temp();
        }

        return $this->_buffer;
    }

}

<?php
/**
 * ActiveSync specific WBXML decoder.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */

/**
 * File      :   wbxml.php
 * Project   :   Z-Push
 * Descr     :   WBXML mapping file
 *
 * Created   :   01.10.2007
 *
 * � Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_Wbxml_Decoder extends Horde_ActiveSync_Wbxml
{
    /**
     * PHP input stream
     *
     * @var stream
     */
    private $_in;

    // These seem to only be used in the Const'r, and I can't find any
    // client code that access these properties...
    public $version;
    public $publicid;
    public $publicstringid;
    public $charsetid;

    public $stringtable;

    private $_tagcp = 0;
    private $_attrcp = 0;
    private $_ungetbuffer;
    private $_logStack = array();

    /**
     * @var Horde_Log_Logger
     */
    private $_logger;

    /**
     * Const'r
     *
     * @param stream $input
     * @param array $dtd
     * @param array $config
     *
     * @return Horde_ActiveSync_Wbxml_Decoder
     */
    public function __construct($input)
    {
        $this->_in = $input;
        $this->_logger = new Horde_Support_Stub();
        $this->version = $this->_getByte();
        $this->publicid = $this->_getMBUInt();
        if ($this->publicid == 0) {
            $this->publicstringid = $this->_getMBUInt();
        }
        $this->charsetid = $this->_getMBUInt();

        // This is used, but not sure what the missing getStringTableEntry()
        // method was supposed to do yet.
        $this->stringtable = $this->_getStringTable();
    }

    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }
    
    /**
     * Returns either start, content or end, and auto-concatenates successive content
     */
    public function getElement()
    {
        $element = $this->getToken();

        switch ($element[Horde_ActiveSync_Wbxml::EN_TYPE]) {
        case Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG:
            return $element;
        case Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG:
            return $element;
        case Horde_ActiveSync_Wbxml::EN_TYPE_CONTENT:
            while (1) {
                $next = $this->getToken();
                if ($next == false) {
                    return false;
                } elseif ($next[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_CONTENT) {
                    $element[Horde_ActiveSync_Wbxml::EN_CONTENT] .= $next[Horde_ActiveSync_Wbxml::EN_CONTENT];
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
     *
     * @return unknown_type
     */
    public function peek()
    {
        $element = $this->getElement();
        $this->_ungetElement($element);

        return $element;
    }

    /**
     *
     * @param $tag
     * @return unknown_type
     */
    public function getElementStartTag($tag)
    {
        $element = $this->getToken();

        if ($element[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG &&
            $element[Horde_ActiveSync_Wbxml::EN_TAG] == $tag) {

            return $element;
        } else {
            $this->_logger->debug('Unmatched tag' .  $tag . ':');
            $this->_ungetElement($element);
        }

        return false;
    }

    /**
     *
     * @return unknown_type
     */
    public function getElementEndTag()
    {
        $element = $this->getToken();
        if ($element[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
            return $element;
        } else {
            $this->_logger->err('Unmatched end tag:');
            $this->_logger->err(print_r($element, true));
            $this->_ungetElement($element);
        }

        return false;
    }

    /**
     *
     * @return unknown_type
     */
    public function getElementContent()
    {
        $element = $this->getToken();
        if ($element[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_CONTENT) {
            return $element[Horde_ActiveSync_Wbxml::EN_CONTENT];
        } else {
            $this->_logger->err('Unmatched content:');
            $this->_logger->err(print_r($element, true));
            $this->_ungetElement($element);
        }

        return false;
    }

    /**
     * @return unknown_type
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
     *
     * @param unknown_type $el
     * @return unknown_type
     */
    private function _logToken($el)
    {
        $spaces = str_repeat(' ', count($this->_logStack));
        switch ($el[Horde_ActiveSync_Wbxml::EN_TYPE]) {
        case Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG:
            if ($el[Horde_ActiveSync_Wbxml::EN_FLAGS] & Horde_ActiveSync_Wbxml::EN_FLAGS_CONTENT) {
                $this->_logger->debug('I ' . $spaces . ' <' . $el[Horde_ActiveSync_Wbxml::EN_TAG] . '>');
                array_push($this->_logStack, $el[Horde_ActiveSync_Wbxml::EN_TAG]);
            } else {
                $this->_logger->debug('I ' . $spaces . ' <' . $el[Horde_ActiveSync_Wbxml::EN_TAG] . '/>');
            }
            break;
        case Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG:
            $tag = array_pop($this->_logStack);
            $this->_logger->debug('I ' . $spaces . '</' . $tag . '>');
            break;
        case Horde_ActiveSync_Wbxml::EN_TYPE_CONTENT:
            $this->_logger->debug('I ' . $spaces . ' ' . $el[Horde_ActiveSync_Wbxml::EN_CONTENT]);
            break;
        }
    }

    /**
     * Returns either a start tag, content or end tag
     */
   private function _getToken() {

        // Get the data from the input stream
        $element = array();

        while (1) {
            $byte = $this->_getByte();

            if (!isset($byte)) {
                break;
            }

            switch ($byte) {
            case Horde_ActiveSync_Wbxml::SWITCH_PAGE:
                $this->_tagcp = $this->_getByte();
                continue;

            case Horde_ActiveSync_Wbxml::END:
                $element[Horde_ActiveSync_Wbxml::EN_TYPE] = Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG;
                return $element;

            case Horde_ActiveSync_Wbxml::ENTITY:
                $entity = $this->_getMBUInt();
                $element[Horde_ActiveSync_Wbxml::EN_TYPE] = Horde_ActiveSync_Wbxml::EN_TYPE_CONTENT;
                $element[Horde_ActiveSync_Wbxml::EN_CONTENT] = $this->entityToCharset($entity);
                return $element;

            case Horde_ActiveSync_Wbxml::STR_I:
                $element[Horde_ActiveSync_Wbxml::EN_TYPE] = Horde_ActiveSync_Wbxml::EN_TYPE_CONTENT;
                $element[Horde_ActiveSync_Wbxml::EN_CONTENT] = $this->_getTermStr();
                return $element;

            case Horde_ActiveSync_Wbxml::LITERAL:
                $element[Horde_ActiveSync_Wbxml::EN_TYPE] = Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG;
                $element[Horde_ActiveSync_Wbxml::EN_TAG] = $this->_getStringTableEntry($this->_getMBUInt());
                $element[Horde_ActiveSync_Wbxml::EN_FLAGS] = 0;
                return $element;

            case Horde_ActiveSync_Wbxml::EXT_I_0:
            case Horde_ActiveSync_Wbxml::EXT_I_1:
            case Horde_ActiveSync_Wbxml::EXT_I_2:
                $this->_getTermStr();
                // Ignore extensions
                continue;

            case Horde_ActiveSync_Wbxml::PI:
                // Ignore PI
                $this->_getAttributes();
                continue;

            case Horde_ActiveSync_Wbxml::LITERAL_C:
                $element[Horde_ActiveSync_Wbxml::EN_TYPE] = Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG;
                $element[Horde_ActiveSync_Wbxml::EN_TAG] = $this->_getStringTableEntry($this->_getMBUInt());
                $element[Horde_ActiveSync_Wbxml::EN_FLAGS] = Horde_ActiveSync_Wbxml::EN_FLAGS_CONTENT;
                return $element;

            case Horde_ActiveSync_Wbxml::EXT_T_0:
            case Horde_ActiveSync_Wbxml::EXT_T_1:
            case Horde_ActiveSync_Wbxml::EXT_T_2:
                $this->_getMBUInt();
                // Ingore extensions;
                continue;

            case Horde_ActiveSync_Wbxml::STR_T:
                $element[Horde_ActiveSync_Wbxml::EN_TYPE] = Horde_ActiveSync_Wbxml::EN_TYPE_CONTENT;
                $element[Horde_ActiveSync_Wbxml::EN_CONTENT] = $this->_getStringTableEntry($this->_getMBUInt());
                return $element;

            case Horde_ActiveSync_Wbxml::LITERAL_A:
                $element[Horde_ActiveSync_Wbxml::EN_TYPE] = Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG;
                $element[Horde_ActiveSync_Wbxml::EN_TAG] = $this->_getStringTableEntry($this->_getMBUInt());
                $element[Horde_ActiveSync_Wbxml::EN_ATTRIBUTES] = $this->_getAttributes();
                $element[Horde_ActiveSync_Wbxml::EN_FLAGS] = Horde_ActiveSync_Wbxml::EN_FLAGS_ATTRIBUTES;
                return $element;
            case Horde_ActiveSync_Wbxml::EXT_0:
            case Horde_ActiveSync_Wbxml::EXT_1:
            case Horde_ActiveSync_Wbxml::EXT_2:
                continue;

            case Horde_ActiveSync_Wbxml::OPAQUE:
                $length = $this->_getMBUInt();
                $element[Horde_ActiveSync_Wbxml::EN_TYPE] = Horde_ActiveSync_Wbxml::EN_TYPE_CONTENT;
                $element[Horde_ActiveSync_Wbxml::EN_CONTENT] = $this->_getOpaque($length);
                return $element;

            case Horde_ActiveSync_Wbxml::LITERAL_AC:
                $element[Horde_ActiveSync_Wbxml::EN_TYPE] = Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG;
                $element[Horde_ActiveSync_Wbxml::EN_TAG] = $this->_getStringTableEntry($this->_getMBUInt());
                $element[Horde_ActiveSync_Wbxml::EN_ATTRIBUTES] = $this->_getAttributes();
                $element[Horde_ActiveSync_Wbxml::EN_FLAGS] = Horde_ActiveSync_Wbxml::EN_FLAGS_ATTRIBUTES | Horde_ActiveSync_Wbxml::EN_FLAGS_CONTENT;
                return $element;

            default:
                $element[Horde_ActiveSync_Wbxml::EN_TYPE] = Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG;
                $element[Horde_ActiveSync_Wbxml::EN_TAG] = $this->_getMapping($this->_tagcp, $byte & 0x3f);
                $element[Horde_ActiveSync_Wbxml::EN_FLAGS] = ($byte & 0x80 ? Horde_ActiveSync_Wbxml::EN_FLAGS_ATTRIBUTES : 0) | ($byte & 0x40 ? Horde_ActiveSync_Wbxml::EN_FLAGS_CONTENT : 0);
                if ($byte & 0x80) {
                    $element[Horde_ActiveSync_Wbxml::EN_ATTRIBUTES] = $this->_getAttributes();
                }
                return $element;
            }
        }
    }

    /**
     *
     * @param $element
     * @return unknown_type
     */
    public function _ungetElement($element)
    {
        if ($this->_ungetbuffer) {
            $this->_logger->err('Double unget!');
        }
        $this->_ungetbuffer = $element;
    }

    /**
     *
     * @return unknown_type
     */
    private function _getAttributes()
    {
        $attributes = array();
        $attr = '';

        while (1) {
            $byte = $this->_getByte();
            if (count($byte) == 0) {
                break;
            }

            switch($byte) {
                case Horde_ActiveSync_Wbxml::SWITCH_PAGE:
                    $this->_attrcp = $this->_getByte();
                    break;

                case Horde_ActiveSync_Wbxml::END:
                    if ($attr != '') {
                        $attributes += $this->_splitAttribute($attr);
                    }
                    return $attributes;

                case Horde_ActiveSync_Wbxml::ENTITY:
                    $entity = $this->_getMBUInt();
                    $attr .= $this->entityToCharset($entity);
                    return $element;

                case Horde_ActiveSync_Wbxml::STR_I:
                    $attr .= $this->_getTermStr();
                    return $element;

                case Horde_ActiveSync_Wbxml::LITERAL:
                    if ($attr != '') {
                        $attributes += $this->_splitAttribute($attr);
                    }
                    $attr = $this->_getStringTableEntry($this->_getMBUInt());
                    return $element;

                case Horde_ActiveSync_Wbxml::EXT_I_0:
                case Horde_ActiveSync_Wbxml::EXT_I_1:
                case Horde_ActiveSync_Wbxml::EXT_I_2:
                    $this->_getTermStr();
                    continue;

                case Horde_ActiveSync_Wbxml::PI:
                case Horde_ActiveSync_Wbxml::LITERAL_C:
                    // Invalid
                    return false;

                case Horde_ActiveSync_Wbxml::EXT_T_0:
                case Horde_ActiveSync_Wbxml::EXT_T_1:
                case Horde_ActiveSync_Wbxml::EXT_T_2:
                    $this->_getMBUInt();
                    continue;

                case Horde_ActiveSync_Wbxml::STR_T:
                    $attr .= $this->_getStringTableEntry($this->_getMBUInt());
                    return $element;

                case Horde_ActiveSync_Wbxml::LITERAL_A:
                    return false;

                case Horde_ActiveSync_Wbxml::EXT_0:
                case Horde_ActiveSync_Wbxml::EXT_1:
                case Horde_ActiveSync_Wbxml::EXT_2:
                    continue;

                case Horde_ActiveSync_Wbxml::OPAQUE:
                    $length = $this->_getMBUInt();
                    $attr .= $this->_getOpaque($length);
                    return $element;

                case Horde_ActiveSync_Wbxml::LITERAL_AC:
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
     *
     * @param $attr
     *
     * @return unknown_type
     */
    private function _splitAttribute($attr)
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
     *
     * @return unknown_type
     */
    private function _getTermStr()
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
     *
     * @param $len
     *
     * @return unknown_type
     */
    private function _getOpaque($len)
    {
        return fread($this->_in, $len);
    }

    /**
     *
     * @return unknown_type
     */
    private function _getByte()
    {
        $ch = fread($this->_in, 1);
        if (strlen($ch) > 0) {
            $ch = ord($ch);
            //$this->_logger->debug('_getByte: ' . $ch);
            return $ch;
        } else {
            return;
        }
    }

    /**
     *
     * @return unknown_type
     */
    private function _getMBUInt()
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

        //$this->_logger->debug('_getMBUInt(): ' . $uint);
        return $uint;
    }

    /**
     *
     * @return unknown_type
     */
    private function _getStringTable()
    {
        $stringtable = '';
        $length = $this->_getMBUInt();
        if ($length > 0) {
            $stringtable = fread($this->_in, $length);
        }

        //$this->_logger->debug('_getStringTable(): ' . $stringtable);
        return $stringtable;
    }

    /**
     * Really don't know for sure what this method is supposed to do, it is
     * called from numerous places in this class, but the original zpush code
     * did not contain this method...so, either it's completely broken, or
     * normal use-cases do not reach the calling code. Either way, it needs to
     * eventually be fixed.
     *
     * @param unknown_type $id
     *
     * @return unknown_type
     */
    private function _getStringTableEntry($id)
    {
        throw new Horde_ActiveSync_Exception('Not implemented');
    }

    /**
     *
     * @param $cp
     * @param $id
     * @return unknown_type
     */
    private function _getMapping($cp, $id)
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

}
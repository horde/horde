<?php
/**
 * From Binary XML Content Format Specification Version 1.3, 25 July 2001
 * found at http://www.wapforum.org
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anthony Mills <amills@pyramid6.com>
 * @package Xml_Wbxml
 */
class Horde_Xml_Wbxml_Decoder extends Horde_Xml_Wbxml_ContentHandler
{
    /**
     * Document Public Identifier type
     * 1 mb_u_int32 well known type
     * 2 string table
     * from spec but converted into a string.
     *
     * Document Public Identifier
     * Used with dpiType.
     */
    protected $_dpi;

    /**
     * String table as defined in 5.7
     */
    protected $_stringTable = array();

    /**
     * Content handler.
     * Currently just outputs raw XML.
     */
    protected $_ch;

    protected $_tagDTD;

    protected $_prevAttributeDTD;

    protected $_attributeDTD;

    /**
     * State variables.
     */
    protected $_tagStack = array();
    protected $_isAttribute;
    protected $_isData = false;

    protected $_error = false;

    /**
     * The DTD Manager.
     *
     * @var Horde_Xml_Wbxml_DtdManager
     */
    protected $_dtdManager;

    /**
     * The string position.
     *
     * @var integer
     */
    protected $_strpos;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_dtdManager = new Horde_Xml_Wbxml_DtdManager();
    }

    /**
     * Sets the contentHandler that will receive the output of the
     * decoding.
     *
     * @param Horde_Xml_Wbxml_ContentHandler $ch The contentHandler
     */
    public function setContentHandler($ch)
    {
        $this->_ch = $ch;
    }
    /**
     * Return one byte from the input stream.
     *
     * @param string $input  The WBXML input string.
     */
    public function getByte($input)
    {
        return ord($input{$this->_strpos++});
    }

    /**
     * Takes a WBXML input document and returns decoded XML.
     * However the preferred and more effecient method is to
     * use decode() rather than decodeToString() and have an
     * appropriate contentHandler deal with the decoded data.
     *
     * @param string $wbxml  The WBXML document to decode.
     *
     * @return string  The decoded XML document.
     */
    public function decodeToString($wbxml)
    {
        $this->_ch = new Horde_Xml_Wbxml_ContentHandler();

        $r = $this->decode($wbxml);
        if (is_a($r, 'PEAR_Error')) {
            return $r;
        }
        return $this->_ch->getOutput();
    }

    /**
     * Takes a WBXML input document and decodes it.
     * Decoding result is directly passed to the contentHandler.
     * A contenthandler must be set using setContentHandler
     * prior to invocation of this method
     *
     * @param string $wbxml  The WBXML document to decode.
     *
     * @return mixed  True on success or PEAR_Error.
     */
    public function decode($wbxml)
    {
        $this->_error = false; // reset state

        $this->_strpos = 0;

        if (empty($this->_ch)) {
            return $this->raiseError('No Contenthandler defined.');
        }

        // Get Version Number from Section 5.4
        // version = u_int8
        // currently 1, 2 or 3
        $this->_wbxmlVersion = $this->getVersionNumber($wbxml);

        // Get Document Public Idetifier from Section 5.5
        // publicid = mb_u_int32 | (zero index)
        // zero = u_int8
        // Containing the value zero (0)
        // The actual DPI is determined after the String Table is read.
        $dpiStruct = $this->getDocumentPublicIdentifier($wbxml);

        // Get Charset from 5.6
        // charset = mb_u_int32
        $this->_charset = $this->getCharset($wbxml);

        // Get String Table from 5.7
        // strb1 = length *byte
        $this->retrieveStringTable($wbxml);

        // Get Document Public Idetifier from Section 5.5.
        $this->_dpi = $this->getDocumentPublicIdentifierImpl($dpiStruct['dpiType'],
                                                             $dpiStruct['dpiNumber'],
                                                             $this->_stringTable);

        // Now the real fun begins.
        // From Sections 5.2 and 5.8


        // Default content handler.
        $this->_dtdManager = new Horde_Xml_Wbxml_DtdManager();

        // Get the starting DTD.
        $this->_tagDTD = $this->_dtdManager->getInstance($this->_dpi);

        if (!$this->_tagDTD) {
            return $this->raiseError('No DTD found for '
                             . $this->_dpi . '/'
                             . $dpiStruct['dpiNumber']);
        }

        $this->_attributeDTD = $this->_tagDTD;

        while (empty($this->_error) && $this->_strpos < strlen($wbxml)) {
            $this->_decode($wbxml);
        }
        if (!empty($this->_error)) {
            return $this->_error;
        }
        return true;
    }

    public function getVersionNumber($input)
    {
        return $this->getByte($input);
    }

    public function getDocumentPublicIdentifier($input)
    {
        $i = Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos);
        if ($i == 0) {
            return array('dpiType' => 2,
                         'dpiNumber' => $this->getByte($input));
        } else {
            return array('dpiType' => 1,
                         'dpiNumber' => $i);
        }
    }

    public function getDocumentPublicIdentifierImpl($dpiType, $dpiNumber)
    {
        if ($dpiType == 1) {
            return Horde_Xml_Wbxml::getDPIString($dpiNumber);
        } else {
            return $this->getStringTableEntry($dpiNumber);
        }
    }

    /**
     * Returns the character encoding. Only default character
     * encodings from J2SE are supported.  From
     * http://www.iana.org/assignments/character-sets and
     * http://java.sun.com/j2se/1.4.2/docs/api/java/nio/charset/Charset.html
     */
    public function getCharset($input)
    {
        $cs = Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos);
        return Horde_Xml_Wbxml::getCharsetString($cs);
    }

    /**
     * Retrieves the string table.
     * The string table consists of an mb_u_int32 length
     * and then length bytes forming the table.
     * References to the string table refer to the
     * starting position of the (null terminated)
     * string in this table.
     */
    public function retrieveStringTable($input)
    {
        $size = Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos);
        $this->_stringTable = substr($input, $this->_strpos, $size);
        $this->_strpos += $size;
        // print "stringtable($size):" . $this->_stringTable ."\n";
    }

    public function getStringTableEntry($index)
    {
        if ($index >= strlen($this->_stringTable)) {
            $this->_error =
                $this->raiseError('Invalid offset ' . $index
                                  . ' value encountered around position '
                                  . $this->_strpos
                                  . '. Broken wbxml?');
            return '';
        }

        // copy of method termstr but without modification of this->_strpos

        $str = '#'; // must start with nonempty string to allow array access

        $i = 0;
        $ch = $this->_stringTable[$index++];
        if (ord($ch) == 0) {
            return ''; // don't return '#'
        }

        while (ord($ch) != 0) {
            $str[$i++] = $ch;
            if ($index >= strlen($this->_stringTable)) {
                break;
            }
            $ch = $this->_stringTable[$index++];
        }
        // print "string table entry: $str\n";
        return $str;

    }

    protected function _decode($input)
    {
        $token = $this->getByte($input);
        $str = '';

        // print "position: " . $this->_strpos . " token: " . $token . " str10: " . substr($input, $this->_strpos, 10) . "\n"; // @todo: remove debug output

        switch ($token) {
        case Horde_Xml_Wbxml::GLOBAL_TOKEN_STR_I:
            // Section 5.8.4.1
            $str = $this->termstr($input);
            $this->_ch->characters($str);
            // print "str:$str\n"; // @TODO Remove debug code
            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_STR_T:
            // Section 5.8.4.1
            $x = Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos);
            $str = $this->getStringTableEntry($x);
            $this->_ch->characters($str);
            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_I_0:
        case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_I_1:
        case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_I_2:
            // Section 5.8.4.2
            $str = $this->termstr($input);
            $this->_ch->characters($str);
            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_T_0:
        case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_T_1:
        case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_T_2:
            // Section 5.8.4.2
            $str = $this->getStringTableEnty(Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos));
            $this->_ch->characters($str);
            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_0:
        case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_1:
        case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_2:
            // Section 5.8.4.2
            $extension = $this->getByte($input);
            $this->_ch->characters($extension);
            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_ENTITY:
            // Section 5.8.4.3
            // UCS-4 chracter encoding?
            $entity = $this->entity(Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos));

            $this->_ch->characters('&#' . $entity . ';');
            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_PI:
            // Section 5.8.4.4
            // throw new IOException
            // die("WBXML global token processing instruction(PI, " + token + ") is unsupported!\n");
            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_LITERAL:
            // Section 5.8.4.5
            $str = $this->getStringTableEntry(Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos));
            $this->parseTag($input, $str, false, false);
            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_LITERAL_A:
            // Section 5.8.4.5
            $str = $this->getStringTableEntry(Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos));
            $this->parseTag($input, $str, true, false);
            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_LITERAL_AC:
            // Section 5.8.4.5
            $str = $this->getStringTableEntry(Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos));
            $this->parseTag($input, $string, true, true);
            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_LITERAL_C:
            // Section 5.8.4.5
            $str = $this->getStringTableEntry(Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos));
            $this->parseTag($input, $str, false, true);
            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_OPAQUE:
            // Section 5.8.4.6
            $size = Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos);
            if ($size>0) {
                $b = substr($input, $this->_strpos, $size);
                // print "opaque of size $size: ($b)\n"; // @todo remove debug
                $this->_strpos += $size;
                // opaque data inside a <data> element may or may not be
                // a nested wbxml document (for example devinf data).
                // We find out by checking the first byte of the data: if it's
                // 1, 2 or 3 we expect it to be the version number of a wbxml
                // document and thus start a new wbxml decoder instance on it.

                if ($size > 0 && $this->_isData && ord($b) <= 10) {
                    $decoder = new Horde_Xml_Wbxml_Decoder(true);
                    $decoder->setContentHandler($this->_ch);
                    $s = $decoder->decode($b);
            //                /* // @todo: FIXME currently we can't decode Nokia
                    // DevInf data. So ignore error for the time beeing.
                    if (is_a($s, 'PEAR_Error')) {
                        $this->_error = $s;
                        return;
                    }
                    // */
                    // $this->_ch->characters($s);
                } else {
                    /* normal opaque behaviour: just copy the raw data: */
                    // print "opaque handled as string=$b\n"; // @todo remove debug
                    $this->_ch->characters($b);
                }
            }
            // old approach to deal with opaque data inside ContentHandler:
            // FIXME Opaque is used by SYNCML.  Opaque data that depends on the context
            // if (contentHandler instanceof OpaqueContentHandler) {
            //     ((OpaqueContentHandler)contentHandler).opaque(b);
            // } else {
            //     String str = new String(b, 0, size, charset);
            //     char[] chars = str.toCharArray();

            //     contentHandler.characters(chars, 0, chars.length);
            // }

            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_END:
            // Section 5.8.4.7.1
            $str = $this->endTag();
            break;

        case Horde_Xml_Wbxml::GLOBAL_TOKEN_SWITCH_PAGE:
            // Section 5.8.4.7.2
            $codePage = $this->getByte($input);
            // print "switch to codepage $codePage\n"; // @todo: remove debug code
            $this->switchElementCodePage($codePage);
            break;

        default:
            // Section 5.8.2
            // Section 5.8.3
            $hasAttributes = (($token & 0x80) != 0);
            $hasContent = (($token & 0x40) != 0);
            $realToken = $token & 0x3F;
            $str = $this->getTag($realToken);

            // print "element:$str\n"; // @TODO Remove debug code
            $this->parseTag($input, $str, $hasAttributes, $hasContent);

            if ($realToken == 0x0f) {
                // store if we're inside a Data tag. This may contain
                // an additional enclosed wbxml document on which we have
                // to run a seperate encoder
                $this->_isData = true;
            } else {
                $this->_isData = false;
            }
            break;
        }
    }

    public function parseTag($input, $tag, $hasAttributes, $hasContent)
    {
        $attrs = array();
        if ($hasAttributes) {
            $attrs = $this->getAttributes($input);
        }

        $this->_ch->startElement($this->getCurrentURI(), $tag, $attrs);

        if ($hasContent) {
            // FIXME I forgot what does this does. Not sure if this is
            // right?
            $this->_tagStack[] = $tag;
        } else {
            $this->_ch->endElement($this->getCurrentURI(), $tag);
        }
    }

    public function endTag()
    {
        if (count($this->_tagStack)) {
            $tag = array_pop($this->_tagStack);
        } else {
            $tag = 'Unknown';
        }

        $this->_ch->endElement($this->getCurrentURI(), $tag);

        return $tag;
    }

    public function getAttributes($input)
    {
        $this->startGetAttributes();
        $hasMoreAttributes = true;

        $attrs = array();
        $attr = null;
        $value = null;
        $token = null;

        while ($hasMoreAttributes) {
            $token = $this->getByte($input);

            switch ($token) {
            // Attribute specified.
            case Horde_Xml_Wbxml::GLOBAL_TOKEN_LITERAL:
                // Section 5.8.4.5
                if (isset($attr)) {
                    $attrs[] = array('attribute' => $attr,
                                     'value' => $value);
                }

                $attr = $this->getStringTableEntry(Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos));
                break;

            // Value specified.
            case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_I_0:
            case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_I_1:
            case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_I_2:
                // Section 5.8.4.2
                $value .= $this->termstr($input);
                break;

            case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_T_0:
            case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_T_1:
            case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_T_2:
                // Section 5.8.4.2
                $value .= $this->getStringTableEntry(Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos));
                break;

            case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_0:
            case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_1:
            case Horde_Xml_Wbxml::GLOBAL_TOKEN_EXT_2:
                // Section 5.8.4.2
                $value .= $input[$this->_strpos++];
                break;

            case Horde_Xml_Wbxml::GLOBAL_TOKEN_ENTITY:
                // Section 5.8.4.3
                $value .= $this->entity(Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos));
                break;

            case Horde_Xml_Wbxml::GLOBAL_TOKEN_STR_I:
                // Section 5.8.4.1
                $value .= $this->termstr($input);
                break;

            case Horde_Xml_Wbxml::GLOBAL_TOKEN_STR_T:
                // Section 5.8.4.1
                $value .= $this->getStringTableEntry(Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos));
                break;

            case Horde_Xml_Wbxml::GLOBAL_TOKEN_OPAQUE:
                // Section 5.8.4.6
                $size = Horde_Xml_Wbxml::MBUInt32ToInt($input, $this->_strpos);
                $b = substr($input, $this->_strpos, $this->_strpos + $size);
                $this->_strpos += $size;

                $value .= $b;
                break;

            case Horde_Xml_Wbxml::GLOBAL_TOKEN_END:
                // Section 5.8.4.7.1
                $hasMoreAttributes = false;
                if (isset($attr)) {
                    $attrs[] = array('attribute' => $attr,
                                     'value' => $value);
                }
                break;

            case Horde_Xml_Wbxml::GLOBAL_TOKEN_SWITCH_PAGE:
                // Section 5.8.4.7.2
                $codePage = $this->getByte($input);
                if (!$this->_prevAttributeDTD) {
                    $this->_prevAttributeDTD = $this->_attributeDTD;
                }

                $this->switchAttributeCodePage($codePage);
                break;

            default:
                if ($token > 128) {
                    if (isset($attr)) {
                        $attrs[] = array('attribute' => $attr,
                                         'value' => $value);
                    }
                    $attr = $this->_attributeDTD->toAttribute($token);
                } else {
                    // Value.
                    $value .= $this->_attributeDTD->toAttribute($token);
                }
                break;
            }
        }

        if (!$this->_prevAttributeDTD) {
            $this->_attributeDTD = $this->_prevAttributeDTD;
            $this->_prevAttributeDTD = false;
        }

        $this->stopGetAttributes();
    }

    public function startGetAttributes()
    {
        $this->_isAttribute = true;
    }

    public function stopGetAttributes()
    {
        $this->_isAttribute = false;
    }

    public function getCurrentURI()
    {
        if ($this->_isAttribute) {
            return $this->_tagDTD->getURI();
        } else {
            return $this->_attributeDTD->getURI();
        }
    }

    public function writeString($str)
    {
        $this->_ch->characters($str);
    }

    public function getTag($tag)
    {
        // Should know which state it is in.
        return $this->_tagDTD->toTagStr($tag);
    }

    public function getAttribute($attribute)
    {
        // Should know which state it is in.
        $this->_attributeDTD->toAttributeInt($attribute);
    }

    public function switchElementCodePage($codePage)
    {
        $this->_tagDTD = $this->_dtdManager->getInstance($this->_tagDTD->toCodePageStr($codePage));
        $this->switchAttributeCodePage($codePage);
    }

    public function switchAttributeCodePage($codePage)
    {
        $this->_attributeDTD = $this->_dtdManager->getInstance($this->_attributeDTD->toCodePageStr($codePage));
    }

    /**
     * Return the hex version of the base 10 $entity.
     */
    public function entity($entity)
    {
        return dechex($entity);
    }

    /**
     * Reads a null terminated string.
     */
    public function termstr($input)
    {
        $str = '#'; // must start with nonempty string to allow array access
        $i = 0;
        $ch = $input[$this->_strpos++];
        if (ord($ch) == 0) {
            return ''; // don't return '#'
        }
        while (ord($ch) != 0) {
            $str[$i++] = $ch;
            $ch = $input[$this->_strpos++];
        }

        return $str;
    }
}

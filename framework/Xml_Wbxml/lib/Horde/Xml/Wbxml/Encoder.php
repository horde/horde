<?php
/**
 * From Binary XML Content Format Specification Version 1.3, 25 July 2001
 * found at http://www.wapforum.org
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Anthony Mills <amills@pyramid6.com>
 * @package Xml_Wbxml
 */
class Horde_Xml_Wbxml_Encoder extends Horde_Xml_Wbxml_ContentHandler
{
    protected $_strings = array();

    protected $_stringTable;

    protected $_hasWrittenHeader = false;

    protected $_dtd;

    protected $_output = '';

    protected $_uris = array();

    protected $_uriNums = array();

    protected $_currentURI;

    protected $_subParser = null;
    protected $_subParserStack = 0;

    /**
     * The XML parser.
     *
     * @var resource
     */
    protected $_parser;

    /**
     * The DTD Manager.
     *
     * @var Horde_Xml_Wbxml_DtdManager
     */
    protected $_dtdManager;

    /**
     * Constructor.
     */
    public function Horde_Xml_Wbxml_Encoder()
    {
        $this->_dtdManager = new Horde_Xml_Wbxml_DtdManager();
        $this->_stringTable = new Horde_Xml_Wbxml_HashTable();
    }

    /**
     * Take the input $xml and turn it into WBXML. This is _not_ the
     * intended way of using this class. It is derived from
     * Contenthandler and one should use it as a ContentHandler and
     * produce the XML-structure with startElement(), endElement(),
     * and characters().
     *
     * @throws Horde_Xml_Wbxml_Exception
     */
    public function encode($xml)
    {
        // Create the XML parser and set method references.
        $this->_parser = xml_parser_create_ns($this->_charset);
        xml_set_object($this->_parser, $this);
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_element_handler($this->_parser, '_startElement', '_endElement');
        xml_set_character_data_handler($this->_parser, '_characters');
        xml_set_processing_instruction_handler($this->_parser, '');
        xml_set_external_entity_ref_handler($this->_parser, '');

        if (!xml_parse($this->_parser, $xml)) {
            throw new Horde_Xml_Wbxml_Exception(
                sprintf('XML error: %s at line %d',
                        xml_error_string(xml_get_error_code($this->_parser)),
                        xml_get_current_line_number($this->_parser)));
        }

        xml_parser_free($this->_parser);

        return $this->_output;
    }

    /**
     * This will write the correct headers.
     *
     * @throws Horde_Xml_Wbxml_Exception
     */
    public function writeHeader($uri)
    {
        // @todo: this is a hack!
        if ($this->_wbxmlVersion == 2 && !preg_match('/1\.2$/', $uri)) {
            $uri .= '1.2';
        }
        if ($this->_wbxmlVersion == 1 && !preg_match('/1\.1$/', $uri)) {
            $uri .= '1.1';
        }
        if ($this->_wbxmlVersion == 0 && !preg_match('/1\.0$/', $uri)) {
            $uri .= '1.0';
        }

        $this->_dtd = $this->_dtdManager->getInstanceURI($uri);
        if (!$this->_dtd) {
            throw new Horde_Xml_Wbxml_Exception('Unable to find dtd for ' . $uri);
        }
        $dpiString = $this->_dtd->getDPI();

        // Set Version Number from Section 5.4
        // version = u_int8
        // currently 1, 2 or 3
        $this->writeVersionNumber($this->_wbxmlVersion);

        // Set Document Public Idetifier from Section 5.5
        // publicid = mb_u_int32 | ( zero index )
        // zero = u_int8
        // containing the value zero (0)
        // The actual DPI is determined after the String Table is read.
        $this->writeDocumentPublicIdentifier($dpiString, $this->_strings);

        // Set Charset from 5.6
        // charset = mb_u_int32
        $this->writeCharset($this->_charset);

        // Set String Table from 5.7
        // strb1 = length *byte
        $this->writeStringTable($this->_strings, $this->_charset, $this->_stringTable);

        $this->_currentURI = $uri;

        $this->_hasWrittenHeader = true;
    }

    public function writeVersionNumber($version)
    {
        $this->_output .= chr($version);
    }

    public function writeDocumentPublicIdentifier($dpiString, &$strings)
    {
        $i = 0;

        // The OMA test suite doesn't like DPI as integer code.
        // So don't try lookup and always send full DPI string.
        // $i = Horde_Xml_Wbxml::getDPIInt($dpiString);

        if ($i == 0) {
            $strings[0] = $dpiString;
            $this->_output .= chr(0);
            $this->_output .= chr(0);
        } else {
            Horde_Xml_Wbxml::intToMBUInt32($this->_output, $i);
        }
    }

    /**
     * @throws Horde_Xml_Wbxml_Exception
     */
    public function writeCharset($charset)
    {
        $cs = Horde_Xml_Wbxml::getCharsetInt($charset);

        if ($cs == 0) {
            throw new Horde_Xml_Wbxml_Exception('Unsupported Charset: ' . $charset);
        } else {
            Horde_Xml_Wbxml::intToMBUInt32($this->_output, $cs);
        }
    }

    public function writeStringTable($strings, $charset, $stringTable)
    {
        $stringBytes = array();
        $count = 0;
        foreach ($strings as $str) {
            $bytes = $this->_getBytes($str, $charset);
            $stringBytes = array_merge($stringBytes, $bytes);
            $nullLength = $this->_addNullByte($bytes);
            $this->_stringTable->set($str, $count);
            $count += count($bytes) + $nullLength;
        }

        Horde_Xml_Wbxml::intToMBUInt32($this->_output, count($stringBytes));
        $this->_output .= implode('', $stringBytes);
    }

    public function writeString($str, $cs)
    {
        $bytes = $this->_getBytes($str, $cs);
        $this->_output .= implode('', $bytes);
        $this->writeNull($cs);
    }

    public function writeNull($charset)
    {
        $this->_output .= chr(0);
        return 1;
    }

    protected function _addNullByte(&$bytes)
    {
        $bytes[] = chr(0);
        return 1;
    }

    protected function _getBytes($string, $cs)
    {
        $nbytes = strlen($string);

        $bytes = array();
        for ($i = 0; $i < $nbytes; $i++) {
            $bytes[] = $string{$i};
        }

        return $bytes;
    }

    protected function _splitURI($tag)
    {
        $parts = explode(':', $tag);
        $name = array_pop($parts);
        $uri = implode(':', $parts);
        return array($uri, $name);
    }

    /**
     * @throws Horde_Xml_Wbxml_Exception
     */
    public function startElement($uri, $name, $attributes = array())
    {
        if ($this->_subParser == null) {
            if (!$this->_hasWrittenHeader) {
                $this->writeHeader($uri);
            }
            if ($this->_currentURI != $uri) {
                $this->changecodepage($uri);
                $this->_currentURI = $uri;
            }
            if ($this->_subParser == null) {
                $this->writeTag($name, $attributes, true, $this->_charset);
            } else {
                $this->_subParser->startElement($uri, $name, $attributes);
            }
        } else {
            $this->_subParserStack++;
            $this->_subParser->startElement($uri, $name, $attributes);
        }
    }

    protected function _startElement($parser, $tag, $attributes)
    {
        list($uri, $name) = $this->_splitURI($tag);
        if (in_array(Horde_String::lower($uri), array('syncml:metinf', 'syncml:devinf'))) {
            $uri .= '1.' . $this->getVersion();
        }
        $this->startElement($uri, $name, $attributes);
    }

    public function opaque($o)
    {
        $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_OPAQUE);
        Horde_Xml_Wbxml::intToMBUInt32($this->_output, strlen($o));
        $this->_output .= $o;
    }

    public function characters($chars)
    {
        $chars = trim($chars);

        if (strlen($chars)) {
            /* We definitely don't want any whitespace. */
            if ($this->_subParser == null) {
                $i = $this->_stringTable->get($chars);
                if ($i != null) {
                    $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_STR_T);
                    Horde_Xml_Wbxml::intToMBUInt32($this->_output, $i);
                } else {
                    $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_STR_I);
                    $this->writeString($chars, $this->_charset);
                }
            } else {
                $this->_subParser->characters($chars);
            }
        }
    }

    protected function _characters($parser, $chars)
    {
        $this->characters($chars);
    }

    /**
     * @throws Horde_Xml_Wbxml_Exception
     */
    public function writeTag($name, $attrs, $hasContent, $cs)
    {
        if ($attrs != null && !count($attrs)) {
            $attrs = null;
        }

        $t = $this->_dtd->toTagInt($name);
        if ($t == -1) {
            $i = $this->_stringTable->get($name);
            if ($i == null) {
                throw new Horde_Xml_Wbxml_Exception($name . ' is not found in String Table or DTD');
            } else {
                if ($attrs == null && !$hasContent) {
                    $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_LITERAL);
                } elseif ($attrs == null && $hasContent) {
                    $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_LITERAL_A);
                } elseif ($attrs != null && $hasContent) {
                    $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_LITERAL_C);
                } elseif ($attrs != null && !$hasContent) {
                    $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_LITERAL_AC);
                }

                Horde_Xml_Wbxml::intToMBUInt32($this->_output, $i);
            }
        } else {
            if ($attrs == null && !$hasContent) {
                $this->_output .= chr($t);
            } elseif ($attrs == null && $hasContent) {
                $this->_output .= chr($t | 64);
            } elseif ($attrs != null && $hasContent) {
                $this->_output .= chr($t | 128);
            } elseif ($attrs != null && !$hasContent) {
                $this->_output .= chr($t | 192);
            }
        }

        if ($attrs != null && is_array($attrs) && count($attrs) > 0) {
            $this->writeAttributes($attrs, $cs);
        }
    }

    public function writeAttributes($attrs, $cs)
    {
        foreach ($attrs as $name => $value) {
            $this->writeAttribute($name, $value, $cs);
        }

        $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_END);
    }

    /**
     * @throws Horde_Xml_Wbxml_Exception
     */
    public function writeAttribute($name, $value, $cs)
    {
        $a = $this->_dtd->toAttribute($name);
        if ($a == -1) {
            $i = $this->_stringTable->get($name);
            if ($i == null) {
                throw new Horde_Xml_Wbxml_Exception($name . ' is not found in String Table or DTD');
            } else {
                $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_LITERAL);
                Horde_Xml_Wbxml::intToMBUInt32($this->_output, $i);
            }
        } else {
            $this->_output .= $a;
        }

        $i = $this->_stringTable->get($name);
        if ($i != null) {
            $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_STR_T);
            Horde_Xml_Wbxml::intToMBUInt32($this->_output, $i);
        } else {
            $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_STR_I);
            $this->writeString($value, $cs);
        }
    }

    public function endElement($uri, $name)
    {
        if ($this->_subParser == null) {
            $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_END);
        } else {
            $this->_subParser->endElement($uri, $name);
            $this->_subParserStack--;

            if ($this->_subParserStack == 0) {
                $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_OPAQUE);

                Horde_Xml_Wbxml::intToMBUInt32($this->_output,
                                         strlen($this->_subParser->getOutput()));
                $this->_output .= $this->_subParser->getOutput();

                $this->_subParser = null;
            }
        }
    }

    protected function _endElement($parser, $tag)
    {
        list($uri, $name) = $this->_splitURI($tag);
        $this->endElement($uri, $name);
    }

    public function changecodepage($uri)
    {
        // @todo: this is a hack!
        if ($this->_dtd->getVersion() == 2 && !preg_match('/1\.2$/', $uri)) {
            $uri .= '1.2';
        }
        if ($this->_dtd->getVersion() == 1 && !preg_match('/1\.1$/', $uri)) {
            $uri .= '1.1';
        }
        if ($this->_dtd->getVersion() == 0 && !preg_match('/1\.0$/', $uri)) {
            $uri .= '1.0';
        }

        $cp = $this->_dtd->toCodePageURI($uri);
        if (strlen($cp)) {
            $this->_dtd = $this->_dtdManager->getInstanceURI($uri);
            if (!$this->_dtd) {
                throw new Horde_Xml_Wbxml_Exception('Unable to find dtd for ' . $uri);
            }
            $this->_output .= chr(Horde_Xml_Wbxml::GLOBAL_TOKEN_SWITCH_PAGE);
            $this->_output .= chr($cp);
        } else {
            $this->_subParser = new Horde_Xml_Wbxml_Encoder(true);
            $this->_subParserStack = 1;
        }
    }

    /**
     * Getter for property output.
     */
    public function getOutput()
    {
        return $this->_output;
    }

    public function getOutputSize()
    {
        return strlen($this->_output);
    }
}

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
class Horde_Xml_Wbxml_ContentHandler
{
    protected $_currentUri;
    protected $_output = '';

    protected $_opaqueHandler;

    /**
     * Charset.
     */
    protected $_charset = 'UTF-8';

    /**
     * WBXML Version.
     * 1, 2, or 3 supported
     */
    protected $_wbxmlVersion = 2;

    public function __construct()
    {
        $this->_currentUri = new Horde_Xml_Wbxml_LifoQueue();
    }

    /**
     */
    public function raiseError($error)
    {
        return PEAR::raiseError($error);
    }

    public function getCharsetStr()
    {
        return $this->_charset;
    }

    public function setCharset($cs)
    {
        $this->_charset = $cs;
    }

    public function getVersion()
    {
        return $this->_wbxmlVersion;
    }

    public function setVersion($v)
    {
        $this->_wbxmlVersion = 2;
    }

    public function getOutput()
    {
        return $this->_output;
    }

    public function getOutputSize()
    {
        return strlen($this->_output);
    }

    public function startElement($uri, $element, $attrs = array())
    {
        $this->_output .= '<' . $element;

        $currentUri = $this->_currentUri->top();

        if (((!$currentUri) || ($currentUri != $uri)) && $uri) {
            $this->_output .= ' xmlns="' . $uri . '"';
        }

        $this->_currentUri->push($uri);

        foreach ($attrs as $attr) {
            $this->_output .= ' ' . $attr['attribute'] . '="' . $attr['value'] . '"';
        }

        $this->_output .= '>';
    }

    public function endElement($uri, $element)
    {
        $this->_output .= '</' . $element . '>';

        $this->_currentUri->pop();
    }

    public function characters($str)
    {
        $this->_output .= $str;
    }

    public function opaque($o)
    {
        $this->_output .= $o;
    }

    public function setOpaqueHandler($opaqueHandler)
    {
        $this->_opaqueHandler = $opaqueHandler;
    }

    public function removeOpaqueHandler()
    {
        unset($this->_opaqueHandler);
    }

    public function createSubHandler()
    {
        $name = get_class($this); // clone current class
        $sh = new $name();
        $sh->setCharset($this->getCharsetStr());
        $sh->setVersion($this->getVersion());
        return $sh;
    }
}

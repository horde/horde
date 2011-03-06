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
class Horde_Xml_Wbxml_Dtd
{
    /* Constants are from Binary XML Content Format Specification Version 1.3,
     * 25 July 2001 found at http://www.wapforum.org */

    /* Not sure where defined. */
    const WML_1_0 = '-//WAPFORUM//DTD WML 1.0//EN';
    const WTA_1_0 = '-//WAPFORUM//DTD WTA 1.0//EN';
    const WML_1_1 = '-//WAPFORUM//DTD WML 1.1//EN';
    const SI_1_1 = '-//WAPFORUM//DTD SI 1.1//EN';
    const SL_1_0 = '-//WAPFORUM//DTD SL 1.0//EN';
    const CO_1_0 = '-//WAPFORUM//DTD CO 1.0//EN';
    const CHANNEL_1_1 = '-//WAPFORUM//DTD CHANNEL 1.1//EN';
    const WML_1_2 = '-//WAPFORUM//DTD WML 1.2//EN';
    const WML_1_3 = '-//WAPFORUM//DTD WML 1.3//EN';
    const PROV_1_0 = '-//WAPFORUM//DTD PROV 1.0//EN';
    const WTA_WML_1_2 = '-//WAPFORUM//DTD WTA-WML 1.2//EN';
    const CHANNEL_1_2 = '-//WAPFORUM//DTD CHANNEL 1.2//EN';

    const SYNCML_1_0 = '-//SYNCML//DTD SyncML 1.0//EN';
    const DEVINF_1_0 = '-//SYNCML//DTD DevInf 1.0//EN';
    const METINF_1_0 = '-//SYNCML//DTD MetInf 1.0//EN';
    const SYNCML_1_1 = '-//SYNCML//DTD SyncML 1.1//EN';
    const DEVINF_1_1 = '-//SYNCML//DTD DevInf 1.1//EN';
    const METINF_1_1 = '-//SYNCML//DTD MetInf 1.1//EN';
    const SYNCML_1_2 = '-//SYNCML//DTD SyncML 1.2//EN';
    const DEVINF_1_2 = '-//SYNCML//DTD DevInf 1.2//EN';
    const METINF_1_2 = '-//SYNCML//DTD MetInf 1.2//EN';

    public $version;
    public $intTags;
    public $intAttributes;
    public $strTags;
    public $strAttributes;
    public $intCodePages;
    public $strCodePages;
    public $strCodePagesURI;
    public $URI;
    public $XMLNS;
    public $DPI;

    public function __construct($v)
    {
        $this->version = $v;
        $this->init();
    }

    public function init()
    {
    }

    public function setAttribute($intAttribute, $strAttribute)
    {
        $this->strAttributes[$strAttribute] = $intAttribute;
        $this->intAttributes[$intAttribute] = $strAttribute;
    }

    public function setTag($intTag, $strTag)
    {
        $this->strTags[$strTag] = $intTag;
        $this->intTags[$intTag] = $strTag;
    }

    public function setCodePage($intCodePage, $strCodePage, $strCodePageURI)
    {
        $this->strCodePagesURI[$strCodePageURI] = $intCodePage;
        $this->strCodePages[$strCodePage] = $intCodePage;
        $this->intCodePages[$intCodePage] = $strCodePage;
    }

    public function toTagStr($tag)
    {
        return isset($this->intTags[$tag]) ? $this->intTags[$tag] : false;
    }

    public function toAttributeStr($attribute)
    {
        return isset($this->intTags[$attribute]) ? $this->intTags[$attribute] : false;
    }

    public function toCodePageStr($codePage)
    {
        return isset($this->intCodePages[$codePage]) ? $this->intCodePages[$codePage] : false;
    }

    public function toTagInt($tag)
    {
        return isset($this->strTags[$tag]) ? $this->strTags[$tag] : false;
    }

    public function toAttributeInt($attribute)
    {
        return isset($this->strAttributes[$attribute]) ? $this->strAttributes[$attribute] : false;
    }

    public function toCodePageInt($codePage)
    {
        return isset($this->strCodePages[$codePage]) ? $this->strCodePages[$codePage] : false;
    }

    public function toCodePageURI($uri)
    {
        $uri = strtolower($uri);
        if (!isset($this->strCodePagesURI[$uri])) {
            die("unable to find codepage for $uri!\n");
        }

        $ret = isset($this->strCodePagesURI[$uri]) ? $this->strCodePagesURI[$uri] : false;

        return $ret;
    }

    /**
     * Getter for property version.
     * @return Value of property version.
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Setter for property version.
     * @param integer $v  New value of property version.
     */
    public function setVersion($v)
    {
        $this->version = $v;
    }

    /**
     * Getter for property URI.
     * @return Value of property URI.
     */
    public function getURI()
    {
        return $this->URI;
    }

    /**
     * Setter for property URI.
     * @param string $u  New value of property URI.
     */
    public function setURI($u)
    {
        $this->URI = $u;
    }

    /**
     * Getter for property DPI.
     * @return Value of property DPI.
     */
    public function getDPI()
    {
        return $this->DPI;
    }

    /**
     * Setter for property DPI.
     * @param DPI New value of property DPI.
     */
    public function setDPI($d)
    {
        $this->DPI = $d;
    }
}

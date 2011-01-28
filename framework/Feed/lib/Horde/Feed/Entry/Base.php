<?php
/**
 * Portions Copyright 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @category Horde
 * @package Horde_Feed
 */

/**
 * Horde_Feed_Entry_Base represents a single entry in an Atom or RSS
 * feed.
 *
 * @category Horde
 * @package Horde_Feed
 */
abstract class Horde_Feed_Entry_Base extends Horde_Xml_Element
{
    /**
     * @var Horde_Http_Client
     */
    protected $_httpClient;

    /**
     * Handle null or array values for $this->_element by initializing
     * with $this->_emptyXml, and importing the array with
     * Horde_Xml_Element::fromArray() if necessary.
     *
     * @see Horde_Xml_Element::__wakeup
     * @see Horde_Xml_Element::fromArray
     */
    public function __construct($element = null, Horde_Http_Client $httpClient = null)
    {
        $this->_element = $element;

        if (is_null($httpClient)) {
            $httpClient = new Horde_Http_Client();
        }
        $this->_httpClient = $httpClient;

        // If we've been passed an array, we'll store it for importing
        // after initializing with the default "empty" feed XML.
        $importArray = null;
        if (is_null($this->_element)) {
            $this->_element = $this->_emptyXml;
        } elseif (is_array($this->_element)) {
            $importArray = $this->_element;
            $this->_element = $this->_emptyXml;
        }

        $this->__wakeup();

        if (!is_null($importArray)) {
            $this->fromArray($importArray);
        }
    }
}

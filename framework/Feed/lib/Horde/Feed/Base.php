<?php
/**
 * Portions Copyright 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @category Horde
 * @package Horde_Feed
 */

/**
 * The Horde_Feed_Base class is an abstract class representing feeds.
 *
 * Horde_Feed_Base implements two core PHP 5 interfaces: ArrayAccess
 * and Iterator. In both cases the collection being treated as an
 * array is considered to be the entry collection, such that iterating
 * over the feed takes you through each of the feed's entries.
 *
 * @category Horde
 * @package Horde_Feed
 */
abstract class Horde_Feed_Base extends Horde_Xml_Element_List
{
    /**
     * Our root ("home") URI
     *
     * @var string
     */
    protected $_uri;

    /**
     * @var Horde_Http_Client
     */
    protected $_httpClient;

    /**
     * Feed constructor
     *
     * The Horde_Feed_Base constructor takes the URI of a feed or a
     * feed represented as a string and loads it as XML.
     *
     * @throws Horde_Feed_Exception If loading the feed failed.
     *
     * @param mixed $xml The feed as a string, a DOMElement, or null.
     * @param string $uri The full URI of the feed, or null if unknown.
     */
    public function __construct($xml = null, $uri = null, Horde_Http_Client $httpClient = null)
    {
        $this->_uri = $uri;

        if (is_null($httpClient)) {
            $httpClient = new Horde_Http_Client();
        }
        $this->_httpClient = $httpClient;

        try {
            parent::__construct($xml);
        } catch (Horde_Xml_Element_Exception $e) {
            throw new Horde_Feed_Exception('Unable to load feed: ' . $e->getMessage());
        }
    }

    /**
     * Handle null or array values for $this->_element by initializing
     * with $this->_emptyXml, and importing the array with
     * Horde_Xml_Element::fromArray() if necessary.
     *
     * @see Horde_Xml_Element::__wakeup
     * @see Horde_Xml_Element::fromArray
     */
    public function __wakeup()
    {
        // If we've been passed an array, we'll store it for importing
        // after initializing with the default "empty" feed XML.
        $importArray = null;
        if (is_null($this->_element)) {
            $this->_element = $this->_emptyXml;
        } elseif (is_array($this->_element)) {
            $importArray = $this->_element;
            $this->_element = $this->_emptyXml;
        }

        parent::__wakeup();

        if (!is_null($importArray)) {
            $this->fromArray($importArray);
        }
    }

    /**
     * Required by the Iterator interface.
     *
     * @internal
     *
     * @return mixed The current row, or null if no rows.
     */
    public function current()
    {
        return new $this->_listItemClassName(
            $this->_listItems[$this->_listItemIndex], $this->_httpClient);
    }
}

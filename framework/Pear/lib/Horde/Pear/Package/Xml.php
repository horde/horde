<?php
/**
 * Handles package.xml files.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Handles package.xml files.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Xml
{
    /** The package.xml namespace */
    const XMLNAMESPACE = 'http://pear.php.net/dtd/package-2.0';

    /**
     * The parsed XML.
     *
     * @var DOMDocument
     */
    private $_xml;

    /**
     * The XPath query handler.
     *
     * @var DOMXpath
     */
    private $_xpath;

    /**
     * Constructor.
     *
     * @param resource $xml The package.xml as stream.
     */
    public function __construct($xml)
    {
        rewind($xml);
        $this->_xml = new DOMDocument('1.0', 'UTF-8');
        $this->_xml->preserveWhiteSpace = true;
        $this->_xml->formatOutput = false;
        $this->_xml->loadXML(stream_get_contents($xml));
        $this->_xpath = new DOMXpath($this->_xml);
        $this->_xpath->registerNamespace('p', self::XMLNAMESPACE);
    }

    /**
     * Return the package name.
     *
     * @return string The name of the package.
     */
    public function getName()
    {
        if ($node = $this->findNode('/p:package/p:name')) {
            return $node->textContent;
        }
        throw new Horde_Pear_Exception('"name" element is missing!');
    }

    /**
     * Mark the package as being release and set the timestamps to now.
     *
     * @return NULL
     */
    public function releaseNow()
    {
        if ($node = $this->findNode('/p:package/p:date')) {
            $new_node = $this->_xml->createElementNS(self::XMLNAMESPACE, 'date');
            $text = $this->_xml->createTextNode(date('Y-m-d'));
            $new_node->appendChild($text);
            $this->_xml->documentElement->replaceChild($new_node, $node);
        }
        if ($node = $this->findNode('/p:package/p:time')) {
            $new_node = $this->_xml->createElementNS(self::XMLNAMESPACE, 'date');
            $text = $this->_xml->createTextNode(date('H:i:s'));
            $new_node->appendChild($text);
            $this->_xml->documentElement->replaceChild($new_node, $node);
        }
        if ($node = $this->findNode('/p:package/p:version/p:release')) {
            $version = $node->textContent;
            foreach($this->findNodes('/p:package/p:changelog/p:release') as $release) {
                if ($node = $this->findNodeRelativeTo('./p:version/p:release', $release)) {
                    if ($node->textContent == $version) {
                        if ($node = $this->findNodeRelativeTo('./p:date', $release)) {
                            $new_node = $this->_xml->createElementNS(self::XMLNAMESPACE, 'date');
                            $text = $this->_xml->createTextNode(date('Y-m-d'));
                            $new_node->appendChild($text);
                            $release->replaceChild($new_node, $node);
                        }
                    }
                }
            }
        }
    }

    /**
     * Return the complete package.xml as string.
     *
     * @return string The package.xml content.
     */
    public function __toString()
    {
        $result = $this->_xml->saveXML();
        $result = preg_replace(
            '#<package (.*) (packagerversion="[.0-9]*" version="2.0")#',
            '<package \2 \1',
            $result
        );
        return preg_replace('#"/>#', '" />', $result);
    }

    /**
     * Return a single named node matching the given XPath query.
     *
     * @param string $query The query.
     *
     * @return DOMNode|false The named DOMNode or empty if no node was found.
     */
    public function findNode($query)
    {
        $result = $this->_xpath->query($query);
        if ($result->length) {
            return $result->item(0);
        }
        return false;
    }

    /**
     * Return a single named node below the given context matching the given
     * XPath query.
     *
     * @param string $query The query.
     *
     * @return DOMNode|false The named DOMNode or empty if no node was found.
     */
    public function findNodeRelativeTo($query, DOMNode $context)
    {
        $result = $this->_xpath->query($query, $context);
        if ($result->length) {
            return $result->item(0);
        }
        return false;
    }

    /**
     * Return all nodes matching the given XPath query.
     *
     * @param string $query The query.
     *
     * @return DOMNodeList The list of DOMNodes.
     */
    public function findNodes($query)
    {
        return $this->_xpath->query($query);
    }
}
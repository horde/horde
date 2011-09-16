<?php
/**
 * Provides DOM utility methods.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Provides DOM utility methods.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @since Horde_Kolab_Format 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Helper
{
    /**
     * The XML document this object works with.
     *
     * @var DOMDocument
     */
    protected $_xmldoc;

    /**
     * The XPath query handler.
     *
     * @var DOMXpath
     */
    private $_xpath;

    /**
     * Constructor
     *
     * @param DOMDocument $xmldoc The XML document this object works with.
     */
    public function __construct($xmldoc)
    {
        $this->_xmldoc = $xmldoc;
        $this->_xpath = new DOMXpath($this->_xmldoc);
    }

    /**
     * Fetch the value of a node.
     *
     * @param DOMNode $node Retrieve the text value for this node.
     *
     * @return string|null The text value or null if no value was identified.
     */
    public function fetchNodeValue($node)
    {
        if (($child = $this->_fetchFirstTextNode($node)) !== null) {
            return $child->nodeValue;
        }
        return null;
    }

    /**
     * Fetch the the first text node.
     *
     * @param DOMNode $node Retrieve the text value for this node.
     *
     * @return DOMNode|null The first text node or null if no such node was
     *                      found.
     */
    private function _fetchFirstTextNode($node)
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                return $child;
            }
        }
    }

    /**
     * Store a value as a new text node.
     *
     * @param DOMNode $parent_node Attach the new node to this parent.
     * @param string  $name        Name of the new child node.
     * @param string  $value       Text value of the new child node.
     *
     * @return DOMNode The new child node.
     */
    public function storeNewNodeValue($parent_node, $name, $value)
    {
        $node = $this->createNewNode($parent_node, $name);
        $this->createNodeValue($node, $name, $value);
        return $node;
    }

    /**
     * Store a value as a new text node.
     *
     * @param DOMNode $parent_node Attach the new node to this parent.
     * @param string  $name        Name of the new child node.
     * @param string  $value       Text value of the new child node.
     *
     * @return DOMNode The new child node.
     */
    public function createNodeValue($parent_node, $name, $value)
    {
        $node = $this->_xmldoc->createTextNode($value);
        $parent_node->appendChild($node);
        return $node;
    }

    /**
     * Append an XML snippet.
     *
     * @param DOMNode $parent_node Attach the XML below this parent.
     * @param string  $xml         The XML to append.
     *
     * @return DOMNode The new child node.
     */
    public function appendXml($parent_node, $xml)
    {
        $node = $this->_xmldoc->createDocumentFragment();
        $node->appendXML($xml);
        $parent_node->appendChild($node);
        return $node;
    }

    /**
     * Create a new node.
     *
     * @param DOMNode $parent_node Attach the new node to this parent.
     * @param string  $name        Name of the new child node.
     *
     * @return DOMNode The new child node.
     */
    public function createNewNode($parent_node, $name)
    {
        $node = $this->_xmldoc->createElement($name);
        $parent_node->appendChild($node);
        return $node;
    }

    /**
     * Store a value as a new text node.
     *
     * @param DOMNode $node  Replace the text value of this node.
     * @param string  $value Text value of the new child node.
     *
     * @return NULL
     */
    public function replaceFirstNodeTextValue($node, $value)
    {
        if (($child = $this->_fetchFirstTextNode($node)) !== null) {
            $node->removeChild($child);
        }
        $new_node = $this->_xmldoc->createTextNode($value);
        if (empty($node->childNodes)) {
            $node->appendChild($new_node);
        } else {
            $node->insertBefore($new_node, $node->childNodes->item(0));
        }
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
        return $this->_findSingleNode($this->findNodes($query));
    }

    /**
     * Return a single named node below the given context matching the given
     * XPath query.
     *
     * @param string  $query   The query.
     * @param DOMNode $context Search below this node.
     *
     * @return DOMNode|false The named DOMNode or empty if no node was found.
     */
    public function findNodeRelativeTo($query, DOMNode $context)
    {
        return $this->_findSingleNode(
            $this->findNodesRelativeTo($query, $context)
        );
    }

    /**
     * Return a single node for the result set.
     *
     * @param DOMNodeList $result The query result.
     *
     * @return DOMNode|false The DOMNode or empty if no node was found.
     */
    private function _findSingleNode($result)
    {
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

    /**
     * Return all nodes matching the given XPath query.
     *
     * @param string  $query   The query.
     * @param DOMNode $context Search below this node.
     *
     * @return DOMNodeList The list of DOMNodes.
     */
    public function findNodesRelativeTo($query, DOMNode $context)
    {
        return $this->_xpath->query($query, $context);
    }

    /**
     * Remove named nodes from a parent node.
     *
     * @param DOMNode $parent_node The parent node.
     * @param string  $name        The name of the children to be removed.
     *
     * @return NULL
     */
    public function removeNodes($parent_node, $name)
    {
        foreach ($this->findNodesRelativeTo('./' . $name, $parent_node) as $child) {
            $parent_node->removeChild($child);
        }
    }

    /**
     * Output the document as XML string.
     *
     * @return string The XML output.
     */
    public function __toString()
    {
        return $this->_xmldoc->saveXML();
    }
}
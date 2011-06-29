<?php
/**
 * Utilities for the various XML handlers.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Utilities for the various XML handlers.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_Base
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
     * The parameters for this handler.
     *
     * @var array
     */
    private $_params;

    /**
     * Constructor
     *
     * @param DOMDocument $xmldoc The XML document this object works with.
     * @param array       $params Additional parameters for this handler.
     */
    public function __construct($xmldoc, $params = array())
    {
        $this->_xmldoc = $xmldoc;
        $this->_xpath = new DOMXpath($this->_xmldoc);
        $this->_params = $params;
    }

    /**
     * Load the node value from the Kolab object.
     *
     * @param string  $name        The name of the the attribute
     *                             to be fetched.
     * @param array   &$attributes The data array that holds all
     *                             attribute values.
     * @param DOMNode $parent_node The parent node of the node to be loaded.
     *
     * @return DOMNode|boolean The named DOMNode or false if no node value was
     *                         found.
     */
    public function load($name, &$attributes, $parent_node)
    {
        if ($node = $this->findNodeRelativeTo('./' . $name, $parent_node)) {
            if (($value = $this->loadNodeValue($node)) !== null) {
                $attributes[$name] = $value;
                return $node;
            }
        }
        return false;
    }

    /**
     * Load the value of a node.
     *
     * @param DOMNode $node Retrieve value for this node.
     *
     * @return mixed|null The value or null if no value was found.
     */
    public function loadNodeValue($node)
    {
        return $this->fetchNodeValue($node);
    }

    /**
     * Fetch the value of a node.
     *
     * @param DOMNode $node Retrieve the text value for this node.
     *
     * @return string|null The text value or null if no value was identified.
     */
    protected function fetchNodeValue($node)
    {
        if (($child = $this->_fetchFirstTextNode($node)) !== null) {
            return $child->nodeValue;
        }
        return null;
    }

    /**
     * Update the specified attribute.
     *
     * @param string       $name        The name of the the attribute
     *                                  to be updated.
     * @param mixed        $value       The value to store.
     * @param DOMNode      $parent_node The parent node of the node that
     *                                  should be updated.
     * @param DOMNode|NULL $old_node    The previous value (or null if
     *                                  there is none).
     *
     * @return DOMNode|boolean The new/updated child node or false if this
     *                         failed.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function saveNodeValue(
        $name,
        $value,
        $parent_node,
        $old_node = null
    ) {
        if ($old_node === null) {
            return $this->storeNewNodeValue($parent_node, $name, $value);
        } else {
            $this->replaceFirstNodeTextValue($old_node, $value);
            return $old_node;
        }
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
        $node = $this->_xmldoc->createElement($name);
        $parent_node->appendChild($node);
        $node->appendChild(
            $this->_xmldoc->createTextNode($value)
        );
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
    protected function replaceFirstNodeTextValue($node, $value)
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
    protected function findNode($query)
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
    protected function findNodeRelativeTo($query, DOMNode $context)
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
    protected function findNodes($query)
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
    protected function findNodesRelativeTo($query, DOMNode $context)
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
    protected function removeNodes($parent_node, $name)
    {
        foreach ($this->findNodesRelativeTo('./' . $name, $parent_node) as $child) {
            $parent_node->removeChild($child);
        }
    }

    /**
     * Return a parameter value.
     *
     * @param string $name The parameter name.
     *
     * @return mixed The parameter value.
     */
    public function getParam($name)
    {
        return isset($this->_params[$name]) ? $this->_params[$name] : null;
    }

    /**
     * Returns if the XML handling should be relaxed.
     *
     * @return boolean True if the XML should not be strict.
     */
    protected function isRelaxed()
    {
        return !empty($this->_params['relaxed']);
    }

    /**
     * Create a handler for the sub type of this attribute.
     *
     * @param array $parameters The parameters for creating the sub type handler.
     *
     * @return Horde_Kolab_Format_Xml_Type The sub type handler.
     */
    protected function createSubType($parameters)
    {
        $original_params = $this->_params;
        unset($original_params['array']);
        $type = $parameters['type'];
        unset($parameters['type']);
        $params = array_merge($original_params, $parameters);
        return $this->getParam('factory')
            ->createXmlType($type, $this->_xmldoc, $params);
    }
}